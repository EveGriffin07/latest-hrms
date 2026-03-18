<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PayrollLineItem;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Employee-facing My Payroll: payslips and summary for the logged-in employee.
 * Employees can see the payroll page after release even when they have no payslip for a period.
 */
class EmployeePayrollController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->user_id)->first();

        if (!$employee) {
            return redirect()->route('employee.dashboard')
                ->with('error', 'Employee record not found. Please contact HR.');
        }

        $payslips = Payslip::where('employee_id', $employee->employee_id)
            ->with(['period', 'payrollRun'])
            ->get()
            ->sortByDesc(function (Payslip $p) {
                return $p->period_month ?? $p->period?->period_month ?? '';
            })
            ->values()
            ->take(24);

        // Released periods (LOCKED, PAID, PUBLISHED) so employees can see payroll even without a payslip
        $releasedPeriods = PayrollPeriod::whereIn('status', ['LOCKED', 'PAID', 'PUBLISHED'])
            ->orderByDesc('period_month')
            ->limit(24)
            ->get();

        $payslipByPeriod = $payslips->keyBy(function (Payslip $p) {
            return $p->period_month ?? $p->period?->period_month ?? '';
        });

        // PayrollRun by period (so we show gross/net after release even before payslips are published)
        $periodIds = $releasedPeriods->pluck('period_id')->filter()->values()->all();
        $runsByPeriod = collect([]);
        if (!empty($periodIds)) {
            $runsByPeriod = PayrollRun::where('employee_id', $employee->employee_id)
                ->whereIn('payroll_period_id', $periodIds)
                ->with('period')
                ->get()
                ->keyBy(function (PayrollRun $r) {
                    return $r->period ? $r->period->period_month : '';
                });
        }

        // Last net pay (most recent published payslip)
        $latest = $payslips->first();
        $lastNetPay = $latest ? (float) $latest->net_salary : null;
        $lastPayDate = $latest && $latest->published_at
            ? $latest->published_at->format('M j, Y')
            : ($latest && $latest->period ? Carbon::parse($latest->period->end_date)->format('M j, Y') : null);

        $currentYear = (int) date('Y');
        $ytdGross = 0.0;
        $ytdTax = 0.0;
        foreach ($payslips as $p) {
            $periodMonth = $p->period_month ?? $p->period?->period_month;
            if (!$periodMonth) {
                continue;
            }
            $year = (int) substr($periodMonth, 0, 4);
            if ($year !== $currentYear) {
                continue;
            }
            $ytdGross += (float) $p->basic_salary + (float) $p->total_allowances;
            if ($p->payrollRun) {
                $ytdTax += (float) $p->payrollRun->tax_total;
            }
        }

        // Build table: released periods — show payroll from Payslip or PayrollRun so employees see gross/net after release
        $recentPayslips = $releasedPeriods->take(12)->map(function (PayrollPeriod $period) use ($payslipByPeriod, $runsByPeriod) {
            $periodMonth = $period->period_month ?? '';
            $label = $periodMonth
                ? Carbon::createFromFormat('Y-m', $periodMonth)->format('M Y')
                : '—';
            $payslip = $payslipByPeriod->get($periodMonth);
            $run = $runsByPeriod->get($periodMonth);

            $statusLabel = match (strtoupper($period->status ?? '')) {
                'PUBLISHED' => 'Published',
                'PAID'      => 'Paid',
                'LOCKED'    => 'Released',
                default     => 'Released',
            };

            if ($payslip) {
                return [
                    'period_month' => $periodMonth,
                    'period_label' => $label,
                    'gross'        => (float) $payslip->basic_salary + (float) $payslip->total_allowances,
                    'net'          => (float) $payslip->net_salary,
                    'status'       => $statusLabel,
                    'payslip_id'   => $payslip->payslip_id,
                    'has_payslip'  => true,
                ];
            }

            if ($run) {
                return [
                    'period_month' => $periodMonth,
                    'period_label' => $label,
                    'gross'        => (float) $run->gross_pay,
                    'net'          => (float) $run->net_pay,
                    'status'       => $statusLabel,
                    'payslip_id'   => null,
                    'has_payslip'  => false,
                ];
            }

            return [
                'period_month' => $periodMonth,
                'period_label' => $label,
                'gross'        => null,
                'net'          => null,
                'status'       => 'Released — No payroll for this period',
                'payslip_id'   => null,
                'has_payslip'  => false,
            ];
        })->values()->all();

        // Tax documents: one row per year we have payslips (placeholder; real tax docs would come from another table)
        $yearsWithPayslips = $payslips->map(function (Payslip $p) {
            $periodMonth = $p->period_month ?? $p->period?->period_month;
            return $periodMonth ? (int) substr($periodMonth, 0, 4) : null;
        })->filter()->unique()->sortDesc()->values();

        $taxDocuments = $yearsWithPayslips->map(function ($year) {
            return [
                'year'   => $year,
                'form'   => 'Annual Tax Summary',
                'status' => 'Available',
            ];
        });

        return view('employee.payroll', [
            'employee'       => $employee,
            'lastNetPay'     => $lastNetPay,
            'lastPayDate'    => $lastPayDate,
            'ytdGross'       => $ytdGross,
            'ytdTax'         => $ytdTax,
            'recentPayslips' => $recentPayslips,
            'taxDocuments'   => $taxDocuments,
        ]);
    }

    /**
     * Get payroll detail for a period (for the logged-in employee). Used by the detail card.
     */
    public function detail(Request $request)
    {
        $request->validate(['period_month' => ['required', 'date_format:Y-m']]);
        $employee = Employee::where('user_id', Auth::id())->first();
        if (!$employee) {
            return response()->json(['message' => 'Employee record not found.'], 403);
        }

        $period = PayrollPeriod::where('period_month', $request->input('period_month'))->first();
        if (!$period) {
            return response()->json(['message' => 'Period not found.'], 404);
        }

        $run = PayrollRun::where('payroll_period_id', $period->period_id)
            ->where('employee_id', $employee->employee_id)
            ->first();

        if (!$run) {
            return response()->json(['message' => 'No payroll data for this period.'], 404);
        }

        $lineItems = PayrollLineItem::where('payroll_run_id', $run->id)
            ->orderByRaw("CASE item_type WHEN 'EARNING' THEN 0 ELSE 1 END")
            ->orderBy('code')
            ->get()
            ->map(fn($li) => [
                'code'        => $li->code,
                'item_type'   => $li->item_type,
                'description' => $li->description ?? $li->code,
                'quantity'    => (float) $li->quantity,
                'rate'        => (float) $li->rate,
                'amount'      => (float) $li->amount,
            ])->values()->all();

        $periodLabel = Carbon::createFromFormat('Y-m', $period->period_month)->format('F Y');
        $gross = (float) $run->gross_pay;
        $deductions = (float) $run->unpaid_leave_deduction + (float) $run->absent_deduction
            + (float) $run->late_deduction + (float) $run->penalty_total
            + (float) $run->epf_total + (float) $run->tax_total;
        $net = (float) $run->net_pay;

        return response()->json([
            'period_label' => $periodLabel,
            'period_month' => $period->period_month,
            'status'       => $period->status,
            'gross'        => round($gross, 2),
            'net'          => round($net, 2),
            'breakdown'    => [
                'basic_salary'   => (float) $run->basic_salary,
                'allowance'      => (float) $run->allowance_total,
                'adjustment'     => (float) $run->adjustment_total,
                'unpaid_leave'   => (float) $run->unpaid_leave_deduction,
                'absent'         => (float) $run->absent_deduction,
                'late'           => (float) $run->late_deduction,
                'penalty'        => (float) $run->penalty_total,
                'epf'            => (float) $run->epf_total,
                'tax'            => (float) $run->tax_total,
                'total_deductions' => round($deductions, 2),
            ],
            'line_items' => $lineItems,
        ]);
    }

    /**
     * Download payslip (must belong to logged-in employee).
     */
    public function downloadPayslip($payslipId)
    {
        $employee = Employee::where('user_id', Auth::id())->first();
        if (!$employee) {
            abort(403, 'Employee record not found.');
        }
        $payslip = Payslip::where('payslip_id', $payslipId)
            ->where('employee_id', $employee->employee_id)
            ->with('period')
            ->firstOrFail();
        $periodLabel = $payslip->period_month
            ? Carbon::createFromFormat('Y-m', $payslip->period_month)->format('F Y')
            : ($payslip->period ? Carbon::parse($payslip->period->end_date)->format('F Y') : 'Payslip');
        $gross = (float) $payslip->basic_salary + (float) $payslip->total_allowances;
        return response()->streamDownload(function () use ($payslip, $periodLabel, $gross) {
            echo view('employee.payslip_plain', [
                'payslip'      => $payslip,
                'period_label' => $periodLabel,
                'gross'        => $gross,
            ])->render();
        }, 'payslip-' . ($payslip->period_month ?? '') . '.html', ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * Download tax document for year (placeholder).
     */
    public function downloadTax($year)
    {
        $employee = Employee::where('user_id', Auth::id())->first();
        if (!$employee) {
            abort(403, 'Employee record not found.');
        }
        return redirect()->route('employee.payroll.payslips')
            ->with('info', 'Tax document download for ' . $year . ' is not yet available.');
    }
}
