<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth; // <--- ADDED: Required for Auth::id()
use App\Models\ApplicantProfile;
use App\Models\Application;

class AdminEmployeeController extends Controller
{
    /**
     * Display the employee overview with filters and counts.
     */
    public function index(Request $request)
    {
        $search       = $request->input('q');
        $departmentId = $request->input('department');
        $status       = $request->input('status');

        $query = Employee::with(['user', 'department', 'position']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                })->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        if ($status) {
            $query->where('employee_status', $status);
        }

        $perPage = (int) $request->input('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        $employees = $query
            ->orderBy('hire_date', 'desc')
            ->paginate($perPage, ['*'], 'page')
            ->withQueryString();

        // Attach service snapshot for each employee for UI badges
        $employees->getCollection()->transform(function ($emp) {
            $emp->service_snapshot = $emp->serviceSnapshot();
            return $emp;
        });

        $totalEmployees   = Employee::count();
        $activeEmployees  = Employee::where('employee_status', 'active')->count();
        $departmentsCount = Department::count();

        $today = now()->toDateString();
        $onLeave = LeaveRequest::where('leave_status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->count();

        $departments = Department::orderBy('department_name')->get();

        $tab = $request->input('tab', 'employees');

        // Applicants (shared filter: q + status; department not applied due to job text field)
        $allApplicants = ApplicantProfile::with('user')
            ->orderBy('full_name')
            ->get();

        $latestApplications = $this->latestApplicationsByApplicant($allApplicants->pluck('applicant_id'));

        $filteredApplicants = $allApplicants->map(function ($applicant) use ($latestApplications) {
            $applicant->latestApplication = $latestApplications->get($applicant->applicant_id);
            return $applicant;
        })->filter(function ($applicant) use ($search, $status) {
            // shared search
            if ($search) {
                $haystack = strtolower(
                    ($applicant->full_name ?? '') . ' ' .
                    ($applicant->first_name ?? '') . ' ' .
                    ($applicant->last_name ?? '') . ' ' .
                    (optional($applicant->user)->email ?? '') . ' ' .
                    ($applicant->phone ?? '')
                );
                if (!str_contains($haystack, strtolower($search))) {
                    return false;
                }
            }
            // status for applicants matches latest application stage if present
            if ($status) {
                $stage = strtolower($applicant->latestApplication->app_stage ?? '');
                if ($stage !== strtolower($status)) {
                    return false;
                }
            }
            return true;
        })->values();

        $totalApplicants    = ApplicantProfile::count();
        $approvedApplicants = $latestApplications->filter(
            fn ($app) => ($app->app_stage === 'Approved')
        )->count();

        $perPage = 10;
        $page = request()->input('page_applicants', 1);
        $applicantsPage = new \Illuminate\Pagination\LengthAwarePaginator(
            $filteredApplicants->forPage($page, $perPage)->values(),
            $filteredApplicants->count(),
            $perPage,
            $page,
            ['path' => url()->current(), 'pageName' => 'page_applicants']
        );
        $applicants = $applicantsPage->getCollection();

        return view('admin.employee_list', compact(
            'employees',
            'totalEmployees',
            'activeEmployees',
            'departmentsCount',
            'onLeave',
            'departments',
            'search',
            'departmentId',
            'status',
            'applicants',
            'applicantsPage',
            'totalApplicants',
            'approvedApplicants',
            'tab'
        ));
    }

    /**
     * Show the create employee form.
     */
    public function create()
    {
        $departments = Department::orderBy('department_name')->get();
        $positions   = Position::orderBy('position_name')->get();
        $latestApplications = $this->latestApplicationsByApplicant();

        $applicants  = ApplicantProfile::with('user')
            ->orderBy('full_name')
            ->get()
            ->map(function ($applicant) use ($latestApplications) {
                $applicant->latestApplication = $latestApplications->get($applicant->applicant_id);
                return $applicant;
            });

        // =========================================================
        // ✨ NEW: Fetch the list of Supervisors (where is_manager = 1)
        // =========================================================
        $supervisors = Employee::with(['user', 'position', 'department'])
                    ->whereHas('position', function($q) {
                        $q->where('is_manager', 1);
                    })->get();

        $totalEmployees   = Employee::count();
        $activeEmployees  = Employee::where('employee_status', 'active')->count();
        $departmentsCount = Department::count();
        $today = now()->toDateString();
        $onLeave = LeaveRequest::where('leave_status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->count();
        $totalApplicants    = ApplicantProfile::count();
        $approvedApplicants = $latestApplications->filter(fn ($a) => $a->app_stage === 'Approved')->count();

        return view('admin.employee_add', compact(
            'departments',
            'positions',
            'applicants',
            'supervisors', // <--- ADDED: Pass the supervisors to the Blade view
            'totalEmployees',
            'activeEmployees',
            'departmentsCount',
            'onLeave',
            'totalApplicants',
            'approvedApplicants'
        ));
    }

    /**
     * Store a newly created employee + user.
     */
    public function store(Request $request)
    {
        $applicant = null;
        $latestApplication = null;
        if ($request->filled('applicant_id')) {
            $applicant = ApplicantProfile::with('user')->findOrFail($request->input('applicant_id'));

            $latestApplication = Application::where('applicant_id', $applicant->applicant_id)
                ->latest()
                ->first();
        }

        $emailRule = Rule::unique('users', 'email');
        if ($applicant && $applicant->user) {
            $emailRule = $emailRule->ignore($applicant->user->user_id, 'user_id');
        }

        $validated = $request->validate([
            'applicant_id'   => ['nullable', Rule::exists('applicant_profiles', 'applicant_id')],
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'max:255', $emailRule],
            'phone'          => ['nullable', 'string', 'max:50'],
            'department_id'  => ['required', Rule::exists('departments', 'department_id')],
            'position_id'    => ['required', Rule::exists('positions', 'position_id')],
            'hire_date'      => ['required', 'date'],
            'employee_status'=> ['required', Rule::in(['active', 'inactive', 'terminated'])],
            'address'        => ['nullable', 'string'],
            'base_salary'    => ['required', 'numeric', 'min:0'],
        ]);

        $usingExistingUser = false;
        $tempPassword = null;

        if ($applicant && $applicant->user) {
            $user = $applicant->user;
            $user->name = $validated['name'] ?? $applicant->full_name;
            $user->email = $validated['email'] ?? $applicant->email;
            $user->role = 'employee';

            // Carry over avatar from applicant profile when available
            if ($applicant->avatar_path && empty($user->avatar_path)) {
                $user->avatar_path = $applicant->avatar_path;
            }
            $user->save();
            $usingExistingUser = true;
        } else {
            $tempPassword = Str::random(10);
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => Hash::make($tempPassword),
                'role'     => 'employee',
            ]);
        }

