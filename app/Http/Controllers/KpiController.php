<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Appraisal; 
use App\Models\Department;
use App\Models\Employee;

class KpiController extends Controller
{
    // =========================================================
    // 1. ADMIN: DASHBOARD PAGE
    // =========================================================
    public function index()
    {
        $totalAppraisals = Appraisal::count();
        $pendingManager  = Appraisal::where('status', 'pending_manager')->count();
        $completed       = Appraisal::where('status', 'completed')->count();
        
        // Calculate the company-wide average score
        $avgScoreRaw = Appraisal::where('status', 'completed')->avg('overall_score') ?? 0;
        $avgScore = number_format($avgScoreRaw, 1);

        // Fetch all appraisals to display in the table
        $appraisals = Appraisal::with(['employee.user', 'employee.position', 'evaluator.user'])
                               ->latest()
                               ->get();

        return view('admin.appraisal_admin', compact('totalAppraisals', 'pendingManager', 'completed', 'avgScore', 'appraisals'));
    }

    // =========================================================
    // 2. ADMIN: CREATE FORM (Initiate New Review)
    // =========================================================
    public function create()
    {
        // Fetch employees to populate the dropdowns
        $employees = Employee::with(['user', 'department', 'position'])->get();
        
        return view('admin.appraisal_add_kpi', compact('employees'));
    }

    // =========================================================
    // 3. ADMIN: STORE RECORD (Save to Database)
    // =========================================================
    public function store(Request $request)
    {
        $request->validate([
            'employee_id'   => 'required|exists:employees,employee_id',
            'evaluator_id'  => 'required|exists:employees,employee_id',
            'review_period' => 'required|string|max:255',
        ]);

        // Prevent duplicate reviews for the same period
        $exists = Appraisal::where('employee_id', $request->employee_id)
                           ->where('review_period', $request->review_period)
                           ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'This employee already has an active appraisal for this Review Period.');
        }

        Appraisal::create([
            'employee_id'   => $request->employee_id,
            'evaluator_id'  => $request->evaluator_id,
            'review_period' => $request->review_period,
            'status'        => 'pending_self_eval' // Kicks off the workflow!
        ]);

        return redirect()->route('admin.appraisal')->with('success', 'Appraisal cycle initiated! The employee has been notified to complete their Self-Evaluation.');
    }

    // =========================================================
    // 4. EMPLOYEE: View their Appraisals
    // =========================================================
    public function selfEvaluationList()
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->user_id ?? $user->id)->orderBy('employee_id', 'desc')->first();

        if (!$employee) {
            return redirect()->route('employee.dashboard')->with('error', 'Employee record not found.');
        }

        // Fetch the employee's appraisals (including who their evaluator is)
        $appraisals = Appraisal::where('employee_id', $employee->employee_id)
                           ->with('evaluator.user')
                           ->latest()
                           ->get();

        return view('employee.kpi_self_eval', compact('appraisals'));
    }

    // =========================================================
    // 5. EMPLOYEE: Submit Self-Evaluation Comments
    // =========================================================
    public function submitSelfEval(Request $request, $id)
    {
        $request->validate([
            'employee_comments' => 'required|string|min:10'
        ], [
            'employee_comments.required' => 'You must provide a self-reflection before submitting.'
        ]);

        $appraisal = Appraisal::findOrFail($id);
        
        // Security check: Make sure this appraisal belongs to the logged-in employee
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->user_id ?? $user->id)->orderBy('employee_id', 'desc')->first();
        if ($appraisal->employee_id !== $employee->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        // Save the comments and move the workflow to the Manager!
        $appraisal->update([
            'employee_comments' => $request->employee_comments,
            'status'            => 'pending_manager' 
        ]);

        return redirect()->back()->with('success', 'Self-evaluation submitted successfully! Your manager will now review and score your performance.');
    }

    // =========================================================
    // 6. SUPERVISOR: View Appraisals assigned to them
    // =========================================================
    public function supervisorInbox()
    {
        $user = Auth::user();
        $supervisor = Employee::where('user_id', $user->user_id ?? $user->id)->first();

        if (!$supervisor) {
            return redirect()->route('employee.dashboard')->with('error', 'Supervisor record not found.');
        }

        // Fetch appraisals where this supervisor is the evaluator
        $appraisals = Appraisal::where('evaluator_id', $supervisor->employee_id)
                           ->with('employee.user', 'employee.position')
                           ->orderBy('created_at', 'desc')
                           ->get();

        return view('supervisor.appraisal_inbox', compact('appraisals'));
    }

    // =========================================================
    // 7. SUPERVISOR: Grade the Employee (1-5 Matrix)
    // =========================================================
    public function supervisorScore(Request $request, $id)
    {
        $request->validate([
            'score_attendance'    => 'required|numeric|min:1|max:5',
            'score_teamwork'      => 'required|numeric|min:1|max:5',
            'score_productivity'  => 'required|numeric|min:1|max:5',
            'score_communication' => 'required|numeric|min:1|max:5',
            'manager_comments'    => 'required|string|min:5',
        ]);

        $appraisal = Appraisal::findOrFail($id);

        // Security check: Only the assigned evaluator can score this!
        $user = Auth::user();
        $supervisor = Employee::where('user_id', $user->user_id ?? $user->id)->first();
        if ($appraisal->evaluator_id !== $supervisor->employee_id) {
            abort(403, 'Unauthorized action. You are not the assigned evaluator for this review.');
        }

        // Calculate the overall average score
        $overall = ($request->score_attendance + $request->score_teamwork + $request->score_productivity + $request->score_communication) / 4;

        $appraisal->update([
            'score_attendance'    => $request->score_attendance,
            'score_teamwork'      => $request->score_teamwork,
            'score_productivity'  => $request->score_productivity,
            'score_communication' => $request->score_communication,
            'overall_score'       => $overall,
            'manager_comments'    => $request->manager_comments,
            'status'              => 'completed' 
        ]);

        return redirect()->back()->with('success', 'Appraisal finalized! The employee can now view their final scores.');
    }
}