<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use App\Models\TrainingEnrollment;
use App\Models\TrainingProgram;
use Carbon\Carbon;

class EmployeeTrainingController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $employee = Employee::where('user_id', $user->user_id)->first();

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }

        $enrollments = TrainingEnrollment::where('employee_id', $employee->employee_id)
                        ->with('training') 
                        ->get();

        $today = Carbon::today();

        // BUG FIXED: It is only upcoming if the status is enrolled AND the training hasn't ended yet
        $upcoming = $enrollments->filter(function ($enrollment) use ($today) {
            $endDate = Carbon::parse($enrollment->training->end_date);
            return $enrollment->completion_status === 'enrolled' && $endDate->greaterThanOrEqualTo($today);
        });

        // BUG FIXED: It is history if it's explicitly completed/failed OR if the end date has passed
        $history = $enrollments->filter(function ($enrollment) use ($today) {
            $endDate = Carbon::parse($enrollment->training->end_date);
            return in_array($enrollment->completion_status, ['completed', 'failed']) || $endDate->lessThan($today);
        });

        return view('employee.training_my_plans', compact('upcoming', 'history'));
    }

    public function show($id)
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->user_id)->first();

        $enrollment = TrainingEnrollment::where('training_id', $id)
                        ->where('employee_id', $employee->employee_id)
                        ->with('training')
                        ->firstOrFail();

        return view('employee.training_show', compact('enrollment'));
    }

    public function scanQr($token)
    {
        $training = TrainingProgram::where('qr_token', $token)->first();

        if (!$training) {
            return redirect()->route('employee.training.index') 
                             ->with('error', 'Invalid or expired QR code.');
        }

        $user = Auth::user();
        $employee = Employee::where('user_id', $user->user_id)->first();

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }

        $enrollment = TrainingEnrollment::where('training_id', $training->training_id)
                        ->where('employee_id', $employee->employee_id)
                        ->first();

        if (!$enrollment) {
            return redirect()->route('employee.training.index') 
                             ->with('error', 'Access Denied: You are not enrolled in ' . $training->training_name);
        }

        if ($enrollment->completion_status === 'completed') {
            return redirect()->route('employee.training.index') 
                             ->with('success', 'Your attendance for ' . $training->training_name . ' is already recorded!');
        }

        $enrollment->update([
            'completion_status' => 'completed',
            'remarks'           => 'Attended (Verified via QR Scan)'
        ]);

        return redirect()->route('employee.training.index') 
                         ->with('success', 'Attendance recorded successfully for ' . $training->training_name . '!');
    }
}