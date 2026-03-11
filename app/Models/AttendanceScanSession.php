<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceScanSession extends Model
{
    protected $table = 'attendance_scan_sessions';

    protected $fillable = [
        'session_id',
        'employee_id',
        'scanned_at',
        'device_id',
        'mode',
        'status',
        'failure_reason',
        'confidence_score',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}
