<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use App\Models\Onboarding;
use App\Models\OnboardingTask;

class EmployeeOnboardingController extends Controller
{
    // =========================================================
    // FOR THE EMPLOYEE: Viewing their own checklist
    // =========================================================
    public function index()
    {
        $user = Auth::user();

        // Fetch the LATEST employee record for the logged-in user
        $employee = Employee::where('user_id', $user->user_id ?? $user->id)
                            ->orderBy('employee_id', 'desc')
                            ->first();

        if (!$employee) {
            return redirect()->route('employee.dashboard')->with('error', 'Employee record not found.');
        }

        // Fetch the onboarding and group the tasks by Category for the UI
        $onboarding = Onboarding::where('employee_id', $employee->employee_id)
                            ->with('tasks')
                            ->first();

        // Group tasks so we can display them in neat sections (IT, HR, Culture)
        $groupedTasks = $onboarding ? $onboarding->tasks->groupBy('category') : collect();

        return view('employee.onboarding_view', compact('onboarding', 'groupedTasks'));
    }

    // =========================================================
    // FOR THE EMPLOYEE: Marking their own task as complete
    // =========================================================
    public function completeTask(Request $request, $id)
    {
        $task = OnboardingTask::with('onboarding.employee')->findOrFail($id);
        $user = Auth::user();
        
        $currentEmployee = Employee::where('user_id', $user->user_id ?? $user->id)
                            ->orderBy('employee_id', 'desc')
                            ->first();

        // SECURITY CHECK
        if ($task->onboarding->employee_id !== $currentEmployee->employee_id) {
            return redirect()->back()->with('error', 'Unauthorized: You can only complete your own onboarding tasks.');
        }

        // === NEW: HANDLE FILE UPLOAD ===
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $filename = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
            
            // Store the file in 'storage/app/public/onboarding_docs'
            $path = $file->storeAs('onboarding_docs', $filename, 'public');
            
            // Save the path to the database
            $task->file_path = $path; 
        }

        // 1. Mark the specific task as completed
        $task->is_completed = true;
        $task->save();

        // 2. CHECK: Are all tasks for this onboarding done?
        $parentOnboarding = $task->onboarding;
        $totalTasks = $parentOnboarding->tasks()->count();
        $completedTasks = $parentOnboarding->tasks()->where('is_completed', true)->count();

        // 3. Dynamically update the overall status
        if ($totalTasks === $completedTasks) {
            $parentOnboarding->update(['status' => 'completed']);
            $message = 'Task completed! Congratulations, you have finished your onboarding journey!';
        } else {
            $parentOnboarding->update(['status' => 'in_progress']);
            $message = 'Task checked off successfully!';
        }

        return redirect()->back()->with('success', $message);
    }
}