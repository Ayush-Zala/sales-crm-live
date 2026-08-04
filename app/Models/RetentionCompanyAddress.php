<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class RetentionCompanyAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'house_no',
        'street',
        'addressline2',
        'zip',
        'timezone',
        'company_id',
        'country_id',
        'state_id',
        'city_id',
    ];

    // Relationship to Country
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    // Relationship to State
    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    // Relationship to City
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }
}
