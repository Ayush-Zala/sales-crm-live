<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class RetentionClientEmail extends Model
{
  
    protected $fillable = [
        'mail',
        'type',
        'clients_id',
    ];

    public function leadClientLink()
    {
        return $this->belongsTo(RetentionClient::class, 'clients_id');
    }
}
