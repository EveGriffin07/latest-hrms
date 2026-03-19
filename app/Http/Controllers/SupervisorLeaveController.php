<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class SupervisorLeaveController extends Controller
{
    /**
     * Shared approval mutation for supervisor workflows.
     */
    private function approveLeaveAsSupervisor(LeaveRequest $leave): void
    {
        $beforeStatus = $leave->leave_status;
        $leave->update([
            'leave_status' => LeaveRequest::STATUS_PENDING_ADMIN,
            'supervisor_approved_by' => Auth::id(),
            'supervisor_approved_at' => now(),
        ]);
        $afterStatus = $leave->leave_status;

        $typeName = $leave->leaveType->leave_name ?? 'Leave';
        $dates = $leave->start_date->format('Y-m-d') . ' to ' . $leave->end_date->format('Y-m-d');
        AuditLogService::log(
            AuditLogService::CATEGORY_LEAVE,
            'leave_supervisor_approved',
            AuditLogService::STATUS_SUCCESS,
            'Supervisor approved leave and sent to admin (' . $typeName . ', ' . $dates . ')',
            [
                'leave_request_id' => $leave->leave_request_id,
                'employee_id' => $leave->employee_id,
                'before_status' => $beforeStatus,
                'after_status' => $afterStatus,
            ],
            $leave->employee_id,
            AuditLogService::SEVERITY_INFO,
            'Leave',
            $leave->leave_request_id
        );
    }

    /**
     * Shared rejection mutation for supervisor workflows.
     */
    private function rejectLeaveAsSupervisor(LeaveRequest $leave, string $reason): void
    {
        $beforeStatus = $leave->leave_status;
        $leave->update([
            'leave_status' => LeaveRequest::STATUS_REJECTED,
            'reject_reason' => $reason,
            'decision_at' => now(),
        ]);
        $afterStatus = $leave->leave_status;

        $typeName = $leave->leaveType->leave_name ?? 'Leave';
        AuditLogService::log(
            AuditLogService::CATEGORY_LEAVE,
            'leave_request_rejected',
            AuditLogService::STATUS_FAILED,
            'Supervisor rejected leave (' . $typeName . '): ' . $reason,
            [
                'leave_request_id' => $leave->leave_request_id,
                'employee_id' => $leave->employee_id,
                'before_status' => $beforeStatus,
                'after_status' => $afterStatus,
            ],
            $leave->employee_id,
            AuditLogService::SEVERITY_INFO,
            'Leave',
            $leave->leave_request_id
        );
    }

    /**
     * Leave requests pending at this supervisor, and all leave this supervisor has approved or rejected.
     */
    public function index()
    {
        $userId = Auth::id();

        $pendingAtSupervisor = LeaveRequest::with(['employee.user', 'employee.department', 'leaveType'])
            ->where('supervisor_id', $userId)
            ->where('leave_status', LeaveRequest::STATUS_PENDING)
            ->orderBy('start_date')
            ->orderBy('leave_request_id')
            ->get();

        // Leave approved by this supervisor (sent to admin)
        $approvedByMe = LeaveRequest::with(['employee.user', 'employee.department', 'leaveType'])
            ->where('supervisor_approved_by', $userId)
            ->whereIn('leave_status', [
                LeaveRequest::STATUS_SUPERVISOR_APPROVED,
                LeaveRequest::STATUS_PENDING_ADMIN,
                LeaveRequest::STATUS_APPROVED,
            ])
            ->orderByDesc('supervisor_approved_at')
            ->get();

        // Leave rejected by this supervisor (was pending at them, now rejected)
        $rejectedByMe = LeaveRequest::with(['employee.user', 'employee.department', 'leaveType'])
            ->where('supervisor_id', $userId)
            ->where('leave_status', LeaveRequest::STATUS_REJECTED)
            ->orderByDesc('decision_at')
            ->get();

        // Combined: all leave acted on by this supervisor (approved + rejected), sorted by action date
        $actedByMe = $approvedByMe->concat($rejectedByMe)->sortByDesc(function ($req) {
            if ($req->supervisor_approved_at) {
                return $req->supervisor_approved_at->timestamp;
            }
            return $req->decision_at ? $req->decision_at->timestamp : 0;
        })->values();

        $totalCount = $pendingAtSupervisor->count() + $actedByMe->count();
        $approvedCount = $actedByMe->filter(fn ($r) => in_array($r->leave_status, [
            LeaveRequest::STATUS_SUPERVISOR_APPROVED,
            LeaveRequest::STATUS_PENDING_ADMIN,
            LeaveRequest::STATUS_APPROVED,
        ], true))->count();
        $rejectedCount = $actedByMe->filter(fn ($r) => $r->leave_status === LeaveRequest::STATUS_REJECTED)->count();

        return view('supervisor.leave_inbox', [
            'pendingAtSupervisor' => $pendingAtSupervisor,
            'actedByMe' => $actedByMe,
            'totalCount' => $totalCount,
            'approvedCount' => $approvedCount,
            'rejectedCount' => $rejectedCount,
        ]);
    }

    /**
     * Supervisor approves a leave request and it goes directly to admin for final approval.
     */
    public function approve(Request $request, LeaveRequest $leave)
    {
        if ($leave->supervisor_id != Auth::id()) {
            return back()->withErrors(['leave' => 'Not assigned to you.']);
        }
        if ($leave->leave_status !== LeaveRequest::STATUS_PENDING) {
            return back()->withErrors(['leave' => 'Only pending requests can be approved.']);
        }

        $this->approveLeaveAsSupervisor($leave);

        return back()->with('success', 'Leave approved and sent to admin for final approval.');
    }

    /**
     * Supervisor rejects a leave request.
     */
    public function reject(Request $request, LeaveRequest $leave)
    {
        $request->validate([
            'reject_reason' => ['required', 'string', 'max:500'],
        ]);

        if ($leave->supervisor_id != Auth::id()) {
            return back()->withErrors(['leave' => 'Not assigned to you.']);
        }
        if ($leave->leave_status !== LeaveRequest::STATUS_PENDING) {
            return back()->withErrors(['leave' => 'Only pending requests can be rejected.']);
        }

        $this->rejectLeaveAsSupervisor($leave, (string) $request->input('reject_reason'));

        return back()->with('success', 'Leave request rejected.');
    }

    /**
     * Bulk approve selected pending leave requests assigned to this supervisor.
     */
    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'leave_ids' => ['required', 'array', 'min:1'],
            'leave_ids.*' => ['integer', 'exists:leave_requests,leave_request_id'],
        ]);

        $ids = array_unique($validated['leave_ids']);
        $approved = 0;
        foreach ($ids as $id) {
            $leave = LeaveRequest::with('leaveType')->find($id);
            if (!$leave) {
                continue;
            }
            if ((int) $leave->supervisor_id !== (int) Auth::id()) {
                continue;
            }
            if ($leave->leave_status !== LeaveRequest::STATUS_PENDING) {
                continue;
            }
            $this->approveLeaveAsSupervisor($leave);
            $approved++;
        }

        return back()->with('success', $approved . ' leave request(s) approved and sent to admin.');
    }

    /**
     * Bulk reject selected pending leave requests assigned to this supervisor.
     */
    public function bulkReject(Request $request)
    {
        $validated = $request->validate([
            'leave_ids' => ['required', 'array', 'min:1'],
            'leave_ids.*' => ['integer', 'exists:leave_requests,leave_request_id'],
            'reject_reason' => ['required', 'string', 'max:500'],
        ]);

        $ids = array_unique($validated['leave_ids']);
        $reason = (string) $validated['reject_reason'];
        $rejected = 0;
        foreach ($ids as $id) {
            $leave = LeaveRequest::with('leaveType')->find($id);
            if (!$leave) {
                continue;
            }
            if ((int) $leave->supervisor_id !== (int) Auth::id()) {
                continue;
            }
            if ($leave->leave_status !== LeaveRequest::STATUS_PENDING) {
                continue;
            }
            $this->rejectLeaveAsSupervisor($leave, $reason);
            $rejected++;
        }

        return back()->with('success', $rejected . ' leave request(s) rejected.');
    }

    /**
     * Supervisor uploads an approved leave to admin for final approval.
     */
    public function uploadToAdmin(LeaveRequest $leave)
    {
        if ($leave->supervisor_approved_by != Auth::id()) {
            return back()->withErrors(['leave' => 'Only the approving supervisor can upload to admin.']);
        }
        if ($leave->leave_status !== LeaveRequest::STATUS_SUPERVISOR_APPROVED) {
            return back()->withErrors(['leave' => 'Only supervisor-approved requests can be uploaded to admin.']);
        }

        $beforeStatus = $leave->leave_status;
        $leave->update([
            'leave_status' => LeaveRequest::STATUS_PENDING_ADMIN,
        ]);
        $afterStatus = $leave->leave_status;

        $typeName = $leave->leaveType->leave_name ?? 'Leave';
        AuditLogService::log(
            AuditLogService::CATEGORY_LEAVE,
            'leave_uploaded_to_admin',
            AuditLogService::STATUS_SUCCESS,
            'Leave uploaded to admin for approval (' . $typeName . ')',
            [
                'leave_request_id' => $leave->leave_request_id,
                'employee_id' => $leave->employee_id,
                'before_status' => $beforeStatus,
                'after_status' => $afterStatus,
            ],
            $leave->employee_id,
            AuditLogService::SEVERITY_INFO,
            'Leave',
            $leave->leave_request_id
        );

        return back()->with('success', 'Leave sent to admin for final approval.');
    }
}