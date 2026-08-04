<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class RetentionDisposition extends Model
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
        'company_id',
        'client_id',
    ];

    public function client()
    {
        return $this->belongsTo(RetentionClient::class, 'client_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function company()
    {
        return $this->belongsTo(Retention::class, 'company_id');
    }

    public function leadDispositionStatus()
    {
        return $this->belongsTo(DispositionStatus::class, 'status_id'); // Adjust the foreign key if necessary
    }
}
