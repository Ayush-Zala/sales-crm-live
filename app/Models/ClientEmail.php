<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientEmail extends Model
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
