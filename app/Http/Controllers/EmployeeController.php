<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\TrainingEnrollment;
use App\Models\Payslip;
use App\Models\Announcement;
use App\Models\Employee;
use App\Models\JobRequisition;
use App\Models\LeaveBalanceOverride;
use App\Models\LeaveType;
use App\Models\OvertimeClaim;
use App\Models\OvertimeRecord;
use App\Models\Penalty;
use App\Services\AttendanceEvaluationService;

class EmployeeController extends Controller
{
    public function index()
    {
        // 1. Get the Logged-in Employee
        $user = Auth::user();
        $employee = $user->employee; // Uses the relationship we just added

        // Safety Check: If login is 'admin' or 'applicant', they won't have an employee profile
        if (!$employee) {
            abort(403, 'User does not have an Employee Profile.');
        }

        // 2. Fetch Attendance for Today
        $todayAttendance = Attendance::where('employee_id', $employee->employee_id)
                                     ->whereDate('date', Carbon::today())
                                     ->first();

        // 3. Calculate Leave Balance (Simplified Logic)
        // Assuming every employee gets 14 days Annual Leave. 
        // In the future, you can fetch this from $employee->position->leave_entitlement
        $totalEntitlement = 14; 
        $leaveUsed = LeaveRequest::where('employee_id', $employee->employee_id)
                                 ->where('leave_status', 'approved')
                                 ->sum('total_days');
        $leaveBalance = $totalEntitlement - $leaveUsed;

        // 4. Count Upcoming Trainings
        // We use whereHas to filter trainings that start in the future
        $upcomingTrainings = TrainingEnrollment::where('employee_id', $employee->employee_id)
                                               ->whereHas('training', function($query) {
                                                   $query->where('start_date', '>', now());
                                               })->count();

        // 5. Get Latest Payslip
        $latestPayslip = Payslip::where('employee_id', $employee->employee_id)
                                ->orderBy('generated_at', 'desc')
                                ->first();

        // 6. Fetch Latest Announcements (NEW LOGIC)
        $announcements = Announcement::where('publish_at', '<=', now())
                                     ->orderByRaw("FIELD(priority, 'High', 'Normal')")
                                     ->orderBy('publish_at', 'desc')
                                     ->take(3)
                                     ->get();

        // 7. Reports: attendance and overtime by scope (employee = self; supervisor = department)
        $selectedMonth = request()->query('month'); // YYYY-MM
        $periodEnd = Carbon::today();
        $periodStart = $periodEnd->copy()->subDays(29);
        if (is_string($selectedMonth) && preg_match('/^\\d{4}-\\d{2}$/', $selectedMonth)) {
            $mStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
            $mEnd = $mStart->copy()->endOfMonth();
            $periodStart = $mStart;
            $periodEnd = $mEnd->greaterThan(Carbon::today()) ? Carbon::today() : $mEnd;
        }
        $reportAttendance = $this->buildAttendanceReport($user, $employee, $periodStart, $periodEnd);
        $reportOvertime = $this->buildOvertimeReport($user, $employee, $periodStart, $periodEnd);
        $reportLeave = $this->buildLeaveReport($user, $employee, $periodStart, $periodEnd);
        $reportPredictive = $this->buildPredictiveReport($reportAttendance, $reportOvertime, $reportLeave);

        // 8. For supervisors: leave requests pending their approval and approved-by-them awaiting upload
        $teamLeavePendingCount = 0;
        $teamLeaveApprovedCount = 0;
        if (strtolower($user->role ?? '') === 'supervisor') {
            $userId = $user->user_id ?? $user->getAuthIdentifier();
            $teamLeavePendingCount = LeaveRequest::where('supervisor_id', $userId)
                ->where('leave_status', LeaveRequest::STATUS_PENDING)
                ->count();
            $teamLeaveApprovedCount = LeaveRequest::where('supervisor_approved_by', $userId)
                ->where('leave_status', LeaveRequest::STATUS_SUPERVISOR_APPROVED)
                ->count();
        }

        return view('employee.dashboard', compact(
            'employee',
            'todayAttendance',
            'leaveBalance',
            'upcomingTrainings',
            'latestPayslip',
            'announcements',
            'teamLeavePendingCount',
            'teamLeaveApprovedCount',
            'reportAttendance',
            'reportOvertime',
            'reportLeave',
            'reportPredictive'
        ));
    }

