<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadsClientsPhone extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'type',
        'lead_client_id',
    ];
}
