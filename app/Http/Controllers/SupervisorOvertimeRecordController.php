<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Department;
use App\Models\OvertimeClaim;
use App\Models\OvertimeRecord;
use App\Services\OtClaimAudit;
use App\Services\OtClaimNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupervisorOvertimeController extends Controller
{
    public function index(Request $request)
    {
        $myAreaIds = Area::where('supervisor_id', Auth::id())->pluck('id');
        $myDeptIds = Department::where('manager_id', Auth::id())->pluck('department_id');

        if ($myAreaIds->isEmpty() && $myDeptIds->isEmpty()) {
            return view('supervisor.overtime_inbox', [
                'pendingClaims' => collect(),
                'actedClaims' => collect(),
                'departments' => Department::orderBy('department_name')->get(),
                'totalCount' => 0,
                'pendingCount' => 0,
                'pendingAdminCount' => 0,
                'flaggedPendingCount' => 0,
                'approvedCount' => 0,
                'rejectedCount' => 0,
            ])->with('message', 'No area or department assigned to you. Contact HR to be set as area supervisor or department manager.');
        }

        $q = $request->get('q');
        $deptId = $request->get('department');
        $start = $request->get('start');
        $end = $request->get('end');

        $baseQuery = OvertimeClaim::with(['employee.user', 'employee.department', 'area', 'user'])
            ->where(function ($qry) use ($myAreaIds, $myDeptIds) {
                if ($myAreaIds->isNotEmpty()) {
                    $qry->orWhereIn('area_id', $myAreaIds);
                }
                if ($myDeptIds->isNotEmpty()) {
                    $qry->orWhereHas('user', fn($u) => $u->whereIn('dept_id', $myDeptIds));
                }
            });

        if ($q) {
            $baseQuery->where(function ($qry) use ($q) {
                $qry->whereHas('employee', function ($e) use ($q) {
                    $e->where('employee_code', 'like', "%{$q}%")
                        ->orWhere('employee_id', $q);
                })->orWhereHas('employee.user', function ($u) use ($q) {
                    $u->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
                });
            });
        }
        if ($deptId) {
            $baseQuery->whereHas('employee', fn($e) => $e->where('department_id', $deptId));
        }
        if ($start) {
            $baseQuery->whereDate('date', '>=', $start);
        }
        if ($end) {
            $baseQuery->whereDate('date', '<=', $end);
        }

        $pendingClaims = (clone $baseQuery)
            ->where('status', OvertimeClaim::STATUS_SUBMITTED_TO_SUPERVISOR)
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get();

        $actedStatuses = [
            OvertimeClaim::STATUS_SUPERVISOR_APPROVED,
            OvertimeClaim::STATUS_ADMIN_PENDING,
            OvertimeClaim::STATUS_ADMIN_APPROVED,
            OvertimeClaim::STATUS_SUPERVISOR_REJECTED,
            OvertimeClaim::STATUS_ADMIN_REJECTED,
            OvertimeClaim::STATUS_ADMIN_ON_HOLD,
        ];
        $actedClaims = (clone $baseQuery)
            ->whereIn('status', $actedStatuses)
            ->orderByDesc('supervisor_action_at')
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get();

        $totalCount = $pendingClaims->count() + $actedClaims->count();
        $pendingCount = $pendingClaims->count();
        $pendingAdminCount = (clone $baseQuery)->where('status', OvertimeClaim::STATUS_ADMIN_PENDING)->count();
        $flaggedPendingCount = $pendingCount;
        $approvedCount = (clone $baseQuery)->where('status', OvertimeClaim::STATUS_ADMIN_APPROVED)->count();
        $rejectedCount = (clone $baseQuery)
            ->whereIn('status', [OvertimeClaim::STATUS_SUPERVISOR_REJECTED, OvertimeClaim::STATUS_ADMIN_REJECTED])
            ->count();

        $departments = Department::orderBy('department_name')->get();

        return view('supervisor.overtime_inbox', compact(
            'pendingClaims',
            'actedClaims',
            'departments',
            'totalCount',
            'pendingCount',
            'pendingAdminCount',
            'flaggedPendingCount',
            'approvedCount',
            'rejectedCount'
        ));
    }

    public function show(OvertimeClaim $claim)
    {
        $this->ensureSupervisorOf($claim);
        $claim->load(['employee.user', 'employee.department', 'period']);
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
        $this->ensureSupervisorOf($claim);
        if (!$claim->isActionableBySupervisor(Auth::id())) {
            return redirect()->route('employee.overtime_inbox.index')->with('error', 'Claim is not pending your approval or already acted on.');
        }

        $validated = $request->validate([
            'remark' => ['nullable', 'string', 'max:500'],
            'approved_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
        ]);
        $remark = $validated['remark'] ?? null;
        $approvedHours = isset($validated['approved_hours']) ? (float) $validated['approved_hours'] : (float) $claim->hours;

        DB::transaction(function () use ($claim, $remark, $approvedHours) {
            $before = $claim->status;
            $claim->update([
                'status' => OvertimeClaim::STATUS_SUPERVISOR_APPROVED,
                'supervisor_remark' => $remark,
                'supervisor_action_at' => now(),
                'supervisor_action_type' => OvertimeClaim::SUPERVISOR_ACTION_APPROVED,
                'approved_hours' => $approvedHours,
            ]);
            OtClaimAudit::log(OtClaimAudit::ACTION_SUPERVISOR_APPROVED, $claim, $before, $claim->status, ['remark' => $remark]);
            OtClaimNotifier::onSupervisorApproved($claim->load(['employee.user']));

            $claim->update(['status' => OvertimeClaim::STATUS_ADMIN_PENDING]);
            OtClaimAudit::log(OtClaimAudit::ACTION_ADMIN_PENDING, $claim, OvertimeClaim::STATUS_SUPERVISOR_APPROVED, OvertimeClaim::STATUS_ADMIN_PENDING, [], 'Claim queued to admin (payroll-ready)');
        });

        return redirect()->route('employee.overtime_inbox.index')->with('success', 'OT claim approved and sent to admin.');
    }

    public function reject(Request $request, OvertimeClaim $claim)
    {
        $this->ensureSupervisorOf($claim);
        if (!$claim->isActionableBySupervisor(Auth::id())) {
            return redirect()->route('employee.overtime_inbox.index')->with('error', 'Claim is not pending your approval.');
        }
        $validated = $request->validate(['remark' => ['required', 'string', 'max:500']]);

        $before = $claim->status;
        $claim->update([
            'status' => OvertimeClaim::STATUS_SUPERVISOR_REJECTED,
            'supervisor_remark' => $validated['remark'],
            'supervisor_action_at' => now(),
        ]);
        OtClaimAudit::log(OtClaimAudit::ACTION_SUPERVISOR_REJECTED, $claim, $before, $claim->status, ['remark' => $validated['remark']]);
        OtClaimNotifier::onSupervisorRejected($claim->load('employee.user'));

        return redirect()->route('employee.overtime_inbox.index')->with('success', 'OT claim rejected.');
    }

    public function returnForChanges(Request $request, OvertimeClaim $claim)
    {
        $this->ensureSupervisorOf($claim);
        if (!$claim->isActionableBySupervisor(Auth::id())) {
            return redirect()->route('employee.overtime_inbox.index')->with('error', 'Claim is not pending your approval.');
        }
        $validated = $request->validate(['remark' => ['required', 'string', 'max:500']]);

        $before = $claim->status;
        $claim->update([
            'status' => OvertimeClaim::STATUS_SUPERVISOR_RETURNED,
            'supervisor_remark' => $validated['remark'],
            'supervisor_action_at' => now(),
        ]);
        OtClaimAudit::log(OtClaimAudit::ACTION_SUPERVISOR_RETURNED, $claim, $before, $claim->status, ['remark' => $validated['remark']]);
        OtClaimNotifier::onSupervisorReturned($claim->load('employee.user'));

        return redirect()->route('employee.overtime_inbox.index')->with('success', 'OT claim returned to employee for changes.');
    }

    /** Approve with adjusted hours; passes to admin as payroll-ready with adjustment reason. */
    public function approveWithAdjustment(Request $request, OvertimeClaim $claim)
    {
        $this->ensureSupervisorOf($claim);
        if (!$claim->isActionableBySupervisor(Auth::id())) {
            return redirect()->route('employee.overtime_inbox.index')->with('error', 'Claim is not pending your approval or already acted on.');
        }
        $validated = $request->validate([
            'remark' => ['nullable', 'string', 'max:500'],
            'approved_hours' => ['required', 'numeric', 'min:0', 'max:24'],
            'adjustment_reason' => ['required', 'string', 'max:500'],
        ]);
        $approvedHours = (float) $validated['approved_hours'];

        DB::transaction(function () use ($claim, $validated, $approvedHours) {
            $before = $claim->status;
            $claim->update([
                'status' => OvertimeClaim::STATUS_SUPERVISOR_APPROVED,
                'supervisor_remark' => $validated['remark'] ?? null,
                'supervisor_action_at' => now(),
                'supervisor_action_type' => OvertimeClaim::SUPERVISOR_ACTION_APPROVED_WITH_ADJUSTMENT,
                'adjustment_reason' => $validated['adjustment_reason'],
                'approved_hours' => $approvedHours,
            ]);
            OtClaimAudit::log(OtClaimAudit::ACTION_SUPERVISOR_APPROVED_WITH_ADJUSTMENT, $claim, $before, $claim->status, [
                'adjustment_reason' => $validated['adjustment_reason'],
                'approved_hours' => $approvedHours,
            ]);
            OtClaimNotifier::onSupervisorApproved($claim->load(['employee.user']));

            $claim->update(['status' => OvertimeClaim::STATUS_ADMIN_PENDING]);
            OtClaimAudit::log(OtClaimAudit::ACTION_ADMIN_PENDING, $claim, OvertimeClaim::STATUS_SUPERVISOR_APPROVED, OvertimeClaim::STATUS_ADMIN_PENDING, [], 'Claim queued to admin (with adjustment)');
        });

        return redirect()->route('employee.overtime_inbox.index')->with('success', 'OT claim approved with adjustment and sent to admin.');
    }

    /** Escalate to admin for exception handling; admin sees escalation reason and recommendation. */
    public function escalateToAdmin(Request $request, OvertimeClaim $claim)
    {
        $this->ensureSupervisorOf($claim);
        if (!$claim->isActionableBySupervisor(Auth::id())) {
            return redirect()->route('employee.overtime_inbox.index')->with('error', 'Claim is not pending your approval or already acted on.');
        }
        $validated = $request->validate([
            'escalation_reason' => ['required', 'string', 'max:500'],
            'recommendation' => ['nullable', 'string', 'max:500'],
            'approved_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
        ]);
        $approvedHours = isset($validated['approved_hours']) ? (float) $validated['approved_hours'] : (float) $claim->hours;

        DB::transaction(function () use ($claim, $validated, $approvedHours) {
            $before = $claim->status;
            $claim->update([
                'status' => OvertimeClaim::STATUS_SUPERVISOR_APPROVED,
                'supervisor_action_at' => now(),
                'supervisor_action_type' => OvertimeClaim::SUPERVISOR_ACTION_ESCALATED_TO_ADMIN,
                'escalation_reason' => $validated['escalation_reason'],
                'recommendation' => $validated['recommendation'] ?? null,
                'approved_hours' => $approvedHours,
            ]);
            OtClaimAudit::log(OtClaimAudit::ACTION_SUPERVISOR_ESCALATED, $claim, $before, $claim->status, [
                'escalation_reason' => $validated['escalation_reason'],
                'recommendation' => $validated['recommendation'] ?? null,
            ]);

            $claim->update(['status' => OvertimeClaim::STATUS_ADMIN_PENDING]);
            OtClaimAudit::log(OtClaimAudit::ACTION_ADMIN_PENDING, $claim, OvertimeClaim::STATUS_SUPERVISOR_APPROVED, OvertimeClaim::STATUS_ADMIN_PENDING, [], 'Claim escalated to admin (exception queue)');
        });

        return redirect()->route('employee.overtime_inbox.index')->with('success', 'OT claim escalated to admin for review.');
    }

    /**
     * Bulk action on multiple OT claims: approve or reject.
     * Applies a common remark/reason to all selected claims.
     */
    public function bulkAction(Request $request)
    {
        $action = $request->input('action');
        $ids = (array) $request->input('ids', []);

        if (!in_array($action, ['approve', 'reject'], true)) {
            return redirect()->route('employee.overtime_inbox.index')->with('error', 'Invalid bulk action.');
        }

        if (empty($ids)) {
            return redirect()->route('employee.overtime_inbox.index')->with('error', 'Please select at least one claim.');
        }

        $rules = [
            'approve' => ['remark' => ['nullable', 'string', 'max:500']],
            'reject' => ['reject_reason' => ['required', 'string', 'max:500']],
        ];
        $validated = $request->validate($rules[$action]);

        $claims = OvertimeClaim::whereIn('id', $ids)->get();
        $acted = 0;

        foreach ($claims as $claim) {
            if (!$claim->isActionableBySupervisor(Auth::id())) {
                continue;
            }
            $this->ensureSupervisorOf($claim);

            DB::transaction(function () use ($claim, $action, $validated, &$acted) {
                $before = $claim->status;
                if ($action === 'approve') {
                    $remark = $validated['remark'] ?? null;
                    $approvedHours = (float) ($claim->hours);
                    $claim->update([
                        'status' => OvertimeClaim::STATUS_SUPERVISOR_APPROVED,
                        'supervisor_remark' => $remark,
                        'supervisor_action_at' => now(),
                        'supervisor_action_type' => OvertimeClaim::SUPERVISOR_ACTION_APPROVED,
                        'approved_hours' => $approvedHours,
                    ]);
                    OtClaimAudit::log(OtClaimAudit::ACTION_SUPERVISOR_APPROVED, $claim, $before, $claim->status, ['remark' => $remark]);
                    OtClaimNotifier::onSupervisorApproved($claim->load(['employee.user']));
                    $claim->update(['status' => OvertimeClaim::STATUS_ADMIN_PENDING]);
                    OtClaimAudit::log(OtClaimAudit::ACTION_ADMIN_PENDING, $claim, OvertimeClaim::STATUS_SUPERVISOR_APPROVED, OvertimeClaim::STATUS_ADMIN_PENDING, [], 'Claim queued to admin (bulk approve)');
                } elseif ($action === 'reject') {
                    $reason = $validated['reject_reason'];
                    $claim->update([
                        'status' => OvertimeClaim::STATUS_SUPERVISOR_REJECTED,
                        'supervisor_remark' => $reason,
                        'supervisor_action_at' => now(),
                    ]);
                    OtClaimAudit::log(OtClaimAudit::ACTION_SUPERVISOR_REJECTED, $claim, $before, $claim->status, ['remark' => $reason]);
                    OtClaimNotifier::onSupervisorRejected($claim->load('employee.user'));
                }
                $acted++;
            });
        }

        if ($acted === 0) {
            return redirect()->route('employee.overtime_inbox.index')->with('error', 'No selected claims could be processed.');
        }

        $messages = [
            'approve' => 'Selected OT claims approved and sent to admin.',
            'reject' => 'Selected OT claims rejected.',
        ];
        return redirect()->route('employee.overtime_inbox.index')->with('success', $messages[$action] ?? 'Bulk action completed.');
    }

    private function ensureSupervisorOf(OvertimeClaim $claim): void
    {
        $myAreaIds = Area::where('supervisor_id', Auth::id())->pluck('id');
        $myDeptIds = Department::where('manager_id', Auth::id())->pluck('department_id');

        if ($myAreaIds->contains($claim->area_id)) {
            return;
        }
        if ($claim->user_id && $myDeptIds->contains($claim->user?->dept_id)) {
            return;
        }
        abort(403, 'You are not the approver for this claim.');
    }
}