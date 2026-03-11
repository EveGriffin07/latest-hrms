<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class PayrollAudit
{
    public const CATEGORY = 'PAYROLL';
    public const ENTITY_TYPE = 'payroll_period';

    public const ACTION_GENERATED = 'PAYROLL_GENERATED';
    public const ACTION_RECALCULATED = 'PAYROLL_RECALCULATED';
    public const ACTION_ADJUSTMENT_ADDED = 'PAYROLL_ADJUSTMENT_ADDED';
    public const ACTION_ADJUSTMENT_UPDATED = 'PAYROLL_ADJUSTMENT_UPDATED';
    public const ACTION_ADJUSTMENT_REMOVED = 'PAYROLL_ADJUSTMENT_REMOVED';
    public const ACTION_RELEASED = 'PAYROLL_RELEASED';
    public const ACTION_PAID = 'PAYROLL_PAID';
    public const ACTION_PUBLISHED = 'PAYROLL_PUBLISHED';

    public static function log(
        string $actionType,
        string $periodMonth,
        ?int $entityId = null,
        ?int $employeeId = null,
        array $metadata = [],
        ?string $message = null
    ): void {
        $user = Auth::user();
        AuditLog::create([
            'employee_id'      => $employeeId,
            'actor_type'      => 'ADMIN',
            'actor_id'        => $user?->user_id ?? null,
            'actor_name'      => $user?->name ?? 'System',
            'action_category' => self::CATEGORY,
            'entity_type'     => self::ENTITY_TYPE,
            'entity_id'       => $entityId !== null ? (string) $entityId : $periodMonth,
            'action_type'     => $actionType,
            'action_status'   => 'SUCCESS',
            'log_type'        => 'Web',
            'severity'        => 'INFO',
            'message'         => $message ?? $actionType,
            'metadata'        => array_merge([
                'period_month' => $periodMonth,
                'timestamp'    => now()->toIso8601String(),
            ], $metadata),
        ]);
    }
}
