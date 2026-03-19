<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = [
            'email'    => $request->email,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();
            $employeeId = $user?->employee?->employee_id ?? null;
            AuditLogService::log(
                AuditLogService::CATEGORY_AUTH,
                'login_success',
                AuditLogService::STATUS_SUCCESS,
                'Login successful',
                ['email' => $request->email],
                $employeeId,
                AuditLogService::SEVERITY_INFO
            );

           $role = strtolower(trim((string) ($user->role ?? '')));
            
            // USE 'intended()' SO IT REMEMBERS THE VERIFICATION EMAIL LINK!
            if (in_array($role, ['admin', 'administrator', 'hr', 'manager'], true)) {
                return redirect()->intended(route('admin.dashboard'));
            }
            if ($role === 'supervisor') {
                return redirect()->intended(route('employee.overtime_inbox.index'));
            }
            if ($role === 'employee') {
                return redirect()->intended(route('employee.dashboard'));
            }
            if ($role === 'applicant') {
                return redirect()->intended(route('applicant.jobs'));
            }
            return redirect()->route('login');
        }

        AuditLogService::log(
            AuditLogService::CATEGORY_AUTH,
            'login_failed',
            AuditLogService::STATUS_FAILED,
            'Failed login attempt',
            ['email' => $request->email],
            null,
            AuditLogService::SEVERITY_WARN
        );

        return back()->withErrors([
            'email' => 'Invalid email or password.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        $employeeId = $user ? ($user->employee->employee_id ?? null) : null;
        AuditLogService::log(
            AuditLogService::CATEGORY_AUTH,
            'logout',
            AuditLogService::STATUS_SUCCESS,
            'Logout',
            null,
            $employeeId,
            AuditLogService::SEVERITY_INFO
        );

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
