<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;
    protected $fillable = [
        'companyId',
        'linkdinurl',
        'designation',
        'lname',
        'fname',
        'blacklisted',
    ];

    public function clientPhones()
    {
        return $this->hasMany(ClientsPhone::class, 'clients_id');
    }
    public function clientEmails()
    {
        return $this->hasMany(ClientsEmail::class, 'clients_id');
    }

    public function dispositionHistory()
    {
        return $this->hasMany(Disposition::class);
    }

    public function callRemarks()
    {
        return $this->hasMany(CallRemark::class, 'clients_id');
    }
}
