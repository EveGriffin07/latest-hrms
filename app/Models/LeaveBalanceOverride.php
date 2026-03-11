<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveBalanceOverride extends Model
{
    protected $primaryKey = 'override_id';
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'plan_year',
        'total_entitlement',
        'updated_by',
        'reason',
    ];

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id', 'leave_type_id');
    }
}
