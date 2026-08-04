<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallLog extends Model
{
    protected $fillable = [
        'call_id',
        'general_id',
        'caller_number',
        'callee_number',
        'start_time',
        'answer_time',
        'end_time',
        'call_duration',
        'direction',
        'department',
        'caller_name',
        'caller_email',
        'caller_did_number',
        'international',
        'calle_name',
        'calle_email',
        'event',
        'result',
        'caller_ext_number',
        'caller_ext_type',
        'caller_number_type',
        'caller_device_type',
        'group_id',
        'recording_id',
        'recording_type',
        'talk_time',
        'hold_time',
        'wait_time',
        'ai_call_summary_id',
        'user_id',
        'operator_name',
        'operator_ext_number',
        'operator_ext_Type',
        'file_url',
        'download_url',
        'operator_ext_id'
    ];

    protected $table = 'call_logs';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $guarded = [];
}
