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

        // Group by first letter of department name for display; only last in each group gets "Create same" button
        $grouped = $departments->groupBy(function (Department $d) {
            $first = mb_strtoupper(mb_substr($d->department_name, 0, 1));
            return ctype_alpha($first) ? $first : '#';
        })->map(fn ($group) => $group->values())->sortKeys();

        return view('admin.departments.index', compact('departments', 'grouped'));
    }

    public function create(Request $request)
    {
        $managers = User::where('role', 'supervisor')->orderBy('name')->get(['user_id', 'name', 'email', 'role']);
        $duplicateFrom = null;
        if ($request->has('from')) {
            $duplicateFrom = Department::find($request->input('from'));
        }
        return view('admin.departments.create', compact('managers', 'duplicateFrom'));
    }

    /** Create a new department with same base name + number (e.g. Finance → Finance1), then redirect to edit to select supervisor and employees. */
    public function duplicate(Department $department)
    {
        $baseName = preg_replace('/\d+$/', '', $department->department_name);
        $baseName = trim($baseName) ?: $department->department_name;

        $maxNum = Department::query()
            ->where('department_name', $baseName)
            ->orWhere('department_name', 'like', $baseName . '%')
            ->get()
            ->map(function (Department $d) use ($baseName) {
                if ($d->department_name === $baseName) {
                    return 0;
                }
                $suffix = substr($d->department_name, strlen($baseName));
                return preg_match('/^\d+$/', $suffix) ? (int) $suffix : 0;
            })
            ->push(0)
            ->max();

        $newName = $baseName . ((string) ($maxNum + 1));
        $newDept = Department::create([
            'department_name' => $newName,
            'manager_id' => null,
        ]);

        return redirect()->route('admin.departments.edit', $newDept)
            ->with('success', 'Department "' . $newName . '" created. Assign a supervisor and employees below.');
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
        $employeesInDept = Employee::query()
            ->where('department_id', $department->department_id)
            // Do not allow supervisors to appear in the employee selection list.
            ->whereHas('user', fn ($q) => $q->where('role', '!=', 'supervisor'))
            ->with(['user', 'position', 'department'])
            ->orderBy('employee_code')
            ->get();
        $employeesOther = Employee::where(function ($q) use ($department) {
            $q->whereNull('department_id')->orWhere('department_id', '!=', $department->department_id);
        })
            // Do not allow supervisors to appear in the employee selection list.
            ->whereHas('user', fn ($q) => $q->where('role', '!=', 'supervisor'))
            ->with(['user', 'department', 'position'])
            ->orderBy('employee_code')
            ->get();
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

        // Never assign/unassign supervisors as employees via this UI.
        // (They shouldn't appear in the checkbox list and should not affect department sync.)
        $ids = Employee::query()
            ->whereIn('employee_id', $ids)
            ->whereHas('user', fn ($q) => $q->where('role', '!=', 'supervisor'))
            ->pluck('employee_id')
            ->values()
            ->all();

        $supervisorEmployeeIds = Employee::query()
            ->where('department_id', $department->department_id)
            ->whereHas('user', fn ($q) => $q->where('role', 'supervisor'))
            ->pluck('employee_id')
            ->values()
            ->all();

        // employees.department_id is NOT NULL, so when we "remove" employees from this department,
        // move them to a safe fallback department instead of setting it to null.
        $fallbackDeptId = Department::query()
            ->where('department_name', 'General')
            ->value('department_id');
        if ($fallbackDeptId === null) {
            // Fallback: keep them in the current department if no "General" exists.
            $fallbackDeptId = $department->department_id;
        }

        // Unassign employees currently in this department but not in the submitted list
        $keepIds = array_values(array_unique(array_merge($ids, $supervisorEmployeeIds)));

        $removed = Employee::query()
            ->where('department_id', $department->department_id)
            ->whereNotIn('employee_id', $keepIds)
            ->whereHas('user', fn ($q) => $q->where('role', '!=', 'supervisor'))
            ->get();
        $removedUserIds = $removed->pluck('user_id')->filter()->values()->all();
        if (!empty($removed->pluck('employee_id')->values()->all())) {
            Employee::query()
                ->where('department_id', $department->department_id)
                ->whereNotIn('employee_id', $keepIds)
                ->whereHas('user', fn ($q) => $q->where('role', '!=', 'supervisor'))
                ->update(['department_id' => $fallbackDeptId]);
        }
        if (!empty($removedUserIds)) {
            User::whereIn('user_id', $removedUserIds)->update(['dept_id' => $fallbackDeptId]);
        }

        // Set this department for selected employees and sync user.dept_id
        Employee::whereIn('employee_id', $ids)->update(['department_id' => $department->department_id]);
        $userIds = Employee::whereIn('employee_id', $ids)->pluck('user_id')->filter()->values()->all();
        if (!empty($userIds)) {
            User::whereIn('user_id', $userIds)->update(['dept_id' => $department->department_id]);
        }

        return redirect()->back()->with('success', 'Employee assignment updated.');
    }

    /** Delete department. Unassigns all employees (and user dept_id) first, then deletes the department. */
    public function destroy(Department $department)
    {
        $employeeIds = Employee::where('department_id', $department->department_id)->pluck('employee_id');
        $userIds = Employee::whereIn('employee_id', $employeeIds)->pluck('user_id')->filter()->values()->all();

        Employee::where('department_id', $department->department_id)->update(['department_id' => null]);
        if (! empty($userIds)) {
            User::whereIn('user_id', $userIds)->update(['dept_id' => null]);
        }

        $department->delete();
        return redirect()->route('admin.departments.index')->with('success', 'Department deleted. Employees have been unassigned from this department.');
    }
}
