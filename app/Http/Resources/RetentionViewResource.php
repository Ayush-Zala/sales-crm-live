<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RetentionViewResource extends JsonResource
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
            'last_order_us_date' => $this->last_order_us_date,
            'email' => $this->retentionEmail->pluck('email'), // Customize company email
            'phone' => $this->retentionPhone->pluck('phone'), // Customize company phone
            'address' => $this->RetentionCompanyAddress->first() ?
                implode(', ', [
                    $this->RetentionCompanyAddress->first()->block,
                    $this->RetentionCompanyAddress->first()->street,
                    $this->RetentionCompanyAddress->first()->address,
                    $this->RetentionCompanyAddress->first()->city->name,
                    $this->RetentionCompanyAddress->first()->state->name,
                    $this->RetentionCompanyAddress->first()->country->name,
                    $this->RetentionCompanyAddress->first()->zip,
                ])
                : null, // Customize company address
            'timezone' => $this->RetentionCompanyAddress->first()->timezone ?? null,
            'clients' => RetentionClientResource::collection($this->clients),
            'disposition_history' => DispositionHistoryResource::collection($this->RetentionDispositionHistory),
            'source' => $this->source,
            'industry' => $this->industry,
            'website' => $this->website,
            'fax' => $this->fax,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
