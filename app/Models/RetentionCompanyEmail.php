<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetentionCompanyEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'type',
        'company_id',
    ];
}
