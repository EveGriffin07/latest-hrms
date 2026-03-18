<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Onboarding;
use App\Models\OnboardingTask;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;

class OnboardingController extends Controller
{
    public function index(Request $request)
    {
        $query = Onboarding::with(['employee.user', 'employee.department', 'tasks']);

        if ($request->has('department') && $request->department != 'All Departments') {
            $query->whereHas('employee.department', function($q) use ($request) {
                $q->where('department_name', $request->department);
            });
        }

        if ($request->has('status') && $request->status != 'All Statuses') {
            $status = match($request->status) {
                'In Progress' => 'in_progress',
                'Completed' => 'completed',
                'Pending' => 'pending',
                default => $request->status
            };
            $query->where('status', $status);
        }

        $onboardings = $query->latest()->get();

        $onboardings->each(function($onboarding) {
            $total = $onboarding->tasks->count();
            $completed = $onboarding->tasks->where('is_completed', true)->count();
            
            if ($total > 0) {
                $newStatus = 'in_progress';
                if ($completed === 0) $newStatus = 'pending';
                elseif ($completed === $total) $newStatus = 'completed';
                
                if ($onboarding->status !== $newStatus) {
                    $onboarding->status = $newStatus;
                    $onboarding->saveQuietly();
                }
            }
            $onboarding->progress = $total > 0 ? round(($completed / $total) * 100) : 0;
        });

        $stats = [
            'total'       => $onboardings->count(),
            'in_progress' => $onboardings->where('status', 'in_progress')->count(),
            'completed'   => $onboardings->where('status', 'completed')->count(),
            'pending'     => $onboardings->where('status', 'pending')->count(),
        ];

        return view('admin.onboarding_admin', compact('onboardings', 'stats'));
    }

    public function showChecklist($id)
    {
        $onboarding = Onboarding::with(['employee.user', 'employee.position', 'employee.department', 'tasks'])->findOrFail($id);

        $totalTasks = $onboarding->tasks->count();
        $completedTasks = $onboarding->tasks->where('is_completed', true)->count();
        $pendingTasks = $totalTasks - $completedTasks;
        $overdueTasks = $onboarding->tasks->where('is_completed', false)->where('due_date', '<', now())->count();
        $onboarding->progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        return view('admin.onboarding_checklist', compact('onboarding', 'totalTasks', 'completedTasks', 'pendingTasks', 'overdueTasks'));
    }

    public function create()
    {
        // Fetch employees without active onboarding
        $employees = Employee::with(['user', 'department', 'position'])
            ->whereDoesntHave('onboarding', function($query) {
                $query->whereIn('status', ['pending', 'in_progress']);
            })->get();

        // Fetch active managers/supervisors to populate the dropdown
        $supervisors = Employee::with(['user', 'department', 'position'])
            ->whereHas('position', function($q) {
                $q->where('is_manager', 1);
            })->get(); 

        return view('admin.onboarding_add', compact('employees', 'supervisors'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id'   => 'required|exists:employees,employee_id',
            'supervisor_id' => 'required|exists:employees,employee_id', // NEW VALIDATION
            'startDate'     => 'required|date',
            'deadline'      => 'required|date|after_or_equal:startDate',
        ]);

        $employee = Employee::findOrFail($request->employee_id);

        // === OFFICIALLY SET THE REPORTING STRUCTURE IN THE DB ===
        $employee->update([
            'supervisor_id' => $request->supervisor_id
        ]);
        
        // Reload to get the new supervisor's details for the task generation
        $employee->load('supervisor.user');

        $onboarding = Onboarding::create([
            'employee_id' => $employee->employee_id,
            'assigned_by' => Auth::id(),
            'start_date'  => $request->startDate,
            'end_date'    => $request->deadline,
            'status'      => 'pending' 
        ]);

        if ($request->has('assets')) {
            foreach ($request->assets as $asset) {
                OnboardingTask::create([
                    'onboarding_id' => $onboarding->onboarding_id,
                    'task_name'     => 'Provision Company ' . ucfirst($asset),
                    'category'      => 'IT & Assets',
                    'is_completed'  => false,
                    'due_date'      => $request->startDate,
                ]);
            }
        }

        if ($request->has('access')) {
            foreach ($request->access as $system) {
                OnboardingTask::create([
                    'onboarding_id' => $onboarding->onboarding_id,
                    'task_name'     => 'Create Account: ' . $system,
                    'category'      => 'IT & Security',
                    'is_completed'  => false,
                    'due_date'      => $request->startDate,
                ]);
            }
        }

        // Auto-Generate Supervisor Meeting Task using the newly assigned Supervisor
        $supervisorName = $employee->supervisor ? $employee->supervisor->user->name : 'Department Manager';
        OnboardingTask::create([
            'onboarding_id' => $onboarding->onboarding_id,
            'task_name'     => 'Introductory Meeting with Supervisor: ' . $supervisorName,
            'category'      => 'Culture & Team',
            'is_completed'  => false,
            'due_date'      => $request->deadline,
        ]);

        if ($request->has('default_tasks')) {
            foreach ($request->default_tasks as $taskCode) {
                $taskName = match($taskCode) {
                    'documents'   => 'Collect Signed HR Documents & ID',
                    'orientation' => 'Complete Company Welcome Orientation',
                    'policies'    => 'Review Employee Handbook & Policies',
                    default       => 'General Task'
                };

                OnboardingTask::create([
                    'onboarding_id' => $onboarding->onboarding_id,
                    'task_name'     => $taskName,
                    'category'      => 'HR & Compliance',
                    'is_completed'  => false,
                    'due_date'      => $request->deadline,
                ]);
            }
        }

        if ($request->filled('customTask')) {
            OnboardingTask::create([
                'onboarding_id' => $onboarding->onboarding_id,
                'task_name'     => 'Custom: ' . substr($request->customTask, 0, 30) . '...',
                'remarks'       => $request->customTask,
                'category'      => 'Manager Task',
                'is_completed'  => false,
                'due_date'      => $request->deadline,
            ]);
        }

        return redirect()->route('admin.onboarding')->with('success', 'Reporting structure updated and checklist generated successfully!');
    }
}