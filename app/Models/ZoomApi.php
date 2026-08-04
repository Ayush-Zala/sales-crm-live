<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZoomApi extends Model
{
    protected $fillable = [
        'email_id',
        'password',
        'account_id',
        'client_key',
        'client_secret',
        'user_id',
        'created_at',
        'updated_at'
    ];
}
