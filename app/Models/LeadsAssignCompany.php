<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadsAssignCompany extends Model
{
    use HasFactory;
    protected $fillable = [
        'lead_company_id',
        'user_id',
        'assign_by',
        'assign_to',
        'is_active'
    ];

    protected $table = 'leads_assign_companies';

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_company_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }



}
