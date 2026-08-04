<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RetentionClientResource extends JsonResource
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
            'name' => $this->fname . ' ' . $this->lname, // Customize client name
            'phones' => $this->clientPhones->pluck('phone'), // Customize client phones
            'emails' => $this->clientEmails->pluck('mail'), // Customize client emails
            'designation' => $this->designation, // Customize client designation
            'linkedin_url' => $this->linkedin_url, // Customize client LinkedIn URL
        ];
    }
}
