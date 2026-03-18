<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollAdjustment extends Model
{
    protected $fillable = [
        'period_id',
        'employee_id',
        'ot_rate',
        'manual_penalty',
        'salary_increase',
        'reason',
        'updated_by',
    ];
}
