<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadsClientsEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'mail',
        'type',
        'lead_client_id',
    ];

    public function leadClientLink()
    {
        return $this->belongsTo(LeadsClient::class, 'lead_client_id');
    }
}
