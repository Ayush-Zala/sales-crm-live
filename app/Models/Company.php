<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'website',
        'industry',
        'fax',
        'converted',
        'assign_by',
        'create_user_id',
        'assign_to',
        'assign'
    ];

    public function companyPhone()
    {
        return $this->hasMany(CompanyPhone::class);
    }

    public function companyEmail()
    {
        return $this->hasMany(CompanyEmail::class);
    }

    public function assignBy()
    {
        return $this->belongsTo(User::class, 'assign_by');
    }

    public function assignTo()
    {
        return $this->belongsTo(User::class, 'assign_to');
    }

    public function clients()
    {
        return $this->hasMany(Client::class, 'companyId');
    }

    public function companyAddress()
    {
        return $this->hasMany(CompanyAddress::class);
    }

    public function companyBusiness()
    {
        return $this->hasMany(CompanyBusiness::class);
    }

    public function disposition()
    {
        //return $this->hasOne(Disposition::class)->get();
        return $this->hasOne(Disposition::class)->latestOfMany();
    }

    // Custom relationship to fetch the latest disposition
    public function latestDisposition()
    {
        return $this->hasOne(Disposition::class, 'company_id')
            ->latest('updated_at'); // Order by updated_at in descending order
    }

    public function dispositionHistory()
    {
        return $this->hasMany(Disposition::class);
    }

    public function companyRemarks()
    {
        return $this->hasMany(CallRemark::class, 'company_id');
    }

}

