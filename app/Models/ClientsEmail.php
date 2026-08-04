<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ClientsEmail extends Model
{
    use HasFactory;
    protected $fillable = [
        'clients_id',
        'mail',
        'type'
    ];

    public function clientlink()
    {
        return $this->belongsTo(Client::class, 'clients_id');
    }
}
