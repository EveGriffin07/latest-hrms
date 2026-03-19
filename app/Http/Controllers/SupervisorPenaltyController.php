<?php

namespace App\Http\Controllers;

use App\Models\PenaltyRemovalRequest;
use App\Models\Department;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupervisorPenaltyController extends Controller
{
    /**
     * List penalty removal requests assigned to the logged-in supervisor (supervisor_id = current user's id).
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $legacyPendingAdmin = 'submitted_to_admin';
        $request->validate([
            'status' => ['nullable', 'string'],
            'q' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'integer', 'exists:departments,department_id'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
        ]);

        $departments = Department::orderBy('department_name')->get();

        $query = PenaltyRemovalRequest::with(['penalty.attendance', 'employee.user', 'employee.department'])
            ->where('supervisor_id', $userId)
            // Only show employees' requests (exclude supervisor's own self-submitted requests).
            ->whereHas('employee', fn ($e) => $e->where('user_id', '!=', $userId))
            ->orderByDesc('submitted_at');

        if ($request->filled('status')) {
            $status = (string) $request->input('status');
            if ($status === $legacyPendingAdmin) {
                $status = PenaltyRemovalRequest::STATUS_PENDING_ADMIN;
            }
            // Treat "Pending admin review" filter as including legacy submitted_to_admin.
            if ($status === PenaltyRemovalRequest::STATUS_PENDING_ADMIN) {
                $query->whereIn('status', [PenaltyRemovalRequest::STATUS_PENDING_ADMIN, $legacyPendingAdmin]);
            } else {
                $query->where('status', $status);
            }
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

        $requests = $query->paginate(20)->withQueryString();

        $counts = [
            'pending' => PenaltyRemovalRequest::where('supervisor_id', $userId)
                ->where('status', PenaltyRemovalRequest::STATUS_PENDING_SUPERVISOR)->count(),
            'submitted' => PenaltyRemovalRequest::where('supervisor_id', $userId)
                ->whereIn('status', [PenaltyRemovalRequest::STATUS_PENDING_ADMIN, $legacyPendingAdmin])->count(),
            'rejected' => PenaltyRemovalRequest::where('supervisor_id', $userId)
                ->where('status', PenaltyRemovalRequest::STATUS_REJECTED_SUPERVISOR)->count(),
        ];

        return view('supervisor.penalty_removal_requests', compact('requests', 'counts', 'departments'));
    }

    /**
     * Approve and forward to admin.
     */
    public function approve(Request $request, PenaltyRemovalRequest $removal)
    {
        if ((int) $removal->supervisor_id !== (int) Auth::id()) {
            abort(403, 'Not authorized to review this request.');
        }
        if ($removal->status !== PenaltyRemovalRequest::STATUS_PENDING_SUPERVISOR) {
            return back()->with('error', 'Only pending requests can be approved.');
        }

        $removal->update([
            'status' => PenaltyRemovalRequest::STATUS_PENDING_ADMIN,
            'supervisor_note' => $request->input('supervisor_note'),
            'supervisor_reviewed_at' => now(),
            // Ensure consistent ordering in admin inbox (older records might not have this populated).
            'submitted_at' => $removal->submitted_at ?? now(),
        ]);

        // Put the penalty under admin review while waiting for final decision (optional but clarifies stage).
        if ($removal->penalty && strtolower((string) $removal->penalty->status) === 'recorded') {
            $removal->penalty->status = 'under_removal_review';
            $removal->penalty->save();
        }

        AuditLogService::log(
            AuditLogService::CATEGORY_ATTENDANCE,
            'penalty_removal_request_forwarded_to_admin',
            AuditLogService::STATUS_SUCCESS,
            'Supervisor approved penalty removal request and forwarded to admin.',
            ['removal_request_id' => $removal->id, 'penalty_id' => $removal->penalty_id, 'employee_id' => $removal->employee_id],
            $removal->employee_id,
            AuditLogService::SEVERITY_INFO,
            'PenaltyRemovalRequest',
            $removal->id
        );

        return back()->with('success', 'Request forwarded to admin for final decision.');
    }

    /**
     * Reject at supervisor level.
     */
    public function reject(Request $request, PenaltyRemovalRequest $removal)
    {
        if ((int) $removal->supervisor_id !== (int) Auth::id()) {
            abort(403, 'Not authorized to review this request.');
        }
        if ($removal->status !== PenaltyRemovalRequest::STATUS_PENDING_SUPERVISOR) {
            return back()->with('error', 'Only pending requests can be rejected.');
        }

        $request->validate(['supervisor_note' => ['required', 'string', 'max:500']]);

        $removal->update([
            'status' => PenaltyRemovalRequest::STATUS_REJECTED_SUPERVISOR,
            'supervisor_note' => $request->input('supervisor_note'),
            'supervisor_reviewed_at' => now(),
            'final_decision_at' => now(),
        ]);

        $removal->penalty->update(['status' => 'recorded']);

        return back()->with('success', 'Request rejected.');
    }
}