<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallRemark extends Model
{
    protected $fillable = ['type', 'remark', 'phone', 'company_id', 'client_id', 'lead_company_id', 'lead_client_id'];

}
