<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\PayrollPeriod;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OvertimeRecord;
use App\Models\Penalty;
use App\Models\Payslip;
use App\Models\PayrollAdjustment;

class PayrollController extends Controller
{
    public function periodsPage()
    {
        $months = [];
        $now = Carbon::now()->startOfMonth();
        for ($i = -6; $i <= 3; $i++) {
            $m = $now->copy()->addMonths($i);
            $key = $m->format('Y-m');
            $period = PayrollPeriod::where('period_month', $key)->first();
            $status = $period ? $period->payroll_status : 'open';
            if (in_array($status, ['processing','completed'])) {
                $status = 'draft';
            }
            if ($status === 'locked') {
                $status = 'paid'; // treat locked as paid for display
            }
            $months[] = [
                'key' => $key,
                'label' => $m->format('F, Y'),
                'start' => $m->toDateString(),
                'end' => $m->copy()->endOfMonth()->toDateString(),
                'status' => $status,
            ];
        }
        return view('admin.payroll_periods', compact('months'));
    }

    public function showPeriod(string $periodMonth)
    {
        $start = Carbon::parse($periodMonth . '-01')->startOfMonth();
        $end   = $start->copy()->endOfMonth();
        $period = PayrollPeriod::where('period_month', $start->format('Y-m'))->first();
        $status = $period ? $period->payroll_status : 'open';
        if (in_array($status, ['processing','completed'])) {
            $status = 'draft';
        }
        if ($status === 'locked') {
            $status = 'paid';
        }
        $periodId = $period->period_id ?? null;
        $rows = [];
        if ($period) {
            $payslips = Payslip::where('period_id', $period->period_id)->get();
            foreach ($payslips as $p) {
                $emp = $p->employee ?? $p->employee()->first();
                $dept = $emp?->department?->department_name ?? 'N/A';
                $code = $emp?->employee_code ?? 'EMP-'.$p->employee_id;
                $adj = PayrollAdjustment::where('period_id',$period->period_id)->where('employee_id',$p->employee_id)->first();
                $rows[] = [
                    'employee_id' => $p->employee_id,
                    'name' => $emp?->user?->name ?? 'Employee '.$p->employee_id,
                    'dept' => $dept,
                    'code' => $code,
                    'base' => (float)$p->basic_salary,
                    'ot'   => (float)$p->total_overtime_amount,
                    'penalty' => (float)$p->total_deductions,
                    'unpaid'  => 0.0,
                    'manual_penalty' => (float)($adj->manual_penalty ?? 0),
                    'salary_increase' => (float)($adj->salary_increase ?? 0),
                    'ot_rate' => $adj->ot_rate ?? null,
                    'net' => (float)$p->net_salary,
                ];
            }
        }

        $periodLabel = $start->format('F, Y');
        return view('admin.payroll_detail', [
            'periodLabel' => $periodLabel,
            'periodKey' => $start->format('Y-m'),
            'periodId' => $periodId,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'status' => $status,
            'rows' => $rows,
        ]);
    }

    public function runPayroll(Request $request, string $periodMonth)
    {
        $start = Carbon::parse($periodMonth . '-01')->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $period = PayrollPeriod::firstOrCreate(
            ['period_month' => $start->format('Y-m')],
            [
                'start_date' => $start->toDateString(),
                'end_date'   => $end->toDateString(),
                'payroll_status' => 'draft'
            ]
        );

        if (in_array($period->payroll_status, ['approved','paid','locked'])) {
            return back()->withErrors('Payroll is finalized; cannot regenerate.');
        }

        $employees = Employee::where('employee_status', 'active')->get();

        DB::transaction(function () use ($employees, $period, $start, $end) {
            foreach ($employees as $emp) {
                $basic = $emp->base_salary ?? 0;

                $otAmount = OvertimeRecord::where('employee_id', $emp->employee_id)
                    ->where('ot_status', 'approved')
                    ->whereBetween('date', [$start, $end])
                    ->selectRaw('SUM(hours * rate_type) as total')
                    ->value('total') ?? 0;

                $penalty = Penalty::where('employee_id', $emp->employee_id)
                    ->whereNull('removed_at')
                    ->whereBetween('assigned_at', [$start, $end])
                    ->sum('default_amount');

                $unpaidLeaveDays = LeaveRequest::where('employee_id', $emp->employee_id)
                    ->where('leave_status','approved')
                    ->whereBetween('start_date', [$start, $end])
                    ->whereHas('leaveType', function($q){
                        $q->where('default_days_year',0)->orWhere('leave_name','Unpaid Leave');
                    })
                    ->sum('total_days');
                $unpaidDeduction = ($basic/30) * $unpaidLeaveDays;

                $gross = $basic + $otAmount;
                $deductions = $penalty + $unpaidDeduction;
                $net = $gross - $deductions;

                Payslip::updateOrCreate(
                    ['employee_id' => $emp->employee_id, 'period_id' => $period->period_id],
                    [
                        'basic_salary' => $basic,
                        'total_allowances' => $otAmount,
                        'total_deductions' => $deductions,
                        'total_overtime_amount' => $otAmount,
                        'net_salary' => $net,
                        'generated_at' => now(),
                    ]
                );
            }

            // remain editable after generation
            $period->update(['payroll_status' => 'draft']);

            DB::table('activity_logs')->insert([
                'user' => auth()->user()->name,
                'module' => 'Payroll',
                'action' => 'Run Payroll',
                'details' => 'Payroll for ' . $period->period_month,
                'status' => 'success',
                'performed_at' => now(),
            ]);
        });

        return redirect()->route('payroll.show', $start->format('Y-m'))->with('success', 'Payroll generated for ' . $period->period_month);
    }

