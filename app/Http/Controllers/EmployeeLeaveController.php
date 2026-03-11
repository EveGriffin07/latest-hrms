<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveBalanceOverride;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class EmployeeLeaveController extends Controller
{
    /**
     * Show leave application, balance, and history for the logged-in employee.
     */
    public function index()
    {
        $employee = Auth::user()->employee;
        abort_unless($employee, 403, 'Employee profile not found');

        // Recompute service band when the leave module is opened
        $employee->recomputeServiceBand();

        $this->ensureLeaveTypesExist();
        $leaveTypes = LeaveType::orderBy('leave_name')
            ->whereRaw('LOWER(leave_name) != ?', ['unpaid leave'])
            ->get();

        $requests = LeaveRequest::with('leaveType')
            ->where('employee_id', $employee->employee_id)
            ->orderBy('start_date', 'desc')
            ->orderBy('leave_request_id', 'desc')
            ->get();

        $summary = [
            'total'    => $requests->count(),
            'pending'  => $requests->where('leave_status', 'pending')->count(),
            'approved' => $requests->where('leave_status', 'approved')->count(),
            'rejected' => $requests->where('leave_status', 'rejected')->count(),
        ];

        $year = now()->year;
        $balances = $leaveTypes->map(function ($type) use ($employee, $year) {
            $entitlement = $this->entitlementFor($employee, $type->leave_name);

            $approved = LeaveRequest::where('employee_id', $employee->employee_id)
                ->where('leave_type_id', $type->leave_type_id)
                ->where('leave_status', 'approved')
                ->whereYear('start_date', $year)
                ->sum('total_days');

            $pending = LeaveRequest::where('employee_id', $employee->employee_id)
                ->where('leave_type_id', $type->leave_type_id)
                ->where('leave_status', 'pending')
                ->whereYear('start_date', $year)
                ->sum('total_days');

            return [
                'name'      => $type->leave_name,
                'total'     => $entitlement,
                'used'      => $approved,
                'pending'   => $pending,
                'remaining' => max($entitlement - $approved - $pending, 0),
            ];
        });

        return view('employee.leave', [
            'leaveTypes' => $leaveTypes,
            'requests'   => $requests,
            'summary'    => $summary,
            'balances'   => $balances,
            'employee'   => $employee,
        ]);
    }

    /**
     * Store a new leave request for the logged-in employee.
     */
    public function store(Request $request)
    {
        $employee = Auth::user()->employee;
        abort_unless($employee, 403, 'Employee profile not found');

        $this->ensureLeaveTypesExist();
        $validated = $request->validate([
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,leave_type_id'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
            'reason'        => ['nullable', 'string', 'max:500'],
        ]);

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end   = Carbon::parse($validated['end_date'])->startOfDay();
        $totalDays = $start->diffInDays($end) + 1; // inclusive

        // Re-check leave type
        $type = LeaveType::find($validated['leave_type_id']);
        if (!$type) {
            return back()->withErrors(['leave_type_id' => 'Invalid leave type selected.'])->withInput();
        }

        // service band entitlement
        $entitlement = $this->entitlementFor($employee, $type->leave_name);
        if ($entitlement <= 0) {
            return back()->withErrors(['leave_type_id' => 'You are not eligible for this leave type.'])->withInput();
        }

        // Prevent overlapping pending/approved leaves for this employee
        $overlap = LeaveRequest::where('employee_id', $employee->employee_id)
            ->whereIn('leave_status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->exists();

        if ($overlap) {
            return back()->withErrors(['start_date' => 'You already have a pending/approved leave in this date range.'])->withInput();
        }

        // Balance check: approved + pending must not exceed entitlement
        $year = now()->year;
        $approvedThisYear = LeaveRequest::where('employee_id', $employee->employee_id)
            ->where('leave_type_id', $type->leave_type_id)
            ->where('leave_status', 'approved')
            ->whereYear('start_date', $year)
            ->sum('total_days');

        $pendingThisYear = LeaveRequest::where('employee_id', $employee->employee_id)
            ->where('leave_type_id', $type->leave_type_id)
            ->where('leave_status', 'pending')
            ->whereYear('start_date', $year)
            ->sum('total_days');

        $remaining = max($entitlement - $approvedThisYear - $pendingThisYear, 0);
        if ($totalDays > $remaining) {
            return back()->withErrors(['end_date' => 'Insufficient balance for this leave type. Remaining (after pending): ' . $remaining . ' day(s).'])->withInput();
        }

        $req = LeaveRequest::create([
            'employee_id'   => $employee->employee_id,
            'leave_type_id' => $validated['leave_type_id'],
            'start_date'    => $start,
            'end_date'      => $end,
            'total_days'    => $totalDays,
            'reason'        => $validated['reason'] ?? null,
            'leave_status'  => 'pending',
        ]);

        AuditLogService::log(
            AuditLogService::CATEGORY_LEAVE,
            'leave_request_created',
            AuditLogService::STATUS_SUCCESS,
            'Leave request created (' . ($type->leave_name ?? '') . ', ' . $start->format('Y-m-d') . ' to ' . $end->format('Y-m-d') . ')',
            ['leave_request_id' => $req->leave_request_id, 'leave_type' => $type->leave_name, 'total_days' => $totalDays],
            $employee->employee_id,
            AuditLogService::SEVERITY_INFO,
            'Leave',
            $req->leave_request_id
        );

        return redirect()
            ->route('employee.leave.apply')
            ->with('success', 'Leave request submitted and pending approval.');
    }

    /**
     * Cancel a pending leave request belonging to the employee.
     */
    public function cancel(LeaveRequest $leave)
    {
        $employee = Auth::user()->employee;
        abort_unless($employee, 403, 'Employee profile not found');
        abort_unless($leave->employee_id === $employee->employee_id, 403, 'Not your leave request');
        if ($leave->leave_status !== 'pending') {
            return back()->withErrors(['leave' => 'Only pending requests can be cancelled.']);
        }

        $leave->update([
            'leave_status' => 'cancelled',
            'decision_at'  => now(),
            'approved_by'  => null,
            'reject_reason'=> null,
        ]);

        $typeName = $leave->leaveType->leave_name ?? 'Leave';
        AuditLogService::log(
            AuditLogService::CATEGORY_LEAVE,
            'leave_request_cancelled',
            AuditLogService::STATUS_SUCCESS,
            'Leave request cancelled (' . $typeName . ')',
            ['leave_request_id' => $leave->leave_request_id],
            $employee->employee_id,
            AuditLogService::SEVERITY_INFO,
            'Leave',
            $leave->leave_request_id
        );

        return back()->with('success', 'Leave request cancelled.');
    }

    /**
     * Ensure default leave types exist so dropdown is populated even on fresh DBs without seeding.
     */
    private function ensureLeaveTypesExist(): void
    {
        // Remove deprecated unpaid leave type
        LeaveType::whereRaw('LOWER(leave_name) = ?', ['unpaid leave'])->delete();

        if (LeaveType::count() > 0) {
            return;
        }

        $defaults = [
            ['leave_name' => 'Annual Leave',        'le_description' => 'Paid annual leave',                 'default_days_year' => 14],
            ['leave_name' => 'Sick Leave',          'le_description' => 'Paid sick leave',                   'default_days_year' => 8],
            ['leave_name' => 'Emergency Leave',     'le_description' => 'Short-notice urgent matters',       'default_days_year' => 3],
            ['leave_name' => 'Compassionate Leave', 'le_description' => 'Bereavement / compassionate leave', 'default_days_year' => 5],
            ['leave_name' => 'Maternity Leave',     'le_description' => 'Maternity entitlement',             'default_days_year' => 60],
            ['leave_name' => 'Paternity Leave',     'le_description' => 'Paternity entitlement',             'default_days_year' => 7],
            ['leave_name' => 'Study Leave',         'le_description' => 'Training / exam leave',             'default_days_year' => 5],
        ];

        foreach ($defaults as $row) {
            LeaveType::updateOrCreate(
                ['leave_name' => $row['leave_name']],
                ['le_description' => $row['le_description'], 'default_days_year' => $row['default_days_year']]
            );
        }
    }

    /**
     * Determine yearly entitlement for a given leave type based on service band and basic eligibility.
     */
    private function entitlementFor($employee, string $leaveName): int
    {
        $band = strtoupper($employee->service_band ?? 'BAND_A');
        $name = strtolower($leaveName);
        $year = now()->year;

        // Override check
        $override = LeaveBalanceOverride::where('employee_id', $employee->employee_id)
            ->whereHas('leaveType', fn($q) => $q->whereRaw('LOWER(leave_name) = ?', [$name]))
            ->where('plan_year', $year)
            ->first();
        if ($override) {
            return (int) $override->total_entitlement;
        }

        // Annual leave
        if (str_contains($name, 'annual')) {
            return match ($band) {
                'BAND_A' => 8,
                'BAND_B' => 12,
                default  => 16, // BAND_C and above
            };
        }

        // Sick leave (non-hospitalisation)
        if (str_contains($name, 'sick')) {
            return match ($band) {
                'BAND_A' => 14,
                'BAND_B' => 18,
                default  => 22,
            };
        }

        // Hospitalisation cap
        if (str_contains($name, 'hospital')) {
            return 60;
        }

        // Maternity
        if (str_contains($name, 'maternity')) {
            return (strtolower($employee->gender ?? '') === 'female') ? 98 : 0;
        }

        // Paternity
        if (str_contains($name, 'paternity')) {
            $isMale = strtolower($employee->gender ?? '') === 'male';
            $isMarried = strtolower($employee->marital_status ?? '') === 'married';
            return ($isMale && $isMarried) ? 7 : 0;
        }

        // Default: use leave type default if provided
        return 0;
    }
}
