<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
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
            'assign_to' => $this->assignTo->name ?? null, // Customize assign to
            'assign_by' => $this->assignBy->name ?? null, // Customize assign by
            'email' => $this->companyEmail->pluck('email'), // Customize company email
            'phone' => $this->companyPhone->pluck('phone'), // Customize company phone
            'address' => $this->companyAddress->first() ?
                implode(', ', [
                    $this->companyAddress->first()->block,
                    $this->companyAddress->first()->street,
                    $this->companyAddress->first()->address,
                    $this->companyAddress->first()->city->name,
                    $this->companyAddress->first()->state->name,
                    $this->companyAddress->first()->country->name,
                    $this->companyAddress->first()->zip,
                ])
                : null, // Customize company address
            'timezone' => $this->companyAddress->first()->timezone, // Customize company timezone
            'vendor_type' => $this->companyBusiness->first()->type ?? null, // Customize company business
            'clients' => ClientResource::collection($this->clients), // Customize clients
            'disposition_history' => DispositionHistoryResource::collection($this->dispositionHistory), // Include disposition history
            'blacklisted' => $this->blacklisted ? true : false, // Customize blacklisted
            'source' => $this->source, // Customize source
            'industry' => $this->industry, // Customize industry
            'website' => $this->website, // Customize website
            'fax' => $this->fax, // Customize fax
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
