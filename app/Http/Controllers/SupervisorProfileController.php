<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Employee;
use App\Models\OvertimeClaim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SupervisorProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $employee = $user ? Employee::with(['department', 'position'])
            ->where('user_id', $user->user_id)
            ->first() : null;

        $subordinateIds = $user ? Employee::where('supervisor_id', $user->user_id)->pluck('employee_id') : collect();
        $stats = [
            'announcements'   => Announcement::count(),
            'subordinates'    => $subordinateIds->count(),
            'pending_ot'      => OvertimeClaim::whereIn('employee_id', $subordinateIds)
                ->where('status', OvertimeClaim::STATUS_SUBMITTED_TO_SUPERVISOR)
                ->count(),
        ];

        return view('supervisor.profile', [
            'user'     => $user,
            'employee' => $employee,
            'stats'    => $stats,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email,' . $user->user_id . ',user_id',
            'phone'    => 'nullable|string|max:20',
            'avatar'   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'password' => 'nullable|min:6|confirmed',
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
            $employee->save();
        }

        return redirect()->route('supervisor.profile')->with('success', 'Profile updated successfully!');
    }
}
