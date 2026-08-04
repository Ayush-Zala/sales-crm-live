<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadsCompanyEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'type',
        'lead_company_id',
    ];
}

