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

        // IF THE SUPERVISOR HAS NO ASSIGNED TEAM:
        if ($myAreaIds->isEmpty() && $myDeptIds->isEmpty()) {
            return view('supervisor.overtime_inbox', [
                'pendingClaims' => collect(),
                'actedClaims' => collect(),
                'departments' => Department::orderBy('department_name')->get(),
                'pendingAdminCount' => 0,
                'flaggedPendingCount' => 0,
                'approvedCount' => 0,
                'rejectedCount' => 0,
                'otRequests' => collect(),
                'otRequestsPendingCount' => 0,
            ])->with('message', 'No area or department assigned to you. Contact HR to be set as area supervisor or department manager.');
        }

        $q = $request->get('q');
        $deptId = $request->get('department');
        $start = $request->get('start');
        $end = $request->get('end');

        // BASE QUERY: Fetch claims belonging to this supervisor's areas/departments
        $query = OvertimeClaim::with(['employee.user', 'employee.department', 'area', 'user'])
            ->where(function ($qry) use ($myAreaIds, $myDeptIds) {
                if ($myAreaIds->isNotEmpty()) {
                    $qry->orWhereIn('area_id', $myAreaIds);
                }
                if ($myDeptIds->isNotEmpty()) {
                    $qry->orWhereHas('user', fn($u) => $u->whereIn('dept_id', $myDeptIds));
                }
            });

        // APPLY FILTERS
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

        // 1. PENDING CLAIMS (For the "Pending your approval" table)
        $pendingClaims = (clone $query)
            ->where('status', OvertimeClaim::STATUS_SUBMITTED_TO_SUPERVISOR)
            ->orderByDesc('submitted_at')
            ->get();

        // 2. ACTED CLAIMS (For the "OT you approved or rejected" table)
        $actedClaims = (clone $query)
            ->whereIn('status', [
                OvertimeClaim::STATUS_SUPERVISOR_APPROVED, 
                OvertimeClaim::STATUS_SUPERVISOR_REJECTED, 
                OvertimeClaim::STATUS_ADMIN_PENDING, 
                OvertimeClaim::STATUS_ADMIN_APPROVED
            ])
            ->orderByDesc('updated_at')
            ->get();

        // 3. COUNTS FOR THE TOP SUMMARY CARDS
        $pendingAdminCount = (clone $query)->where('status', OvertimeClaim::STATUS_ADMIN_PENDING)->count();
        $flaggedPendingCount = $pendingClaims->count();
        $approvedCount = (clone $query)->whereIn('status', [
            OvertimeClaim::STATUS_SUPERVISOR_APPROVED, 
            OvertimeClaim::STATUS_ADMIN_PENDING, 
            OvertimeClaim::STATUS_ADMIN_APPROVED
        ])->count();
        $rejectedCount = (clone $query)->where('status', OvertimeClaim::STATUS_SUPERVISOR_REJECTED)->count();

        $departments = Department::orderBy('department_name')->get();

        // 4. PRESERVE ORIGINAL OT REQUESTS (Just in case the Sidebar uses it)
        $supervisorEmpId = Auth::user()->employee->employee_id ?? 0;
        $otRequestsQuery = OvertimeRecord::with(['employee.user', 'employee.department'])
            ->whereHas('employee', function($q) use ($supervisorEmpId) {
                $q->where('supervisor_id', $supervisorEmpId);
            })
            ->where('ot_status', \App\Models\OvertimeRecord::FINAL_PENDING_SUPERVISOR ?? 'pending');
            
        $otRequestsPendingCount = (clone $otRequestsQuery)->count();
        $otRequests = (clone $otRequestsQuery)->orderBy('date', 'desc')->orderBy('ot_id', 'desc')->limit(10)->get();

        // RETURN ALL ALIGNED VARIABLES TO THE VIEW!
        return view('supervisor.overtime_inbox', compact(
            'pendingClaims', 
            'actedClaims', 
            'departments', 
            'pendingAdminCount', 
            'flaggedPendingCount', 
            'approvedCount', 
            'rejectedCount',
            'otRequests',
            'otRequestsPendingCount'
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