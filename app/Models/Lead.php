<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'website',
        'fax',
        'industry',
        'lead_provide_by',
        'assign_to',
        'assign_by',
        'description',
        'lead_source',
        'lead_status',
    ];

    public function assignedUsers()
    {
        return $this->hasMany(LeadsAssignCompany::class, 'lead_company_id');
    }

    public function assignLeads()
    {
        return $this->hasOne(LeadsAssignCompany::class, 'lead_company_id')->where('is_active', true)->latest('id');
    }

    public function disposition()
    {
        return $this->hasMany(LeadsDisposition::class, 'lead_company_id')->latest('id');
    }

    public function latestDisposition()
    {
        return $this->hasOne(LeadsDisposition::class, 'lead_company_id')->latestOfMany();
    }

    public function leadAddress()
    {
        return $this->hasOne(LeadsCompanyAddress::class, 'lead_company_id');
    }

    public function users()
    {
        return $this->hasManyThrough(User::class, LeadsAssignCompany::class, 'lead_company_id', 'id', 'id', 'user_id')
            ->where('leads_assign_companies.is_active', true)
            ->latest('leads_assign_companies.id')
            ->take(1);
    }

    public function reporting_manager()
    {
        return $this->hasManyThrough(User::class, LeadsAssignCompany::class, 'lead_company_id', 'id', 'id', 'assign_by')
            ->where('leads_assign_companies.is_active', true)
            ->latest('leads_assign_companies.id')
            ->take(1);
    }

    // Define the relationship with User through AssignCompany
    public function leadusers()
    {
        return $this->belongsTo(User::class, 'lead_provide_by');
    }
    public function leadauthorityusers()
    {
        return $this->belongsTo(User::class, 'reporting_authority_id');
    }

    public function managerUser()
    {
        return $this->belongsTo(User::class, 'reporting_authority_id', 'lead_provide_by');
    }


    // public function reporting_manager()
    // {
    //     return $this->hasManyThrough(User::class, Lead::class, 'lead_provide_by', 'id', 'id', 'lead_provide_by')->latest('id')->take(1);
    // }

    public function clients()
    {
        return $this->hasMany(LeadsClient::class, 'lead_company_id');
    }

    public function leadPhones()
    {
        return $this->hasMany(LeadsCompanyPhone::class, 'lead_company_id');
    }

    public function leadEmails()
    {
        return $this->hasMany(LeadsCompanyEmail::class, 'lead_company_id');
    }

    // Define nested relationships for address parts
    public function country()
    {
        return $this->hasOneThrough(Country::class, LeadsCompanyAddress::class, 'lead_company_id', 'id', 'id', 'country_id');
    }

    public function state()
    {
        return $this->hasOneThrough(State::class, LeadsCompanyAddress::class, 'lead_company_id', 'id', 'id', 'state_id');
    }

    public function city()
    {
        return $this->hasOneThrough(City::class, LeadsCompanyAddress::class, 'lead_company_id', 'id', 'id', 'city_id');
    }

}
