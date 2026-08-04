<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ClientsPhone extends Model
{
    use HasFactory;
    protected $fillable = [
        'clients_id',
        'phone',
        'type'
    ];
}
