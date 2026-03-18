<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OvertimeClaim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class EmployeeProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $employee = $user ? Employee::with(['department.manager', 'position', 'supervisor'])
            ->where('user_id', $user->user_id)
            ->first() : null;

        $stats = [
            'announcements' => Announcement::count(),
            'leave_requests' => $employee ? LeaveRequest::where('employee_id', $employee->employee_id)->count() : 0,
            'ot_claims'      => $employee ? OvertimeClaim::where('employee_id', $employee->employee_id)->count() : 0,
        ];

        return view('employee.profile', [
            'user'     => $user,
            'employee' => $employee,
            'stats'    => $stats,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $bankCodes = array_keys(config('hrms.banks', []));
        $accountTypes = array_keys(config('hrms.bank_account_types', []));

        $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|max:255|unique:users,email,' . $user->user_id . ',user_id',
            'phone'                 => 'nullable|string|max:20',
            'avatar'                => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'password'              => 'nullable|min:6|confirmed',
            'bank_code'             => ['nullable', 'string', 'max:20', 'in:' . implode(',', $bankCodes)],
            'bank_account_holder'   => ['nullable', 'string', 'max:120', 'regex:/^[\pL\pM\s\-\'\\.]+$/u'],
            'bank_account_number'   => ['nullable', 'string', 'max:50', 'regex:/^[0-9\s]+$/'],
            'account_type'          => ['nullable', 'string', 'max:20', 'in:' . implode(',', $accountTypes)],
        ], [
            'bank_account_holder.regex' => 'Account holder name may only contain letters, spaces, hyphens, and apostrophes.',
            'bank_account_number.regex' => 'Account number may only contain digits and spaces.',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path && Storage::exists('public/' . $user->avatar_path)) {
                Storage::delete('public/' . $user->avatar_path);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar_path = $path;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        $employee = Employee::where('user_id', $user->user_id)->first();
        if ($employee) {
            $employee->phone = $request->phone;
            $employee->bank_code = $request->input('bank_code') ?: null;
            $employee->bank_name = $employee->bank_code && config('hrms.banks')
                ? (config('hrms.banks')[$employee->bank_code] ?? null)
                : null;
            $employee->bank_account_holder = $request->input('bank_account_holder') ?: null;
            $employee->bank_account_number = $request->filled('bank_account_number')
                ? preg_replace('/\s+/', '', $request->input('bank_account_number'))
                : null;
            $employee->account_type = $request->input('account_type') ?: null;
            $employee->save();
        }

        return redirect()->route('employee.profile')->with('success', 'Profile updated successfully!');
    }
}
