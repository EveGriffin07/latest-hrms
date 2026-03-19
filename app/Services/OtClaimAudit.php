<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\OvertimeClaim;
use Illuminate\Support\Facades\Auth;
use App\Services\AuditLogService;

class OtClaimAudit
{
    public const CATEGORY = 'OT_CLAIM';
    public const ENTITY_TYPE = 'overtime_claim';

    public const ACTION_SUBMITTED = 'OT_CLAIM_SUBMITTED';
    public const ACTION_CANCELLED = 'OT_CLAIM_CANCELLED';
    public const ACTION_RESUBMITTED = 'OT_CLAIM_RESUBMITTED';
    public const ACTION_SUPERVISOR_APPROVED = 'OT_CLAIM_SUPERVISOR_APPROVED';
    public const ACTION_SUPERVISOR_APPROVED_WITH_ADJUSTMENT = 'OT_CLAIM_SUPERVISOR_APPROVED_WITH_ADJUSTMENT';
    public const ACTION_SUPERVISOR_ESCALATED = 'OT_CLAIM_SUPERVISOR_ESCALATED';
    public const ACTION_SUPERVISOR_REJECTED = 'OT_CLAIM_SUPERVISOR_REJECTED';
    public const ACTION_SUPERVISOR_RETURNED = 'OT_CLAIM_SUPERVISOR_RETURNED';
    public const ACTION_ADMIN_APPROVED = 'OT_CLAIM_ADMIN_APPROVED';
    public const ACTION_ADMIN_REJECTED = 'OT_CLAIM_ADMIN_REJECTED';
    public const ACTION_ADMIN_ON_HOLD = 'OT_CLAIM_ADMIN_ON_HOLD';
    public const ACTION_ADMIN_PENDING = 'OT_CLAIM_ADMIN_PENDING';

    public static function log(
        string $actionType,
        OvertimeClaim $claim,
        ?string $beforeStatus = null,
        ?string $afterStatus = null,
        array $metadata = [],
        ?string $message = null
    ): void {
        $user = Auth::user();

        // SUCCESS/FAILED is used by the audit drawer filters.
        // Approvals and intermediate transitions are SUCCESS; rejected/cancelled are FAILED.
        $actionStatus = in_array($actionType, [
            self::ACTION_SUPERVISOR_REJECTED,
            self::ACTION_ADMIN_REJECTED,
            self::ACTION_CANCELLED,
        ], true) ? AuditLogService::STATUS_FAILED : AuditLogService::STATUS_SUCCESS;

        $before = $beforeStatus ?? $claim->getRawOriginal('status');
        $after = $afterStatus ?? $claim->status;

        // Ensure drawer shows "from -> to" directly without relying only on metadata.
        $message = $message ?? $actionType;
        $messageWithTransition = $message . ' (' . $before . ' -> ' . $after . ')';

        AuditLog::create([
            'employee_id' => $claim->employee_id,
            'actor_type' => self::actorType(),
            'actor_id' => $user?->user_id ?? null,
            'actor_name' => $user?->name ?? 'System',
            'action_category' => self::CATEGORY,
            'entity_type' => self::ENTITY_TYPE,
            'entity_id' => (string) $claim->id,
            'action_type' => $actionType,
            'action_status' => $actionStatus,
            'log_type' => 'Web',
            'severity' => 'INFO',
            'message' => $messageWithTransition,
            'metadata' => array_merge([
                'claim_id' => $claim->id,
                'employee_id' => $claim->employee_id,
                'before_status' => $before,
                'after_status' => $after,
                'timestamp' => now()->toIso8601String(),
            ], $metadata),
        ]);
    }

    private static function actorType(): string
    {
        $user = Auth::user();
        if (!$user) {
            return 'SYSTEM';
        }
        if ($user->role === 'admin') {
            return 'ADMIN';
        }
        $emp = \App\Models\Employee::where('user_id', $user->user_id)->first();
        $isSupervisor = $emp && \App\Models\Employee::where('supervisor_id', $user->user_id)->exists();
        return $isSupervisor ? 'SUPERVISOR' : 'EMPLOYEE';
    }
}
