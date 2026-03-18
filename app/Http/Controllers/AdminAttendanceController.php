<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OvertimeRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AdminAttendanceController extends Controller
{
    private const ALLOWED_ROLES = ['admin', 'administrator', 'hr', 'manager'];

    private function canAccess(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        $role = strtolower(trim((string) ($user->role ?? '')));
        if (in_array($role, self::ALLOWED_ROLES, true)) {
            return true;
        }
        $userId = $user->user_id ?? $user->id ?? null;
        if ($userId === 1 || $userId === '1') {
            return true;
        }
        return false;
    }

    /**
     * Default period: this week. Single source of truth from DB.
     */
    public function tracking(Request $request)
    {
        if (! $this->canAccess()) {
            return redirect()->route('admin.dashboard')->with('error', 'Access denied. Only Admin, HR, or Manager can view attendance tracking.');
        }

        $departments = Department::orderBy('department_name')->get();
        $now = now();
        $startOfWeek = $now->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $startOfWeek->copy()->endOfWeek(Carbon::SUNDAY);
        $start = $request->input('start', $startOfWeek->format('Y-m-d'));
        $end = $request->input('end', $endOfWeek->format('Y-m-d'));

        return view('admin.attendance_tracking', compact('departments', 'start', 'end'));
    }

    /**
     * Paginated data with server-side status computation.
     * Status rule: Leave (approved) > Present/Late (by check-in vs threshold) > Absent.
     */
    public function data(Request $request)
    {
        if (! $this->canAccess()) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
            'status' => ['nullable', 'string', 'in:present,absent,late,leave'],
            'department' => ['nullable', 'integer', 'exists:departments,department_id'],
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'in:25,50,100'],
            'include_inactive' => ['nullable', 'boolean'],
        ]);

        $start = Carbon::parse($request->input('start'))->startOfDay();
        $end = Carbon::parse($request->input('end'))->endOfDay();
        $statusFilter = $request->input('status');
        $departmentId = $request->input('department');
        $search = trim((string) $request->input('q'));
        $perPage = (int) ($request->input('per_page') ?: 25);
        $perPage = in_array($perPage, [25, 50, 100], true) ? $perPage : 25;
        $includeInactive = filter_var($request->input('include_inactive'), FILTER_VALIDATE_BOOLEAN)
            ?? config('attendance.include_inactive_by_default', false);

        $lateThreshold = config('attendance.late_threshold', '09:00');
        $thresholdTime = Carbon::createFromFormat('H:i', $lateThreshold)->format('H:i');

        $employeeQuery = Employee::with(['department', 'user']);
        if (! $includeInactive) {
            $employeeQuery->where(function ($q) {
                $q->whereNull('employee_status')->orWhere('employee_status', 'active');
            });
        }
        if ($departmentId) {
            $employeeQuery->where('department_id', $departmentId);
        }
        if ($search !== '') {
            $employeeQuery->where(function ($q) use ($search) {
                $q->where('employee_code', 'like', "%{$search}%")
                    ->orWhere('employee_id', $search)
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }
        $employees = $employeeQuery->orderBy('employee_id')->get();
        if ($employees->isEmpty()) {
            return response()->json([
                'data' => [],
                'summary' => ['total' => 0, 'present' => 0, 'late' => 0, 'absent' => 0, 'leave' => 0],
                'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => $perPage, 'total' => 0],
            ]);
        }

        $employeeIds = $employees->keyBy('employee_id');
        $dateStart = $start->copy()->startOfDay();
        $dateEnd = $end->copy()->startOfDay();
        if ($dateEnd->gt($dateStart->copy()->addDays(93))) {
            $dateEnd = $dateStart->copy()->addDays(93);
        }

        $leaves = LeaveRequest::where('leave_status', 'approved')
            ->whereIn('employee_id', $employeeIds->keys())
            ->where(function ($q) use ($dateStart, $dateEnd) {
                $q->where('start_date', '<=', $dateEnd->format('Y-m-d'))
                    ->where('end_date', '>=', $dateStart->format('Y-m-d'));
            })
            ->get();

        $leaveSet = [];
        foreach ($leaves as $l) {
            $s = Carbon::parse($l->start_date)->startOfDay();
            $e = Carbon::parse($l->end_date)->startOfDay();
            for ($d = $s->copy(); $d->lte($e) && $d->lte($dateEnd); $d->addDay()) {
                if ($d->gte($dateStart)) {
                    $leaveSet[$l->employee_id][$d->format('Y-m-d')] = true;
                }
            }
        }

        $attendances = Attendance::whereIn('employee_id', $employeeIds->keys())
            ->whereDate('date', '>=', $dateStart->format('Y-m-d'))
            ->whereDate('date', '<=', $dateEnd->format('Y-m-d'))
            ->get()
            ->groupBy(function ($a) {
                return $a->employee_id . '_' . Carbon::parse($a->date)->format('Y-m-d');
            });

        $rows = [];
        for ($d = $dateStart->copy(); $d->lte($dateEnd); $d->addDay()) {
            $dateStr = $d->format('Y-m-d');
            foreach ($employees as $emp) {
                $eid = $emp->employee_id;
                $key = $eid . '_' . $dateStr;
                $status = 'absent';
                $clockIn = null;
                $clockOut = null;
                $attendanceId = null;

                if (isset($leaveSet[$eid][$dateStr])) {
                    $status = 'leave';
                } else {
                    $rec = $attendances->get($key);
                    if ($rec) {
                        $rec = $rec->first();
                        $attendanceId = $rec->attendance_id;
                        $clockIn = $rec->clock_in_time ? Carbon::parse($rec->clock_in_time)->format('H:i') : null;
                        $clockOut = $rec->clock_out_time ? Carbon::parse($rec->clock_out_time)->format('H:i') : null;
                        $status = $clockIn ? (substr((string) $clockIn, 0, 5) <= $thresholdTime ? 'present' : 'late') : 'absent';
                    }
                }

                $rows[] = [
                    'attendance_id' => $attendanceId,
                    'date' => $dateStr,
                    'employee_id' => $eid,
                    'id' => $emp->employee_code ?? ('EMP-' . $eid),
                    'name' => $emp->user->name ?? 'Unknown',
                    'dept' => $emp->department->department_name ?? 'N/A',
                    'in' => $clockIn ?? '-',
                    'out' => $clockOut ?? '-',
                    'status' => $status,
                ];
            }
        }

        if ($statusFilter) {
            $rows = array_values(array_filter($rows, function ($r) use ($statusFilter) {
                return $r['status'] === $statusFilter;
            }));
        }

        $summary = [
            'total' => count($rows),
            'present' => (int) collect($rows)->where('status', 'present')->count(),
            'late' => (int) collect($rows)->where('status', 'late')->count(),
            'absent' => (int) collect($rows)->where('status', 'absent')->count(),
            'leave' => (int) collect($rows)->where('status', 'leave')->count(),
        ];

        $total = count($rows);
        $page = max(1, (int) $request->input('page'));
        $lastPage = (int) ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        $rows = array_slice($rows, $offset, $perPage);

        $data = array_map(function ($r) {
            $r['status_display'] = $r['status'] === 'leave' ? 'Absent/Leave' : ucfirst($r['status']);
            return $r;
        }, $rows);

        return response()->json([
            'data' => $data,
            'summary' => $summary,
            'pagination' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
            ],
        ]);
    }

    /**
     * Admin daily log (same UI as employee "My Attendance").
     * Shows today's status, last 30 days, and recent overtime requests.
     */
    public function myLog(Request $request)
    {
        if (! $this->canAccess()) {
            return redirect()->route('admin.dashboard')->with('error', 'Access denied.');
        }

        $employee = Auth::user()?->employee;
        abort_unless($employee, 403, 'Employee profile not found');

        $today = Carbon::today();

        // Global working-time config (could be moved to config/attendance.php)
        $workStart = Carbon::createFromFormat('H:i', '09:00');
        $workEnd = Carbon::createFromFormat('H:i', '18:00');
        $graceMinutes = 10;
        $absentMarkTime = Carbon::createFromFormat('H:i', '11:00');
        $minWorkMinutes = 240; // 4 hours

        $from = $today->copy()->subDays(29);
        $rows = Attendance::where('employee_id', $employee->employee_id)
            ->whereBetween('date', [$from->toDateString(), $today->toDateString()])
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date');

        $recentAttendance = [];
        $lateCount = 0;
        $absentCount = 0;

        for ($d = $from->copy(); $d <= $today; $d->addDay()) {
            $dateStr = $d->toDateString();
            $att = $rows->get($dateStr);

            $in = $att?->clock_in_time ? Carbon::parse($att->clock_in_time) : null;
            $out = $att?->clock_out_time ? Carbon::parse($att->clock_out_time) : null;
            $status = 'absent';
            $reason = 'No check-in after cutoff';

            $isWeekend = in_array($d->dayOfWeekIso, [6, 7], true);
            $todayIsCurrent = $d->isSameDay($today);
            $now = Carbon::now();

            // Step 1: weekend/holiday/leave exclusions (simplified)
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
                            $status = 'pending';
                            $reason = 'No check-in yet';
                        } elseif ($now->lt($absentMarkTime)) {
                            $status = 'late';
                            $reason = 'No check-in yet (late)';
                            $lateCount++;
                        } else {
                            $status = 'absent';
                            $reason = 'No check-in';
                            $absentCount++;
                        }
                    } else {
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

                        if ($workedMinutes < $minWorkMinutes) {
                            $flags[] = 'Short hours';
                        }
                        if ($out->lt($workEnd) && $workedMinutes >= $minWorkMinutes) {
                            $flags[] = 'Early leave';
                        }
                        if ($out->gt($workEnd->copy()->addMinutes(15))) {
                            $flags[] = 'Overtime';
                        }

                        $reason = empty($flags) ? 'Normal day' : implode(', ', $flags);
                    }
                }
            }

            $recentAttendance[] = [
                'date' => $dateStr,
                'in' => $att?->clock_in_time,
                'out' => $att?->clock_out_time,
                'status' => $status,
                'reason' => $reason,
                'overtime_hours' => null,
            ];
        }

        $recentAttendance = array_reverse($recentAttendance);

        $todayRecord = $rows->get($today->toDateString());
        $todayStatus = $todayRecord?->at_status ?? 'absent';

        $overtimeHours = (float) OvertimeRecord::where('employee_id', $employee->employee_id)
            ->whereBetween('date', [$from->toDateString(), $today->toDateString()])
            ->where('ot_status', 'approved')
            ->sum('hours');

        $recentOvertime = OvertimeRecord::where('employee_id', $employee->employee_id)
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get();

        return view('admin.attendance_my', [
            'todayRecord' => $todayRecord,
            'todayStatus' => $todayStatus,
            'recentAttendance' => $recentAttendance,
            'lateCount' => $lateCount,
            'overtimeHours' => $overtimeHours,
            'absentCount' => $absentCount,
            'recentOvertime' => $recentOvertime,
        ]);
    }
}
