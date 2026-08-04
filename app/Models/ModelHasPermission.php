<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelHasPermission extends Model
{
    use HasFactory;
    protected $table = 'model_has_permissions';
    protected $fillable = ['model_id', 'permission_id'];

    public $timestamps = false;
}
