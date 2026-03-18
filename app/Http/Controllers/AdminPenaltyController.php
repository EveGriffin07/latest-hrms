<?php

namespace App\Http\Controllers;

use App\Models\Penalty;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Department;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AdminPenaltyController extends Controller
{
    private const ALLOWED_ROLES = ['admin', 'administrator', 'hr', 'manager'];

    private function canAccess(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        $role = strtolower(trim((string) ($user->role ?? '')));
        if (in_array($role, self::ALLOWED_ROLES, true)) {
            return true;
        }
        $userId = $user->user_id ?? $user->id ?? null;
        if ($userId === 1 || $userId === '1') {
            return true;
        }
        return false;
    }

    /**
     * Penalty Removal & Tracking page. Admin/HR/Manager only.
     */
    public function index()
    {
        if (! $this->canAccess()) {
            return redirect()->route('admin.dashboard')->with('error', 'Access denied. Only Admin, HR, or Manager can access penalty tracking.');
        }

        // This legacy page is removed from the UI. Keep the route working by redirecting
        // to the new admin penalty removal inbox.
        return redirect()->route('admin.attendance.penalty_removal_requests.index');

        $departments = Department::orderBy('department_name')->get();

        // Detect today's late/absent and ensure there is a corresponding pending Penalty row
        $today = Carbon::today()->format('Y-m-d');
        $attendancePenaltyRows = Attendance::with(['employee'])
            ->whereDate('date', $today)
            ->whereIn('at_status', ['late', 'absent'])
            ->get();

        foreach ($attendancePenaltyRows as $att) {
            if (! $att->employee) {
                continue;
            }
            // Avoid duplicates: one pending penalty per attendance record
            $exists = Penalty::where('attendance_id', $att->attendance_id)->exists();
            if ($exists) {
                continue;
            }

            $reason = $att->at_status === 'late' ? 'Late' : 'Absent';
            $points = $att->at_status === 'late'
                ? max(1, (int) ceil(($att->late_minutes ?? 0) / 15)) // e.g. 1 point per 15 mins
                : 1; // base point for full-day absence

            Penalty::create([
                'employee_id'   => $att->employee_id,
                'attendance_id' => $att->attendance_id,
                'penalty_name'  => $reason,
                'default_amount'=> $points,
                'assigned_at'   => $today,
                'status'        => 'pending',
            ]);
        }

        // For the "Today’s Auto Penalties" helper table we still show the attendance-based view
        $todayAttendancePenalties = Attendance::with(['employee.department', 'employee.user'])
            ->whereDate('date', $today)
            ->whereIn('at_status', ['late', 'absent'])
            ->orderBy('date', 'desc')
            ->get();

        return view('admin.attendance_penalty', compact('departments', 'todayAttendancePenalties'));
    }

    /**
     * Paginated list + summary from same filtered dataset.
     */
    public function data(Request $request)
    {
        if (! $this->canAccess()) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $request->validate([
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
            'status' => ['nullable', 'string', 'in:pending,approved,rejected'],
            'department' => ['nullable', 'integer', 'exists:departments,department_id'],
            'reason' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'in:25,50,100'],
        ]);

        $query = Penalty::with(['employee.department', 'employee.user']);

        if ($request->filled('start')) {
            $query->whereDate('assigned_at', '>=', $request->input('start'));
        }
        if ($request->filled('end')) {
            $query->whereDate('assigned_at', '<=', $request->input('end'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('reason')) {
            $reason = $request->input('reason');
            $query->where('penalty_name', 'like', "%{$reason}%");
        }
        if ($request->filled('department')) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $request->input('department')));
        }
        if ($request->filled('q')) {
            $search = trim($request->input('q'));
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

        $query->orderBy('assigned_at', 'desc')->orderBy('penalty_id', 'desc');

        $perPage = (int) ($request->input('per_page') ?: 25);
        $perPage = in_array($perPage, [25, 50, 100], true) ? $perPage : 25;

        $summary = [
            'total' => (clone $query)->count(),
            'pending' => (int) (clone $query)->where('status', 'pending')->count(),
            'approved' => (int) (clone $query)->where('status', 'approved')->count(),
            'rejected' => (int) (clone $query)->where('status', 'rejected')->count(),
        ];
        $paginator = $query->paginate($perPage);
        $penalties = $paginator->getCollection();

        $data = $penalties->map(function ($p) {
            $emp = $p->employee;
            $dept = $emp?->department;
            $user = $emp?->user;
            return [
                'penalty_id' => $p->penalty_id,
                'pid' => 'P-' . str_pad((string) $p->penalty_id, 4, '0', STR_PAD_LEFT),
                'id' => $emp?->employee_code ?? ('EMP-' . $p->employee_id),
                'name' => $user?->name ?? 'Unknown',
                'dept' => $dept?->department_name ?? 'N/A',
                'reason' => $p->penalty_name,
                'points' => (float) $p->default_amount,
                'date' => Carbon::parse($p->assigned_at)->format('Y-m-d'),
                'status' => ucfirst(strtolower($p->status ?? 'pending')),
                'status_raw' => strtolower($p->status ?? 'pending'),
            ];
        });

        return response()->json([
            'data' => $data,
            'summary' => $summary,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Approve or reject penalty. Requires expected_status=PENDING. Reject requires rejection_remark.
     */
    public function updateStatus(Request $request, Penalty $penalty)
    {
        if (! $this->canAccess()) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'expected_status' => ['nullable', 'string', 'in:pending'],
            'rejection_remark' => ['required_if:action,reject', 'nullable', 'string', 'max:1000'],
        ]);

        $current = strtolower($penalty->status ?? '');
        if ($current !== 'pending') {
            return response()->json(['message' => 'Already processed. This penalty has already been decided.'], 422);
        }

        $action = $request->input('action');
        $oldStatus = $penalty->status;

        if ($action === 'approve') {
            $penalty->status = 'approved';
            $penalty->removed_at = now()->toDateString();
            $penalty->rejection_remark = null;
            $penalty->save();

            AuditLogService::log(
                AuditLogService::CATEGORY_ATTENDANCE,
                'penalty_removal_approved',
                AuditLogService::STATUS_SUCCESS,
                'Admin approved penalty removal: ' . $penalty->penalty_name . ' (Points: ' . $penalty->default_amount . ')',
                [
                    'penalty_id' => $penalty->penalty_id,
                    'employee_id' => $penalty->employee_id,
                    'reason' => $penalty->penalty_name,
                    'points' => $penalty->default_amount,
                    'old_status' => $oldStatus,
                    'new_status' => 'approved',
                ],
                $penalty->employee_id,
                AuditLogService::SEVERITY_INFO
            );

            return response()->json(['message' => 'Penalty approved.']);
        }

        $remark = trim((string) $request->input('rejection_remark', ''));
        if ($remark === '') {
            return response()->json(['message' => 'Rejection reason is required.'], 422);
        }

        $penalty->status = 'rejected';
        $penalty->removed_at = null;
        $penalty->rejection_remark = $remark;
        $penalty->save();

        AuditLogService::log(
            AuditLogService::CATEGORY_ATTENDANCE,
            'penalty_removal_rejected',
            AuditLogService::STATUS_FAILED,
            'Admin rejected penalty removal: ' . $penalty->penalty_name . '. Remark: ' . $remark,
            [
                'penalty_id' => $penalty->penalty_id,
                'employee_id' => $penalty->employee_id,
                'reason' => $penalty->penalty_name,
                'points' => $penalty->default_amount,
                'old_status' => $oldStatus,
                'new_status' => 'rejected',
                'rejection_remark' => $remark,
            ],
            $penalty->employee_id,
            AuditLogService::SEVERITY_INFO
        );

        return response()->json(['message' => 'Penalty rejected.']);
    }
}
