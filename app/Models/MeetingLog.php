<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingLog extends Model
{
    protected $table = 'meeting_logs';

    protected $fillable = [
        'meeting_id',
        'account_id',
        'host_id',
        'topic',
        'type',
        'start_time',
        'tmezone',
        'duration',
        'share_url',
        'participants',
        'meeting_key',
        'recording_start',
        'user_id',
        'recording_play_passcode',
        'transcript',
        'audio_transcript',
        'audio_file_script_url',
        'recording_end',
        'created_at',
        'updated_at'
    ];
}
