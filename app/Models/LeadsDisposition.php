<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadsDisposition extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'status_id',
        'status',
        'description',
        'followup_date',
        'followup_time',
        'timezone',
        'start_time',
        'end_time',
        'total_time',
        'user_id',
        'lead_company_id',
        'lead_client_id',
    ];

    public function leadDispositionStatus()
    {
        return $this->belongsTo(DispositionStatus::class, 'status_id'); // Adjust the foreign key if necessary
    }
}
