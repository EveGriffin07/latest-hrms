<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveBalanceAdjustment extends Model
{
    protected $primaryKey = 'adjustment_id';
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'plan_year',
        'old_total',
        'new_total',
        'admin_user_id',
        'reason',
    ];
}
