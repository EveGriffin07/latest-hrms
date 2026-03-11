<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaceEnrollmentSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'employee_id',
        'device_id',
        'status',
        'movement_state',
        'current_step',
        'movement_completed',
        'frame_count',
        'liveness_passed',
        'quality_score',
        'started_at',
        'completed_at',
        'failure_reason',
    ];

    protected $casts = [
        'liveness_passed' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}