        $employee = Employee::create([
            'user_id'         => $user->user_id,
            'department_id'   => $validated['department_id'],
            'supervisor_id'   => $request->input('supervisor_id'), // <--- Already correctly added by you!
            'position_id'     => $validated['position_id'],
            'employee_code'   => $this->generateEmployeeCode(),
            'employee_status' => $validated['employee_status'],
            'hire_date'       => $validated['hire_date'],
            'base_salary'     => $validated['base_salary'],
            'phone'           => $validated['phone'] ?? ($applicant?->phone ?? null),
            'address'         => $validated['address'] ?? ($applicant?->location ?? null),
        ]);


        if ($latestApplication) {
            $latestApplication->app_stage = 'Hired';
            $latestApplication->save();
        }

        $message = $usingExistingUser
            ? 'Employee created from applicant profile.'
            : 'Employee created. Temporary password: ' . $tempPassword;

        return redirect()
            ->route('admin.employee.list')
            ->with('success', $message);
    }

    /**
     * Generate next employee code in ascending order: EMP-0001, EMP-0002, ...
     * Uses the numeric part of existing employee_code values so the sequence
     * is always strictly ascending regardless of employee_id.
     */
    private function generateEmployeeCode(): string
    {
        $codes = Employee::query()
            ->where('employee_code', 'like', 'EMP-%')
            ->pluck('employee_code');

        $maxNum = $codes->map(function ($code) {
            return preg_match('/^EMP-(\d+)$/', $code, $m) ? (int) $m[1] : 0;
        })->push(0)->max();

        $next = $maxNum + 1;
        return 'EMP-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
    /**
     * Show a single employee profile.
     */
    public function show($employeeId)
    {
        $employee = Employee::with(['user', 'department', 'position'])->findOrFail($employeeId);

        return view('admin.employee_profile', compact('employee'));
    }

    /**
     * Get the latest application per applicant (keyed by applicant_id).
     */
    private function latestApplicationsByApplicant($applicantIds = null)
    {
        $query = Application::with('job')
            ->select('applications.*')
            ->whereIn('application_id', function ($sub) use ($applicantIds) {
                $sub->selectRaw('MAX(application_id)')
                    ->from('applications');
                if ($applicantIds) {
                    $sub->whereIn('applicant_id', $applicantIds);
                }
                $sub->groupBy('applicant_id');
            });

        if ($applicantIds) {
            $query->whereIn('applicant_id', $applicantIds);
        }

        return $query->get()->keyBy('applicant_id');
    }
}