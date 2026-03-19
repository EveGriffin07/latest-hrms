<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ApplicantProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;

class RegisterController extends Controller
{
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        // 1. Validate (Now strictly blocks numbers and symbols in the Name)
        $request->validate([
            'name'     => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\-\.\']+$/'],
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ], [
            // Custom error message so the user knows exactly why it failed
            'name.regex' => 'Your name may only contain letters, spaces, hyphens, and apostrophes. Numbers are not allowed.'
        ]);

        // 2. Create User
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'applicant',
        ]);

        // 3. Create Profile
        ApplicantProfile::create([
            'user_id'   => $user->user_id,
            'full_name' => $request->name,
            'email'     => $request->email,
        ]);

        // 4. Trigger the verification email
        event(new Registered($user));

        // 5. Login
        Auth::login($user);

        // Redirect to a notice page instead of directly to jobs
        return redirect()->route('verification.notice')
                         ->with('success', 'Registration successful! Please check your email to verify your account.');
    }
}