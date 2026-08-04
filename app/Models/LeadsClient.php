<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadsClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'fname',
        'lname',
        'designation',
        'linkedin_url',
        'lead_company_id',
    ];

    public function leadCompanyLink()
    {
        return $this->belongsTo(Company::class, 'lead_company_id');
    }

    public function leadClientPhones()
    {
        return $this->hasMany(LeadsClientsPhone::class, 'lead_client_id')->select('lead_client_id', 'phone', 'type');
    }

    public function leadClientEmails()
    {
        return $this->hasMany(LeadsClientsEmail::class, 'lead_client_id')->select('lead_client_id', 'mail', 'type');
    }
}
