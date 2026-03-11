<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payslip;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Employee-facing My Payroll: payslips and summary for the logged-in employee.
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

        // Format rows for recent payslips table
        $recentPayslips = $payslips->take(12)->map(function (Payslip $p) {
            $periodMonth = $p->period_month ?? $p->period?->period_month ?? '—';
            $label = $periodMonth !== '—'
                ? Carbon::createFromFormat('Y-m', $periodMonth)->format('M Y')
                : '—';
            return [
                'period_month' => $periodMonth,
                'period_label' => $label,
                'gross'        => (float) $p->basic_salary + (float) $p->total_allowances,
                'net'          => (float) $p->net_salary,
                'status'       => 'Paid',
                'payslip_id'   => $p->payslip_id,
            ];
        });

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
