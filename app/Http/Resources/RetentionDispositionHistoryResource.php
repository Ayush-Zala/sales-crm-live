<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RetentionDispositionHistoryResource extends JsonResource
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
            'phone' => $this->phone,
            'status_id' => $this->status_id,
            'status' => $this->status,
            'description' => $this->description,
            'followup_date' => $this->followup_date,
            'followup_time' => $this->followup_time,
            'followup_description' => $this->followup_description,
            'timezone' => $this->timezone,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'total_time' => $this->total_time,
            'user_id' => $this->user_id,
            'company_id' => $this->company_id,
            'client_id' => $this->client_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'company_name' => $this->company->name ?? null, // Include company name
            'client_name' => $this->client->name ?? null,   // Include client name
            'user_name' => $this->user->name ?? null,       // Include user name
        ];
    }
}
