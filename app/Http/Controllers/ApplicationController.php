<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Application;
use App\Models\JobPost;
use App\Models\ApplicantProfile;

class ApplicationController extends Controller
{
    // 1. List all Applicants
    public function index(Request $request)
    {
        $query = Application::with(['applicant.user', 'job'])->latest();

        $selectedJob = null;
        if ($request->has('job_id')) {
            $query->where('job_id', $request->job_id);
            $selectedJob = JobPost::find($request->job_id); 
        }

        $applications = $query->get();

        // Make sure it points to your correct admin view
        return view('admin.recruitment_applicants', compact('applications', 'selectedJob'));
    }
    
    // 2. Show Specific Applicant Details
    public function show($id)
    {
        // Added the extra relationships so the Admin's view can see the dynamic cards
        $application = Application::with([
            'job', 
            'applicant.user',
            'applicant.experiences',
            'applicant.educations',
            'applicant.skills',
            'applicant.languages'
        ])->findOrFail($id);

        // ========================================================
        // 🔥 THE MAGIC TRICK: SILENT STATUS UPDATE (Read Receipt)
        // ========================================================
        // If this is the FIRST time HR is opening this application, 
        // automatically move it to the "Reviewing" stage!
        if ($application->app_stage === 'Applied') {
            $application->app_stage = 'Reviewing';
            $application->save();
        }
        // ========================================================

        return view('admin.applicants_show', compact('application'));
    }

    // 3. Update Status
    public function updateStatus(Request $request, $id)
    {
        $application = Application::findOrFail($id);

        // Update the status to whatever HR selected
        $application->app_stage = $request->status;
        $application->save();

        // If the candidate is hired, redirect to the Employee Management index
        if ($request->status === 'Hired') {
            return redirect()->route('admin.employee.list')->with('success', 'Candidate marked as Hired! You have been redirected to Employee Management to finalize their profile.');
        }

        // For other statuses (Reviewing, Rejected, etc.), just stay on the current page
        return redirect()->back()->with('success', 'Applicant status updated successfully!');
    }

    // 3.5 Schedule Interview
    public function scheduleInterview(Request $request, $id)
    {
        // STRICT VALIDATION: 'after:now' ensures the time is in the future
        $request->validate([
            'interview_datetime' => 'required|date|after:now',
            'interview_location' => 'required|string|max:255',
        ], [
            'interview_datetime.after' => 'Error: The interview must be scheduled for a future date and time.',
        ]);

        $application = Application::findOrFail($id);
        
        $application->app_stage = 'Interview'; 
        $application->interview_datetime = $request->interview_datetime;
        $application->interview_location = $request->interview_location;
        $application->save();

        return redirect()->back()->with('success', 'Interview successfully scheduled! The applicant can now see these details on their dashboard.');
    }

    // 4. Save Evaluation Scores
    public function saveEvaluation(Request $request, $id)
    {
        $application = Application::findOrFail($id);

        // Security Check: Prevent evaluation if no interview has been scheduled
        if (is_null($application->interview_datetime)) {
            return redirect()->back()->withErrors(['error' => 'You cannot evaluate a candidate before scheduling an interview!']);
        }

        // === NEW: STRICT EVALUATION VALIDATION ===
        $request->validate([
            'test_score'      => 'required|numeric|min:0|max:100',
            'interview_score' => 'required|numeric|min:0|max:100',
            'notes'           => 'required|string|min:10', // Forces HR to write at least a brief sentence
        ], [
            'test_score.required'      => 'Error: You must provide a Technical/Test score.',
            'test_score.max'           => 'Error: The Test score cannot exceed 100.',
            'interview_score.required' => 'Error: You must provide an Interview score.',
            'interview_score.max'      => 'Error: The Interview score cannot exceed 100.',
            'notes.required'           => 'Error: HR Notes are required to justify these scores.',
            'notes.min'                => 'Error: HR Notes must be at least 10 characters long.',
        ]);
        // ==========================================

        $overall = ($request->test_score + $request->interview_score) / 2;

        $application->update([
            'test_score'        => $request->test_score,
            'interview_score'   => $request->interview_score,
            'overall_score'     => $overall,
            'evaluation_notes'  => $request->notes,
            'app_stage'         => 'Interview'
        ]);

        return redirect()->back()->with('success', 'Evaluation saved successfully!');
    }

    // 5. Show Applicant's History
    public function myApplications()
    {
        $user = Auth::user();
        $profile = ApplicantProfile::where('user_id', $user->user_id)->first();

        if (!$profile) {
            return view('applicant.applications', ['applications' => []]);
        }

        $applications = Application::where('applicant_id', $profile->applicant_id)
            ->with('job')
            ->latest()
            ->get();

        return view('applicant.applications', compact('applications'));
    }
}