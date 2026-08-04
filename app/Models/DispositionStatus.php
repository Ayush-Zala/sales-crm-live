<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DispositionStatus extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'status'
    ];

    public function dispositions()
    {
        return $this->hasMany(Disposition::class, 'status_id');
    }
}
