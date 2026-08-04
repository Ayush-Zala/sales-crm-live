<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadsCompanyPhone extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'type',
        'lead_company_id',
    ];
}
