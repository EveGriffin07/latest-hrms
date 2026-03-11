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

class AdminOvertimeClaimController extends Controller
{
    public function index(Request $request)
    {
        $departments = Department::orderBy('department_name')->get();
        $status = $request->get('status', OvertimeClaim::STATUS_ADMIN_PENDING);

        $query = OvertimeClaim::with(['employee.user', 'employee.department', 'period']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }
        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($qry) use ($q) {
                $qry->whereHas('employee', function ($e) use ($q) {
                    $e->where('employee_code', 'like', "%{$q}%")->orWhere('employee_id', $q);
                })->orWhereHas('employee.user', function ($u) use ($q) {
                    $u->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
                });
            });
        }
        if ($request->filled('department')) {
            $query->whereHas('employee', fn($e) => $e->where('department_id', $request->input('department')));
        }
        if ($request->filled('start')) {
            $query->whereDate('date', '>=', $request->input('start'));
        }
        if ($request->filled('end')) {
            $query->whereDate('date', '<=', $request->input('end'));
        }

        $perPage = min(100, max(10, (int) $request->input('per_page', 25)));
        $claims = $query->orderByDesc('submitted_at')->orderByDesc('id')->paginate($perPage);

        $pending = OvertimeClaim::where('status', OvertimeClaim::STATUS_ADMIN_PENDING)->count();
        $approved = OvertimeClaim::where('status', OvertimeClaim::STATUS_ADMIN_APPROVED)->count();
        $rejected = OvertimeClaim::where('status', OvertimeClaim::STATUS_ADMIN_REJECTED)->count();
        $onHold = OvertimeClaim::where('status', OvertimeClaim::STATUS_ADMIN_ON_HOLD)->count();

        return view('admin.overtime_claims', compact('departments', 'claims', 'pending', 'approved', 'rejected', 'onHold'));
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
        if (!$claim->isActionableByAdmin()) {
            return redirect()->route('admin.payroll.overtime_claims')->with('error', 'Only claims pending admin can be approved.');
        }
        $remark = $request->input('remark');

        $effectiveHours = $claim->getEffectiveApprovedHours();
        $multiplier = $this->getMultiplierForDate($claim->date);

        DB::transaction(function () use ($claim, $remark, $effectiveHours, $multiplier) {
            $record = null;
            if ($claim->overtime_record_id) {
                $record = OvertimeRecord::find($claim->overtime_record_id);
            }
            if (!$record) {
                $periodId = $claim->period_id;
                if (!$periodId) {
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

        return redirect()->route('admin.payroll.overtime_claims')->with('success', 'OT claim approved and posted to payroll.');
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

        return redirect()->route('admin.payroll.overtime_claims')->with('success', 'OT claim rejected.');
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

        return redirect()->route('admin.payroll.overtime_claims')->with('success', 'OT claim put on hold.');
    }
}
