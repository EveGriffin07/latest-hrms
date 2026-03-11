<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    public const CATEGORY_AUTH = 'AUTH';
    public const CATEGORY_FACE = 'FACE';
    public const CATEGORY_ATTENDANCE = 'ATTENDANCE';
    public const CATEGORY_LEAVE = 'LEAVE';
    public const CATEGORY_PROFILE = 'PROFILE';

    public const ACTOR_EMPLOYEE = 'EMPLOYEE';
    public const ACTOR_ADMIN = 'ADMIN';
    public const ACTOR_SYSTEM = 'SYSTEM';

    public const STATUS_SUCCESS = 'SUCCESS';
    public const STATUS_FAILED = 'FAILED';

    public const SEVERITY_INFO = 'INFO';
    public const SEVERITY_WARN = 'WARN';
    public const SEVERITY_ERROR = 'ERROR';

    public const LOG_TYPE_WEB = 'Web';
    public const LOG_TYPE_API = 'API';
    public const LOG_TYPE_SYSTEM = 'System';
    public const LOG_TYPE_FACESCAN = 'FaceScan';

    public const ENV_PRODUCTION = 'Production';
    public const ENV_STAGING = 'Staging';
    public const ENV_DEMO = 'Demo';

    /**
     * Central logging: every critical action should call this.
     *
     * @param  string  $actionCategory  AUTH | FACE | ATTENDANCE | LEAVE | PROFILE
     * @param  string  $actionType  CREATE | UPDATE | DELETE | APPROVE | REJECT | CHECK_IN | CHECK_OUT | ENROLL_SUCCESS etc.
     * @param  string  $actionStatus  SUCCESS | FAILED
     * @param  string|null  $message  Human-readable summary
     * @param  array|null  $metadata  device_id, ip, similarity_score, reason, etc. (no embeddings)
     * @param  int|null  $employeeId  Subject employee (nullable for admin-only events)
     * @param  string  $severity  INFO | WARN | ERROR
     * @param  string|null  $entityType  e.g. Attendance, Leave, Face, Profile (defaults to action_category)
     * @param  string|int|null  $entityId  e.g. attendance_id, leave_request_id
     * @param  string  $logType  Web | API | System | FaceScan
     * @param  string|null  $environment  Production | Staging | Demo
     */
    public static function log(
        string $actionCategory,
        string $actionType,
        string $actionStatus = self::STATUS_SUCCESS,
        ?string $message = null,
        ?array $metadata = null,
        ?int $employeeId = null,
        string $severity = self::SEVERITY_INFO,
        ?string $entityType = null,
        $entityId = null,
        string $logType = self::LOG_TYPE_WEB,
        ?string $environment = null
    ): ?AuditLog {
        $user = Auth::user();
        $actorType = self::ACTOR_SYSTEM;
        $actorId = null;
        $actorName = null;
        $actorRole = null;
        $actorAvatarUrl = null;
        if ($user) {
            $actorType = in_array(strtolower($user->role ?? ''), ['admin', 'hr', 'manager'], true)
                ? self::ACTOR_ADMIN
                : self::ACTOR_EMPLOYEE;
            $actorId = $user->user_id ?? $user->id ?? null;
            $actorName = $user->name ?? null;
            $actorRole = $user->role ?? null;
            $actorAvatarUrl = $user->avatar_url ?? $user->profile_photo_url ?? null;
        }

        $meta = $metadata ?? [];
        $req = request();
        if (! isset($meta['ip']) && $req) {
            $meta['ip'] = self::maskIp($req->ip());
        }

        $env = $environment ?? (config('app.env') === 'production' ? self::ENV_PRODUCTION : (config('app.env') === 'local' ? self::ENV_DEMO : self::ENV_STAGING));

        try {
            return AuditLog::create([
                'employee_id' => $employeeId,
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'actor_name' => $actorName,
                'actor_role' => $actorRole,
                'actor_avatar_url' => $actorAvatarUrl,
                'action_category' => $actionCategory,
                'entity_type' => $entityType ?? $actionCategory,
                'entity_id' => $entityId !== null ? (string) $entityId : null,
                'action_type' => $actionType,
                'action_status' => $actionStatus,
                'log_type' => $logType,
                'environment' => $env,
                'severity' => $severity,
                'message' => $message,
                'metadata' => $meta,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    /** Mask IP for privacy (e.g. 192.168.1.100 -> 192.168.1.xxx) */
    public static function maskIp(?string $ip): ?string
    {
        if (! $ip) {
            return null;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return preg_replace('/:[0-9a-f]{4}$/i', ':xxxx', $ip) ?: $ip;
        }
        return preg_replace('/\.\d+$/', '.xxx', $ip) ?: $ip;
    }
}
