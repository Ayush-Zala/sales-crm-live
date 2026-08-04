<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetentionClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'fname',
        'lname',
        'designation',
        'linkedinurl',
        'companyId',
    ];

    public function clientPhones()
    {
        return $this->hasMany(RetentionClientPhone::class, 'clients_id');
    }

    public function clientEmails()
    {
        return $this->hasMany(RetentionClientEmail::class, 'clients_id');
    }

    public function dispositionHistory()
    {
        return $this->hasMany(RetentionDisposition::class);
    }

    public function callRemarks()
    {
        return $this->hasMany(CallRemark::class, 'retention_client_id');
    }
}
