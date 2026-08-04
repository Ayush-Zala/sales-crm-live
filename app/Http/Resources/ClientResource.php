<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
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
            'linkedin_url' => $this->linkdinurl, // Customize client LinkedIn URL
            'blacklisted' => $this->blacklisted ? true : false, // Customize blacklisted
            'companyId' => $this->companyId,
        ];
    }
}
