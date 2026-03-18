<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\OvertimeRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class EmployeeAttendanceController extends Controller
{
    /**
     * Employee daily log: today summary, last 30 days (including absences), and recent overtime.
     */
    public function index(Request $request)
    {
        $employee = Auth::user()->employee;
        abort_if(! $employee, 403, 'Only employees can view attendance.');

        $today = Carbon::today();

        // Global working-time config (could be moved to config/attendance.php)
        $workStart = Carbon::createFromFormat('H:i', '09:00');
        $workEnd = Carbon::createFromFormat('H:i', '18:00');
        $graceMinutes = 10;
        $absentMarkTime = Carbon::createFromFormat('H:i', '11:00');
        $minWorkMinutes = 240; // 4 hours

        // Raw attendance rows for last 30 days
        $from = $today->copy()->subDays(29);
        $rows = Attendance::where('employee_id', $employee->employee_id)
            ->whereBetween('date', [$from->toDateString(), $today->toDateString()])
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date');

        // Build continuous last-30-days view, filling missing days as "absent"
        $recentAttendance = [];
        $lateCount = 0;
        $absentCount = 0;

        for ($d = $from->copy(); $d <= $today; $d->addDay()) {
            $dateStr = $d->toDateString();
            /** @var Attendance|null $att */
            $att = $rows->get($dateStr);

            // Base fields
            $in = $att?->clock_in_time ? Carbon::parse($att->clock_in_time) : null;
            $out = $att?->clock_out_time ? Carbon::parse($att->clock_out_time) : null;
            $status = 'absent';
            $reason = 'No check-in after cutoff';

            $isWeekend = in_array($d->dayOfWeekIso, [6, 7], true);
            $todayIsCurrent = $d->isSameDay($today);
            $now = Carbon::now();

            // Step 1: weekend/holiday/leave exclusions (simplified: weekend + holidays from config)
            $holidays = config('hrms.overtime.holidays', []);
            if ($employee->hire_date && $d->lt(Carbon::parse($employee->hire_date))) {
                $status = '—';
                $reason = 'Not employed yet';
            } elseif (($employee->employee_status ?? 'active') !== 'active') {
                $status = '—';
                $reason = 'Inactive';
            } elseif ($isWeekend) {
                $status = 'off_day';
                $reason = 'Weekend';
            } elseif (in_array($dateStr, $holidays, true)) {
                $status = 'holiday';
                $reason = 'Public holiday';
            } else {
                // Step 2: attendance rules on working days
                if (! $in) {
                    $lateAfter = $workStart->copy()->addMinutes($graceMinutes);
                    if ($todayIsCurrent) {
                        if ($now->lte($lateAfter)) {
                            // Before or at 09:10 – still on time window
                            $status = 'pending';
                            $reason = 'No check-in yet';
                        } elseif ($now->lt($absentMarkTime)) {
                            // After 09:10 but before absent cutoff → treat as late (no check-in yet)
                            $status = 'late';
                            $reason = 'No check-in yet (late)';
                            $lateCount++;
                        } else {
                            // After cutoff (e.g. 11:00) with no check-in → absent
                            $status = 'absent';
                            $reason = 'No check-in';
                            $absentCount++;
                        }
                    } else {
                        // Past days with no check-in are absences
                        $status = 'absent';
                        $reason = 'No check-in';
                        $absentCount++;
                    }
                } else {
                    $lateAfter = $workStart->copy()->addMinutes($graceMinutes);
                    $status = $in->lte($lateAfter) ? 'present' : 'late';
                    if ($status === 'late') {
                        $lateCount++;
                    }

                    if (! $out) {
                        $status = 'incomplete';
                        $reason = 'Missing checkout';
                    } else {
                        $workedMinutes = $in->diffInMinutes($out);
                        $flags = [];

                        // Short hours flag
                        if ($workedMinutes < $minWorkMinutes) {
                            $flags[] = 'Short hours';
                        }

                        // Early leave: left before end of day, but only if worked at least minimum hours
                        if ($out->lt($workEnd) && $workedMinutes >= $minWorkMinutes) {
                            $flags[] = 'Early leave';
                        }

                        // Overtime: stayed significantly after end of day
                        if ($out->gt($workEnd->copy()->addMinutes(15))) {
                            $flags[] = 'Overtime';
                        }

                        if (empty($flags)) {
                            $reason = 'Normal day';
                        } else {
                            $reason = implode(', ', $flags);
                        }
                    }
                }
            }

            $recentAttendance[] = [
                'date' => $dateStr,
                'in' => $att?->clock_in_time,
                'out' => $att?->clock_out_time,
                'status' => $status,
                'reason' => $reason,
                'overtime_hours' => null, // can be extended with OT per day if needed
            ];
        }

        // Show most recent days first in the table
        $recentAttendance = array_reverse($recentAttendance);

        // Today card
        $todayRecord = $rows->get($today->toDateString());
        $todayStatus = $todayRecord?->at_status ?? 'absent';

        // Overtime summary: approved overtime in last 30 days
        $overtimeHours = (float) OvertimeRecord::where('employee_id', $employee->employee_id)
            ->whereBetween('date', [$from->toDateString(), $today->toDateString()])
            ->where('ot_status', 'approved')
            ->sum('hours');

        // Recent overtime rows (last 5)
        $recentOvertime = OvertimeRecord::where('employee_id', $employee->employee_id)
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get();

        return view('employee.attendance', [
            'employee'        => $employee,
            'todayRecord'     => $todayRecord,
            'todayStatus'     => $todayStatus,
            'recentAttendance'=> $recentAttendance,
            'lateCount'       => $lateCount,
            'overtimeHours'   => $overtimeHours,
            'absentCount'     => $absentCount,
            'recentOvertime'  => $recentOvertime,
        ]);
    }
}

