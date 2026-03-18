<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\PenaltyGenerationService;
use App\Services\AttendanceEvaluationService;
use Carbon\Carbon;

class Attendance extends Model {
    protected $table = 'attendance'; // Because 'attendance' is singular, Laravel might look for 'attendances'
    protected $primaryKey = 'attendance_id';
    protected $guarded = [];

    public function employee() {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function setAtStatusAttribute($value): void
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            $this->attributes['at_status'] = null;
            return;
        }
        $normalized = strtolower(str_replace(' ', '_', $raw));
        $allowed = ['present', 'late', 'absent', 'early_leave', 'off_day', 'holiday', 'leave', 'incomplete', 'pending'];
        $this->attributes['at_status'] = in_array($normalized, $allowed, true) ? $normalized : $normalized;
    }

    protected static function booted(): void
    {
        static::saving(function (Attendance $attendance) {
            // Compute and persist a normalized primary status using the shared evaluation service.
            try {
                $employee = $attendance->employee ?: Employee::find($attendance->employee_id);
                if ($employee && $attendance->date) {
                    $service = app(AttendanceEvaluationService::class);
                    $date = $attendance->date instanceof Carbon
                        ? $attendance->date
                        : Carbon::parse($attendance->date);
                    $eval = $service->evaluate($employee, $date, $attendance);
                    if (! empty($eval['primary_status'])) {
                        $attendance->at_status = $eval['primary_status'];
                    }
                }
            } catch (\Throwable $e) {
                // Do not block attendance saving if evaluation fails.
            }
        });

        static::saved(function (Attendance $attendance) {
            // Generate or update penalties whenever an attendance record is saved.
            try {
                app(PenaltyGenerationService::class)->syncForAttendance($attendance);
            } catch (\Throwable $e) {
                // Fail-safe: do not break attendance saving if penalty generation has an issue.
            }
        });
    }
}