    public function applyAdjustment(Request $request)
    {
        $request->validate([
            'period_id' => 'required|integer|exists:payroll_periods,period_id',
            'employee_id' => 'required|integer|exists:employees,employee_id',
            'ot_rate' => 'nullable|numeric',
            'manual_penalty' => 'nullable|numeric',
            'salary_increase' => 'nullable|numeric',
            'reason' => 'nullable|string'
        ]);

        $period = PayrollPeriod::findOrFail($request->period_id);
        if ($period->payroll_status !== 'draft') {
            return back()->withErrors('Adjustments allowed only in draft.');
        }

        $adjustment = PayrollAdjustment::updateOrCreate(
            ['period_id' => $period->period_id, 'employee_id' => $request->employee_id],
            [
                'ot_rate' => $request->ot_rate,
                'manual_penalty' => $request->manual_penalty ?? 0,
                'salary_increase' => $request->salary_increase ?? 0,
                'reason' => $request->reason,
                'updated_by' => auth()->id(),
            ]
        );

        $payslip = Payslip::where('period_id', $period->period_id)
            ->where('employee_id', $request->employee_id)
            ->firstOrFail();

        $otHours = OvertimeRecord::where('employee_id', $request->employee_id)
            ->where('ot_status', 'approved')
            ->whereBetween('date', [$period->start_date, $period->end_date])
            ->sum('hours');

        $otAmount = $otHours * ($adjustment->ot_rate ?? 0);

        $gross = $payslip->basic_salary
            + $payslip->total_allowances
            + $otAmount
            + $adjustment->salary_increase;

        $deductions = $payslip->total_deductions + $adjustment->manual_penalty;

        $payslip->update([
            'total_allowances' => $payslip->total_allowances + $otAmount + $adjustment->salary_increase,
            'total_deductions' => $deductions,
            'net_salary' => $gross - $deductions
        ]);

        return back()->with('success', 'Adjustment saved and payslip updated.');
    }

    public function approve($periodId)
    {
        $period = PayrollPeriod::findOrFail($periodId);
        if ($period->payroll_status !== 'draft') {
            return back()->withErrors('Only draft can be approved.');
        }
        $period->update(['payroll_status' => 'approved']);
        return back()->with('success', 'Payroll approved.');
    }

    public function markPaid($periodId)
    {
        $period = PayrollPeriod::findOrFail($periodId);
        if ($period->payroll_status !== 'approved') {
            return back()->withErrors('Only approved can be marked paid.');
        }
        $period->update([
            'payroll_status' => 'paid',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        Payslip::where('period_id', $periodId)->update([
            'payment_method' => 'bank_transfer',
            'paid_at' => now(),
        ]);

        DB::table('activity_logs')->insert([
            'user' => auth()->user()->name,
            'module' => 'Payroll',
            'action' => 'Mark Paid',
            'details' => 'Payroll for ' . $period->period_month,
            'status' => 'success',
            'performed_at' => now(),
        ]);

        return back()->with('success', 'Payroll marked as paid.');
    }

    public function lock($periodId)
    {
        $period = PayrollPeriod::findOrFail($periodId);
        if ($period->payroll_status !== 'paid') {
            return back()->withErrors('Only paid can be locked.');
        }
        $period->update(['payroll_status' => 'locked']);
        return back()->with('success', 'Payroll locked.');
    }
}
