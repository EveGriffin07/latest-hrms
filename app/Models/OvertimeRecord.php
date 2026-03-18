<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OvertimeRecord extends Model
{
    protected $primaryKey = 'ot_id';

    // final_status: workflow state
    public const FINAL_PENDING_SUPERVISOR = 'PENDING_SUPERVISOR';
    public const FINAL_PENDING_ADMIN = 'PENDING_ADMIN';
    public const FINAL_REJECTED_SUPERVISOR = 'REJECTED_SUPERVISOR';
    public const FINAL_APPROVED_ADMIN = 'APPROVED_ADMIN';
    public const FINAL_REJECTED_ADMIN = 'REJECTED_ADMIN';

    // supervisor_decision
    public const SUPERVISOR_APPROVED = 'APPROVED';
    public const SUPERVISOR_REJECTED = 'REJECTED';

    // admin_decision
    public const ADMIN_APPROVED = 'APPROVED';
    public const ADMIN_REJECTED = 'REJECTED';

    protected $fillable = [
        'employee_id',
        'department_id',
        'supervisor_id',
        'period_id',
        'date',
        'hours',
        'rate_type',
        'ot_status',
        'final_status',
        'supervisor_decision',
        'reason',
        'supervisor_approved_by',
        'supervisor_action_at',
        'submitted_to_admin_at',
        'flagged_for_admin',
        'admin_review_remark',
        'issue_flagged_by',
        'issue_flagged_at',
        'admin_decision',
        'admin_comment',
        'admin_action_at',
        'approved_by',
    ];

    protected $casts = [
        'date' => 'date',
        'hours' => 'decimal:2',
        'rate_type' => 'decimal:2',
        'submitted_to_admin_at' => 'datetime',
        'supervisor_action_at' => 'datetime',
        'issue_flagged_at' => 'datetime',
        'admin_action_at' => 'datetime',
        'flagged_for_admin' => 'boolean',
    ];

    /** issue_flag: use flagged_for_admin; issue_reason: use admin_review_remark */
    public function isFlaggedForAdmin(): bool
    {
        return (bool) $this->flagged_for_admin;
    }


    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /** User (supervisor) assigned from department at submit time. */
    public function supervisorUser()
    {
        return $this->belongsTo(User::class, 'supervisor_id', 'user_id');
    }

    public function period()
    {
        return $this->belongsTo(PayrollPeriod::class, 'period_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }

    public function supervisorApprover()
    {
        return $this->belongsTo(User::class, 'supervisor_approved_by', 'user_id');
    }

    public function issueFlaggedByUser()
    {
        return $this->belongsTo(User::class, 'issue_flagged_by', 'user_id');
    }
}
