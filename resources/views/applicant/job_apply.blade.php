@extends('applicant.layout')

@section('content')

<style>
    /* Premium SaaS Form Layout */
    .apply-container { max-width: 800px; margin: 0 auto; padding-bottom: 40px; }
    
    .back-link { display: inline-flex; align-items: center; gap: 8px; color: #64748b; text-decoration: none; font-size: 14px; font-weight: 500; margin-bottom: 20px; transition: color 0.2s; }
    .back-link:hover { color: #0f172a; }
    
    .apply-card { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); overflow: hidden; }
    
    .apply-header { background: #f8fafc; padding: 35px 40px; border-bottom: 1px solid #e5e7eb; position: relative; }
    .apply-header::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: #2563eb; }
    
    .apply-body { padding: 40px; }
    
    .form-group { margin-bottom: 25px; }
    .form-label { display: block; font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px; }
    .form-label span { color: #dc2626; } /* Required Asterisk */
    
    .form-control { width: 100%; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; color: #0f172a; transition: 0.2s; font-family: inherit; }
    .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); outline: none; }
    .form-control[readonly] { background: #f1f5f9; color: #64748b; cursor: not-allowed; border-color: #e2e8f0; }
    
    /* Beautiful File Upload Zone */
    .file-drop-area { border: 2px dashed #cbd5e1; border-radius: 8px; padding: 40px 20px; text-align: center; background: #f8fafc; transition: 0.2s; cursor: pointer; display: block; }
    .file-drop-area:hover { border-color: #2563eb; background: #eff6ff; }
    .file-input { display: none; }
    
    /* Smart Apply Notice */
    .profile-notice { display: flex; gap: 15px; align-items: flex-start; background: #eff6ff; border: 1px solid #bfdbfe; padding: 20px; border-radius: 8px; margin-bottom: 35px; }
    .profile-notice i { color: #2563eb; font-size: 24px; margin-top: 2px; }
    .profile-notice h4 { margin: 0 0 5px 0; font-size: 15px; color: #1e3a8a; }
    .profile-notice p { margin: 0; font-size: 13px; color: #1e40af; line-height: 1.5; }
    
    .section-title { font-size: 18px; font-weight: 600; color: #111827; margin-bottom: 20px; border-bottom: 2px solid #f3f4f6; padding-bottom: 10px; margin-top: 40px; }
    .section-title:first-of-type { margin-top: 0; }
</style>

@php
    // Fetch the user's profile to see if they already have a resume uploaded
    $profile = \App\Models\ApplicantProfile::where('user_id', Auth::user()->user_id)->first();
    $hasResume = $profile && $profile->resume_path;
@endphp

<div class="apply-container">
    
    <a href="{{ route('applicant.jobs.show', $job->job_id) }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i> Back to Job Details
    </a>

    {{-- ERROR ALERTS --}}
    @if ($errors->any())
        <div style="background: #fee2e2; color: #b91c1c; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f87171;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li style="font-size: 14px; margin-bottom: 5px;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="apply-card">
        
        {{-- CARD HEADER --}}
        <div class="apply-header">
            <span style="background: #e0e7ff; color: #3730a3; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; margin-bottom: 15px; display: inline-block;">{{ $job->job_type }}</span>
            <h1 style="font-size: 26px; font-weight: 700; color: #0f172a; margin: 0 0 8px 0;">Submit your application</h1>
            <p style="color: #64748b; font-size: 15px; margin: 0;">Applying for <strong>{{ $job->job_title }}</strong> at {{ $job->department }}</p>
        </div>

        {{-- CARD BODY (FORM) --}}
        <div class="apply-body">
            <form action="{{ route('applicant.jobs.submit', $job->job_id) }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- Smart Apply UX Touch --}}
                <div class="profile-notice">
                    <i class="fa-solid fa-id-card-clip"></i>
                    <div>
                        <h4>Smart Apply Enabled</h4>
                        <p>Your complete Applicant Profile (including your General Details, Experience, Education, and Skills) will automatically be attached to this application for the HR department to review.</p>
                    </div>
                </div>

                <h3 class="section-title">1. Contact Information</h3>
                
                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" value="{{ Auth::user()->name }}" readonly>
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" value="{{ Auth::user()->email }}" readonly>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number <span>*</span></label>
                    {{-- Pre-fill phone if available from profile --}}
                    <input type="text" id="phone" name="phone" class="form-control phone-only" value="{{ old('phone', $profile->phone ?? '') }}" placeholder="e.g. +60 12-345 6789" required>
                    <small style="color: #64748b; font-size: 12px; margin-top: 6px; display: block;">We will use this number if we need to contact you for an interview.</small>
                </div>

                <h3 class="section-title">2. Supporting Documents</h3>

                {{-- DYNAMIC RESUME SELECTOR --}}
                @if($hasResume)
                    <div class="form-group">
                        <label class="form-label">Resume / CV <span>*</span></label>
                        
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 15px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <i class="fa-solid fa-file-pdf" style="color: #ef4444; font-size: 28px;"></i>
                                    <div>
                                        <p style="font-weight: 600; color: #1e293b; margin: 0; font-size: 15px;">Profile Resume Saved</p>
                                        <a href="{{ asset('storage/' . $profile->resume_path) }}" target="_blank" style="font-size: 13px; color: #2563eb; text-decoration: none;">View Current File</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 30px; border-top: 1px solid #e2e8f0; padding-top: 15px;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px; font-weight: 500; color: #334155;">
                                    <input type="radio" name="resume_choice" value="existing" checked onchange="toggleResumeUpload(false)" style="accent-color: #2563eb; width: 16px; height: 16px;">
                                    Use existing resume
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px; font-weight: 500; color: #334155;">
                                    <input type="radio" name="resume_choice" value="new" onchange="toggleResumeUpload(true)" style="accent-color: #2563eb; width: 16px; height: 16px;">
                                    Upload a new one
                                </label>
                            </div>
                        </div>
                    </div>
                @else
                    <input type="hidden" name="resume_choice" value="new">
                @endif

                {{-- NEW UPLOAD DROPZONE (Hidden if using existing) --}}
                <div class="form-group" id="resumeUploadBox" style="{{ $hasResume ? 'display: none;' : '' }}">
                    <label class="form-label">Upload New Resume / CV <span>*</span></label>
                    
                    <label class="file-drop-area" for="resume">
                        <i class="fa-solid fa-cloud-arrow-up" style="font-size: 36px; color: #94a3b8; margin-bottom: 15px;"></i>
                        <h4 style="font-size: 16px; color: #1e293b; margin: 0 0 5px 0;">Click to upload your resume</h4>
                        <p style="font-size: 13px; color: #64748b; margin: 0;">Accepted formats: PDF, DOC, DOCX (Max 2MB)</p>
                        
                        <p id="fileNameDisplay" style="font-size: 14px; font-weight: 600; color: #2563eb; margin-top: 15px; display: none; background: #dbeafe; padding: 8px; border-radius: 6px; display: inline-block;"></p>
                    </label>
                    
                    <input type="file" id="resume" name="resume" class="file-input" accept=".pdf,.doc,.docx" {{ $hasResume ? '' : 'required' }} onchange="showFileName(this)">
                    
                    @if($hasResume)
                        <small style="color: #64748b; font-size: 12px; margin-top: 8px; display: block;">
                            <i class="fa-solid fa-circle-info" style="color: #2563eb;"></i> Uploading a new resume here will automatically update your main Profile resume as well.
                        </small>
                    @endif
                </div>

                <div class="form-group">
                    <label class="form-label" for="cover_letter">Cover Letter / Pitch (Optional)</label>
                    <textarea id="cover_letter" name="cover_letter" class="form-control" rows="6" placeholder="Briefly introduce yourself, explain why you are a great fit for this role, and highlight your most relevant achievements..."></textarea>
                </div>

                {{-- SUBMIT AREA --}}
                <div style="margin-top: 40px; text-align: right; border-top: 1px solid #e5e7eb; padding-top: 30px; display: flex; justify-content: flex-end; align-items: center; gap: 20px;">
                    <a href="{{ route('applicant.jobs.show', $job->job_id) }}" style="color: #64748b; text-decoration: none; font-size: 14px; font-weight: 600;">Cancel</a>
                    
                    <button type="submit" style="background: #2563eb; color: #fff; padding: 14px 32px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 6px rgba(37,99,235,0.2); transition: 0.2s;">
                        Submit Application <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                    </button>
                </div>
                
            </form>
        </div>
    </div>
</div>

<script>
    // Show/Hide the file uploader depending on radio button choice
    function toggleResumeUpload(showNew) {
        const uploadBox = document.getElementById('resumeUploadBox');
        const fileInput = document.getElementById('resume');
        
        if (showNew) {
            uploadBox.style.display = 'block';
            fileInput.required = true;
        } else {
            uploadBox.style.display = 'none';
            fileInput.required = false;
            fileInput.value = ''; // clear the file input
            document.getElementById('fileNameDisplay').style.display = 'none';
        }
    }

    // Show the selected file name in the upload box
    function showFileName(input) {
        const display = document.getElementById('fileNameDisplay');
        if (input.files && input.files.length > 0) {
            display.innerHTML = '<i class="fa-solid fa-file-check" style="margin-right: 5px;"></i> Attached: ' + input.files[0].name;
            display.style.display = 'inline-block';
        } else {
            display.style.display = 'none';
        }
    }

    // Enforce the strict phone number validation instantly
    document.querySelectorAll('.phone-only').forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9\+\-\s]/g, '');
        });
    });
</script>

@endsection