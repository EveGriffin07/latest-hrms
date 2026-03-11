<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceAuditLog extends Model
{
    protected $table = 'face_audit_logs';

    protected $fillable = [
        'event_type',
        'employee_id',
        'actor_id',
        'actor_type',
        'success',
        'failure_reason',
        'confidence_score',
        'reason',
        'meta',
        'occurred_at',
    ];

    protected $casts = [
        'success' => 'boolean',
        'confidence_score' => 'float',
        'meta' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id', 'user_id');
    }

    /**
     * Log a face-related event (enrollment, attendance scan, template reset, manual override).
     */
    public static function log(
        string $eventType,
        ?int $employeeId = null,
        bool $success = false,
        ?int $actorId = null,
        ?string $actorType = null,
        ?string $failureReason = null,
        ?float $confidenceScore = null,
        ?string $reason = null,
        ?array $meta = null
    ): self {
        return self::create([
            'event_type' => $eventType,
            'employee_id' => $employeeId,
            'actor_id' => $actorId,
            'actor_type' => $actorType ?? (auth()->check() ? (auth()->user()->role === 'admin' ? 'admin' : 'employee') : 'system'),
            'success' => $success,
            'failure_reason' => $failureReason,
            'confidence_score' => $confidenceScore,
            'reason' => $reason,
            'meta' => $meta,
            'occurred_at' => now(),
        ]);
    }
}
