<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class RetentionClientPhone extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'type',
        'clients_id',
    ];
}
