<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class RetentionAssignCompanies extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_id',
        'user_id',
        'assign_by',
        'assign_to',
        'is_active'
    ];

    protected $table = 'retention_assign_companies';

    public function lead()
    {
        return $this->belongsTo(Retention::class, 'company_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
