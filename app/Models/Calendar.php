<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'start_date',
        'end_date',
        'phone',
        'timezone',
        'description',
        'zoom_meeting',
        'zoom_meeting_id',
        'zoom_meeting_url',
        'zoom_meeting_details',
        'all_day',
        'repeat',
        'repeat_rule',
        'colors',
        'company_id',
        'client_id',
        'created_by',
        'updated_by',
    ];
}
