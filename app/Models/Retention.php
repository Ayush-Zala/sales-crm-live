<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Retention extends Model
{
    protected $fillable = [
        'name',
        'website',
        'fax',
        'industry',
        'lead_provide_by',
        'assign_to',
        'assign_by',
        'description',
        'source',
        'last_order_us_date',
        'status',
    ];

    public function RetentionPhone()
    {
        return $this->hasMany(RetentionCompanyPhone::class, 'company_id');
    }

    public function RetentionEmail()
    {
        return $this->hasMany(RetentionCompanyEmail::class, 'company_id');
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
        return $this->hasMany(RetentionClient::class, 'companyId');
    }

    public function RetentionCompanyAddress()
    {
        return $this->hasMany(RetentionCompanyAddress::class, 'company_id');
    }

    public function RetentionDisposition()
    {
        return $this->hasMany(RetentionDisposition::class, 'company_id')->latest('id');
    }

    // Custom relationship to fetch the latest disposition
    public function latestRetentionDisposition()
    {
        return $this->hasOne(RetentionDisposition::class, 'company_id')
            ->latest('updated_at'); // Order by updated_at in descending order
    }

    public function RetentionDispositionHistory()
    {
        return $this->hasMany(RetentionDisposition::class, 'company_id');
    }

    public function companyRemarks()
    {
        return $this->hasMany(CallRemark::class, 'retention_company_id');
    }
}
