<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;

class AdminDepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::withCount('employees')
            ->with('manager:user_id,name,email')
            ->orderBy('department_name')
            ->get();
        return view('admin.departments.index', compact('departments'));
    }

    public function create()
    {
        $managers = User::where('role', 'supervisor')->orderBy('name')->get(['user_id', 'name', 'email', 'role']);
        return view('admin.departments.create', compact('managers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_name' => ['required', 'string', 'max:255'],
            'manager_id' => ['nullable', 'exists:users,user_id'],
        ]);
        Department::create($validated);
        return redirect()->route('admin.departments.index')->with('success', 'Department created.');
    }

    public function edit(Department $department)
    {
        $department->load('manager');
        $managers = User::where('role', 'supervisor')->orderBy('name')->get(['user_id', 'name', 'email', 'role']);
        // Employees: current department first, then others (for "auto show certain department first")
        $employeesInDept = Employee::where('department_id', $department->department_id)->with(['user', 'position', 'department'])->orderBy('employee_code')->get();
        $employeesOther = Employee::where(function ($q) use ($department) {
            $q->whereNull('department_id')->orWhere('department_id', '!=', $department->department_id);
        })->with(['user', 'department', 'position'])->orderBy('employee_code')->get();
        $allEmployees = $employeesInDept->concat($employeesOther);
        return view('admin.departments.edit', compact('department', 'managers', 'allEmployees', 'employeesInDept'));
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'department_name' => ['required', 'string', 'max:255'],
            'manager_id' => ['nullable', 'exists:users,user_id'],
        ]);
        $department->update($validated);
        return redirect()->route('admin.departments.index')->with('success', 'Department updated.');
    }

    /** Bulk assign employees to this department (checkbox selection). Also syncs user.dept_id. */
    public function assignEmployees(Request $request, Department $department)
    {
        $validated = $request->validate([
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,employee_id'],
        ]);
        $ids = $validated['employee_ids'] ?? [];

        // Unassign employees currently in this department but not in the submitted list
        $removed = Employee::where('department_id', $department->department_id)->whereNotIn('employee_id', $ids)->get();
        $removedUserIds = $removed->pluck('user_id')->filter()->values()->all();
        Employee::where('department_id', $department->department_id)->whereNotIn('employee_id', $ids)->update(['department_id' => null]);
        if (!empty($removedUserIds)) {
            User::whereIn('user_id', $removedUserIds)->update(['dept_id' => null]);
        }

        // Set this department for selected employees and sync user.dept_id
        Employee::whereIn('employee_id', $ids)->update(['department_id' => $department->department_id]);
        $userIds = Employee::whereIn('employee_id', $ids)->pluck('user_id')->filter()->values()->all();
        if (!empty($userIds)) {
            User::whereIn('user_id', $userIds)->update(['dept_id' => $department->department_id]);
        }

        return redirect()->back()->with('success', 'Employee assignment updated.');
    }
}
