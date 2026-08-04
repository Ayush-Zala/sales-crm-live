<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyAddress extends Model
{
    use HasFactory;
    protected $fillable = [
        'block',
        'street',
        'address',
        'zip',
        'timezone',
        'company_id',
        'country_id',
        'state_id',
        'city_id'
    ];

    // Relationship to Country
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id'); // Adjust the foreign key if necessary
    }

    // Relationship to State
    public function state()
    {
        return $this->belongsTo(State::class, 'state_id'); // Adjust the foreign key if necessary
    }

    // Relationship to City
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id'); // Adjust the foreign key if necessary
    }
}
