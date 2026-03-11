<?php

namespace App\Http\Controllers;

use App\Models\OvertimeRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupervisorOvertimeRecordController extends Controller
{
    /**
     * List OT requests assigned to this supervisor. Tab: Pending (PENDING_SUPERVISOR) or Reviewed (PENDING_ADMIN, REJECTED_SUPERVISOR).
     */
    public function index(Request $request)
    {
        $supervisorId = Auth::id();
        $tab = $request->input('tab', 'pending');

        $query = OvertimeRecord::with(['employee.user', 'employee.department', 'period'])
            ->where('supervisor_id', $supervisorId);

        if ($tab === 'reviewed') {
            $query->whereIn('final_status', [
                OvertimeRecord::FINAL_PENDING_ADMIN,
                OvertimeRecord::FINAL_REJECTED_SUPERVISOR,
            ]);
        } else {
            $query->where('final_status', OvertimeRecord::FINAL_PENDING_SUPERVISOR);
        }

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($qry) use ($q) {
                $qry->whereHas('employee', function ($e) use ($q) {
                    $e->where('employee_code', 'like', "%{$q}%")->orWhere('employee_id', $q);
                })->orWhereHas('employee.user', function ($u) use ($q) {
                    $u->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
                });
            });
        }

        $records = $query->orderBy('date', 'desc')->orderBy('ot_id', 'desc')->paginate(20)->withQueryString();
        $pendingCount = OvertimeRecord::where('supervisor_id', $supervisorId)
            ->where('final_status', OvertimeRecord::FINAL_PENDING_SUPERVISOR)
            ->count();

        return view('supervisor.overtime_requests', compact('records', 'pendingCount', 'tab'));
    }

    /**
     * Approve an OT request. Sets supervisor_decision = APPROVED, final_status = PENDING_ADMIN.
     */
    public function approve(OvertimeRecord $overtime)
    {
        abort_unless($overtime->supervisor_id === Auth::id(), 403, 'You are not the assigned supervisor for this request.');
        if ($overtime->final_status !== OvertimeRecord::FINAL_PENDING_SUPERVISOR) {
            return redirect()->route('employee.overtime_requests.index')->with('error', 'Request already processed.');
        }

        $overtime->update([
            'supervisor_decision'     => OvertimeRecord::SUPERVISOR_APPROVED,
            'final_status'           => OvertimeRecord::FINAL_PENDING_ADMIN,
            'supervisor_approved_by' => Auth::id(),
            'supervisor_action_at'   => now(),
        ]);

        return redirect()->route('employee.overtime_requests.index')->with('success', 'OT request approved. It is now pending admin approval.');
    }

    /**
     * Reject an OT request. Sets supervisor_decision = REJECTED, final_status = REJECTED_SUPERVISOR.
     */
    public function reject(Request $request, OvertimeRecord $overtime)
    {
        abort_unless($overtime->supervisor_id === Auth::id(), 403, 'You are not the assigned supervisor for this request.');
        if ($overtime->final_status !== OvertimeRecord::FINAL_PENDING_SUPERVISOR) {
            return redirect()->route('employee.overtime_requests.index')->with('error', 'Request already processed.');
        }

        $overtime->update([
            'supervisor_decision'   => OvertimeRecord::SUPERVISOR_REJECTED,
            'final_status'          => OvertimeRecord::FINAL_REJECTED_SUPERVISOR,
            'ot_status'             => 'rejected',
            'supervisor_action_at'  => now(),
        ]);

        return redirect()->route('employee.overtime_requests.index')->with('success', 'OT request rejected.');
    }

    /**
     * Mark issue (flag for admin). Only when final_status = PENDING_ADMIN. Supervisor must have approved first.
     */
    public function markIssue(Request $request, OvertimeRecord $overtime)
    {
        abort_unless($overtime->supervisor_id === Auth::id(), 403, 'You are not the assigned supervisor for this request.');
        if ($overtime->final_status !== OvertimeRecord::FINAL_PENDING_ADMIN) {
            return redirect()->route('employee.overtime_requests.index', ['tab' => 'reviewed'])->with('error', 'Can only flag requests that are pending admin.');
        }

        $request->validate(['issue_reason' => ['required', 'string', 'max:500']]);

        $overtime->update([
            'flagged_for_admin'   => true,
            'admin_review_remark' => $request->input('issue_reason'),
            'issue_flagged_by'    => Auth::id(),
            'issue_flagged_at'    => now(),
        ]);

        return redirect()->route('employee.overtime_requests.index', ['tab' => 'reviewed'])->with('success', 'Request marked for admin review.');
    }

    /**
     * Approval summary: list OT requests approved by this supervisor (final_status = PENDING_ADMIN).
     */
    public function approvalSummary(Request $request)
    {
        $supervisorId = Auth::id();
        $query = OvertimeRecord::with(['employee.user', 'employee.department', 'period'])
            ->where('supervisor_id', $supervisorId)
            ->where('final_status', OvertimeRecord::FINAL_PENDING_ADMIN);

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($qry) use ($q) {
                $qry->whereHas('employee', function ($e) use ($q) {
                    $e->where('employee_code', 'like', "%{$q}%")->orWhere('employee_id', $q);
                })->orWhereHas('employee.user', function ($u) use ($q) {
                    $u->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
                });
            });
        }

        $records = $query->orderBy('date', 'desc')->orderBy('ot_id', 'desc')->paginate(20);
        $totalToSend = OvertimeRecord::where('supervisor_id', $supervisorId)
            ->where('final_status', OvertimeRecord::FINAL_PENDING_ADMIN)
            ->count();

        return view('supervisor.overtime_approval_summary', compact('records', 'totalToSend'));
    }

    /**
     * Send summary (legacy): now a no-op; admin sees all PENDING_ADMIN. Redirect to approval summary.
     */
    public function sendSummary(Request $request)
    {
        return redirect()->route('employee.overtime_requests.approval_summary')
            ->with('info', 'Approved requests are already visible to admin. Use "Mark Issue" on reviewed items if admin should review carefully.');
    }
}
