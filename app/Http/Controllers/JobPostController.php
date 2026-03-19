<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobPost;
use App\Models\Department;
use App\Models\Position; 
use Illuminate\Support\Facades\Auth;
use App\Models\JobRequisition;

class JobPostController extends Controller
{
    // 1. List all active jobs & Pending Requisitions
    public function index()
    {

        JobPost::where('job_status', 'Open')
               ->whereDate('closing_date', '<', now()->toDateString())
               ->update(['job_status' => 'Closed']);
               
        // Get all job posts
        $jobPosts = JobPost::latest()->get(); 
        
        // Fetch Pending Hiring Requests from Managers
        $requisitions = JobRequisition::with(['department', 'requester.user'])
                        ->where('status', 'Pending')
                        ->latest()
                        ->get();

        // Fetch departments for the Add/Edit Modal
        $departments = Department::all();

        return view('admin.recruitment_admin', compact('jobPosts', 'requisitions', 'departments'));
    }

    // === REQUISITION APPROVAL LOGIC ===
    public function approveRequisition(Request $request, $id)
    {
        $requisition = JobRequisition::with('department')->findOrFail($id);
        
        // 1. Change Status to Approved
        $requisition->update(['status' => 'Approved']);

        // 2. Automatically create a Draft Job Post based on the manager's request
        JobPost::create([
            'requisition_id'  => $requisition->requisition_id,
            'job_title'       => $requisition->job_title,
            'job_type'        => $requisition->employment_type,
            'department'      => $requisition->department->department_name ?? 'General',
            'location'        => 'TBD', // HR will edit this later
            'salary_range'    => 'TBD',
            'job_description' => "Requested by Manager.\nJustification: " . $requisition->justification,
            'requirements'    => 'To be filled by HR',
            'closing_date'    => now()->addDays(30), // Default to 30 days from now
            'job_status'      => 'Draft', // Set as Draft so it doesn't go public immediately
            'posted_by'       => Auth::id() ?? 1,
        ]);

        return redirect()->back()->with('success', 'Hiring request approved! A draft job post has been automatically created.');
    }

    public function rejectRequisition($id)
    {
        $requisition = JobRequisition::findOrFail($id);
        $requisition->update(['status' => 'Rejected']);
        
        return redirect()->back()->with('success', 'Hiring request rejected.');
    }

    // =======================================

    // 2. Show the Create Form
    public function create()
    {
        $departments = Department::all(); 
        return view('admin.recruitment_add', compact('departments'));
    }

