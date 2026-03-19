<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenaltyRemovalRequest extends Model
{
    protected $table = 'penalty_removal_requests';

    protected $fillable = [
        'penalty_id',
        'employee_id',
        'supervisor_id',
        'admin_id',
        'request_reason',
        'attachment_path',
        'employee_note',
        'supervisor_note',
        'admin_note',
        'status',
        'submitted_at',
        'supervisor_reviewed_at',
        'admin_reviewed_at',
        'final_decision_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'supervisor_reviewed_at' => 'datetime',
        'admin_reviewed_at' => 'datetime',
        'final_decision_at' => 'datetime',
    ];

    public const STATUS_PENDING_SUPERVISOR = 'pending_supervisor_review';
    public const STATUS_NEEDS_CLARIFICATION = 'needs_clarification';
    public const STATUS_PENDING_ADMIN = 'pending_admin';
    public const STATUS_REJECTED_SUPERVISOR = 'rejected_by_supervisor';
    public const STATUS_SUBMITTED_ADMIN = 'submitted_to_admin';
    public const STATUS_APPROVED_ADMIN = 'approved_by_admin';
    public const STATUS_REJECTED_ADMIN = 'rejected_by_admin';
    public const STATUS_CANCELLED_EMPLOYEE = 'cancelled_by_employee';

    public static function terminalStatuses(): array
    {
        return [
            self::STATUS_REJECTED_SUPERVISOR,
            self::STATUS_REJECTED_ADMIN,
            self::STATUS_APPROVED_ADMIN,
            self::STATUS_CANCELLED_EMPLOYEE,
        ];
    }

    public function penalty()
    {
        return $this->belongsTo(Penalty::class, 'penalty_id', 'penalty_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id', 'user_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id', 'user_id');
    }
}