    public function showAnnouncement($id)
    {
        $announcement = \App\Models\Announcement::findOrFail($id);
        return view('employee.announcement_show', compact('announcement'));
    }

    public function storeRequisition(Request $request)
{
    $employee = Auth::user()->employee;

    // Security check: Only allow if they are actually a manager
    if (!$employee->position->is_manager) {
        abort(403, 'Unauthorized action.');
    }

    JobRequisition::create([
        'department_id'   => $employee->department_id,
        'requested_by'    => $employee->employee_id,
        'job_title'       => $request->job_title,
        'employment_type' => $request->employment_type,
        'headcount'       => $request->headcount,
        'justification'   => $request->justification,
        'status'          => 'Pending',
    ]);

    return redirect()->back()->with('success', 'Hiring request submitted to HR successfully!');
}
public function scanQr($token)
    {
        // 1. Find the training by the secret token
        $training = \App\Models\TrainingProgram::where('qr_token', $token)->firstOrFail();
        
        // 2. Get the currently logged-in employee
        $user = \Illuminate\Support\Facades\Auth::user();
        $employee = \App\Models\Employee::where('user_id', $user->user_id)->first();

        // 3. Find their enrollment record
        $enrollment = \App\Models\TrainingEnrollment::where('training_id', $training->training_id)
                        ->where('employee_id', $employee->employee_id)
                        ->first();

        // 4. Logic Checks
        if (!$enrollment) {
            return redirect()->route('employee.training.index')
                             ->with('error', 'You are not enrolled in this training.');
        }

        if ($enrollment->completion_status == 'completed') {
            return redirect()->route('employee.training.show', $training->training_id)
                             ->with('success', 'Your attendance was already recorded!');
        }

        // 5. Mark as Attended (Completed)
        $enrollment->update([
            'completion_status' => 'completed',
            'remarks'           => 'Attended (Verified via QR Scan)'
        ]);

        return redirect()->route('employee.training.show', $training->training_id)
                         ->with('success', 'Attendance recorded successfully! Thank you for attending.');
    }

