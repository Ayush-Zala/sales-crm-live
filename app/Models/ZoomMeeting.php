<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZoomMeeting extends Model
{
    protected $fillable = [
        'id',
        'meeting_uuid',
        'topic',
        'start_time',
        'timezone',
        'user_id',
        'created_at',
        'updated_at',
    ];
}
