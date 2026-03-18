<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\LeaveBalanceOverride;
use Illuminate\Http\Request;

class AdminLeaveBalanceController extends Controller
{
    /**
     * Return leave balance for one employee (JSON). Used by admin and supervisor leave approval pages.
     */
    public function employeeBalance(Employee $employee)
    {
        $types = LeaveType::orderBy('leave_name')
            ->whereRaw('LOWER(leave_name) != ?', ['unpaid leave'])
            ->get();
        $year = now()->year;
        $summary = [];
        foreach ($types as $t) {
            $entitlement = $this->entitlementFor($employee, $t->leave_name);
            $approved = LeaveRequest::where('employee_id', $employee->employee_id)
                ->where('leave_type_id', $t->leave_type_id)
                ->where('leave_status', 'approved')
                ->whereYear('start_date', $year)
                ->sum('total_days');
            $pending = LeaveRequest::where('employee_id', $employee->employee_id)
                ->where('leave_type_id', $t->leave_type_id)
                ->whereIn('leave_status', ['pending', 'supervisor_approved', 'pending_admin'])
                ->whereYear('start_date', $year)
                ->sum('total_days');
            $summary[] = [
                'type'      => $t->leave_name,
                'total'     => $entitlement,
                'used'      => (int) $approved,
                'pending'   => (int) $pending,
                'remaining' => max($entitlement - $approved - $pending, 0),
            ];
        }
        return response()->json([
            'employee' => [
                'id'   => $employee->employee_id,
                'name' => $employee->user->name ?? 'Unknown',
                'code' => $employee->employee_code ?? ('EMP-' . $employee->employee_id),
            ],
            'balances' => $summary,
            'year'     => $year,
        ]);
    }

    public function index()
    {
        $departments = \App\Models\Department::orderBy('department_name')->get();
        $types = LeaveType::orderBy('leave_name')->get();
        return view('admin.leave_balance', compact('departments', 'types'));
    }

    public function data(Request $request)
    {
        $request->validate([
            'department' => ['nullable', 'integer', 'exists:departments,department_id'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $employees = Employee::with(['department', 'user'])
            ->when($request->department, fn($q) => $q->where('department_id', $request->department))
            ->get();

        // Stub: using static balances until balance table exists (excluding unpaid leave)
        $stubUsed = [
            'annual leave' => 3,
            'sick leave'   => 1,
        ];

        $types = LeaveType::orderBy('leave_name')
            ->whereRaw('LOWER(leave_name) != ?', ['unpaid leave'])
            ->get();

        $year = now()->year;

        $data = $employees->map(function ($e) use ($types, $year) {
            $summary = [];
            foreach ($types as $t) {
                $entitlement = $this->entitlementFor($e, $t->leave_name);
                $approved = LeaveRequest::where('employee_id', $e->employee_id)
                    ->where('leave_type_id', $t->leave_type_id)
                    ->where('leave_status', 'approved')
                    ->whereYear('start_date', $year)
                    ->sum('total_days');
                $pending = LeaveRequest::where('employee_id', $e->employee_id)
                    ->where('leave_type_id', $t->leave_type_id)
                    ->where('leave_status', 'pending')
                    ->whereYear('start_date', $year)
                    ->sum('total_days');
                $summary[] = [
                    'type'      => $t->leave_name,
                    'total'     => $entitlement,
                    'used'      => $approved,
                    'pending'   => $pending,
                    'remaining' => max($entitlement - $approved - $pending, 0),
                    'statutory' => (bool)($t->is_statutory ?? true),
                ];
            }

            $annualRow = collect($summary)->firstWhere('type', 'Annual Leave');
            $sickRow   = collect($summary)->firstWhere('type', 'Sick Leave');

            return [
                'id'      => $e->employee_code ?? ('EMP-' . $e->employee_id),
                'name'    => $e->user->name ?? 'Unknown',
                'dept'    => $e->department->department_name ?? 'N/A',
                'annual'  => $annualRow['remaining'] ?? 0,
                'annual_total' => $annualRow['total'] ?? 0,
                'annual_used'  => $annualRow['used'] ?? 0,
                'annual_pending'=> $annualRow['pending'] ?? 0,
                'sick'    => $sickRow['remaining'] ?? 0,
                'sick_total' => $sickRow['total'] ?? 0,
                'sick_used'  => $sickRow['used'] ?? 0,
                'sick_pending'=> $sickRow['pending'] ?? 0,
                'service_label' => $e->serviceSnapshot()['label'] ?? '',
                'detail'  => $summary,
            ];
        });

        return response()->json(['data' => $data]);
    }

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
            return (strtolower($employee->gender ?? '') === 'female') ? 98 : 0;
        }

        if (str_contains($name, 'paternity')) {
            $isMale = strtolower($employee->gender ?? '') === 'male';
            $isMarried = strtolower($employee->marital_status ?? '') === 'married';
            return ($isMale && $isMarried) ? 7 : 0;
        }

        $type = LeaveType::whereRaw('LOWER(leave_name) = ?', [$name])->first();
        return $type ? (int) ($type->default_days_year ?? 0) : 0;
    }
}