    // 3. Store the new Job Post (AND Update Position Manager Status)
    public function store(Request $request)
    {
        // === NEW: STRICT VALIDATION APPLIED HERE ===
        $request->validate([
            'job_title' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s\-\.,&+\/]+$/'],
            'job_type' => 'required|string',
            'department' => 'required|string',
            'location' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s\-\.,&]+$/'],
            'closing_date' => 'required|date|after_or_equal:today',
            'salary_range' => ['nullable', 'string', 'max:100', 'regex:/^[0-9\s\-\.,]+$/'],
            'job_description' => 'required|string',
            'requirements' => 'required|string',
        ], [
            'closing_date.after_or_equal' => 'The closing date cannot be set in the past.',
            'job_title.regex' => 'The job title contains invalid symbols.',
            'location.regex' => 'The location contains invalid symbols.',
            'salary_range.regex' => 'Salary range may only contain numbers, spaces, commas, dots, and hyphens.',
        ]);

        // A. Find the Department ID (since form sends the Name)
        $dept = Department::where('department_name', $request->department)->firstOrFail();

        // Check if the HR admin ticked the box (returns true/false)
        $isManagerRole = $request->has('is_manager');

        // B. Update or Create the Position with the new Description and Manager flag
        Position::updateOrCreate(
            [
                'position_name' => $request->job_title,      // Search Condition 1
                'department_id' => $dept->department_id      // Search Condition 2
            ],
            [
                'pos_description' => $request->job_description,
                'is_manager'      => $isManagerRole         // Saved here!
            ]
        );

        // C. Create the Job Post
        JobPost::create([
            'job_title' => $request->job_title,
            'job_type' => $request->job_type,
            'department' => $request->department,
            'location' => $request->location,
            'closing_date' => $request->closing_date,
            'salary_range' => $request->salary_range,
            'job_description' => $request->job_description,
            'requirements' => $request->requirements,
            'job_status' => 'Open',
            'posted_by' => Auth::id() ?? 1,
        ]);

        return redirect()->route('admin.recruitment.index')
                         ->with('success', 'Job Posted and Position updated successfully!');
    }

    // 4. Show Edit Form
    public function edit($id)
    {
        $job = JobPost::findOrFail($id);
        $departments = Department::all();
        return view('admin.recruitment_add', compact('job', 'departments')); 
    }

    // 5. Save the Changes (Update)
    public function update(Request $request, $id)
    {
        // === NEW: STRICT VALIDATION APPLIED HERE ===
        $request->validate([
            'job_title' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s\-\.,&+\/]+$/'],
            'department' => 'required|string|max:255',
            'job_type' => 'required|string',
            'location' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s\-\.,&]+$/'],
            'closing_date' => 'required|date', // Allowed to be in the past IF they are just editing a typo on a closed job
            'salary_range' => ['nullable', 'string', 'max:100', 'regex:/^[0-9\s\-\.,]+$/'],
            'job_description' => 'required|string',
            'requirements' => 'required|string',
            'job_status' => 'required|string',
        ], [
            'job_title.regex' => 'The job title contains invalid symbols.',
            'location.regex' => 'The location contains invalid symbols.',
            'salary_range.regex' => 'Salary range may only contain numbers, spaces, commas, dots, and hyphens.',
        ]);

        $job = JobPost::findOrFail($id);

        $dept = Department::where('department_name', $request->department)->first();
        
        // Also update the manager flag if edited
        $isManagerRole = $request->has('is_manager');

        if ($dept) {
            Position::updateOrCreate(
                ['position_name' => $request->job_title, 'department_id' => $dept->department_id],
                [
                    'pos_description' => $request->job_description,
                    'is_manager'      => $isManagerRole
                ]
            );
        }

        $job->update([
            'job_title' => $request->job_title,
            'department' => $request->department,
            'job_type' => $request->job_type,
            'location' => $request->location,
            'closing_date' => $request->closing_date,
            'salary_range' => $request->salary_range,
            'job_description' => $request->job_description,
            'requirements' => $request->requirements,
            'job_status' => $request->job_status,
        ]);

        return redirect()->route('admin.recruitment.index')
                         ->with('success', 'Job post updated successfully!');
    }

    // 6. Delete Job
    public function destroy($id)
    {
        $job = JobPost::findOrFail($id);
        
        // Delete all applications tied to this job first!
        $job->applications()->delete(); 
        
        // Now it is safe to delete the job itself
        $job->delete();
        
        return redirect()->route('admin.recruitment.index')
                         ->with('success', 'Job post and its associated applications deleted successfully!');
    }

    public function duplicate($id)
    {
        // 1. Find the job we want to copy
        $originalJob = JobPost::findOrFail($id);

        // 2. Use Laravel's magic replicate() method to clone it
        $newJob = $originalJob->replicate();

        // 3. Tweak the copied data before saving
        $newJob->job_title    = $originalJob->job_title . ' (Copy)'; 
        $newJob->job_status   = 'Draft';                             
        $newJob->closing_date = now()->addDays(30);                  
        $newJob->created_at   = now();
        $newJob->updated_at   = now();

        // 4. Save the new cloned job to the database
        $newJob->save();

        return redirect()->route('admin.recruitment.index')
                         ->with('success', 'Job post duplicated successfully! It is currently saved as a Draft.');
    }

    // ==========================================
    // MANAGER ACTION CENTER: Submit Requisition
    // ==========================================
    public function storeRequisition(Request $request)
{
    // 1. Get the current logged-in employee (the supervisor)
    $employee = auth()->user()->employee;

    // 2. Validate the request
    $validated = $request->validate([
        'job_title'       => 'required|string|max:255',
        'employment_type' => 'required|string',
        'headcount'       => 'required|integer|min:1',
        'justification'   => 'required|string',
    ]);

    // 3. Create the requisition with the 'requested_by' field included
    \App\Models\JobRequisition::create([
        'job_title'       => $validated['job_title'],
        'employment_type' => $validated['employment_type'],
        'headcount'       => $validated['headcount'],
        'justification'   => $validated['justification'],
        'department_id'   => $employee->department_id, // Auto-assign to supervisor's dept
        'requested_by'    => $employee->employee_id,   // THIS FIXES THE ERROR
        'status'          => 'Pending',
    ]);

    return back()->with('success', 'Job requisition submitted successfully!');
}
}