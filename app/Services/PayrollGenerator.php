<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
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
    * Generate payroll runs for a given period (YYYY-MM) and optional department filter.
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

        // Fetch employees
        $employees = Employee::where('employee_status', 'active')
            ->whereDate('hire_date', '<=', $end)
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->get();

        $runs = collect();

        foreach ($employees as $employee) {
            $basicSalary = (float) $employee->base_salary;
            $allowance   = 0.0; // placeholder until allowance structure exists

            // Attendance metrics
            $attendance = Attendance::where('employee_id', $employee->employee_id)
                ->whereBetween('date', [$start, $end]);

            $absentDays  = (float) (clone $attendance)->where('at_status', 'absent')->count();
            $lateMinutes = (float) (clone $attendance)->where('at_status', 'late')->sum('late_minutes');

            // Leave (treated as unpaid for now)
            $unpaidLeaveDays = (float) LeaveRequest::where('employee_id', $employee->employee_id)
                ->where('leave_status', 'approved')
                ->whereDate('start_date', '<=', $end)
                ->whereDate('end_date', '>=', $start)
                ->sum('total_days');

            // Overtime: not included in payroll; claimed and paid separately via Claim Overtime.
            $otTotal = 0.0;

            // Penalties
            $penaltyTotal = (float) Penalty::where('employee_id', $employee->employee_id)
                ->where('status', 'approved')
                ->whereBetween('assigned_at', [$start, $end])
                ->sum('default_amount');

            // Rates
            $dailyRate  = $basicSalary / 26;
            $hourlyRate = $dailyRate / 8;
            $minuteRate = $hourlyRate / 60;

            $unpaidLeaveDeduction = $unpaidLeaveDays * $dailyRate;
            $absentDeduction      = $absentDays * $dailyRate;
            $lateDeduction        = $lateMinutes * $minuteRate;

            // Existing adjustment items (if regenerating)
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

            // Statutory (placeholder formulas)
            $epfTotal = round($basicSalary * 0.11, 2);
            $taxTotal = 0.0;

            $grossPay = $basicSalary + $allowance;
            $netPay   = $grossPay
                        - ($unpaidLeaveDeduction + $absentDeduction + $lateDeduction + $penaltyTotal + $epfTotal + $taxTotal)
                        + $adjustmentTotal;

            $run = DB::transaction(function () use (
                $period,
                $employee,
                $basicSalary,
                $allowance,
                $unpaidLeaveDeduction,
                $absentDeduction,
                $lateDeduction,
                $penaltyTotal,
                $adjustmentTotal,
                $epfTotal,
                $taxTotal,
                $grossPay,
                $netPay,
                $dailyRate,
                $minuteRate,
                $lateMinutes
            ) {
                $run = PayrollRun::updateOrCreate(
                    [
                        'payroll_period_id' => $period->period_id,
                        'employee_id'       => $employee->employee_id,
                    ],
                    [
                        'basic_salary'           => round($basicSalary, 2),
                        'allowance_total'        => round($allowance, 2),
                        'ot_total'               => 0,
                        'unpaid_leave_deduction' => round($unpaidLeaveDeduction, 2),
                        'absent_deduction'       => round($absentDeduction, 2),
                        'late_deduction'         => round($lateDeduction, 2),
                        'penalty_total'          => round($penaltyTotal, 2),
                        'adjustment_total'       => round($adjustmentTotal, 2),
                        'epf_total'              => round($epfTotal, 2),
                        'tax_total'              => round($taxTotal, 2),
                        'gross_pay'              => round($grossPay, 2),
                        'net_pay'                => round($netPay, 2),
                        'status'                 => 'DRAFT',
                    ]
                );

                // Reset line items then rebuild
                PayrollLineItem::where('payroll_run_id', $run->id)->delete();

                $items = [
                    ['EARNING',    'BASIC',          1,             $basicSalary, $basicSalary, 'Base salary'],
                    ['EARNING',    'ALLOWANCE',      1,             $allowance,   $allowance,   'Allowance total'],
                    ['DEDUCTION',  'UNPAID_LEAVE',   $unpaidLeaveDeduction ? $unpaidLeaveDeduction / ($dailyRate ?: 1) : 0, $dailyRate, $unpaidLeaveDeduction, 'Unpaid leave'],
                    ['DEDUCTION',  'ABSENT',         $absentDeduction ? $absentDeduction / ($dailyRate ?: 1) : 0, $dailyRate, $absentDeduction, 'Absences'],
                    ['DEDUCTION',  'LATE',           $lateMinutes,  $minuteRate,  $lateDeduction, 'Late minutes'],
                    ['DEDUCTION',  'PENALTY',        null,          null,         $penaltyTotal, 'Approved penalties'],
                    ['DEDUCTION',  'EPF',            null,          null,         $epfTotal,     'EPF (11%)'],
                    ['DEDUCTION',  'TAX',            null,          null,         $taxTotal,     'Tax'],
                ];

                if ($adjustmentTotal !== 0.0) {
                    $items[] = [
                        $adjustmentTotal >= 0 ? 'EARNING' : 'DEDUCTION',
                        'ADJUSTMENT',
                        null,
                        null,
                        abs($adjustmentTotal),
                        'Manual adjustments carried forward',
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

        // Set period to DRAFT after generation
        $period->update(['status' => 'DRAFT']);

        return $runs;
    }
}
