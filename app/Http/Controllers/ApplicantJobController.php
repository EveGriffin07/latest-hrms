<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\JobPost;
use App\Models\Application;
use App\Models\ApplicantProfile; 

class ApplicantJobController extends Controller
{
    // =========================================================
    // 1. Show All Open Jobs (With Smart Recommendations)
    // =========================================================
   public function index(Request $request)
    {
        $user = Auth::user();
        
        $profile = ApplicantProfile::where('user_id', $user->user_id)->first();
        $industryInterest = $profile->industry_interest ?? null;

        // Fetch RECOMMENDED jobs (Limited to top 3 so it doesn't flood the page)
        $recommendedJobs = collect(); 
        if ($industryInterest) {
            $recommendedJobs = JobPost::where('job_status', 'Open')
                ->whereDate('closing_date', '>=', now()->toDateString())
                ->where(function($query) use ($industryInterest) {
                    $query->where('department', 'LIKE', "%{$industryInterest}%")
                          ->orWhere('job_title', 'LIKE', "%{$industryInterest}%");
                })
                ->latest()
                ->take(3) // Only show the 3 newest matches
                ->get();
        }

        // Build the query for ALL OPEN JOBS
        $query = JobPost::where('job_status', 'Open');

        // Apply Backend Search Filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('job_title', 'LIKE', "%{$search}%")
                  ->orWhere('department', 'LIKE', "%{$search}%");
            });
        }

        // Apply Backend Type Filter
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('job_type', $request->type);
        }

        // Paginate (9 per page) and keep the search query in the URL links
        $allJobs = $query->latest()->paginate(9)->appends($request->all());

        return view('applicant.jobs', compact('allJobs', 'recommendedJobs', 'industryInterest'));
    }
    
    public function show($id)
    {
        $job = JobPost::findOrFail($id);
        return view('applicant.job_details', compact('job'));
    }

    public function applyForm($id)
    {
        $job = JobPost::findOrFail($id);
        
        if (\Carbon\Carbon::parse($job->closing_date)->isPast() || $job->job_status === 'Closed') {
            return redirect()->route('applicant.jobs')->withErrors(['error' => 'This job is no longer accepting applications.']);
        }

        // === NEW: PREVENT DUPLICATE APPLICATIONS ===
        $user = Auth::user();
        $profile = ApplicantProfile::where('user_id', $user->user_id)->first();
        
        if ($profile) {
            $alreadyApplied = Application::where('job_id', $id)
                                         ->where('applicant_id', $profile->applicant_id)
                                         ->exists();
            if ($alreadyApplied) {
                return redirect()->route('applicant.jobs')->withErrors(['error' => 'You have already applied for this position. Please check "My Applications" for status updates.']);
            }
        }
        // ===========================================

        return view('applicant.job_apply', compact('job'));
    }

    public function submitApplication(Request $request, $id)
    {
        $job = JobPost::findOrFail($id);

        if (\Carbon\Carbon::parse($job->closing_date)->isPast() || $job->job_status === 'Closed') {
            return redirect()->route('applicant.jobs')->withErrors(['error' => 'Sorry, the deadline for this job has passed.']);
        }

        $user = Auth::user();
        $profile = ApplicantProfile::where('user_id', $user->user_id)->first();

        if ($profile) {
            $alreadyApplied = Application::where('job_id', $id)
                                         ->where('applicant_id', $profile->applicant_id)
                                         ->exists();
            if ($alreadyApplied) {
                return redirect()->route('applicant.jobs')->withErrors(['error' => 'Duplicate submission detected. You have already applied for this job.']);
            }
        }
        
        // DYNAMIC VALIDATION: If they have a profile resume and chose to use it, the file upload is NOT required.
        $resumeRule = ($request->resume_choice === 'existing' && $profile && $profile->resume_path) 
                      ? 'nullable|mimes:pdf,doc,docx|max:2048' 
                      : 'required|mimes:pdf,doc,docx|max:2048';

        $request->validate([
            'resume' => $resumeRule, 
            'phone' => 'required|string', 
            'cover_letter' => 'nullable|string',
        ]);

        $profile = ApplicantProfile::updateOrCreate(
            ['user_id' => $user->user_id],
            [
                'full_name' => $user->name,
                'phone' => $request->phone, 
            ]
        );

        $resumePath = null;
        if ($request->hasFile('resume')) {
            // They uploaded a new file! Save it and update their main profile too.
            $resumePath = $request->file('resume')->store('resumes', 'public');
            $profile->resume_path = $resumePath;
            $profile->save();
        } elseif ($profile->resume_path) {
            // They chose to use their existing profile resume
            $resumePath = $profile->resume_path;
        }

        Application::create([
            'job_id' => $id,
            'applicant_id' => $profile->applicant_id, 
            'app_stage' => 'Applied',
            'resume_path' => $resumePath,
            'cover_letter' => $request->cover_letter,
        ]);

        return redirect()->route('applicant.jobs')
                         ->with('success', 'Application submitted successfully!');
    }

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

    // =========================================================
    // Profile Management
    // =========================================================
    public function profile()
    {
        $user = Auth::user();

        $profile = ApplicantProfile::with(['educations', 'languages', 'skills', 'experiences'])
            ->firstOrCreate(
                ['user_id' => $user->user_id],
                ['full_name' => $user->name, 'email' => $user->email]
            );

        return view('applicant.profile', compact('user', 'profile'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $profile = ApplicantProfile::where('user_id', $user->user_id)->firstOrFail();

        // STRICT REGEX VALIDATION APPLIED HERE
        // regex:/^[a-zA-Z\s\-]+$/ means ONLY letters, spaces, and hyphens allowed
        $request->validate([
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9\+\-\s]+$/'],
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z\s\-]+$/'],
            'state' => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z\s\-]+$/'],
            'postcode' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'personal_summary' => 'nullable|string',
            'industry_interest' => ['nullable', 'string', 'max:255'],
            'licenses_certifications' => 'nullable|string|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'portfolio_url' => 'nullable|url|max:255',
            'resume' => 'nullable|mimes:pdf,doc,docx|max:2048',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', 
        ], [
            'city.regex' => 'The city may only contain letters and spaces.',
            'state.regex' => 'The state may only contain letters and spaces.',
            'industry_interest.regex' => 'The industry interest may only contain letters and spaces.',
            'phone.regex' => 'The phone number may only contain digits, spaces, and the plus sign.',
        ]);

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $profile->avatar_path = $avatarPath;
        }

        if ($request->hasFile('resume')) {
            $resumePath = $request->file('resume')->store('resumes', 'public');
            $profile->resume_path = $resumePath;
        }

        $profile->phone = $request->phone;
        $profile->address_line_1 = $request->address_line_1;
        $profile->address_line_2 = $request->address_line_2;
        $profile->city = $request->city;
        $profile->state = $request->state;
        $profile->postcode = $request->postcode;
        $profile->personal_summary = $request->personal_summary;
        $profile->industry_interest = $request->industry_interest;
        $profile->licenses_certifications = $request->licenses_certifications;
        $profile->linkedin_url = $request->linkedin_url;
        $profile->portfolio_url = $request->portfolio_url;
        
        $profile->save();

        return redirect()->route('applicant.profile')
                         ->with('success', 'Profile updated successfully!');
    }

    public function deleteResume()
    {
        $user = Auth::user();
        $profile = ApplicantProfile::where('user_id', $user->user_id)->firstOrFail();

        if ($profile->resume_path) {
            $profile->resume_path = null; 
            $profile->save();
            return redirect()->back()->with('success', 'Resume removed successfully.');
        }

        return redirect()->back()->with('error', 'No resume to remove.');
    }

    // =========================================================
    // Card Actions: Experience
    // =========================================================
    public function storeExperience(Request $request)
    {
        $request->validate([
            'job_title' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'start_date' => 'required|date',
        ]);
        $profile = ApplicantProfile::where('user_id', Auth::user()->user_id)->firstOrFail();
        $data = $request->all();
        $data['is_current'] = $request->has('is_current');
        if ($data['is_current']) $data['end_date'] = null;
        
        $profile->experiences()->create($data);
        return back()->with('success', 'Experience added!');
    }

    public function deleteExperience($id)
    {
        $profile = ApplicantProfile::where('user_id', Auth::user()->user_id)->firstOrFail();
        $profile->experiences()->where('id', $id)->delete();
        return back()->with('success', 'Experience removed.');
    }

    // =========================================================
    // Card Actions: Education
    // =========================================================
    public function storeEducation(Request $request)
    {
        $request->validate([
            'institution_name' => 'required|string|max:255',
            'degree_title' => 'required|string|max:255',
            'field_of_study' => 'nullable|string|max:255',
        ]);

        $profile = ApplicantProfile::where('user_id', Auth::user()->user_id)->firstOrFail();
        $profile->educations()->create($request->all());

        return back()->with('success', 'Education added successfully!');
    }

    public function deleteEducation($id)
    {
        $profile = ApplicantProfile::where('user_id', Auth::user()->user_id)->firstOrFail();
        $profile->educations()->where('id', $id)->delete();
        return back()->with('success', 'Education removed.');
    }

    // =========================================================
    // Card Actions: Languages
    // =========================================================
    public function storeLanguage(Request $request)
    {
        $request->validate([
            'language_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\-]+$/'],
            'proficiency' => 'nullable|string|max:255',
        ], [
            'language_name.regex' => 'The language name may only contain letters and spaces.'
        ]);

        $profile = ApplicantProfile::where('user_id', Auth::user()->user_id)->firstOrFail();
        $profile->languages()->create($request->all());

        return back()->with('success', 'Language added successfully!');
    }

    public function deleteLanguage($id)
    {
        $profile = ApplicantProfile::where('user_id', Auth::user()->user_id)->firstOrFail();
        $profile->languages()->where('id', $id)->delete();
        return back()->with('success', 'Language removed.');
    }

    // =========================================================
    // Card Actions: Skills
    // =========================================================
    public function storeSkill(Request $request)
    {
        $request->validate([
            'skill_name' => 'required|string|max:255',
            'proficiency' => 'nullable|string|max:255',
        ]);

        $profile = ApplicantProfile::where('user_id', Auth::user()->user_id)->firstOrFail();
        $profile->skills()->create($request->all());

        return back()->with('success', 'Skill added successfully!');
    }

    public function deleteSkill($id)
    {
        $profile = ApplicantProfile::where('user_id', Auth::user()->user_id)->firstOrFail();
        $profile->skills()->where('id', $id)->delete();
        return back()->with('success', 'Skill removed.');
    }

    
}