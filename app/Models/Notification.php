<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_id',
        'client_id',
        'title',
        'description',
        'status',
        'followup_date',
        'followup_time',
        'timezone',
        'flag'
    ];
}