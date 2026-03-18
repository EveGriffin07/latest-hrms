<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\OvertimeClaim;
use App\Models\OvertimeRecord;
use App\Models\PayrollPeriod;
use App\Services\OtClaimAudit;
use App\Services\OtClaimNotifier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminOvertimeClaimController extends Controller
{
    public function index(Request $request)
    {
        $departments = Department::orderBy('department_name')->get();
        $queue = $request->get('queue', 'all'); // all | payroll-ready | exceptions

        $baseQuery = OvertimeClaim::with(['employee.user', 'employee.department', 'period', 'supervisor']);

        if ($request->filled('q')) {
            $q = $request->input('q');
            $baseQuery->where(function ($qry) use ($q) {
                $qry->whereHas('employee', function ($e) use ($q) {
                    $e->where('employee_code', 'like', "%{$q}%")->orWhere('employee_id', $q);
                })->orWhereHas('employee.user', function ($u) use ($q) {
                    $u->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
                });
            });
        }
        if ($request->filled('department')) {
            $baseQuery->whereHas('employee', fn($e) => $e->where('department_id', $request->input('department')));
        }
        if ($request->filled('start')) {
            $baseQuery->whereDate('date', '>=', $request->input('start'));
        }
        if ($request->filled('end')) {
            $baseQuery->whereDate('date', '<=', $request->input('end'));
        }

        $pendingQuery = (clone $baseQuery)->where('status', OvertimeClaim::STATUS_ADMIN_PENDING);
        if ($queue === 'exceptions') {
            $pendingQuery->where('supervisor_action_type', OvertimeClaim::SUPERVISOR_ACTION_ESCALATED_TO_ADMIN);
        } elseif ($queue === 'payroll-ready') {
            $pendingQuery->where(function ($q) {
                $q->whereNull('supervisor_action_type')
                    ->orWhereIn('supervisor_action_type', [
                        OvertimeClaim::SUPERVISOR_ACTION_APPROVED,
                        OvertimeClaim::SUPERVISOR_ACTION_APPROVED_WITH_ADJUSTMENT,
                    ]);
            });
        }
        $pendingClaims = (clone $pendingQuery)->orderByDesc('submitted_at')->orderByDesc('id')->get();

        $actedStatuses = [
            OvertimeClaim::STATUS_ADMIN_APPROVED,
            OvertimeClaim::STATUS_ADMIN_REJECTED,
            OvertimeClaim::STATUS_ADMIN_ON_HOLD,
        ];
        $actedClaims = (clone $baseQuery)
            ->whereIn('status', $actedStatuses)
            ->orderByDesc('admin_action_at')
            ->orderByDesc('id')
            ->get();

        $pendingCount = (clone $baseQuery)->where('status', OvertimeClaim::STATUS_ADMIN_PENDING)->count();
        $approvedCount = (clone $baseQuery)->where('status', OvertimeClaim::STATUS_ADMIN_APPROVED)->count();
        $rejectedCount = (clone $baseQuery)->where('status', OvertimeClaim::STATUS_ADMIN_REJECTED)->count();
        $onHoldCount = (clone $baseQuery)->where('status', OvertimeClaim::STATUS_ADMIN_ON_HOLD)->count();
        $payrollReadyCount = (clone $baseQuery)->where('status', OvertimeClaim::STATUS_ADMIN_PENDING)
            ->where(function ($q) {
                $q->whereNull('supervisor_action_type')
                    ->orWhereIn('supervisor_action_type', [
                        OvertimeClaim::SUPERVISOR_ACTION_APPROVED,
                        OvertimeClaim::SUPERVISOR_ACTION_APPROVED_WITH_ADJUSTMENT,
                    ]);
            })->count();
        $exceptionsCount = (clone $baseQuery)->where('status', OvertimeClaim::STATUS_ADMIN_PENDING)
            ->where('supervisor_action_type', OvertimeClaim::SUPERVISOR_ACTION_ESCALATED_TO_ADMIN)->count();

        $totalCount = $pendingCount + $approvedCount + $rejectedCount + $onHoldCount;

        return view('admin.overtime_claims', compact(
            'departments',
            'pendingClaims',
            'actedClaims',
            'queue',
            'totalCount',
            'pendingCount',
            'approvedCount',
            'rejectedCount',
            'onHoldCount',
            'payrollReadyCount',
            'exceptionsCount'
        ));
    }

    public function bulkApprove(Request $request)
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:overtime_claims,id'],
            'password' => [
                'required',
                function (string $attribute, $value, \Closure $fail) {
                    if (! Hash::check($value, Auth::user()->getAuthPassword())) {
                        $fail('The password is incorrect. Re-enter your admin password.');
                    }
                },
            ],
            'remark' => ['nullable', 'string', 'max:500'],
        ]);
        $ids = $request->input('ids');
        $remark = $request->input('remark');
        $queryParams = array_filter([
            'queue' => $request->get('queue'),
            'q' => $request->get('q'),
            'department' => $request->get('department'),
            'start' => $request->get('start'),
            'end' => $request->get('end'),
        ]);
        $approved = 0;
        foreach ($ids as $id) {
            $claim = OvertimeClaim::find($id);
            if ($claim && $claim->isActionableByAdmin()) {
                $this->approveOne($claim, $remark);
                $approved++;
            }
        }
        $url = route('admin.payroll.overtime_claims') . (count($queryParams) > 0 ? '?' . http_build_query($queryParams) : '');
        return redirect()->to($url)->with('success', $approved > 0 ? "{$approved} OT claim(s) approved and posted to payroll." : 'No claims were approved.');
    }

    private function approveOne(OvertimeClaim $claim, ?string $remark): void
    {
        $effectiveHours = $claim->getEffectiveApprovedHours();
        $multiplier = $this->getMultiplierForDate($claim->date);
        DB::transaction(function () use ($claim, $remark, $effectiveHours, $multiplier) {
            $record = null;
            if ($claim->overtime_record_id) {
                $record = OvertimeRecord::find($claim->overtime_record_id);
            }
            if (! $record) {
                $periodId = $claim->period_id;
                if (! $periodId) {
                    $periodMonth = $claim->date->format('Y-m');
                    $period = PayrollPeriod::firstOrCreate(
                        ['period_month' => $periodMonth],
                        ['start_date' => $claim->date->copy()->startOfMonth(), 'end_date' => $claim->date->copy()->endOfMonth()]
                    );
                    $periodId = $period->period_id;
                }
                $record = OvertimeRecord::create([
                    'employee_id' => $claim->employee_id,
                    'period_id' => $periodId,
                    'date' => $claim->date,
                    'hours' => $effectiveHours,
                    'rate_type' => $multiplier,
                    'reason' => $claim->reason,
                    'ot_status' => 'approved',
                    'approved_by' => Auth::id(),
                ]);
            } else {
                $record->update([
                    'ot_status' => 'approved',
                    'approved_by' => Auth::id(),
                    'hours' => $effectiveHours,
                    'rate_type' => $multiplier,
                ]);
            }
            $before = $claim->status;
            $claim->update([
                'status' => OvertimeClaim::STATUS_ADMIN_APPROVED,
                'admin_remark' => $remark,
                'admin_acted_by' => Auth::id(),
                'admin_action_at' => now(),
                'overtime_record_id' => $record->ot_id,
            ]);
            OtClaimAudit::log(OtClaimAudit::ACTION_ADMIN_APPROVED, $claim, $before, $claim->status, ['remark' => $remark]);
            OtClaimNotifier::onAdminApproved($claim->load(['employee.user']));
        });
    }

    public function bulkReject(Request $request)
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:overtime_claims,id'],
            'remark' => ['required', 'string', 'max:500'],
        ]);
        $ids = $request->input('ids');
        $remark = $request->input('remark');
        $queryParams = array_filter([
            'queue' => $request->get('queue'),
            'q' => $request->get('q'),
            'department' => $request->get('department'),
            'start' => $request->get('start'),
            'end' => $request->get('end'),
        ]);
        $rejected = 0;
        foreach ($ids as $id) {
            $claim = OvertimeClaim::find($id);
            if ($claim && $claim->isActionableByAdmin()) {
                $before = $claim->status;
                $claim->update([
                    'status' => OvertimeClaim::STATUS_ADMIN_REJECTED,
                    'admin_remark' => $remark,
                    'admin_acted_by' => Auth::id(),
                    'admin_action_at' => now(),
                ]);
                OtClaimAudit::log(OtClaimAudit::ACTION_ADMIN_REJECTED, $claim, $before, $claim->status, ['remark' => $remark]);
                OtClaimNotifier::onAdminRejected($claim->load('employee.user'));
                $rejected++;
            }
        }
        $url = route('admin.payroll.overtime_claims') . (count($queryParams) > 0 ? '?' . http_build_query($queryParams) : '');
        return redirect()->to($url)->with('success', $rejected > 0 ? "{$rejected} OT claim(s) rejected." : 'No claims were rejected.');
    }

    public function show(OvertimeClaim $claim)
    {
        $claim->load(['employee.user', 'employee.department', 'period', 'supervisor']);
        return response()->json([
            'claim' => [
                'id' => $claim->id,
                'employee' => $claim->employee->user->name ?? 'Unknown',
                'employee_code' => $claim->employee->employee_code ?? '',
                'department' => $claim->employee->department->department_name ?? 'N/A',
                'date' => $claim->date->format('Y-m-d'),
                'hours' => (float) $claim->hours,
                'rate_type' => (float) $claim->rate_type,
                'reason' => $claim->reason,
                'supporting_info' => $claim->supporting_info,
                'status' => $claim->status,
                'submitted_at' => $claim->submitted_at?->toIso8601String(),
                'supervisor_remark' => $claim->supervisor_remark,
                'admin_remark' => $claim->admin_remark,
            ],
        ]);
    }

    public function approve(Request $request, OvertimeClaim $claim)
    {
        if (! $claim->isActionableByAdmin()) {
            return redirect()->route('admin.payroll.overtime_claims')->with('error', 'Only claims pending admin can be approved.');
        }
        $request->validate([
            'password' => [
                'required',
                function (string $attribute, $value, \Closure $fail) {
                    if (! Hash::check($value, Auth::user()->getAuthPassword())) {
                        $fail('The password is incorrect. Re-enter your admin password to release payroll.');
                    }
                },
            ],
        ]);
        $this->approveOne($claim, $request->input('remark'));
        $queryParams = array_filter(['queue' => $request->get('queue'), 'q' => $request->get('q'), 'department' => $request->get('department'), 'start' => $request->get('start'), 'end' => $request->get('end')]);
        $url = count($queryParams) > 0 ? route('admin.payroll.overtime_claims') . '?' . http_build_query($queryParams) : route('admin.payroll.overtime_claims');
        return redirect()->to($url)->with('success', 'OT claim approved and posted to payroll.');
    }

    /** Weekday 1.5, Weekend 2.0, Holiday 3.0 */
    private function getMultiplierForDate(Carbon $date): float
    {
        return self::multiplierForDate($date);
    }

    public static function multiplierForDate(Carbon $date): float
    {
        $dateStr = $date->format('Y-m-d');
        $holidays = config('hrms.overtime.holidays', []);
        if (in_array($dateStr, $holidays, true)) {
            return (float) config('hrms.overtime.multiplier_holiday', 3.0);
        }
        if ($date->isWeekend()) {
            return (float) config('hrms.overtime.multiplier_weekend', 2.0);
        }
        return (float) config('hrms.overtime.multiplier_weekday', 1.5);
    }

    /** Payout = approved_hours * hourly_rate * multiplier. */
    public static function computePayout(OvertimeClaim $claim): float
    {
        $hours = $claim->getEffectiveApprovedHours();
        $monthly = (float) ($claim->employee->base_salary ?? 0);
        $hoursPerMonth = (float) config('hrms.overtime.working_hours_per_month', 160);
        $hourly = $hoursPerMonth > 0 ? $monthly / $hoursPerMonth : 0;
        $multiplier = self::multiplierForDate($claim->date);
        return round($hours * $hourly * $multiplier, 2);
    }

    public function reject(Request $request, OvertimeClaim $claim)
    {
        if (!$claim->isActionableByAdmin()) {
            return redirect()->route('admin.payroll.overtime_claims')->with('error', 'Only claims pending admin can be rejected.');
        }
        $validated = $request->validate(['remark' => ['required', 'string', 'max:500']]);

        $before = $claim->status;
        $claim->update([
            'status' => OvertimeClaim::STATUS_ADMIN_REJECTED,
            'admin_remark' => $validated['remark'],
            'admin_acted_by' => Auth::id(),
            'admin_action_at' => now(),
        ]);
        OtClaimAudit::log(OtClaimAudit::ACTION_ADMIN_REJECTED, $claim, $before, $claim->status, ['remark' => $validated['remark']]);
        OtClaimNotifier::onAdminRejected($claim->load('employee.user'));

        $queryParams = array_filter(['queue' => $request->get('queue'), 'q' => $request->get('q'), 'department' => $request->get('department'), 'start' => $request->get('start'), 'end' => $request->get('end')]);
        $url = count($queryParams) > 0 ? route('admin.payroll.overtime_claims') . '?' . http_build_query($queryParams) : route('admin.payroll.overtime_claims');
        return redirect()->to($url)->with('success', 'OT claim rejected.');
    }

    public function onHold(Request $request, OvertimeClaim $claim)
    {
        if (!$claim->isActionableByAdmin()) {
            return redirect()->route('admin.payroll.overtime_claims')->with('error', 'Only claims pending admin can be put on hold.');
        }
        $validated = $request->validate(['remark' => ['required', 'string', 'max:500']]);

        $before = $claim->status;
        $claim->update([
            'status' => OvertimeClaim::STATUS_ADMIN_ON_HOLD,
            'admin_remark' => $validated['remark'],
            'admin_acted_by' => Auth::id(),
            'admin_action_at' => now(),
        ]);
        OtClaimAudit::log(OtClaimAudit::ACTION_ADMIN_ON_HOLD, $claim, $before, $claim->status, ['remark' => $validated['remark']]);
        OtClaimNotifier::onAdminOnHold($claim->load('employee.user'));

        $queryParams = array_filter(['queue' => $request->get('queue'), 'q' => $request->get('q'), 'department' => $request->get('department'), 'start' => $request->get('start'), 'end' => $request->get('end')]);
        $url = count($queryParams) > 0 ? route('admin.payroll.overtime_claims') . '?' . http_build_query($queryParams) : route('admin.payroll.overtime_claims');
        return redirect()->to($url)->with('success', 'OT claim put on hold.');
    }
}
