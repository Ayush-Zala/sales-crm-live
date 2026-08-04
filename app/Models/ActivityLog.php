<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'lead_subject_type',
        'event',
        'subject_id',
        'lead_subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'created_at',
        'updated_at',
    ];
}
