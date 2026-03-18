<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUPERVISOR_APPROVED = 'supervisor_approved';
    public const STATUS_PENDING_ADMIN = 'pending_admin';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $primaryKey = 'leave_request_id';

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'proof_path',
        'supervisor_id',
        'supervisor_approved_at',
        'supervisor_approved_by',
        'leave_status',
        'approved_by',
        'reject_reason',
        'decision_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'decision_at' => 'datetime',
        'supervisor_approved_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }

    /** Supervisor (user) who must approve first (department manager). */
    public function supervisorUser()
    {
        return $this->belongsTo(User::class, 'supervisor_id', 'user_id');
    }

    /** User who approved as supervisor (before upload to admin). */
    public function supervisorApprover()
    {
        return $this->belongsTo(User::class, 'supervisor_approved_by', 'user_id');
    }

    public function isPendingAtSupervisor(): bool
    {
        return $this->leave_status === self::STATUS_PENDING && $this->supervisor_id;
    }

    public function isSupervisorApproved(): bool
    {
        return $this->leave_status === self::STATUS_SUPERVISOR_APPROVED;
    }

    public function isPendingAdmin(): bool
    {
        return $this->leave_status === self::STATUS_PENDING_ADMIN;
    }

    public function isActionableByAdmin(): bool
    {
        return $this->leave_status === self::STATUS_PENDING_ADMIN;
    }

    /** Human-readable status for employee views. */
    public function getStatusLabel(): string
    {
        return match ($this->leave_status) {
            self::STATUS_PENDING => 'Pending (with supervisor)',
            self::STATUS_SUPERVISOR_APPROVED => 'Approved by supervisor',
            self::STATUS_PENDING_ADMIN => 'Pending (with admin)',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $this->leave_status ?? '')),
        };
    }
}
