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

        $leave->update([
            'leave_status' => LeaveRequest::STATUS_PENDING_ADMIN,
            'supervisor_approved_by' => Auth::id(),
            'supervisor_approved_at' => now(),
        ]);

        $typeName = $leave->leaveType->leave_name ?? 'Leave';
        $dates = $leave->start_date->format('Y-m-d') . ' to ' . $leave->end_date->format('Y-m-d');
        AuditLogService::log(
            AuditLogService::CATEGORY_LEAVE,
            'leave_supervisor_approved',
            AuditLogService::STATUS_SUCCESS,
            'Supervisor approved leave and sent to admin (' . $typeName . ', ' . $dates . ')',
            ['leave_request_id' => $leave->leave_request_id, 'employee_id' => $leave->employee_id],
            $leave->employee_id,
            AuditLogService::SEVERITY_INFO,
            'Leave',
            $leave->leave_request_id
        );

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

        $leave->update([
            'leave_status' => LeaveRequest::STATUS_REJECTED,
            'reject_reason' => $request->input('reject_reason'),
            'decision_at' => now(),
        ]);

        $typeName = $leave->leaveType->leave_name ?? 'Leave';
        AuditLogService::log(
            AuditLogService::CATEGORY_LEAVE,
            'leave_request_rejected',
            AuditLogService::STATUS_FAILED,
            'Supervisor rejected leave (' . $typeName . '): ' . $request->input('reject_reason'),
            ['leave_request_id' => $leave->leave_request_id, 'employee_id' => $leave->employee_id],
            $leave->employee_id,
            AuditLogService::SEVERITY_INFO,
            'Leave',
            $leave->leave_request_id
        );

        return back()->with('success', 'Leave request rejected.');
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

        $leave->update([
            'leave_status' => LeaveRequest::STATUS_PENDING_ADMIN,
        ]);

        $typeName = $leave->leaveType->leave_name ?? 'Leave';
        AuditLogService::log(
            AuditLogService::CATEGORY_LEAVE,
            'leave_uploaded_to_admin',
            AuditLogService::STATUS_SUCCESS,
            'Leave uploaded to admin for approval (' . $typeName . ')',
            ['leave_request_id' => $leave->leave_request_id, 'employee_id' => $leave->employee_id],
            $leave->employee_id,
            AuditLogService::SEVERITY_INFO,
            'Leave',
            $leave->leave_request_id
        );

        return back()->with('success', 'Leave sent to admin for final approval.');
    }
}
