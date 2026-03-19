<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\JobPost;
use App\Models\LeaveBalanceOverride;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\OvertimeClaim;
use App\Models\OvertimeRecord;
use App\Models\TrainingProgram;
use App\Models\EmployeeKpi;
use App\Models\Department;
use App\Models\Application;
use App\Models\TrainingEnrollment;
use App\Models\Announcement;
use App\Models\User; 
use App\Models\Penalty;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage; // Needed for file deletion
use App\Services\AttendanceEvaluationService;

class AdminController extends Controller
{
    /**
     * Admin Dashboard Index
     */
    public function index()
    {
        // --- 1. Top Metrics Cards ---
        $totalEmployees = Employee::where('employee_status', 'active')->count();
        
        $newEmployeesThisMonth = Employee::whereMonth('hire_date', Carbon::now()->month)
                                         ->whereYear('hire_date', Carbon::now()->year)
                                         ->count();

        $activeJobPosts = JobPost::where('job_status', 'open')->count();
        $ongoingTraining = TrainingProgram::whereIn('tr_status', ['active', 'ongoing'])->count();
        $pendingReviews = EmployeeKpi::whereIn('kpi_status', ['pending', 'in_progress'])->count();

        // --- 2. Module Overview Data ---
        $newApplicants = Application::where('created_at', '>=', Carbon::now()->startOfWeek())->count();
        $interviewsScheduled = Application::where('app_stage', 'Interview')->count();

        $completedReviews = EmployeeKpi::where('kpi_status', 'completed')->count();
        $avgKpiScore = EmployeeKpi::where('kpi_status', 'completed')->avg('actual_score') ?? 0;

        $completedTrainings = TrainingProgram::where('tr_status', 'completed')->count();
        $totalParticipants  = TrainingEnrollment::count();

        // --- 3. Charts Data ---
        $employeeGrowth = Employee::selectRaw('DATE_FORMAT(hire_date, "%M") as month, COUNT(*) as count')
            ->where('hire_date', '>=', Carbon::now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('hire_date')
            ->get();
        
        $growthLabels = $employeeGrowth->pluck('month');
        $growthData   = $employeeGrowth->pluck('count');

        $deptDist = Department::withCount('employees')->get();
        $deptLabels = $deptDist->pluck('department_name');
        $deptData   = $deptDist->pluck('employees_count');

        // --- 4. Sidebar Data ---
        $upcomingInterviews = Application::with(['applicant', 'job'])
            ->where('app_stage', 'Interview')
            ->orderBy('updated_at', 'desc')
            ->take(3)
            ->get();

        $announcements = Announcement::latest('publish_at')->take(5)->get();

        // --- 5. Central Reports & Analytics (Admin: all employees) ---
        $selectedMonth = request()->query('month'); // YYYY-MM
        $periodEnd = Carbon::today();
        $periodStart = $periodEnd->copy()->subDays(29);
        if (is_string($selectedMonth) && preg_match('/^\\d{4}-\\d{2}$/', $selectedMonth)) {
            $mStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
            $mEnd = $mStart->copy()->endOfMonth();
            $periodStart = $mStart;
            $periodEnd = $mEnd->greaterThan(Carbon::today()) ? Carbon::today() : $mEnd;
        }
        $reportAttendance = $this->buildAttendanceReportAll($periodStart, $periodEnd);
        $reportOvertime = $this->buildOvertimeReportAll($periodStart, $periodEnd);
        $reportLeave = $this->buildLeaveReportAll($periodStart, $periodEnd);
        $reportPredictive = $this->buildPredictiveReport($reportAttendance, $reportOvertime, $reportLeave);

        return view('admin.dashboard_dashboard', compact(
            'totalEmployees', 'newEmployeesThisMonth',
            'activeJobPosts', 'ongoingTraining', 'pendingReviews',
            'growthLabels', 'growthData',
            'deptLabels', 'deptData',
            'newApplicants', 'interviewsScheduled',
            'completedReviews', 'avgKpiScore',
            'completedTrainings', 'totalParticipants',
            'upcomingInterviews', 'announcements',
            'reportAttendance', 'reportOvertime', 'reportLeave', 'reportPredictive'
        ));
    }

    /**
     * Central Reports & Analytics data (AJAX) for the selected month.
     * Returns JSON for dashboard widgets/charts without full page reload.
     */
    public function centralReportsData(Request $request)
    {
        $selectedMonth = $request->query('month'); // YYYY-MM

        $periodEnd = Carbon::today();
        $periodStart = $periodEnd->copy()->subDays(29);

        if (is_string($selectedMonth) && preg_match('/^\\d{4}-\\d{2}$/', $selectedMonth)) {
            $mStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
            $mEnd = $mStart->copy()->endOfMonth();
            $periodStart = $mStart;
            $periodEnd = $mEnd->greaterThan(Carbon::today()) ? Carbon::today() : $mEnd;
        }

        $reportAttendance = $this->buildAttendanceReportAll($periodStart, $periodEnd);
        $reportOvertime = $this->buildOvertimeReportAll($periodStart, $periodEnd);
        $reportLeave = $this->buildLeaveReportAll($periodStart, $periodEnd);
        $reportPredictive = $this->buildPredictiveReport($reportAttendance, $reportOvertime, $reportLeave);

        return response()->json([
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'reportAttendance' => $reportAttendance,
            'reportOvertime' => $reportOvertime,
            'reportLeave' => $reportLeave,
            'reportPredictive' => $reportPredictive,
        ]);
    }

    /** Default hourly rate (RM) for OT cost display when not from payroll. */
    private const OT_HOURLY_RATE = 90;

    /** Admin scope: all employees (active only for dashboards). */
    private function allEmployeeIds(): array
    {
        return Employee::query()
            ->where('employee_status', 'active')
            ->pluck('employee_id')
            ->all();
    }

    /** Attendance report for last 30 days + last 7 days trend (all employees). */
    private function buildAttendanceReportAll(?Carbon $start = null, ?Carbon $end = null): array
    {
        $employeeIds = $this->allEmployeeIds();
        $end = $end ?: Carbon::today();
        $start = $start ?: $end->copy()->subDays(29);

        // Load employees for working-day evaluation rules.
        $employees = Employee::whereIn('employee_id', $employeeIds)->get();
        $employeeCount = max(1, $employees->count());

        // Attendance records keyed by employee+date (for in/out times).
        $records = Attendance::whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get()
            ->keyBy(function ($a) {
                return $a->employee_id . '_' . Carbon::parse($a->date)->format('Y-m-d');
            });

        // Approved leave overlay for the selected date range.
        $leaves = LeaveRequest::where('leave_status', 'approved')
            ->whereIn('employee_id', $employeeIds)
            ->where(function ($q) use ($start, $end) {
                $q->where('start_date', '<=', $end->format('Y-m-d'))
                    ->where('end_date', '>=', $start->format('Y-m-d'));
            })
            ->get();

        $leaveSet = [];
        foreach ($leaves as $l) {
            $s = Carbon::parse($l->start_date)->startOfDay();
            $e = Carbon::parse($l->end_date)->startOfDay();
            for ($d = $s->copy(); $d->lte($e) && $d->lte($end); $d->addDay()) {
                if ($d->gte($start)) {
                    $leaveSet[$l->employee_id][$d->format('Y-m-d')] = true;
                }
            }
        }

        $service = app(AttendanceEvaluationService::class);

        $totalDays = $start->diffInDays($end) + 1;
        $workingDaySlots = 0;
        $presentCount = 0;
        $lateCount = 0;
        $absentCount = 0;
        $leaveCount = 0;

        for ($d = $start->copy()->startOfDay(); $d->lte($end->copy()->startOfDay()); $d->addDay()) {
            $dateStr = $d->format('Y-m-d');
            foreach ($employees as $emp) {
                $eid = $emp->employee_id;

                // Leave supersedes late/absent.
                if (isset($leaveSet[$eid][$dateStr])) {
                    $leaveCount++;
                    continue;
                }

                $att = $records->get($eid . '_' . $dateStr);
                $eval = $service->evaluate($emp, $d->copy(), $att);

                if (!($eval['is_working_day'] ?? false)) {
                    continue;
                }

                $workingDaySlots++;

                $status = (string) ($eval['primary_status'] ?? '');
                if ($status === 'present') {
                    $presentCount++;
                } elseif ($status === 'late') {
                    $lateCount++;
                } elseif ($status === 'absent' || $status === 'pending') {
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
            $d = $end->copy()->startOfDay()->subDays($i);
            $dateStr = $d->format('Y-m-d');
            $trendLabels[] = $dayNames[(int) $d->format('w')];

            $p = 0;
            $l = 0;
            $a = 0;
            $lv = 0;

            foreach ($employees as $emp) {
                $eid = $emp->employee_id;

                if (isset($leaveSet[$eid][$dateStr])) {
                    $lv++;
                    continue;
                }

                $att = $records->get($eid . '_' . $dateStr);
                $eval = $service->evaluate($emp, $d->copy(), $att);

                if (!($eval['is_working_day'] ?? false)) {
                    continue;
                }

                $status = (string) ($eval['primary_status'] ?? '');
                if ($status === 'present') $p++;
                elseif ($status === 'late') $l++;
                elseif ($status === 'absent' || $status === 'pending') $a++;
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
            if ($late > 0) $highlights[] = ['day' => $label, 'text' => $late . ' late'];
            if ($absent > 0) $highlights[] = ['day' => $label, 'text' => $absent . ' absent'];
        }

        return [
            'scope_label'     => 'All employees',
            'attendance_rate' => $attendanceRate,
            'total_days'      => $totalDays,
            'period_start'    => $start->format('Y-m-d'),
            'period_end'      => $end->format('Y-m-d'),
            'present_count'   => $presentCount,
            'late_count'      => $lateCount,
            'absent_count'    => $absentCount,
            'leave_count'     => $leaveCount,
            'employee_count'  => $employeeCount,
            'trend_labels'    => $trendLabels,
            'trend_present'   => $trendPresent,
            'trend_late'      => $trendLate,
            'trend_absent'    => $trendAbsent,
            'trend_leave'     => $trendLeave,
            'highlights'      => $highlights,
        ];
    }

    /** Overtime report (last 12 months) for all employees: combines OvertimeClaim + OvertimeRecord. */
    private function buildOvertimeReportAll(?Carbon $periodStart = null, ?Carbon $periodEnd = null): array
    {
        $employeeIds = $this->allEmployeeIds();
        $end = $periodEnd ?: Carbon::today();
        $start = $periodStart ?: $end->copy()->subMonths(11)->startOfMonth();

        $claims = OvertimeClaim::whereIn('employee_id', $employeeIds)
            ->where('status', OvertimeClaim::STATUS_ADMIN_APPROVED)
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get();

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
        foreach ($byMonth as $row) {
            $labels[] = $row['label'];
            $hoursData[] = round($row['hours'], 1);
            $costData[] = (int) $row['cost'];
            $tableRows[] = ['month' => $row['label'], 'hours' => round($row['hours'], 1), 'cost' => (int) $row['cost']];
        }

        $totalHours = $claims->sum(fn ($c) => (float) ($c->approved_hours ?? $c->hours ?? 0))
            + $records->sum(fn ($r) => (float) ($r->hours ?? 0));

        return [
            'scope_label'  => 'All employees',
            'total_hours'  => round($totalHours, 1),
            'total_cost'   => array_sum($costData),
            'period_start' => $start->format('Y-m-d'),
            'period_end'   => $end->format('Y-m-d'),
            'labels'       => $labels,
            'hours_data'   => $hoursData,
            'cost_data'    => $costData,
            'table_rows'   => $tableRows,
        ];
    }

    /** Leave report, aggregated across all employees (as-of selected month end). */
    private function buildLeaveReportAll(?Carbon $periodStart = null, ?Carbon $periodEnd = null): array
    {
        $employeeIds = $this->allEmployeeIds();
        $employees = Employee::whereIn('employee_id', $employeeIds)->get();
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
            // Total entitlement across all employees (respect overrides per employee)
            $entitlementTotal = 0;
            foreach ($employees as $emp) {
                $emp->recomputeServiceBand();
                $entitlementTotal += $this->entitlementForEmployee($emp, $type, $year);
            }

            // Filter-based "used" days: count only the overlap between the leave period and the selected [periodStart, periodEnd].
            $used = 0.0;
            $leaves = LeaveRequest::whereIn('employee_id', $employeeIds)
                ->where('leave_type_id', $type->leave_type_id)
                ->where('leave_status', 'approved')
                ->whereDate('start_date', '<=', $periodEnd->format('Y-m-d'))
                ->whereDate('end_date', '>=', $periodStart->format('Y-m-d'))
                ->get();

            foreach ($leaves as $leave) {
                $ls = Carbon::parse($leave->start_date)->startOfDay();
                $le = Carbon::parse($leave->end_date)->startOfDay();

                $overlapStart = $ls->max($periodStart);
                $overlapEnd = $le->min($periodEnd);

                if ($overlapStart->lte($overlapEnd)) {
                    $used += (float) ($overlapStart->diffInDays($overlapEnd) + 1);
                }
            }

            $remaining = max($entitlementTotal - $used, 0);
            if ($entitlementTotal <= 0 && $used <= 0) {
                continue;
            }

            $typeName = (string) ($type->leave_name ?? 'Leave');
            $labels[] = $this->shortLeaveLabel($typeName);
            $usedData[] = $used;
            $remainingData[] = $remaining;
            $rows[] = ['type' => $typeName, 'used' => $used, 'remaining' => $remaining];
        }

        return [
            'scope_label' => 'All employees',
            'year' => $year,
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'labels' => $labels,
            'used' => $usedData,
            'remaining' => $remainingData,
            'rows' => $rows,
        ];
    }

    /** Entitlement for ONE employee for a leave type for year (includes overrides). */
    private function entitlementForEmployee(Employee $employee, LeaveType $type, int $year): int
    {
        $override = LeaveBalanceOverride::where('employee_id', $employee->employee_id)
            ->where('leave_type_id', $type->leave_type_id)
            ->where('plan_year', $year)
            ->first();
        if ($override) {
            return (int) $override->total_entitlement;
        }

        $name = strtolower((string) ($type->leave_name ?? ''));
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

    /** Predictive signals (same rules as employee dashboard). */
    private function buildPredictiveReport(array $attendance, array $overtime, array $leave): array
    {
        // Normalized attendance risk: weighted incident rate over last 30 days.
        $late = (int) ($attendance['late_count'] ?? 0);
        $absent = (int) ($attendance['absent_count'] ?? 0);
        $totalDays = max(1, (int) ($attendance['total_days'] ?? 30));
        $employeeCount = max(1, (int) ($attendance['employee_count'] ?? 1));

        $expected = $totalDays * $employeeCount;
        $weightedIncidents = ($late * 1) + ($absent * 3);
        $ratePct = $expected > 0 ? ($weightedIncidents / $expected) * 100 : 0;

        $score = (int) round(min(max($ratePct, 0), 100));
        $risk = 'Low';
        if ($score >= 12) $risk = 'High';
        elseif ($score >= 5) $risk = 'Medium';

        $costData = array_values(array_filter(($overtime['cost_data'] ?? []), fn ($v) => is_numeric($v)));
        $last3 = array_slice($costData, -3);
        $avg3 = count($last3) ? (int) round(array_sum($last3) / count($last3)) : 0;

        $shortageTypes = 0;
        foreach (($leave['rows'] ?? []) as $row) {
            $remaining = (float) ($row['remaining'] ?? 0);
            $used = (float) ($row['used'] ?? 0);
            if ($used > 0 && $remaining <= 0) $shortageTypes++;
        }
        $leaveSignal = $shortageTypes > 0 ? ('Shortage (' . $shortageTypes . ')') : 'OK';

        return [
            'scope_label' => $attendance['scope_label'] ?? 'All employees',
            'attendance_risk_label' => $risk,
            'attendance_risk_score' => $score,
            'projected_ot_cost' => $avg3,
            'leave_signal' => $leaveSignal,
        ];
    }

    /**
     * Show Profile Page
     */
    public function profile()
    {
        $user = Auth::user();
        
        // Calculate Stats for the Sidebar
        $stats = [
            'announcements' => Announcement::count(), 
            'employees'     => Employee::count(),
            'users'         => User::count(), 
        ];

        return view('admin.profile', compact('user', 'stats'));
    }

    /**
     * Update Profile (Including Avatar)
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        // 1. Validation
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email,' . $user->user_id . ',user_id',
            'phone'    => 'nullable|string|max:20',
            'avatar'   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB image
            'password' => 'nullable|min:6|confirmed',
        ]);

        // 2. Update Basic User Info
        $user->name = $request->name;
        $user->email = $request->email;

        // 3. Handle Avatar Upload
        if ($request->hasFile('avatar')) {
            // Optional: Delete old avatar if it exists and isn't a default one
            if ($user->avatar_path && Storage::exists('public/' . $user->avatar_path)) {
                Storage::delete('public/' . $user->avatar_path);
            }

            // Save new file to 'storage/app/public/avatars'
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar_path = $path;
        }

        // 4. Update Password (if provided)
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        // 5. Update Employee Details (Phone)
        // This ensures the phone number is saved to the 'employees' table
        $employee = Employee::where('user_id', $user->user_id)->first();
        
        if ($employee) {
            $employee->phone = $request->phone;
            $employee->save();
        }

        return redirect()->back()->with('success', 'Profile updated successfully!');
    }
}