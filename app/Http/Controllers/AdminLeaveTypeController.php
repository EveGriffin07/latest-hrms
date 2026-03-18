<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use Illuminate\Http\Request;

class AdminLeaveTypeController extends Controller
{
    public function index()
    {
        $types = LeaveType::orderBy('leave_name')->get();
        return view('admin.leave_types', compact('types'));
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        $validated = $request->validate([
            'proof_requirement' => ['required', 'string', 'in:none,optional,required'],
            'proof_label'       => ['nullable', 'string', 'max:100'],
        ]);

        $leaveType->update([
            'proof_requirement' => $validated['proof_requirement'],
            'proof_label'       => $validated['proof_label'] ?: null,
        ]);

        return redirect()->route('admin.leave.types')->with('success', 'Leave type "' . $leaveType->leave_name . '" proof settings updated.');
    }
}
