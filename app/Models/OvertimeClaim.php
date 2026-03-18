<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class OvertimeClaim extends Model
{
    // Employee stage
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_SUBMITTED_TO_SUPERVISOR = 'SUBMITTED_TO_SUPERVISOR';

    // Supervisor stage
    public const STATUS_SUPERVISOR_APPROVED = 'SUPERVISOR_APPROVED';
    public const STATUS_SUPERVISOR_REJECTED = 'SUPERVISOR_REJECTED';
    public const STATUS_SUPERVISOR_RETURNED = 'SUPERVISOR_RETURNED';

    // Admin stage
    public const STATUS_ADMIN_PENDING = 'ADMIN_PENDING';
    public const STATUS_ADMIN_APPROVED = 'ADMIN_APPROVED';
    public const STATUS_ADMIN_REJECTED = 'ADMIN_REJECTED';
    public const STATUS_ADMIN_ON_HOLD = 'ADMIN_ON_HOLD';

    public const STATUS_CANCELLED = 'CANCELLED';

    /** Supervisor action when passing to admin (null = legacy). */
    public const SUPERVISOR_ACTION_APPROVED = 'approved';
    public const SUPERVISOR_ACTION_APPROVED_WITH_ADJUSTMENT = 'approved_with_adjustment';
    public const SUPERVISOR_ACTION_ESCALATED_TO_ADMIN = 'escalated_to_admin';

    public const LOCATION_INSIDE = 'INSIDE';
    public const LOCATION_OUTSIDE = 'OUTSIDE';
    public const LOCATION_CLIENT_SITE = 'CLIENT_SITE';
    public const LOCATION_REMOTE_WFH = 'REMOTE_WFH';
    public const LOCATION_OTHER = 'OTHER';

    protected $fillable = [
        'employee_id', 'user_id', 'area_id', 'period_id', 'date', 'start_time', 'end_time', 'break_minutes', 'hours', 'rate_type',
        'reason', 'supporting_info', 'attachment_path', 'status', 'submitted_at', 'cancelled_at',
        'supervisor_id', 'supervisor_remark', 'supervisor_action_at', 'supervisor_action_type',
        'adjustment_reason', 'escalation_reason', 'recommendation', 'approved_hours',
        'admin_acted_by', 'admin_remark', 'admin_action_at', 'overtime_record_id',
        'location_type', 'location_other', 'proof_image_path', 'missing_proof_reason', 'no_proof_flag',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'hours' => 'decimal:2',
        'rate_type' => 'decimal:2',
        'approved_hours' => 'decimal:2',
        'no_proof_flag' => 'boolean',
        'submitted_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'supervisor_action_at' => 'datetime',
        'admin_action_at' => 'datetime',
    ];

    /** Whether this claim is flagged as having no proof (OUTSIDE without proof image). */
    public function hasNoProofFlag(): bool
    {
        return (bool) $this->no_proof_flag;
    }

    /** Proof status label for display. */
    public function getProofStatusLabel(): string
    {
        if ($this->location_type === self::LOCATION_INSIDE) {
            return 'N/A (Inside)';
        }
        return $this->proof_image_path ? 'Has proof' : 'NO PROOF';
    }

    /** URL for proof image (storage path). */
    public function getProofImageUrlAttribute(): ?string
    {
        if (!$this->proof_image_path) {
            return null;
        }
        return \Illuminate\Support\Facades\Storage::url($this->proof_image_path);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    /** Claimant user (denormalized for JOIN with Users.dept_id). */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function period()
    {
        return $this->belongsTo(PayrollPeriod::class, 'period_id', 'period_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id', 'user_id');
    }

    public function overtimeRecord()
    {
        return $this->belongsTo(OvertimeRecord::class, 'overtime_record_id', 'ot_id');
    }

    /** Allowed transitions: from -> [to]. */
    public static function allowedTransitions(): array
    {
        return [
            self::STATUS_DRAFT => [self::STATUS_SUBMITTED_TO_SUPERVISOR],
            self::STATUS_SUBMITTED_TO_SUPERVISOR => [
                self::STATUS_CANCELLED,
                self::STATUS_SUPERVISOR_APPROVED,
                self::STATUS_SUPERVISOR_REJECTED,
                self::STATUS_SUPERVISOR_RETURNED,
            ],
            self::STATUS_SUPERVISOR_RETURNED => [self::STATUS_SUBMITTED_TO_SUPERVISOR],
            self::STATUS_SUPERVISOR_APPROVED => [self::STATUS_ADMIN_PENDING],
            self::STATUS_ADMIN_PENDING => [
                self::STATUS_ADMIN_APPROVED,
                self::STATUS_ADMIN_REJECTED,
                self::STATUS_ADMIN_ON_HOLD,
            ],
            self::STATUS_ADMIN_ON_HOLD => [self::STATUS_ADMIN_PENDING],
        ];
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = self::allowedTransitions()[$this->status] ?? [];
        return in_array($newStatus, $allowed, true);
    }

    /** Employee can edit when DRAFT or SUPERVISOR_RETURNED. */
    public function isEditableByEmployee(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SUPERVISOR_RETURNED], true);
    }

    /** Employee can cancel only when SUBMITTED_TO_SUPERVISOR (supervisor has not acted). */
    public function isCancellableByEmployee(): bool
    {
        return $this->status === self::STATUS_SUBMITTED_TO_SUPERVISOR;
    }

    /** Only assigned supervisor (claim's supervisor_id is user_id) can act when SUBMITTED_TO_SUPERVISOR. */
    public function isActionableBySupervisor(?int $userId): bool
    {
        return $this->status === self::STATUS_SUBMITTED_TO_SUPERVISOR
            && $userId && (int) $this->supervisor_id === (int) $userId;
    }

    /** Admin can act only when ADMIN_PENDING. */
    public function isActionableByAdmin(): bool
    {
        return $this->status === self::STATUS_ADMIN_PENDING;
    }

    /** Payroll-eligible: admin approved and linked to OvertimeRecord. */
    public function isPayrollEligible(): bool
    {
        return $this->status === self::STATUS_ADMIN_APPROVED && $this->overtime_record_id;
    }

    /** Hours to use for payout (supervisor-approved or claimed). */
    public function getEffectiveApprovedHours(): float
    {
        return (float) ($this->approved_hours ?? $this->hours);
    }

    /** True when supervisor sent to admin as payroll-ready (approve or approve with adjustment). */
    public function isPayrollReadyForAdmin(): bool
    {
        if ($this->status !== self::STATUS_ADMIN_PENDING) {
            return false;
        }
        $action = $this->supervisor_action_type;
        return $action === null
            || $action === self::SUPERVISOR_ACTION_APPROVED
            || $action === self::SUPERVISOR_ACTION_APPROVED_WITH_ADJUSTMENT;
    }

    /** True when supervisor escalated to admin (exception queue). */
    public function isExceptionForAdmin(): bool
    {
        return $this->status === self::STATUS_ADMIN_PENDING
            && $this->supervisor_action_type === self::SUPERVISOR_ACTION_ESCALATED_TO_ADMIN;
    }

    /** Label for supervisor action type. */
    public function getSupervisorActionTypeLabel(): ?string
    {
        return match ($this->supervisor_action_type) {
            self::SUPERVISOR_ACTION_APPROVED => 'Approved',
            self::SUPERVISOR_ACTION_APPROVED_WITH_ADJUSTMENT => 'Approved with adjustment',
            self::SUPERVISOR_ACTION_ESCALATED_TO_ADMIN => 'Escalated to admin',
            default => null,
        };
    }

    /** Progress label for supervisor view: where the claim is after supervisor action. */
    public function getProgressLabelForSupervisor(): string
    {
        return match ($this->status) {
            self::STATUS_SUBMITTED_TO_SUPERVISOR => 'Pending your action',
            self::STATUS_SUPERVISOR_APPROVED, self::STATUS_ADMIN_PENDING => 'Pending admin',
            self::STATUS_ADMIN_APPROVED => 'Posted to payroll',
            self::STATUS_SUPERVISOR_REJECTED => 'Rejected by you',
            self::STATUS_ADMIN_REJECTED => 'Rejected by admin',
            self::STATUS_ADMIN_ON_HOLD => 'On hold',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst(strtolower(str_replace('_', ' ', $this->status ?? ''))),
        };
    }

    /** Progress label for admin view (acted list). */
    public function getProgressLabelForAdmin(): string
    {
        return match ($this->status) {
            self::STATUS_ADMIN_APPROVED => 'Posted to payroll',
            self::STATUS_ADMIN_REJECTED => 'Rejected',
            self::STATUS_ADMIN_ON_HOLD => 'On hold',
            default => ucfirst(strtolower(str_replace('_', ' ', $this->status ?? ''))),
        };
    }

    /** Multiplier for OT rate by date (weekday 1.5, weekend 2.0, holiday 3.0). */
    public static function multiplierForDate(Carbon $date): float
    {
        $dateStr = $date->format('Y-m-d');
        $holidays = config('hrms.overtime.holidays', []);
        if (in_array($dateStr, $holidays, true)) {
            return (float) config('hrms.overtime.multiplier_holiday', 3.0);
        }
        if ($date->isWeekend()) {
            return (float) config('hrms.overtime.multiplier_weekend', 2.0);
        }
        return (float) config('hrms.overtime.multiplier_weekday', 1.5);
    }

    /** Whether this claim has an approved payroll (posted to payroll). */
    public function hasApprovedPayroll(): bool
    {
        return $this->status === self::STATUS_ADMIN_APPROVED;
    }

    /** Payout amount for approved claims (approved_hours × hourly_rate × multiplier). */
    public function getPayout(): float
    {
        $hours = $this->getEffectiveApprovedHours();
        $monthly = (float) ($this->employee->base_salary ?? 0);
        $hoursPerMonth = (float) config('hrms.overtime.working_hours_per_month', 160);
        $hourly = $hoursPerMonth > 0 ? $monthly / $hoursPerMonth : 0;
        $multiplier = self::multiplierForDate($this->date);
        return round($hours * $hourly * $multiplier, 2);
    }

    /** Breakdown for payroll card: hours, hourly_rate, multiplier, payout, date. */
    public function getPayoutBreakdown(): array
    {
        $hours = $this->getEffectiveApprovedHours();
        $monthly = (float) ($this->employee->base_salary ?? 0);
        $hoursPerMonth = (float) config('hrms.overtime.working_hours_per_month', 160);
        $hourly = $hoursPerMonth > 0 ? $monthly / $hoursPerMonth : 0;
        $multiplier = self::multiplierForDate($this->date);
        $payout = round($hours * $hourly * $multiplier, 2);
        return [
            'date' => $this->date?->format('Y-m-d'),
            'hours' => $hours,
            'hourly_rate' => round($hourly, 2),
            'multiplier' => $multiplier,
            'payout' => $payout,
        ];
    }
}
