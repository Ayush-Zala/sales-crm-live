<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignCompanies extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_id',
        'user_id',
        'assign_by',
        'is_active'
    ];

    protected $table = 'assign_companies';

    // Define the relationship with Company
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // Define the relationship with User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Define the relationship with User for the assigned to field
    public function assignTo()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Define the relationship with Assign By
    public function assignBy()
    {
        return $this->belongsTo(User::class, 'assign_by');
    }
}
