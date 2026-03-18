<?php

namespace App\Http\Controllers;

use App\Models\Penalty;
use App\Models\PenaltyRemovalRequest;
use App\Models\Department;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminPenaltyRemovalController extends Controller
{
    /**
     * Admin inbox for penalty removal requests (submitted by supervisor).
     */
    public function index(Request $request)
    {
        $request->validate([
            'status' => ['nullable', 'string', 'in:' . implode(',', [
                PenaltyRemovalRequest::STATUS_SUBMITTED_ADMIN,
                PenaltyRemovalRequest::STATUS_APPROVED_ADMIN,
                PenaltyRemovalRequest::STATUS_REJECTED_ADMIN,
            ])],
            'q' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'integer', 'exists:departments,department_id'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
        ]);

        $status = $request->input('status', PenaltyRemovalRequest::STATUS_SUBMITTED_ADMIN);

        $departments = Department::orderBy('department_name')->get();

        $query = PenaltyRemovalRequest::with([
            'penalty.attendance',
            'employee.user',
            'employee.department',
            'supervisor',
            'admin',
        ])->whereIn('status', [
            PenaltyRemovalRequest::STATUS_SUBMITTED_ADMIN,
            PenaltyRemovalRequest::STATUS_APPROVED_ADMIN,
            PenaltyRemovalRequest::STATUS_REJECTED_ADMIN,
        ]);

        if ($status) {
            $query->where('status', $status);
        }

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($q) use ($search) {
                $q->whereHas('employee', function ($e) use ($search) {
                    $e->where('employee_code', 'like', "%{$search}%")
                        ->orWhere('employee_id', $search);
                })->orWhereHas('employee.user', function ($u) use ($search) {
                    $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            });
        }

        if ($request->filled('department')) {
            $deptId = (int) $request->input('department');
            $query->whereHas('employee', fn ($e) => $e->where('department_id', $deptId));
        }

        if ($request->filled('reason')) {
            $reason = (string) $request->input('reason');
            $query->where('request_reason', 'like', "%{$reason}%");
        }

        if ($request->filled('start') || $request->filled('end')) {
            $start = $request->input('start');
            $end = $request->input('end');
            $query->whereHas('penalty', function ($p) use ($start, $end) {
                if ($start) {
                    $p->whereDate('assigned_at', '>=', $start);
                }
                if ($end) {
                    $p->whereDate('assigned_at', '<=', $end);
                }
            });
        }

        $query->orderByDesc('submitted_at')->orderByDesc('id');

        $requests = $query->paginate(25)->withQueryString();

        $counts = [
            'pending' => PenaltyRemovalRequest::where('status', PenaltyRemovalRequest::STATUS_SUBMITTED_ADMIN)->count(),
            'approved' => PenaltyRemovalRequest::where('status', PenaltyRemovalRequest::STATUS_APPROVED_ADMIN)->count(),
            'rejected' => PenaltyRemovalRequest::where('status', PenaltyRemovalRequest::STATUS_REJECTED_ADMIN)->count(),
        ];

        return view('admin.penalty_removal_requests', compact('requests', 'counts', 'status', 'departments'));
    }

    public function approve(Request $request, PenaltyRemovalRequest $removal)
    {
        if ($removal->status !== PenaltyRemovalRequest::STATUS_SUBMITTED_ADMIN) {
            return back()->with('error', 'Only submitted requests can be approved.');
        }

        $removal->update([
            'status' => PenaltyRemovalRequest::STATUS_APPROVED_ADMIN,
            'admin_id' => Auth::id(),
            'admin_note' => $request->input('admin_note'),
            'admin_reviewed_at' => now(),
            'final_decision_at' => now(),
        ]);

        // Mark penalty as removed (kept consistent with existing admin penalty approval behavior).
        $penalty = $removal->penalty;
        if ($penalty) {
            $penalty->status = 'removed';
            $penalty->removed_at = now()->toDateString();
            $penalty->save();
        }

        AuditLogService::log(
            AuditLogService::CATEGORY_ATTENDANCE,
            'penalty_removal_request_admin_approved',
            AuditLogService::STATUS_SUCCESS,
            'Admin approved penalty removal request and removed penalty.',
            ['removal_request_id' => $removal->id, 'penalty_id' => $removal->penalty_id, 'employee_id' => $removal->employee_id],
            $removal->employee_id,
            AuditLogService::SEVERITY_INFO,
            'PenaltyRemovalRequest',
            $removal->id
        );

        return back()->with('success', 'Request approved. Penalty removed.');
    }

    public function reject(Request $request, PenaltyRemovalRequest $removal)
    {
        $request->validate([
            'admin_note' => ['required', 'string', 'max:500'],
        ]);

        if ($removal->status !== PenaltyRemovalRequest::STATUS_SUBMITTED_ADMIN) {
            return back()->with('error', 'Only submitted requests can be rejected.');
        }

        $removal->update([
            'status' => PenaltyRemovalRequest::STATUS_REJECTED_ADMIN,
            'admin_id' => Auth::id(),
            'admin_note' => $request->input('admin_note'),
            'admin_reviewed_at' => now(),
            'final_decision_at' => now(),
        ]);

        // If penalty was previously moved to an intermediate state, restore it.
        $penalty = $removal->penalty;
        if ($penalty && strtolower((string) $penalty->status) === 'under_removal_review') {
            $penalty->status = 'recorded';
            $penalty->save();
        }

        AuditLogService::log(
            AuditLogService::CATEGORY_ATTENDANCE,
            'penalty_removal_request_admin_rejected',
            AuditLogService::STATUS_SUCCESS,
            'Admin rejected penalty removal request.',
            ['removal_request_id' => $removal->id, 'penalty_id' => $removal->penalty_id, 'employee_id' => $removal->employee_id],
            $removal->employee_id,
            AuditLogService::SEVERITY_INFO,
            'PenaltyRemovalRequest',
            $removal->id
        );

        return back()->with('success', 'Request rejected.');
    }
}

