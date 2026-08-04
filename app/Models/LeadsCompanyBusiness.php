<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadsCompanyBusiness extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_company_id',
        'type',
        'business_type',
        'description',
    ];
}
