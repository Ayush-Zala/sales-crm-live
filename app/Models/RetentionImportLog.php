<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetentionImportLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'summary'     => 'array',
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];
}
