<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'website' => $this->website,
            'industry' => $this->industry,
            'fax' => $this->fax,
            'source' => $this->source,
            'blacklisted' => $this->blacklisted ? true : false,
            'assignTo' => $this->assignTo ? ['id' => $this->assignTo->id, 'name' => $this->assignTo->name] : null,
            'assignBy' => $this->assignBy ? ['id' => $this->assignBy->id, 'name' => $this->assignBy->name] : null,
            'companyPhones' => $this->companyPhone,
            'companyEmails' => $this->companyEmail,
            'companyAddress' => count($this->companyAddress) > 0 ? [
                'country' => $this->companyAddress[0]->country ? $this->companyAddress[0]->country->name : null,
                'state' => $this->companyAddress[0]->state ? $this->companyAddress[0]->state->name : null,
                'timezone' => $this->companyAddress[0]->timezone,
            ] : null,
            'companyBusiness' => $this->companyBusiness[0] ?? null,
            'disposition' => $this->disposition ? [
                'id' => $this->disposition->id,
                'phone' => $this->disposition->phone,
                'statusId' => $this->disposition->status_id,
                'status' => $this->disposition->status,
                'updatedAt' => $this->disposition->updated_at,
            ] : null,
            'clients' => $this->clients->map(function ($client) {
                return [
                    'id' => $client->id,
                    'fname' => $client->fname,
                    'lname' => $client->lname,
                    'clientPhones' => $client->clientPhones,
                    'clientEmails' => $client->clientEmails,
                    'blacklisted' => $client->blacklisted ? true : false,
                ];
            }),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
