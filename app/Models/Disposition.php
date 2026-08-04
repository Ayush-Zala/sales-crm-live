<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Disposition extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'user_id',
        'company_id',
        'client_id',
        'timezone',
        'phone',
        'status_id',
        'status',
        'description',
        'followup_date',
        'followup_time',
        'start_time',
        'end_time',
        'total_time',
        'created_at',
        'updated_at',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dispositionStatus()
    {
        return $this->belongsTo(DispositionStatus::class, 'status_id');
    }
}
