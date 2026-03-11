<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    const UPDATED_AT = null;

    protected $table = 'audit_logs';

    protected $fillable = [
        'employee_id',
        'actor_type',
        'actor_id',
        'actor_name',
        'actor_role',
        'actor_avatar_url',
        'action_category',
        'entity_type',
        'entity_id',
        'action_type',
        'action_status',
        'log_type',
        'environment',
        'severity',
        'message',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id', 'user_id');
    }
}
