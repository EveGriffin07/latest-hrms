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
            $otRequests = collect();
            $otRequestsPendingCount = 0;
            return view('supervisor.overtime_inbox', [
                'claims' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 25),
                'departments' => Department::orderBy('department_name')->get(),
                'total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0,
                'otRequests' => $otRequests,
                'otRequestsPendingCount' => $otRequestsPendingCount,
            ])->with('message', 'No area or department assigned to you. Contact HR to be set as area supervisor or department manager.');
        }

        $status = $request->get('status', OvertimeClaim::STATUS_SUBMITTED_TO_SUPERVISOR);
        $q = $request->get('q');
        $deptId = $request->get('department');
        $start = $request->get('start');
        $end = $request->get('end');

        $query = OvertimeClaim::with(['employee.user', 'employee.department', 'area', 'user'])
            ->where(function ($qry) use ($myAreaIds, $myDeptIds) {
                if ($myAreaIds->isNotEmpty()) {
                    $qry->orWhereIn('area_id', $myAreaIds);
                }
                if ($myDeptIds->isNotEmpty()) {
                    $qry->orWhereHas('user', fn($u) => $u->whereIn('dept_id', $myDeptIds));
                }
            });

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($q) {
            $query->where(function ($qry) use ($q) {
                $qry->whereHas('employee', function ($e) use ($q) {
                    $e->where('employee_code', 'like', "%{$q}%")
                        ->orWhere('employee_id', $q);
                })->orWhereHas('employee.user', function ($u) use ($q) {
                    $u->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
                });
            });
        }
        if ($deptId) {
            $query->whereHas('employee', fn($e) => $e->where('department_id', $deptId));
        }
        if ($start) {
            $query->whereDate('date', '>=', $start);
        }
        if ($end) {
            $query->whereDate('date', '<=', $end);
        }

        $perPage = min(100, max(10, (int) $request->get('per_page', 25)));
        $claims = $query->orderByDesc('submitted_at')->orderByDesc('id')->paginate($perPage);

        $baseQuery = OvertimeClaim::where(function ($qry) use ($myAreaIds, $myDeptIds) {
            if ($myAreaIds->isNotEmpty()) $qry->orWhereIn('area_id', $myAreaIds);
            if ($myDeptIds->isNotEmpty()) $qry->orWhereHas('user', fn($u) => $u->whereIn('dept_id', $myDeptIds));
        });
        $total = (clone $baseQuery)->count();
        $pending = (clone $baseQuery)->where('status', OvertimeClaim::STATUS_SUBMITTED_TO_SUPERVISOR)->count();
        $approved = (clone $baseQuery)->whereIn('status', [OvertimeClaim::STATUS_SUPERVISOR_APPROVED, OvertimeClaim::STATUS_ADMIN_PENDING, OvertimeClaim::STATUS_ADMIN_APPROVED])->count();
        $rejected = (clone $baseQuery)->where('status', OvertimeClaim::STATUS_SUPERVISOR_REJECTED)->count();

        $departments = Department::orderBy('department_name')->get();

        // OT Requests (OvertimeRecord) assigned to this supervisor, pending their action
        $otRequestsQuery = OvertimeRecord::with(['employee.user', 'employee.department'])
            ->where('supervisor_id', Auth::id())
            ->where('final_status', \App\Models\OvertimeRecord::FINAL_PENDING_SUPERVISOR);
        $otRequestsPendingCount = (clone $otRequestsQuery)->count();
        $otRequests = (clone $otRequestsQuery)->orderBy('date', 'desc')->orderBy('ot_id', 'desc')->limit(10)->get();

        return view('supervisor.overtime_inbox', compact('claims', 'departments', 'total', 'pending', 'approved', 'rejected', 'otRequests', 'otRequestsPendingCount'));
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
                'approved_hours' => $approvedHours,
            ]);
            OtClaimAudit::log(OtClaimAudit::ACTION_SUPERVISOR_APPROVED, $claim, $before, $claim->status, ['remark' => $remark]);
            OtClaimNotifier::onSupervisorApproved($claim->load(['employee.user']));

            $claim->update(['status' => OvertimeClaim::STATUS_ADMIN_PENDING]);
            OtClaimAudit::log(OtClaimAudit::ACTION_ADMIN_PENDING, $claim, OvertimeClaim::STATUS_SUPERVISOR_APPROVED, OvertimeClaim::STATUS_ADMIN_PENDING, [], 'Claim queued to admin');
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
