<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class RetentionImportService
{
    /**
     * Import clients with no order in the last N months from patterns247_db
     * into the crm_v4 retention tables.
     *
     * Idempotent: safe to run repeatedly. Existing companies (matched by
     * normalized name) are updated; new ones are inserted unassigned.
     * Contacts / emails / phones are merged insert-missing with normalized
     * dedup, so no duplicates are ever created.
     */
    public function run(int $months = 6): array
    {
        $cutoff  = Carbon::now()->subMonths($months)->toDateTimeString();
        $summary = [
            'companies_updated'       => 0,
            'companies_inserted'      => 0,
            'contacts_inserted'       => 0,
            'contact_emails_inserted' => 0,
            'contact_phones_inserted' => 0,
            'company_emails_inserted' => 0,
            'company_phones_inserted' => 0,
            'errors'                  => 0,
        ];

        // Eligible clients: blacklisted skipped; no order (via their contacts)
        // in the last N months (includes never-ordered).
        $eligible = DB::connection('patterns')->select("
            SELECT c.client_id, c.client_name, c.client_website, c.client_industry,
                   c.client_source, c.about_client, agg.last_order_date
            FROM clients c
            JOIN (
                SELECT c.client_id, MAX(o.order_date) AS last_order_date
                FROM clients c
                LEFT JOIN client_contacts cc ON cc.client_id = c.client_id
                LEFT JOIN `order` o          ON o.client_contact_id = cc.client_contacts_id
                WHERE c.is_blacklist = 0
                GROUP BY c.client_id
                HAVING last_order_date IS NULL OR last_order_date < ?
            ) agg ON agg.client_id = c.client_id
        ", [$cutoff]);

        // Process in chunks so child data can be bulk-loaded from source
        // (avoids N+1 queries across servers).
        foreach (array_chunk($eligible, 300) as $chunk) {
            $clientIds = array_map(fn ($r) => $r->client_id, $chunk);

            $contacts   = DB::connection('patterns')->table('client_contacts')
                            ->whereIn('client_id', $clientIds)->get();
            $contactIds = $contacts->pluck('client_contacts_id')->all();

            $contactsByClient = $contacts->groupBy('client_id');

            $ctEmails = $contactIds
                ? DB::connection('patterns')->table('client_contact_emails')
                    ->whereIn('client_contact_id', $contactIds)->get()->groupBy('client_contact_id')
                : collect();
            $ctPhones = $contactIds
                ? DB::connection('patterns')->table('client_contact_mobiles')
                    ->whereIn('client_contact_id', $contactIds)->get()->groupBy('client_contact_id')
                : collect();
            $coEmails = DB::connection('patterns')->table('client_emails')
                            ->whereIn('client_id', $clientIds)->get()->groupBy('client_id');
            $coPhones = DB::connection('patterns')->table('client_mobiles')
                            ->whereIn('client_id', $clientIds)->get()->groupBy('client_id');

            foreach ($chunk as $client) {
                try {
                    DB::transaction(function () use (
                        $client, $contactsByClient, $ctEmails, $ctPhones,
                        $coEmails, $coPhones, &$summary
                    ) {
                        $this->upsertCompany(
                            $client,
                            $contactsByClient->get($client->client_id, collect()),
                            $ctEmails, $ctPhones,
                            $coEmails->get($client->client_id, collect()),
                            $coPhones->get($client->client_id, collect()),
                            $summary
                        );
                    });
                } catch (\Throwable $e) {
                    $summary['errors']++;
                    Log::error('Retention import failed for client '.$client->client_id.': '.$e->getMessage());
                }
            }
        }

        return $summary;
    }

    private function upsertCompany($client, $contacts, $ctEmails, $ctPhones, $coEmails, $coPhones, array &$summary): void
    {
        $nameKey = $this->nameKey($client->client_name);

        // Match existing retention company by normalized name (newest row wins).
        $existing = DB::table('retentions')
            ->whereRaw('LOWER(TRIM(name)) = ?', [$nameKey])
            ->orderByDesc('id')->first();

        if ($existing) {
            DB::table('retentions')->where('id', $existing->id)->update([
                'website'            => $this->coalesce($client->client_website, $existing->website),
                'industry'           => $this->coalesce($client->client_industry, $existing->industry),
                'source'             => $this->coalesce($client->client_source, $existing->source),
                'description'        => $this->coalesce($client->about_client, $existing->description),
                'last_order_us_date' => $client->last_order_date,
                'updated_at'         => now(),
            ]);
            $companyId = $existing->id;
            $summary['companies_updated']++;
        } else {
            $companyId = DB::table('retentions')->insertGetId([
                'name'               => trim((string) $client->client_name),
                'website'            => $client->client_website,
                'industry'           => $client->client_industry,
                'source'             => $client->client_source,
                'description'        => $client->about_client,
                'last_order_us_date' => $client->last_order_date,
                'assign_to'          => null,   // new companies come in unassigned
                'assign_by'          => null,
                'create_user_id'     => null,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
            $summary['companies_inserted']++;
        }

        // --- Company-level emails / phones (insert-missing, normalized dedup) ---
        $seenEmails = $this->keySet(
            DB::table('retention_company_emails')->where('company_id', $companyId)->pluck('email'), 'email'
        );
        foreach ($coEmails as $row) {
            $k = $this->emailKey($row->email_address);
            if ($k === null || isset($seenEmails[$k])) continue;
            DB::table('retention_company_emails')->insert([
                'email' => $row->email_address, 'type' => $row->email_type,
                'company_id' => $companyId, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $seenEmails[$k] = true; $summary['company_emails_inserted']++;
        }

        $seenPhones = $this->keySet(
            DB::table('retention_company_phones')->where('company_id', $companyId)->pluck('phone'), 'phone'
        );
        foreach ($coPhones as $row) {
            $k = $this->phoneKey($row->mobile_number);
            if ($k === null || isset($seenPhones[$k])) continue;
            DB::table('retention_company_phones')->insert([
                'phone' => $row->mobile_number, 'type' => $row->mobile_number_type,
                'company_id' => $companyId, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $seenPhones[$k] = true; $summary['company_phones_inserted']++;
        }

        // --- Contacts (insert-missing by first/last name) + their emails/phones ---
        foreach ($contacts as $ct) {
            $rc = DB::table('retention_clients')
                ->where('companyId', $companyId)
                ->whereRaw("IFNULL(fname,'') = ? AND IFNULL(lname,'') = ?",
                          [(string) ($ct->first_name ?? ''), (string) ($ct->last_name ?? '')])
                ->orderByDesc('id')->first();

            if ($rc) {
                $clientsId = $rc->id;
            } else {
                $clientsId = DB::table('retention_clients')->insertGetId([
                    'fname'       => $ct->first_name,
                    'lname'       => $ct->last_name,
                    'designation' => $ct->designation,
                    'linkdinurl'  => $ct->linkedin_url,
                    'companyId'   => $companyId,
                    'created_at'  => now(), 'updated_at' => now(),
                ]);
                $summary['contacts_inserted']++;
            }

            $seenCE = $this->keySet(
                DB::table('retention_client_emails')->where('clients_id', $clientsId)->pluck('mail'), 'email'
            );
            foreach ($ctEmails->get($ct->client_contacts_id, collect()) as $er) {
                $k = $this->emailKey($er->contact_email_address);
                if ($k === null || isset($seenCE[$k])) continue;
                DB::table('retention_client_emails')->insert([
                    'mail' => $er->contact_email_address, 'type' => $er->contact_email_type,
                    'clients_id' => $clientsId, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $seenCE[$k] = true; $summary['contact_emails_inserted']++;
            }

            $seenCP = $this->keySet(
                DB::table('retention_client_phones')->where('clients_id', $clientsId)->pluck('phone'), 'phone'
            );
            foreach ($ctPhones->get($ct->client_contacts_id, collect()) as $pr) {
                $k = $this->phoneKey($pr->contact_mobile_number);
                if ($k === null || isset($seenCP[$k])) continue;
                DB::table('retention_client_phones')->insert([
                    'phone' => $pr->contact_mobile_number, 'type' => $pr->contact_mobile_number_type,
                    'clients_id' => $clientsId, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $seenCP[$k] = true; $summary['contact_phones_inserted']++;
            }
        }
    }

    // --- normalization helpers (must match everywhere) ---

    private function nameKey(?string $n): string
    {
        return mb_strtolower(trim((string) $n));
    }

    private function emailKey(?string $e): ?string
    {
        $e = mb_strtolower(trim((string) $e));
        return $e === '' ? null : $e;
    }

    private function phoneKey(?string $p): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $p);
        if ($d === '') return null;
        if (strlen($d) === 11 && $d[0] === '1') $d = substr($d, 1); // strip US leading 1
        return $d;
    }

    private function coalesce($new, $old)
    {
        $new = is_string($new) ? trim($new) : $new;
        return ($new === null || $new === '') ? $old : $new;
    }

    /** Build an associative set of normalized keys from a pluck() collection. */
    private function keySet($values, string $kind): array
    {
        $set = [];
        foreach ($values as $v) {
            $k = $kind === 'phone' ? $this->phoneKey($v) : $this->emailKey($v);
            if ($k !== null) $set[$k] = true;
        }
        return $set;
    }
}
