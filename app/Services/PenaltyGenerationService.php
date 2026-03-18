<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OvertimeRecord;
use App\Models\PayrollLineItem;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Penalty;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PayrollGenerator
{
    /**
     * Apply attendance deduction cap and compute EPF/Tax on chargeable salary only.
     * Attendance deductions (late + absent + unpaid leave + penalties) are capped at basic salary.
     * Chargeable salary = max(0, Gross - capped attendance deduction). EPF and Tax apply only to chargeable salary.
     * Net pay = max(0, chargeable salary - EPF - Tax).
     *
     * @return array{original_attendance_deduction: float, capped_attendance_deduction: float, chargeable_salary: float, epf_total: float, tax_total: float, net_pay: float}
     */
    public static function applyAttendanceCapAndStatutory(
        float $grossPay,
        float $basicSalary,
        float $lateDeduction,
        float $absentDeduction,
        float $unpaidLeaveDeduction,
        float $penaltyTotal,
        float $epfRate,
        float $taxRate
    ): array {
        $originalAttendanceDeduction = $lateDeduction + $absentDeduction + $unpaidLeaveDeduction + $penaltyTotal;
        $cappedAttendanceDeduction = min($originalAttendanceDeduction, $basicSalary);
        $chargeableSalary = max(0.0, round($grossPay - $cappedAttendanceDeduction, 2));
        $epf = $chargeableSalary > 0 ? round($chargeableSalary * $epfRate, 2) : 0.0;
        $tax = $chargeableSalary > 0 ? round($chargeableSalary * $taxRate, 2) : 0.0;
        $netPay = max(0.0, round($chargeableSalary - $epf - $tax, 2));
        return [
            'original_attendance_deduction' => round($originalAttendanceDeduction, 2),
            'capped_attendance_deduction'   => round($cappedAttendanceDeduction, 2),
            'chargeable_salary'             => $chargeableSalary,
            'epf_total'                     => $epf,
            'tax_total'                     => $tax,
            'net_pay'                       => $netPay,
        ];
    }

    /**
     * Generate payroll runs for a given period (YYYY-MM) and optional department filter.
     * Gross = Basic + Allowance + Adjustments. Attendance deductions (late+absent+unpaid+penalties) capped at basic salary.
     * Chargeable salary = max(0, Gross - capped). EPF/Tax on chargeable only. Net = max(0, chargeable - EPF - Tax).
     *
     * @return \Illuminate\Support\Collection
     */
    public function generate(string $periodMonth, ?int $departmentId = null)
    {
        $start = Carbon::createFromFormat('Y-m', $periodMonth)->startOfMonth()->toDateString();
        $end   = Carbon::createFromFormat('Y-m', $periodMonth)->endOfMonth()->toDateString();

        try {
            $attrs = [
                'start_date' => $start,
                'end_date'   => $end,
            ];
            if (Schema::hasColumn('payroll_periods', 'status')) {
                $attrs['status'] = 'OPEN';
            }

            $period = PayrollPeriod::firstOrCreate(
                ['period_month' => $periodMonth],
                $attrs
            );
        } catch (\Throwable $e) {
            throw new \RuntimeException($e->getMessage());
        }

        $employees = Employee::where('employee_status', 'active')
            ->whereDate('hire_date', '<=', $end)
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->get();

        $config = config('hrms.payroll', []);
        $workingDays = (int) ($config['working_days_per_month'] ?? 26);
        $dailyRateDivisor = (float) ($config['daily_rate_divisor'] ?? 26);
        $epfRate = (float) ($config['epf_employee_rate'] ?? 0.11);
        $taxRate = (float) ($config['tax_rate'] ?? 0.03);
        $lateMode = $config['late_deduction_mode'] ?? 'per_minute';
        $latePerRecord = (float) ($config['late_deduction_per_record'] ?? 10);
        $latePerMinute = (float) ($config['late_deduction_per_minute'] ?? 0.50);
        $fixedAllowanceDefault = (float) ($config['fixed_allowance_default'] ?? 0);

        $runs = collect();

        foreach ($employees as $employee) {
            $baseSalary = (float) $employee->base_salary;
            $dailyRate = $dailyRateDivisor > 0 ? round($baseSalary / $dailyRateDivisor, 2) : 0;

            // --- Attendance (selected month only) ---
            $attendance = Attendance::where('employee_id', $employee->employee_id)
                ->whereBetween('date', [$start, $end])
                ->get();
            $presentDays = $attendance->where('at_status', 'present')->count();
            $lateRecords = $attendance->where('at_status', 'late');
            $lateCount = $lateRecords->count();
            $lateMinutesTotal = (int) $lateRecords->sum('late_minutes');
            $workedDays = $presentDays + $lateCount;
            $leaveDaysInAttendance = $attendance->where('at_status', 'leave')->count();

            // Late deduction: company rule
            if ($lateMode === 'per_record') {
                $lateDeduction = round($lateCount * $latePerRecord, 2);
            } else {
                $lateDeduction = round($lateMinutesTotal * $latePerMinute, 2);
            }

            // --- Approved leave overlapping period (days in this month) ---
            $approvedLeaveDays = $this->approvedLeaveDaysInPeriod($employee->employee_id, $start, $end);
            $unpaidLeaveDays = $this->unpaidLeaveDaysInPeriod($employee->employee_id, $start, $end);

            // Absent for deduction = working days not covered by worked days or approved leave (so unworked days are deducted)
            $absentDays = max(0, $workingDays - $workedDays - $approvedLeaveDays);
            $absentDeduction = round($absentDays * $dailyRate, 2);
            $unpaidLeaveDeduction = round($unpaidLeaveDays * $dailyRate, 2);

            // --- Penalties (approved, assigned in period) ---
            $penaltyTotal = (float) Penalty::where('employee_id', $employee->employee_id)
                ->where('status', 'approved')
                ->whereDate('assigned_at', '>=', $start)
                ->whereDate('assigned_at', '<=', $end)
                ->sum('default_amount');
            $penaltyTotal = round($penaltyTotal, 2);

            // --- Allowance: fixed + approved OT + any other ---
            $fixedAllowance = $fixedAllowanceDefault;
            if (Schema::hasColumn('employees', 'fixed_allowance')) {
                $fixedAllowance = (float) ($employee->fixed_allowance ?? $fixedAllowanceDefault);
            }
            $approvedOtAmount = 0.0;
            if (Schema::hasTable('overtime_records')) {
                $otRecords = OvertimeRecord::where('employee_id', $employee->employee_id)
                    ->where('period_id', $period->period_id)
                    ->where('final_status', OvertimeRecord::FINAL_APPROVED_ADMIN)
                    ->get();
                $hourlyRate = $dailyRateDivisor > 0 && 8 > 0 ? $baseSalary / $dailyRateDivisor / 8 : 0;
                foreach ($otRecords as $ot) {
                    $mult = (float) ($ot->rate_type ?? 1.5);
                    $approvedOtAmount += (float) $ot->hours * $hourlyRate * $mult;
                }
                $approvedOtAmount = round($approvedOtAmount, 2);
            }
            $allowanceTotal = round($fixedAllowance + $approvedOtAmount, 2);

            // --- Adjustments (existing line items) ---
            $existingRun = PayrollRun::where('payroll_period_id', $period->period_id)
                ->where('employee_id', $employee->employee_id)
                ->first();
            $adjustmentTotal = 0.0;
            if ($existingRun) {
                $adjustmentTotal = (float) PayrollLineItem::where('payroll_run_id', $existingRun->id)
                    ->where('code', 'ADJUSTMENT')
                    ->get()
                    ->sum(function ($item) {
                        return $item->item_type === 'DEDUCTION'
                            ? -1 * (float) $item->amount
                            : (float) $item->amount;
                    });
            }

            // Gross = Basic Salary + Allowance + Adjustments
            $grossPay = round($baseSalary + $allowanceTotal + $adjustmentTotal, 2);
            $statutory = self::applyAttendanceCapAndStatutory(
                $grossPay,
                $baseSalary,
                $lateDeduction,
                $absentDeduction,
                $unpaidLeaveDeduction,
                $penaltyTotal,
                $epfRate,
                $taxRate
            );
            $employeeEpf = $statutory['epf_total'];
            $taxTotal = $statutory['tax_total'];
            $netPay = $statutory['net_pay'];

            $run = DB::transaction(function () use (
                $period,
                $employee,
                $baseSalary,
                $allowanceTotal,
                $adjustmentTotal,
                $lateDeduction,
                $absentDeduction,
                $unpaidLeaveDeduction,
                $penaltyTotal,
                $employeeEpf,
                $taxTotal,
                $grossPay,
                $netPay
            ) {
                $run = PayrollRun::updateOrCreate(
                    [
                        'payroll_period_id' => $period->period_id,
                        'employee_id'       => $employee->employee_id,
                    ],
                    [
                        'basic_salary'             => round($baseSalary, 2),
                        'allowance_total'          => round($allowanceTotal, 2),
                        'ot_total'                 => round($allowanceTotal, 2),
                        'unpaid_leave_deduction'   => round($unpaidLeaveDeduction, 2),
                        'absent_deduction'         => round($absentDeduction, 2),
                        'late_deduction'           => round($lateDeduction, 2),
                        'penalty_total'            => round($penaltyTotal, 2),
                        'adjustment_total'         => round($adjustmentTotal, 2),
                        'epf_total'                => round($employeeEpf, 2),
                        'tax_total'                => round($taxTotal, 2),
                        'gross_pay'                => round($grossPay, 2),
                        'net_pay'                  => round($netPay, 2),
                        'status'                   => 'DRAFT',
                    ]
                );

                PayrollLineItem::where('payroll_run_id', $run->id)->delete();
                $items = [
                    ['EARNING', 'BASIC',      1, $baseSalary, $baseSalary, 'Basic salary'],
                    ['EARNING', 'ALLOWANCE',  1, $allowanceTotal, $allowanceTotal, 'Allowance (fixed + OT + other)'],
                    ['DEDUCTION', 'LATE',      null, null, $lateDeduction, 'Late deduction'],
                    ['DEDUCTION', 'ABSENT',    null, null, $absentDeduction, 'Absent deduction'],
                    ['DEDUCTION', 'UNPAID_LEAVE', null, null, $unpaidLeaveDeduction, 'Unpaid leave deduction'],
                    ['DEDUCTION', 'PENALTY',  null, null, $penaltyTotal, 'Penalties'],
                    ['DEDUCTION', 'EPF',      null, null, $employeeEpf, 'Employee EPF (11%)'],
                    ['DEDUCTION', 'TAX',      null, null, $taxTotal, 'Tax (3%)'],
                ];
                if ($adjustmentTotal != 0.0) {
                    $items[] = [
                        $adjustmentTotal >= 0 ? 'EARNING' : 'DEDUCTION',
                        'ADJUSTMENT',
                        null,
                        null,
                        abs($adjustmentTotal),
                        'Adjustments',
                    ];
                }
                foreach ($items as [$type, $code, $qty, $rate, $amt, $desc]) {
                    PayrollLineItem::create([
                        'payroll_run_id' => $run->id,
                        'item_type'      => $type,
                        'code'           => $code,
                        'quantity'       => $qty ?? 0,
                        'rate'           => $rate ?? 0,
                        'amount'         => round($amt, 2),
                        'description'    => $desc,
                        'created_by'     => auth()->id() ?? 1,
                    ]);
                }
                return $run;
            });

            $runs->push($run);
        }

        $period->update(['status' => 'DRAFT']);
        return $runs;
    }

    /**
     * Approved leave days that fall within the period (overlap).
     */
    private function approvedLeaveDaysInPeriod(int $employeeId, string $start, string $end): float
    {
        $leaves = LeaveRequest::with('leaveType')
            ->where('employee_id', $employeeId)
            ->whereIn('leave_status', ['approved', 'supervisor_approved'])
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->get();
        $days = 0;
        $startCarbon = Carbon::parse($start);
        $endCarbon = Carbon::parse($end);
        foreach ($leaves as $leave) {
            $overlapStart = Carbon::parse($leave->start_date)->max($startCarbon);
            $overlapEnd = Carbon::parse($leave->end_date)->min($endCarbon);
            if ($overlapStart->lte($overlapEnd)) {
                $days += $overlapStart->diffInDays($overlapEnd) + 1;
            }
        }
        return (float) $days;
    }

    /**
     * Unpaid leave days in period (approved, unpaid type only).
     */
    private function unpaidLeaveDaysInPeriod(int $employeeId, string $start, string $end): float
    {
        $leaves = LeaveRequest::with('leaveType')
            ->where('employee_id', $employeeId)
            ->whereIn('leave_status', ['approved', 'supervisor_approved'])
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->get();
        $days = 0;
        $startCarbon = Carbon::parse($start);
        $endCarbon = Carbon::parse($end);
        foreach ($leaves as $leave) {
            $name = $leave->leaveType->leave_name ?? '';
            if (stripos($name, 'unpaid') === false) {
                continue;
            }
            $overlapStart = Carbon::parse($leave->start_date)->max($startCarbon);
            $overlapEnd = Carbon::parse($leave->end_date)->min($endCarbon);
            if ($overlapStart->lte($overlapEnd)) {
                $days += $overlapStart->diffInDays($overlapEnd) + 1;
            }
        }
        return (float) $days;
    }

    /**
     * Compute payroll for one employee for a period (for display/fallback without persisting).
     * Returns array with basic_salary, allowance_total, late_deduction, absent_deduction, unpaid_leave_deduction, penalty_total, adjustment_total, gross_pay, epf_total, tax_total, net_pay, absent_days, late_minutes, unpaid_leave_days.
     */
    public function computeForEmployee(Employee $employee, string $periodMonth, ?float $adjustmentTotal = 0, ?int $periodId = null): array
    {
        $start = Carbon::createFromFormat('Y-m', $periodMonth)->startOfMonth()->toDateString();
        $end   = Carbon::createFromFormat('Y-m', $periodMonth)->endOfMonth()->toDateString();
        $config = config('hrms.payroll', []);
        $workingDays = (int) ($config['working_days_per_month'] ?? 26);
        $dailyRateDivisor = (float) ($config['daily_rate_divisor'] ?? 26);
        $epfRate = (float) ($config['epf_employee_rate'] ?? 0.11);
        $taxRate = (float) ($config['tax_rate'] ?? 0.03);
        $lateMode = $config['late_deduction_mode'] ?? 'per_minute';
        $latePerRecord = (float) ($config['late_deduction_per_record'] ?? 10);
        $latePerMinute = (float) ($config['late_deduction_per_minute'] ?? 0.50);
        $fixedAllowanceDefault = (float) ($config['fixed_allowance_default'] ?? 0);

        $baseSalary = (float) $employee->base_salary;
        $dailyRate = $dailyRateDivisor > 0 ? round($baseSalary / $dailyRateDivisor, 2) : 0;

        $attendance = Attendance::where('employee_id', $employee->employee_id)
            ->whereBetween('date', [$start, $end])
            ->get();
        $presentDays = $attendance->where('at_status', 'present')->count();
        $lateRecords = $attendance->where('at_status', 'late');
        $lateCount = $lateRecords->count();
        $lateMinutesTotal = (int) $lateRecords->sum('late_minutes');
        $workedDays = $presentDays + $lateCount;

        if ($lateMode === 'per_record') {
            $lateDeduction = round($lateCount * $latePerRecord, 2);
        } else {
            $lateDeduction = round($lateMinutesTotal * $latePerMinute, 2);
        }

        $approvedLeaveDays = $this->approvedLeaveDaysInPeriod($employee->employee_id, $start, $end);
        $unpaidLeaveDays = $this->unpaidLeaveDaysInPeriod($employee->employee_id, $start, $end);
        // Absent for deduction = working days not covered by worked or approved leave
        $absentDays = max(0, $workingDays - $workedDays - $approvedLeaveDays);
        $absentDeduction = round($absentDays * $dailyRate, 2);
        $unpaidLeaveDeduction = round($unpaidLeaveDays * $dailyRate, 2);

        $penaltyTotal = (float) Penalty::where('employee_id', $employee->employee_id)
            ->where('status', 'approved')
            ->whereDate('assigned_at', '>=', $start)
            ->whereDate('assigned_at', '<=', $end)
            ->sum('default_amount');
        $penaltyTotal = round($penaltyTotal, 2);

        $fixedAllowance = $fixedAllowanceDefault;
        if (Schema::hasColumn('employees', 'fixed_allowance')) {
            $fixedAllowance = (float) ($employee->fixed_allowance ?? $fixedAllowanceDefault);
        }
        $approvedOtAmount = 0.0;
        if (Schema::hasTable('overtime_records')) {
            $otQuery = OvertimeRecord::where('employee_id', $employee->employee_id)
                ->where('final_status', OvertimeRecord::FINAL_APPROVED_ADMIN);
            if ($periodId) {
                $otQuery->where('period_id', $periodId);
            } else {
                $otQuery->whereBetween('date', [$start, $end]);
            }
            $otRecords = $otQuery->get();
            $hourlyRate = $dailyRateDivisor > 0 ? $baseSalary / $dailyRateDivisor / 8 : 0;
            foreach ($otRecords as $ot) {
                $mult = (float) ($ot->rate_type ?? 1.5);
                $approvedOtAmount += (float) $ot->hours * $hourlyRate * $mult;
            }
            $approvedOtAmount = round($approvedOtAmount, 2);
        }
        $allowanceTotal = round($fixedAllowance + $approvedOtAmount, 2);

        $grossPay = round($baseSalary + $allowanceTotal + $adjustmentTotal, 2);
        $statutory = self::applyAttendanceCapAndStatutory(
            $grossPay,
            $baseSalary,
            $lateDeduction,
            $absentDeduction,
            $unpaidLeaveDeduction,
            $penaltyTotal,
            $epfRate,
            $taxRate
        );

        return [
            'basic_salary'                   => round($baseSalary, 2),
            'allowance_total'                => $allowanceTotal,
            'late_deduction'                 => $lateDeduction,
            'absent_deduction'               => $absentDeduction,
            'unpaid_leave_deduction'         => $unpaidLeaveDeduction,
            'penalty_total'                  => $penaltyTotal,
            'adjustment_total'               => round($adjustmentTotal, 2),
            'gross_pay'                      => $grossPay,
            'original_attendance_deduction'  => $statutory['original_attendance_deduction'],
            'capped_attendance_deduction'    => $statutory['capped_attendance_deduction'],
            'chargeable_salary'               => $statutory['chargeable_salary'],
            'epf_total'                      => $statutory['epf_total'],
            'tax_total'                      => $statutory['tax_total'],
            'net_pay'                        => $statutory['net_pay'],
            'absent_days'                    => (float) $absentDays,
            'late_minutes'                   => (float) $lateMinutesTotal,
            'unpaid_leave_days'              => $unpaidLeaveDays,
        ];
    }
}
