<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Department;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AdminLeaveController extends Controller
{
    public function index()
    {
        $this->ensureLeaveTypesExist();
        $departments = Department::orderBy('department_name')->get();
        $leaveTypes  = LeaveType::orderBy('leave_name')
            ->whereRaw('LOWER(leave_name) != ?', ['unpaid leave'])
            ->get();
        return view('admin.leave_request', compact('departments', 'leaveTypes'));
    }

    public function data(Request $request)
    {
        $request->validate([
            'department' => ['nullable', 'integer', 'exists:departments,department_id'],
            'type'       => ['nullable', 'integer', 'exists:leave_types,leave_type_id'],
            'status'     => ['nullable', 'string', 'in:pending,supervisor_approved,pending_admin,approved,rejected,cancelled'],
            'q'          => ['nullable', 'string', 'max:255'],
        ]);

        $query = LeaveRequest::with(['employee.department', 'employee.user', 'leaveType']);

        if ($request->filled('department')) {
            $deptId = $request->input('department');
            $query->whereHas('employee', fn($q) => $q->where('department_id', $deptId));
        }

        if ($request->filled('type')) {
            $query->where('leave_type_id', $request->input('type'));
        }

        if ($request->filled('status')) {
            $query->where('leave_status', $request->input('status'));
        }

        $statusLabels = [
            'pending' => 'Pending (supervisor)',
            'supervisor_approved' => 'Supervisor approved',
            'pending_admin' => 'Pending admin',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
        ];

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

        $rows = $query->orderBy('start_date', 'desc')->orderBy('leave_request_id', 'desc')->get();

        $data = $rows->map(function ($r) use ($statusLabels) {
            $emp  = $r->employee;
            $user = $emp?->user;
            $dept = $emp?->department;
            $type = $r->leaveType;
            $rawStatus = $r->leave_status ?? 'pending';
            return [
                'id'        => $r->leave_request_id,
                'employee_id' => $emp?->employee_id ?? $r->employee_id,
                'employee'  => $user->name ?? 'Unknown',
                'code'      => $emp?->employee_code ?? ('EMP-' . $r->employee_id),
                'dept_id'   => $dept->department_id ?? null,
                'dept'      => $dept->department_name ?? 'N/A',
                'type_id'   => $type->leave_type_id ?? null,
                'type'      => $type->leave_name ?? 'N/A',
                'start'     => Carbon::parse($r->start_date)->format('Y-m-d'),
                'end'       => Carbon::parse($r->end_date)->format('Y-m-d'),
                'days'      => (int) $r->total_days,
                'reason'    => $r->reason ?? '-',
                'submitted' => $r->created_at ? Carbon::parse($r->created_at)->format('Y-m-d') : null,
                'supervisor' => $r->supervisorApprover?->name ?? $r->supervisorUser?->name,
                'status'    => $statusLabels[$rawStatus] ?? ucfirst($rawStatus),
                'status_raw' => $rawStatus,
            ];
        });

        $summary = [
            'total'    => $rows->count(),
            'pending'  => $rows->whereIn('leave_status', ['pending', 'supervisor_approved', 'pending_admin'])->count(),
            'pending_admin' => $rows->where('leave_status', 'pending_admin')->count(),
            'approved' => $rows->where('leave_status', 'approved')->count(),
            'rejected' => $rows->where('leave_status', 'rejected')->count(),
        ];

        return response()->json(['data' => $data, 'summary' => $summary]);
    }

    public function updateStatus(Request $request, LeaveRequest $leave)
    {
        $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'reason' => ['nullable', 'string', 'max:500'],
            'expected' => ['nullable', 'string', 'in:pending_admin'],
        ]);

        if ($request->filled('expected') && $leave->leave_status !== LeaveRequest::STATUS_PENDING_ADMIN) {
            return response()->json(['message' => 'Already processed'], 409);
        }

        if ($leave->leave_status !== LeaveRequest::STATUS_PENDING_ADMIN) {
            return response()->json(['message' => 'Only requests sent to admin (pending admin) can be approved or rejected here.'], 409);
        }

        $leave->leave_status = $request->input('status');
        $leave->approved_by  = Auth::id();
        $leave->decision_at  = now();

        if ($leave->leave_status === 'rejected') {
            $leave->reject_reason = $request->input('reason') ?: null;
        } else {
            $leave->reject_reason = null;
        }
        $leave->save();

        $typeName = $leave->leaveType->leave_name ?? 'Leave';
        $dates = $leave->start_date . ' to ' . $leave->end_date;
        if ($leave->leave_status === 'approved') {
            AuditLogService::log(
                AuditLogService::CATEGORY_LEAVE,
                'leave_request_approved',
                AuditLogService::STATUS_SUCCESS,
                'Admin approved leave request (' . $typeName . ', ' . $dates . ')',
                ['leave_request_id' => $leave->leave_request_id, 'employee_id' => $leave->employee_id],
                $leave->employee_id,
                AuditLogService::SEVERITY_INFO,
                'Leave',
                $leave->leave_request_id
            );
        } else {
            AuditLogService::log(
                AuditLogService::CATEGORY_LEAVE,
                'leave_request_rejected',
                AuditLogService::STATUS_FAILED,
                'Admin rejected leave request (reason: ' . ($leave->reject_reason ?? 'none') . ')',
                ['leave_request_id' => $leave->leave_request_id, 'employee_id' => $leave->employee_id, 'reason' => $leave->reject_reason],
                $leave->employee_id,
                AuditLogService::SEVERITY_INFO,
                'Leave',
                $leave->leave_request_id
            );
        }

        return response()->json(['message' => 'Leave status updated']);
    }

    /**
     * Ensure default leave types exist so admin/employee views always have options.
     */
    private function ensureLeaveTypesExist(): void
    {
        // Remove deprecated unpaid leave type
        LeaveType::whereRaw('LOWER(leave_name) = ?', ['unpaid leave'])->delete();

        if (LeaveType::count() > 0) {
            return;
        }

        $defaults = [
            ['leave_name' => 'Annual Leave',        'le_description' => 'Paid annual leave',                 'default_days_year' => 14],
            ['leave_name' => 'Sick Leave',          'le_description' => 'Paid sick leave',                   'default_days_year' => 8],
            ['leave_name' => 'Emergency Leave',     'le_description' => 'Short-notice urgent matters',       'default_days_year' => 3],
            ['leave_name' => 'Compassionate Leave', 'le_description' => 'Bereavement / compassionate leave', 'default_days_year' => 5],
            ['leave_name' => 'Maternity Leave',     'le_description' => 'Maternity entitlement',             'default_days_year' => 60],
            ['leave_name' => 'Paternity Leave',     'le_description' => 'Paternity entitlement',             'default_days_year' => 7],
            ['leave_name' => 'Study Leave',         'le_description' => 'Training / exam leave',             'default_days_year' => 5],
        ];

        foreach ($defaults as $row) {
            LeaveType::updateOrCreate(
                ['leave_name' => $row['leave_name']],
                ['le_description' => $row['le_description'], 'default_days_year' => $row['default_days_year']]
            );
        }
    }
}
