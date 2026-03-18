<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\OvertimeClaim;
use App\Models\OvertimeRecord;
use App\Models\PayrollPeriod;
use App\Services\OtClaimApproverResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeOvertimeController extends Controller
{
    /**
     * Show overtime submission form and history for the logged-in employee.
     */
    public function index()
    {
        $employee = Auth::user()->employee;
        abort_unless($employee, 403, 'Employee profile not found');

        $records = OvertimeRecord::where('employee_id', $employee->employee_id)
            ->orderBy('date', 'desc')
            ->orderBy('ot_id', 'desc')
            ->get();

        return view('employee.overtime', compact('employee', 'records'));
    }

    /**
     * Store a new overtime request for the logged-in employee.
     */
    public function store(Request $request)
    {
        $employee = Auth::user()->employee;
        abort_unless($employee, 403, 'Employee profile not found');

        $validated = $request->validate([
            'date'      => ['required', 'date', 'before_or_equal:today'],
            'hours'     => ['required', 'numeric', 'min:0.25', 'max:24'],
            'rate_type' => ['nullable', 'numeric', 'min:1', 'max:3'],
            'reason'    => ['nullable', 'string', 'max:255'],
        ]);

        // Route by department supervisor (manager_id)
        $department = $employee->department_id
            ? Department::find($employee->department_id)
            : null;

        if (!$department) {
            return back()->withErrors(['date' => 'Your profile has no department assigned. Please contact HR.'])->withInput();
        }

        $supervisorId = $department->manager_id;
        if (!$supervisorId) {
            return back()->withErrors(['date' => 'Your department has no supervisor assigned. Please contact HR.'])->withInput();
        }

        // Find payroll period covering the selected date
        $period = PayrollPeriod::whereDate('start_date', '<=', $validated['date'])
            ->whereDate('end_date', '>=', $validated['date'])
            ->orderBy('start_date', 'desc')
            ->first();

        if (!$period) {
            return back()->withErrors(['date' => 'No payroll period covers the selected date. Please contact HR.'])->withInput();
        }

        $this->syncUserDeptId($employee);
        $approverId = OtClaimApproverResolver::resolve($employee, null);

        $record = OvertimeRecord::create([
            'employee_id'          => $employee->employee_id,
            'department_id'        => $department->department_id,
            'supervisor_id'        => $supervisorId,
            'period_id'            => $period->period_id,
            'date'                 => $validated['date'],
            'hours'                => $validated['hours'],
            'rate_type'            => $validated['rate_type'] ?? 1.5,
            'reason'               => $validated['reason'] ?? null,
            'ot_status'            => 'pending',
            'final_status'         => OvertimeRecord::FINAL_PENDING_SUPERVISOR,
        ]);

        OvertimeClaim::create([
            'employee_id'   => $employee->employee_id,
            'user_id'       => $employee->user_id,
            'area_id'       => $employee->user->area_id ?? null,
            'period_id'     => $period->period_id,
            'date'          => $validated['date'],
            'hours'         => $validated['hours'],
            'rate_type'     => $validated['rate_type'] ?? 1.5,
            'reason'        => $validated['reason'] ?? null,
            'status'        => OvertimeClaim::STATUS_SUBMITTED_TO_SUPERVISOR,
            'submitted_at'  => now(),
            'supervisor_id' => $approverId,
            'location_type' => OvertimeClaim::LOCATION_INSIDE,
            'overtime_record_id' => $record->ot_id,
        ]);

        return back()->with('success', 'Overtime request submitted. Your supervisor will see it in OT Claims.');
    }

    /**
     * Allow employee to delete their own pending overtime record.
     */
    public function destroy(OvertimeRecord $overtime)
    {
        $employee = Auth::user()->employee;
        abort_unless($employee, 403, 'Employee profile not found');

        if ($overtime->employee_id !== $employee->employee_id) {
            abort(403, 'You can only delete your own request.');
        }
        if ($overtime->final_status !== OvertimeRecord::FINAL_PENDING_SUPERVISOR) {
            abort(403, 'You can only delete requests that are still pending supervisor.');
        }

        $overtime->delete();

        return back()->with('success', 'Overtime request removed.');
    }

    private function syncUserDeptId(\App\Models\Employee $employee): void
    {
        $user = $employee->user;
        if (!$user) {
            return;
        }
        $deptId = $employee->department_id ?? null;
        if ($user->dept_id != $deptId) {
            $user->update(['dept_id' => $deptId]);
        }
    }
}
