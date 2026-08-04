<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientPhone extends Model
{
    use HasFactory;
    protected $fillable = [
        'clients_id',
        'phone',
        'type'
    ];
}