    /**
     * Build attendance report data for dashboard: employee sees own; supervisor sees department employees.
     */
    private function buildAttendanceReport($user, $employee, ?Carbon $periodStart = null, ?Carbon $periodEnd = null): array
    {
        $role = strtolower(trim((string) ($user->role ?? '')));
        $employeeIds = [$employee->employee_id];

        if ($role === 'supervisor') {
            $deptIds = Department::where('manager_id', $user->user_id)->pluck('department_id');
            $employeeIds = Employee::whereIn('department_id', $deptIds)->pluck('employee_id')->all();
            if (empty($employeeIds)) {
                $employeeIds = [$employee->employee_id];
            }
        }

        $periodEnd = $periodEnd ?: Carbon::today();
        $periodStart = $periodStart ?: $periodEnd->copy()->subDays(29);

        // Load employees so we can evaluate working-day rules consistently.
        $employees = Employee::whereIn('employee_id', $employeeIds)->get();
        $employeeCount = max(1, $employees->count());

        // Attendance records (if present) for the period keyed by employee+date.
        $records = Attendance::whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$periodStart->format('Y-m-d'), $periodEnd->format('Y-m-d')])
            ->get()
            ->keyBy(function ($a) {
                return $a->employee_id . '_' . Carbon::parse($a->date)->format('Y-m-d');
            });

        // Approved leave overlay for the same period, keyed by employee+date.
        $leaves = LeaveRequest::where('leave_status', 'approved')
            ->whereIn('employee_id', $employeeIds)
            ->where(function ($q) use ($periodStart, $periodEnd) {
                $q->whereDate('start_date', '<=', $periodEnd->format('Y-m-d'))
                    ->whereDate('end_date', '>=', $periodStart->format('Y-m-d'));
            })
            ->get();

        $leaveSet = [];
        foreach ($leaves as $l) {
            $s = Carbon::parse($l->start_date)->startOfDay();
            $e = Carbon::parse($l->end_date)->startOfDay();
            for ($d = $s->copy(); $d->lte($e) && $d->lte($periodEnd); $d->addDay()) {
                if ($d->gte($periodStart)) {
                    $leaveSet[$l->employee_id][$d->format('Y-m-d')] = true;
                }
            }
        }

        $service = app(AttendanceEvaluationService::class);

        $totalDays = $periodStart->diffInDays($periodEnd) + 1;
        $workingDaySlots = 0; // denominator for attendance rate (working days only; excludes inactive/off/holiday/leave)

        $presentCount = 0;
        $lateCount = 0;
        $absentCount = 0;
        $leaveCount = 0;

        for ($d = $periodStart->copy()->startOfDay(); $d->lte($periodEnd->copy()->startOfDay()); $d->addDay()) {
            $dateStr = $d->format('Y-m-d');
            foreach ($employees as $emp) {
                $eid = $emp->employee_id;

                // Leave supersedes late/absent (approved leave day)
                if (isset($leaveSet[$eid][$dateStr])) {
                    $leaveCount++;
                    continue;
                }

                $att = $records->get($eid . '_' . $dateStr);
                $eval = $service->evaluate($emp, $d->copy(), $att);
                $status = (string) ($eval['primary_status'] ?? 'absent');
                $isWorkingDay = (bool) ($eval['is_working_day'] ?? false);

                if (! $isWorkingDay) {
                    continue;
                }

                // Working day counts
                $workingDaySlots++;
                if ($status === 'present') {
                    $presentCount++;
                } elseif ($status === 'late') {
                    $lateCount++;
                } elseif ($status === 'absent') {
                    $absentCount++;
                }
            }
        }

        $attendanceRate = $workingDaySlots > 0
            ? (int) round(($presentCount + $lateCount) / $workingDaySlots * 100)
            : 0;

        // Last 7 days trend (day label + counts per status)
        $trendLabels = [];
        $trendPresent = [];
        $trendLate = [];
        $trendAbsent = [];
        $trendLeave = [];
        $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        for ($i = 6; $i >= 0; $i--) {
            $d = $periodEnd->copy()->startOfDay()->subDays($i);
            $dateStr = $d->format('Y-m-d');
            $trendLabels[] = $dayNames[(int) $d->format('w')];
            $p = 0; $l = 0; $a = 0; $lv = 0;
            foreach ($employees as $emp) {
                $eid = $emp->employee_id;
                if (isset($leaveSet[$eid][$dateStr])) {
                    $lv++;
                    continue;
                }
                $att = $records->get($eid . '_' . $dateStr);
                $eval = $service->evaluate($emp, $d->copy(), $att);
                if (! ($eval['is_working_day'] ?? false)) {
                    continue;
                }
                $status = (string) ($eval['primary_status'] ?? 'absent');
                if ($status === 'present') $p++;
                elseif ($status === 'late') $l++;
                elseif ($status === 'absent') $a++;
            }
            $trendPresent[] = $p;
            $trendLate[] = $l;
            $trendAbsent[] = $a;
            $trendLeave[] = $lv;
        }

        $highlights = [];
        foreach ($trendLabels as $idx => $label) {
            $late = $trendLate[$idx] ?? 0;
            $absent = $trendAbsent[$idx] ?? 0;
            if ($late > 0) {
                $highlights[] = ['day' => $label, 'text' => $late . ' late'];
            }
            if ($absent > 0) {
                $highlights[] = ['day' => $label, 'text' => $absent . ' absent'];
            }
        }

        return [
            'scope_label'     => $role === 'supervisor' ? 'Your department' : 'You',
            'attendance_rate' => $attendanceRate,
            'total_days'      => $totalDays,
            'period_start'    => $periodStart->format('Y-m-d'),
            'period_end'      => $periodEnd->format('Y-m-d'),
            'present_count'   => $presentCount,
            'late_count'      => $lateCount,
            'absent_count'    => $absentCount,
            'leave_count'     => $leaveCount,
            'employee_count'  => $employeeCount,
            'trend_labels'   => $trendLabels,
            'trend_present'   => $trendPresent,
            'trend_late'     => $trendLate,
            'trend_absent'   => $trendAbsent,
            'trend_leave'    => $trendLeave,
            'highlights'      => $highlights,
        ];
    }

    /**
     * Build leave usage report for the current year:
     * - Employee: own leave usage
     * - Supervisor: aggregated usage for employees in departments they manage
     */
    private function buildLeaveReport($user, $employee, ?Carbon $periodStart = null, ?Carbon $periodEnd = null): array
    {
        $role = strtolower(trim((string) ($user->role ?? '')));
        $employeeIds = [$employee->employee_id];

        if ($role === 'supervisor') {
            $deptIds = Department::where('manager_id', $user->user_id)->pluck('department_id');
            $employeeIds = Employee::whereIn('department_id', $deptIds)->pluck('employee_id')->all();
            if (empty($employeeIds)) {
                $employeeIds = [$employee->employee_id];
            }
        }

        // Ensure service band is up-to-date for entitlement calculations
        $employee->recomputeServiceBand();

        $periodEnd = $periodEnd ?: Carbon::today();
        $periodStart = $periodStart ?: $periodEnd->copy()->subDays(29);
        $year = (int) $periodEnd->year;
        $yearStart = $periodEnd->copy()->startOfYear();
        $leaveTypes = LeaveType::orderBy('leave_name')->get();
        $rows = [];
        $labels = [];
        $usedData = [];
        $remainingData = [];

        foreach ($leaveTypes as $type) {
            $typeName = (string) ($type->leave_name ?? 'Leave');
            $entitlementPerEmployee = $this->entitlementForEmployee($employee, $type);
            $entitlementTotal = $entitlementPerEmployee * count($employeeIds);

            // “Follow month”: usage + remaining as of the end of selected month (YTD up to periodEnd).
            // Overlap logic handles leaves spanning across dates.
            $used = (float) LeaveRequest::whereIn('employee_id', $employeeIds)
                ->where('leave_type_id', $type->leave_type_id)
                ->where('leave_status', 'approved')
                ->whereDate('start_date', '<=', $periodEnd->format('Y-m-d'))
                ->whereDate('end_date', '>=', $yearStart->format('Y-m-d'))
                ->sum('total_days');

            $remaining = max($entitlementTotal - $used, 0);

            // Skip leave types with no entitlement and no usage (keeps dashboard clean)
            if ($entitlementTotal <= 0 && $used <= 0) {
                continue;
            }

            $labels[] = $this->shortLeaveLabel($typeName);
            $usedData[] = (float) $used;
            $remainingData[] = (float) $remaining;
            $rows[] = [
                'type' => $typeName,
                'used' => $used,
                'remaining' => $remaining,
            ];
        }

        return [
            'scope_label' => $role === 'supervisor' ? 'Your department' : 'You',
            'year' => $year,
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'labels' => $labels,
            'used' => $usedData,
            'remaining' => $remainingData,
            'rows' => $rows,
        ];
    }

    /** Entitlement for a leave type for ONE employee for this year (includes overrides). */
    private function entitlementForEmployee($employee, LeaveType $type): int
    {
        $name = strtolower((string) ($type->leave_name ?? ''));
        $year = (int) now()->year;

        $override = LeaveBalanceOverride::where('employee_id', $employee->employee_id)
            ->where('leave_type_id', $type->leave_type_id)
            ->where('plan_year', $year)
            ->first();
        if ($override) {
            return (int) $override->total_entitlement;
        }

        $band = strtoupper((string) ($employee->service_band ?? 'BAND_A'));

        if (str_contains($name, 'annual')) {
            return match ($band) {
                'BAND_A' => 8,
                'BAND_B' => 12,
                default  => 16,
            };
        }

        if (str_contains($name, 'sick')) {
            return match ($band) {
                'BAND_A' => 14,
                'BAND_B' => 18,
                default  => 22,
            };
        }

        if (str_contains($name, 'hospital')) {
            return 60;
        }

        if (str_contains($name, 'maternity')) {
            return (strtolower((string) ($employee->gender ?? '')) === 'female') ? 98 : 0;
        }

        if (str_contains($name, 'paternity')) {
            $isMale = strtolower((string) ($employee->gender ?? '')) === 'male';
            $isMarried = strtolower((string) ($employee->marital_status ?? '')) === 'married';
            return ($isMale && $isMarried) ? 7 : 0;
        }

        return (int) ($type->default_days_year ?? 0);
    }

    private function shortLeaveLabel(string $leaveName): string
    {
        $n = strtolower(trim($leaveName));
        if (str_contains($n, 'annual')) return 'Annual';
        if (str_contains($n, 'sick')) return 'Sick';
        if (str_contains($n, 'emergency')) return 'Emergency';
        if (str_contains($n, 'compassion')) return 'Compassionate';
        if (str_contains($n, 'maternity')) return 'Maternity';
        if (str_contains($n, 'paternity')) return 'Paternity';
        if (str_contains($n, 'study')) return 'Study';
        return ucwords($leaveName);
    }

    /**
     * Predictive signals (rule-based): attendance risk, OT projection, leave shortage.
     * Uses already-built report arrays to avoid extra queries.
     */
    private function buildPredictiveReport(array $attendance, array $overtime, array $leave): array
    {
        /**
         * Attendance risk score (last 30 days):
         * Use a normalized incident rate so supervisor/department views don't inflate risk by headcount.
         * We also weight absences heavier than lates.
         */
        $late = (int) ($attendance['late_count'] ?? 0);
        $absent = (int) ($attendance['absent_count'] ?? 0);
        $totalDays = max(1, (int) ($attendance['total_days'] ?? 30));
        $employeeCount = max(1, (int) ($attendance['employee_count'] ?? 1));

        $expected = $totalDays * $employeeCount;
        $weightedIncidents = ($late * 1) + ($absent * 3);
        $ratePct = $expected > 0 ? ($weightedIncidents / $expected) * 100 : 0;

        // Score in 0..100 (rounded) for display and thresholding.
        $score = (int) round(min(max($ratePct, 0), 100));
        $risk = 'Low';
        if ($score >= 12) {
            $risk = 'High';
        } elseif ($score >= 5) {
            $risk = 'Medium';
        }

        // Projected OT Cost: average of last 3 available months (from reportOvertime cost_data)
        $costData = array_values(array_filter(($overtime['cost_data'] ?? []), fn ($v) => is_numeric($v)));
        $last3 = array_slice($costData, -3);
        $avg3 = 0;
        if (count($last3) > 0) {
            $avg3 = (int) round(array_sum($last3) / count($last3));
        }

        // Leave shortage: count leave types with remaining <= 0 (for the scope)
        $shortageTypes = 0;
        foreach (($leave['rows'] ?? []) as $row) {
            $remaining = (float) ($row['remaining'] ?? 0);
            $used = (float) ($row['used'] ?? 0);
            if ($used > 0 && $remaining <= 0) {
                $shortageTypes++;
            }
        }
        $leaveSignal = $shortageTypes > 0 ? ('Shortage (' . $shortageTypes . ')') : 'OK';

        return [
            'scope_label' => $attendance['scope_label'] ?? ($overtime['scope_label'] ?? ($leave['scope_label'] ?? 'You')),
            'attendance_risk_label' => $risk,
            'attendance_risk_score' => $score,
            'projected_ot_cost' => $avg3,
            'leave_signal' => $leaveSignal,
        ];
    }

    /** Default hourly rate (RM) for OT cost display when not from payroll. */
    private const OT_HOURLY_RATE = 90;

    /**
     * Build overtime report: employee = own approved claims; supervisor = department approved claims.
     */
    private function buildOvertimeReport($user, $employee, ?Carbon $periodStart = null, ?Carbon $periodEnd = null): array
    {
        $role = strtolower(trim((string) ($user->role ?? '')));
        $employeeIds = [$employee->employee_id];

        if ($role === 'supervisor') {
            $deptIds = Department::where('manager_id', $user->user_id)->pluck('department_id');
            $employeeIds = Employee::whereIn('department_id', $deptIds)->pluck('employee_id')->all();
            if (empty($employeeIds)) {
                $employeeIds = [$employee->employee_id];
            }
        }

        $end = $periodEnd ?: Carbon::today();
        $start = $periodStart ?: $end->copy()->subMonths(11)->startOfMonth();

        // 1) Approved OT Claims (OT Claims flow)
        $claims = OvertimeClaim::whereIn('employee_id', $employeeIds)
            ->where('status', OvertimeClaim::STATUS_ADMIN_APPROVED)
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get();

        // 2) Approved OvertimeRecord (Attendance · Overtime / "My Overtime History" flow)
        $records = OvertimeRecord::whereIn('employee_id', $employeeIds)
            ->where('ot_status', 'approved')
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get();

        $monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        $byMonth = [];

        foreach ($claims as $c) {
            $d = $c->date instanceof \Carbon\Carbon ? $c->date : Carbon::parse($c->date);
            $key = $d->format('Y-m');
            $hours = (float) ($c->approved_hours ?? $c->hours ?? 0);
            $rate = (float) ($c->rate_type ?? 1.5);
            $cost = round($hours * self::OT_HOURLY_RATE * $rate, 0);
            if (!isset($byMonth[$key])) {
                $byMonth[$key] = ['hours' => 0, 'cost' => 0, 'label' => $monthNames[(int) $d->format('n') - 1] . ' ' . $d->format('Y')];
            }
            $byMonth[$key]['hours'] += $hours;
            $byMonth[$key]['cost'] += $cost;
        }

        foreach ($records as $r) {
            $d = $r->date instanceof \Carbon\Carbon ? $r->date : Carbon::parse($r->date);
            $key = $d->format('Y-m');
            $hours = (float) ($r->hours ?? 0);
            $rate = (float) ($r->rate_type ?? 1.5);
            $cost = round($hours * self::OT_HOURLY_RATE * $rate, 0);
            if (!isset($byMonth[$key])) {
                $byMonth[$key] = ['hours' => 0, 'cost' => 0, 'label' => $monthNames[(int) $d->format('n') - 1] . ' ' . $d->format('Y')];
            }
            $byMonth[$key]['hours'] += $hours;
            $byMonth[$key]['cost'] += $cost;
        }

        ksort($byMonth);

        $labels = [];
        $hoursData = [];
        $costData = [];
        $tableRows = [];
        foreach ($byMonth as $key => $row) {
            $labels[] = $row['label'];
            $hoursData[] = round($row['hours'], 1);
            $costData[] = (int) $row['cost'];
            $tableRows[] = ['month' => $row['label'], 'hours' => round($row['hours'], 1), 'cost' => (int) $row['cost']];
        }

        $totalHours = $claims->sum(fn ($c) => (float) ($c->approved_hours ?? $c->hours ?? 0))
            + $records->sum(fn ($r) => (float) ($r->hours ?? 0));
        $totalCost = array_sum($costData);

        return [
            'scope_label'  => $role === 'supervisor' ? 'Your department' : 'You',
            'total_hours'  => round($totalHours, 1),
            'total_cost'   => $totalCost,
            'labels'       => $labels,
            'hours_data'   => $hoursData,
            'cost_data'    => $costData,
            'table_rows'   => $tableRows,
        ];
    }
}