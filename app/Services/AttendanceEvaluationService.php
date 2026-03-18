<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;

class AttendanceEvaluationService
{
    /**
     * Evaluate attendance for a given employee and date.
     *
     * Returns:
     * - primary_status: string (present, late, absent, off_day, holiday, leave, incomplete, pending, etc.)
     * - penalty_types: array of strings (subset of ['late','early_leave','absent'])
     * - reason: human readable reason
     * - late_minutes: int
     * - worked_minutes: int
     * - is_working_day: bool
     */
    public function evaluate(Employee $employee, Carbon $date, ?Attendance $attendance = null): array
    {
        $today = Carbon::today();

        // Working time rules (keep in sync with EmployeeAttendanceController)
        $workStart = Carbon::createFromFormat('H:i', '09:00');
        $workEnd = Carbon::createFromFormat('H:i', '18:00');
        $graceMinutes = 10;
        $absentMarkTime = Carbon::createFromFormat('H:i', '11:00');
        $minWorkMinutes = 240; // 4 hours

        $status = 'absent';
        $reason = 'No check-in after cutoff';
        $lateMinutes = 0;
        $workedMinutes = 0;
        $penaltyTypes = [];
        $isWorkingDay = true;

        $holidays = config('hrms.overtime.holidays', []);
        $dateStr = $date->toDateString();
        $isWeekend = in_array($date->dayOfWeekIso, [6, 7], true);
        $todayIsCurrent = $date->isSameDay($today);
        $now = Carbon::now();

        $in = $attendance?->clock_in_time ? Carbon::parse($attendance->clock_in_time) : null;
        $out = $attendance?->clock_out_time ? Carbon::parse($attendance->clock_out_time) : null;

        // Employment / schedule exclusions
        if ($employee->hire_date && $date->lt(Carbon::parse($employee->hire_date))) {
            $status = 'inactive';
            $reason = 'Not employed yet';
            $isWorkingDay = false;
        } elseif (($employee->employee_status ?? 'active') !== 'active') {
            $status = 'inactive';
            $reason = 'Inactive';
            $isWorkingDay = false;
        } elseif ($isWeekend) {
            $status = 'off_day';
            $reason = 'Weekend';
            $isWorkingDay = false;
        } elseif (in_array($dateStr, $holidays, true)) {
            $status = 'holiday';
            $reason = 'Public holiday';
            $isWorkingDay = false;
        } else {
            // Working day rules
            if (! $in) {
                $lateAfter = $workStart->copy()->addMinutes($graceMinutes);
                if ($todayIsCurrent) {
                    if ($now->lte($lateAfter)) {
                        $status = 'pending';
                        $reason = 'No check-in yet';
                    } elseif ($now->lt($absentMarkTime)) {
                        $status = 'late';
                        $reason = 'No check-in yet (late)';
                        $penaltyTypes[] = 'late';
                    } else {
                        $status = 'absent';
                        $reason = 'No check-in';
                        $penaltyTypes[] = 'absent';
                    }
                } else {
                    $status = 'absent';
                    $reason = 'No check-in';
                    $penaltyTypes[] = 'absent';
                }
            } else {
                $lateAfter = $workStart->copy()->addMinutes($graceMinutes);
                if ($in->lte($lateAfter)) {
                    $status = 'present';
                } else {
                    $status = 'late';
                    $lateMinutes = $in->diffInMinutes($lateAfter);
                    $penaltyTypes[] = 'late';
                }

                if (! $out) {
                    $status = 'incomplete';
                    $reason = 'Missing checkout';
                } else {
                    $workedMinutes = $in->diffInMinutes($out);
                    $flags = [];

                    if ($workedMinutes < $minWorkMinutes) {
                        $flags[] = 'Short hours';
                    }

                    if ($out->lt($workEnd) && $workedMinutes >= $minWorkMinutes) {
                        $flags[] = 'Early leave';
                        $penaltyTypes[] = 'early_leave';
                    }

                    if (empty($flags)) {
                        $reason = 'Normal day';
                    } else {
                        $reason = implode(', ', $flags);
                    }
                }
            }
        }

        // Normalise penalty types
        $penaltyTypes = array_values(array_unique($penaltyTypes));

        return [
            'primary_status' => $status,
            'penalty_types'  => $penaltyTypes,
            'reason'         => $reason,
            'late_minutes'   => $lateMinutes,
            'worked_minutes' => $workedMinutes,
            'is_working_day' => $isWorkingDay,
        ];
    }
}

