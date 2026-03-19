<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Penalty;
use App\Models\PenaltyRemovalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EmployeePenaltyController extends Controller
{
    /**
     * Show all penalties for the logged-in user (employee or supervisor).
     */
    public function index()
    {
        $user = Auth::user();
        $employee = $user?->employee;

        abort_unless($employee, 403, 'Employee profile not found');

        $penalties = Penalty::with(['activeRemovalRequest'])
            ->where('employee_id', $employee->employee_id)
            // Once admin approves removal, hide the penalty from the main penalties list.
            // The request remains visible in the "Removal Requests" section.
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'removed');
            })
            ->orderBy('assigned_at', 'desc')
            ->orderBy('penalty_id', 'desc')
            ->get();

        $removalRequests = PenaltyRemovalRequest::with(['penalty.attendance'])
            ->where('employee_id', $employee->employee_id)
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->take(20)
            ->get();

        return view('employee.penalties', compact('penalties', 'employee', 'removalRequests'));
    }

    public function submitRemovalRequest(Request $request, Penalty $penalty)
    {
        $employee = Auth::user()?->employee;
        abort_unless($employee, 403, 'Employee profile not found');
        abort_unless((int) $penalty->employee_id === (int) $employee->employee_id, 403, 'Not allowed');

        // Prevent duplicates: one active request per penalty.
        $existing = PenaltyRemovalRequest::where('penalty_id', $penalty->penalty_id)
            ->whereNotIn('status', PenaltyRemovalRequest::terminalStatuses())
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            return redirect()->route('employee.penalties.index')
                ->with('error', 'A removal request is already in progress for this penalty.');
        }

        $data = $request->validate([
            'request_reason' => ['required', 'string', 'min:10', 'max:2000'],
            'employee_note' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ]);

        $path = null;
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('penalty_removal_requests', 'public');
        }

        // penalty_removal_requests.supervisor_id must be users.user_id (for supervisor inbox filter).
        // employees.supervisor_id may be supervisor's employee_id; resolve to user_id.
        $supervisorUserId = null;
        if ($employee->supervisor_id) {
            $rawSupervisorId = (int) $employee->supervisor_id;

            // Primary path: supervisor_id references employees.employee_id.
            $supervisorEmployee = $employee->supervisor;
            $supervisorUserId = $supervisorEmployee?->user_id;

            // Fallbacks for inconsistent legacy data:
            // - supervisor_id might already be users.user_id
            // - or the relationship might not be configured/filled correctly
            if ($supervisorUserId === null) {
                $supervisorEmployeeDirect = Employee::query()->where('employee_id', $rawSupervisorId)->first();
                $supervisorUserId = $supervisorEmployeeDirect?->user_id;
            }
            if ($supervisorUserId === null) {
                $supervisorUserId = $rawSupervisorId;
            }
        }
        if ($supervisorUserId === null && $employee->department && $employee->department->manager_id) {
            $supervisorUserId = $employee->department->manager_id;
        }

        $user = Auth::user();
        $role = strtolower(trim((string) ($user?->role ?? '')));
        $isSupervisorRequester = $role === 'supervisor';
        $autoForwardToAdmin = $isSupervisorRequester || $supervisorUserId === null;

        $created = PenaltyRemovalRequest::create([
            'penalty_id' => $penalty->penalty_id,
            'employee_id' => $employee->employee_id,
            // If requester is a supervisor, skip supervisor review and go straight to admin.
            // If no supervisor could be resolved, auto-forward to admin instead of getting stuck.
            // Keep supervisor_id for traceability when we can.
            'supervisor_id' => $isSupervisorRequester
                ? ($user?->user_id ?? Auth::id())
                : $supervisorUserId,
            'request_reason' => $data['request_reason'],
            'employee_note' => $data['employee_note'] ?? null,
            'attachment_path' => $path,
            // If requester is a supervisor, mark as "submitted_to_admin" (legacy-compatible)
            // so it's clearly distinguishable from team requests forwarded by supervisor.
            'status' => $autoForwardToAdmin
                ? ($isSupervisorRequester ? 'submitted_to_admin' : PenaltyRemovalRequest::STATUS_PENDING_ADMIN)
                : PenaltyRemovalRequest::STATUS_PENDING_SUPERVISOR,
            'submitted_at' => now(),
            'supervisor_reviewed_at' => $autoForwardToAdmin ? now() : null,
            'supervisor_note' => $isSupervisorRequester
                ? 'Auto-forwarded (requester is supervisor)'
                : ($supervisorUserId === null ? 'Auto-forwarded (no supervisor assigned)' : null),
        ]);

        if ($autoForwardToAdmin && strtolower((string) $penalty->status) === 'recorded') {
            $penalty->status = 'under_removal_review';
            $penalty->save();
        }

        return redirect()->route('employee.penalties.index')
            ->with('success', $autoForwardToAdmin
                ? ($isSupervisorRequester ? 'Removal request submitted to admin for review.' : 'Removal request submitted. Pending admin review.')
                : 'Removal request submitted. Pending supervisor review.');
    }

    public function cancelRemovalRequest(PenaltyRemovalRequest $removal)
    {
        $employee = Auth::user()?->employee;
        abort_unless($employee, 403, 'Employee profile not found');
        abort_unless((int) $removal->employee_id === (int) $employee->employee_id, 403, 'Not allowed');

        if (in_array($removal->status, PenaltyRemovalRequest::terminalStatuses(), true)) {
            return redirect()->route('employee.penalties.index');
        }

        $removal->status = PenaltyRemovalRequest::STATUS_CANCELLED_EMPLOYEE;
        $removal->final_decision_at = now();
        $removal->save();

        return redirect()->route('employee.penalties.index')
            ->with('success', 'Removal request cancelled.');
    }
}