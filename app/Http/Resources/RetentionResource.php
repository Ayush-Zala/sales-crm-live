<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RetentionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'website' => $this->website,
            'industry' => $this->industry,
            'fax' => $this->fax,
            'source' => $this->source,
            'status' => $this->status,
            'lead_provide_by' => $this->lead_provide_by,
            'create_user_id' => $this->create_user_id,
            'last_order_us_date' => $this->last_order_us_date,
            'description' => $this->description,
            'assignTo' => $this->assignTo ? ['id' => $this->assignTo->id, 'name' => $this->assignTo->name] : null,
            'assignBy' => $this->assignBy ? ['id' => $this->assignBy->id, 'name' => $this->assignBy->name] : null,
            'retention_phone' => $this->retention_phone,
            'retention_email' => $this->retention_email,
            'retention_company_address' => $this->retention_company_address && count($this->retention_company_address) > 0 ? [
                'country' => $this->retention_company_address[0]->country ? $this->retention_company_address[0]->country->name : null,
                'state' => $this->retention_company_address[0]->state ? $this->retention_company_address[0]->state->name : null,
                'timezone' => $this->retention_company_address[0]->timezone,
            ] : null,
            'retention_disposition' => count($this->RetentionDisposition) > 0 ? [
                'id' => $this->RetentionDisposition[0]->id,
                'phone' => $this->RetentionDisposition[0]->phone,
                'statusId' => $this->RetentionDisposition[0]->status_id,
                'status' => $this->RetentionDisposition[0]->leadDispositionStatus->name,
                'updatedAt' => $this->RetentionDisposition[0]->updated_at,
            ] : null,
            'clients' => $this->clients->map(function ($client) {
                return [
                    'id' => $client->id,
                    'fname' => $client->fname,
                    'lname' => $client->lname,
                    'client_phones' => $client->clientPhones,
                    'client_emails' => $client->clientEmails,
                ];
            }),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
