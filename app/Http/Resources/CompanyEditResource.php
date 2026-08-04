<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyEditResource extends JsonResource
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
            'email' => $this->companyEmail->map(function ($email) {
                return [
                    'companyId' => $this->id,
                    'emailId' => $email->id,
                    'email' => $email->email,
                    'type' => $email->type,
                ];
            }), // Customize company email
            'phone' => $this->companyPhone->map(function ($phone) {
                return [
                    'companyId' => $this->id,
                    'phoneId' => $phone->id,
                    'phone' => $phone->phone,
                    'type' => $phone->type,
                ];
            }), // Customize company phone
            'block' => $this->companyAddress->first()->block, // Customize company address block
            'addressline1' => $this->companyAddress->first()->street, // Customize company address street
            'addressline2' => $this->companyAddress->first()->address, // Customize company address address
            'city' => $this->companyAddress->first()->city->id, // Customize company address city
            'state' => $this->companyAddress->first()->state->id, // Customize company address state
            'country' => $this->companyAddress->first()->country->id, // Customize company address country
            'zip' => $this->companyAddress->first()->zip, // Customize company address zip
            'timezone' => $this->companyAddress->first()->timezone, // Customize company timezone
            'vendor_type' => $this->companyBusiness->first()->type ?? null, // Customize company business
            'clients' => ClientResource::collection($this->clients)->additional([
                'companyId' => $this->id
            ]), // Customize clients
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