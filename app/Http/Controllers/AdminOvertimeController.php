<?php

namespace App\Http\Controllers;

use App\Models\OvertimeRecord;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AdminOvertimeController extends Controller
{
    /**
     * Show the overtime claim review page. Dashboard cards: Pending Admin, Flagged, Approved, Rejected (Supervisor/Admin).
     */
    public function index()
    {
        $departments = Department::orderBy('department_name')->get();
        $base = OvertimeRecord::query();
        
        // CHANGED: Replaced 'final_status' with 'ot_status' and used standard string values matching the DB
        $pendingAdmin = (clone $base)->where('ot_status', 'pending')->count();
        $flaggedPending = (clone $base)->where('ot_status', 'pending')->where('flagged_for_admin', true)->count();
        $approvedAdmin = (clone $base)->where('ot_status', 'approved')->count();
        $rejectedSupervisor = (clone $base)->where('ot_status', 'rejected')->count(); 
        $rejectedAdmin = 0; // DB only has one 'rejected' state, so we default this to 0 to prevent errors

        return view('admin.payroll_overtime', compact('departments', 'pendingAdmin', 'flaggedPending', 'approvedAdmin', 'rejectedSupervisor', 'rejectedAdmin'));
    }

    /**
     * Return filtered overtime claims as JSON for the datatable.
     */
    public function data(Request $request)
    {
        $request->validate([
            'start'         => ['nullable', 'date'],
            'end'           => ['nullable', 'date', 'after_or_equal:start'],
            'review_filter' => ['nullable', 'in:all,flagged'],
            'department'    => ['nullable', 'integer', 'exists:departments,department_id'],
            'q'             => ['nullable', 'string', 'max:255'],
            'page'          => ['nullable', 'integer', 'min:1'],
            'per_page'      => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        // CHANGED: Admin sees all OT requests with ot_status = pending
        $query = OvertimeRecord::with(['employee.department', 'employee.user', 'supervisorUser'])
            ->where('ot_status', 'pending');

        if ($request->filled('start')) {
            $query->whereDate('date', '>=', $request->input('start'));
        }
        if ($request->filled('end')) {
            $query->whereDate('date', '<=', $request->input('end'));
        }
        $reviewFilter = $request->input('review_filter', 'all');
        if ($reviewFilter === 'flagged') {
            $query->where('flagged_for_admin', true);
        }
        if ($request->filled('department')) {
            $deptId = $request->input('department');
            $query->whereHas('employee', function ($q) use ($deptId) {
                $q->where('department_id', $deptId);
            });
        }
        if ($request->filled('q')) {
            $search = $request->input('q');
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

        $perPage = min(100, max(10, (int) $request->input('per_page', 25)));
        if ($reviewFilter === 'flagged') {
            $query->orderByDesc('issue_flagged_at');
        }
        $paginator = $query
            ->orderBy('date', 'desc')
            ->orderBy('ot_id', 'desc')
            ->paginate($perPage);

        $data = $paginator->getCollection()->map(function ($r) {
            $emp  = $r->employee;
            $user = $emp?->user;
            $dept = $emp?->department;
            $supervisor = $r->supervisorUser;
            return [
                'ot_id'            => $r->ot_id,
                'employee'         => $user->name ?? 'Unknown',
                'code'             => $emp?->employee_code ?? ('EMP-' . $r->employee_id),
                'dept'             => $dept->department_name ?? 'N/A',
                'supervisor'       => $supervisor->name ?? '—',
                'date'             => Carbon::parse($r->date)->format('Y-m-d'),
                'hours'            => (float) $r->hours,
                'reason'           => $r->reason ?? 'N/A',
                'supervisor_comment' => $r->admin_review_remark ?? '', 
                'status'           => $r->ot_status, // CHANGED: from final_status to ot_status
                'has_issue'        => (bool) $r->flagged_for_admin,
                'issue_remark'     => $r->admin_review_remark ?? '',
            ];
        })->values()->all();

        return response()->json([
            'data' => $data,
            'pagination' => [
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }

    /**
     * Admin approve: sets ot_status = approved.
     * Admin reject: sets ot_status = rejected, admin_comment.
     */
    public function updateStatus(Request $request, OvertimeRecord $overtime)
    {
        $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        // CHANGED: Checking ot_status instead of final_status
        if ($overtime->ot_status !== 'pending') {
            return response()->json(['message' => 'Request already decided or not pending admin.'], 422);
        }

        $action = $request->input('action');
        if ($action === 'approve') {
            $overtime->update([
                // REMOVED final_status line completely
                'ot_status'        => 'approved',
                'approved_by'      => Auth::id(),
                'admin_action_at'  => now(),
            ]);
        } else {
            $overtime->update([
                 // REMOVED final_status line completely
                'ot_status'        => 'rejected',
                'admin_comment'    => $request->input('comment'),
                'admin_action_at'  => now(),
            ]);
        }

        return response()->json(['message' => 'Overtime updated']);
    }
}