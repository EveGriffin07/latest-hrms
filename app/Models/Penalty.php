<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penalty extends Model
{
    protected $primaryKey = 'penalty_id';

    protected $fillable = [
        'employee_id',
        'attendance_id',
        'penalty_name',
        'default_amount',
        'assigned_at',
        'removed_at',
        'status',
        'rejection_remark',
    ];

    protected $casts = [
        'assigned_at' => 'date',
        'removed_at'  => 'date',
    ];

    /**
     * Relationship: Get the removal request that is currently active.
     * This excludes requests that the employee has cancelled.
     */
    public function activeRemovalRequest()
    {
        return $this->hasOne(PenaltyRemovalRequest::class, 'penalty_id', 'penalty_id')
            ->where('status', '!=', PenaltyRemovalRequest::STATUS_CANCELLED_EMPLOYEE);
    }

    /**
     * Relationship: Get all removal requests for this penalty.
     */
    public function removalRequests()
    {
        return $this->hasMany(PenaltyRemovalRequest::class, 'penalty_id', 'penalty_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }
}