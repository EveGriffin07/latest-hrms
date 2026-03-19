<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Details - HRMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
    
    <style>
        .details-container { display: flex; gap: 30px; margin-top: 20px; align-items: flex-start; flex-wrap: wrap; }
        
        /* Sidebar Card (Left) */
        .sidebar-card { flex: 1 1 300px; max-width: 350px; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); position: sticky; top: 20px; }

        /* Content Card (Right) */
        .content-card { flex: 2 1 600px; background: transparent; display: flex; flex-direction: column; gap: 25px; }
        .info-card { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; }

        /* Typography & Badges */
        .section-title { font-size: 18px; font-weight: 600; color: #0f172a; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; display: flex; align-items: center; gap: 8px; }
        
        .status-badge { display: inline-block; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-top: 10px; }
        .status-applied { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
        .status-reviewing, .status-shortlisted { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .status-interview { background: #fae8ff; color: #a21caf; border: 1px solid #f5d0fe; }
        .status-hired, .status-offered { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .status-rejected { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }

        /* Sidebar Info Rows */
        .contact-row { display: flex; gap: 12px; margin-bottom: 15px; align-items: flex-start; }
        .contact-row i { color: #64748b; font-size: 16px; margin-top: 3px; width: 16px; text-align: center; }
        .contact-row div p { margin: 0; font-size: 13px; color: #1e293b; font-weight: 500; }
        .contact-row div span { font-size: 12px; color: #64748b; }

        /* Buttons */
        .btn-action { width: 100%; padding: 12px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 10px; transition: 0.2s; text-align: center; color: white; display: flex; justify-content: center; align-items: center; gap: 8px; font-size: 14px; }
        .btn-hire { background: #10b981; } .btn-hire:hover { background: #059669; }
        .btn-reject { background: #ef4444; } .btn-reject:hover { background: #dc2626; }
        .btn-interview { background: #8b5cf6; } .btn-interview:hover { background: #7c3aed; }
        
        .social-link { display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; background: #f8fafc; color: #334155; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 500; border: 1px solid #e2e8f0; width: calc(50% - 6px); justify-content: center; transition: 0.2s; }
        .social-link:hover { background: #f1f5f9; color: #0f172a; border-color: #cbd5e1; }

        /* Resume Area */
        .resume-box { background: #eff6ff; border: 1px dashed #bfdbfe; padding: 15px; border-radius: 8px; text-align: center; margin-top: 20px; }
        .resume-btns { display: flex; gap: 10px; margin-top: 10px; justify-content: center; }
        .resume-btns a { padding: 6px 12px; border-radius: 6px; font-size: 12px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
        .btn-view { background: #2563eb; color: #fff; }
        .btn-view:hover { background: #1d4ed8; }
        .btn-down { background: #fff; color: #2563eb; border: 1px solid #bfdbfe; }
        .btn-down:hover { background: #dbeafe; }

        /* Profile Cards (Right Side) */
        .history-item { margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px dashed #e2e8f0; }
        .history-item:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
        .history-title { font-size: 15px; font-weight: 600; color: #0f172a; margin-bottom: 4px; }
        .history-sub { font-size: 14px; color: #475569; font-weight: 500; margin-bottom: 4px; }
        .history-date { font-size: 12px; color: #64748b; margin-bottom: 8px; }
        .history-desc { font-size: 13px; color: #334155; line-height: 1.6; white-space: pre-line; }

        .tag-pill { display: inline-block; background: #f1f5f9; color: #334155; padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: 500; margin: 0 5px 10px 0; border: 1px solid #e2e8f0; }
        .tag-muted { color: #64748b; font-weight: 400; font-size: 12px; }

        /* Evaluation Section */
        .eval-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; }
        .eval-input { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; font-family: inherit; }
        .eval-input:focus { border-color: #2563eb; outline: none; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .info-label { font-size: 12px; color: #64748b; font-weight: 600; display: block; margin-bottom: 5px; }
    </style>
</head>
<body>

    <header>
        <div class="title">Web-Based HRMS</div>
        <div class="user-info">
            <a href="{{ route('admin.profile') }}" style="text-decoration: none; color: inherit;">
                <i class="fa-regular fa-user"></i> &nbsp; {{ Auth::user()->name ?? 'HR Admin' }}
            </a>
        </div>
    </header>

    <div class="container">
        @include('admin.layout.sidebar')

        <main>
            <div class="breadcrumb">
                <a href="{{ route('admin.recruitment.index') }}" style="color:#64748b; text-decoration:none;">Recruitment</a> > 
                <a href="{{ route('admin.applicants.index') }}" style="color:#64748b; text-decoration:none;">Applicants</a> > 
                <span style="color:#0f172a; font-weight:500;">{{ $application->applicant->full_name }}</span>
            </div>

            @if(session('success'))
                <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #bbf7d0;">
                    <i class="fa-solid fa-check-circle"></i> {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div style="background-color: #fee2e2; color: #991b1b; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #f87171;">
                    <ul style="margin: 0; padding-left: 20px;">
                        @foreach ($errors->all() as $error)
                            <li style="font-size: 14px; margin-bottom: 4px;">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php
                $profile = $application->applicant; 
            @endphp

            <div class="details-container">
                
                {{-- ==========================================
                     LEFT SIDEBAR: CANDIDATE SNAPSHOT
                =========================================== --}}
                <div class="sidebar-card">
                    
                    {{-- AVATAR --}}
                    <div style="margin: 0 auto 15px auto; width: 120px; height: 120px; display: flex; justify-content: center; align-items: center;">
                        @if($profile->avatar_path)
                            <img src="{{ asset('storage/' . $profile->avatar_path) }}" style="width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 4px solid #f8fafc; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        @else
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($profile->full_name) }}&background=f1f5f9&color=475569&size=128" style="width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 4px solid #f8fafc; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        @endif
                    </div>

                    <h3 style="font-size: 18px; color: #0f172a; margin-bottom: 5px;">{{ $profile->full_name }}</h3>
                    <p style="font-size: 13px; color: #64748b; margin-bottom: 5px;">Applied for <strong style="color: #2563eb;">{{ $application->job->job_title ?? 'Unknown Role' }}</strong></p>
                    
                    <div class="status-badge status-{{ strtolower(str_replace(' ', '', $application->app_stage)) }}">
                        {{ $application->app_stage }}
                    </div>

                    <div style="margin-top: 30px; text-align: left; padding-top: 20px; border-top: 1px solid #f1f5f9;">
                        
                        <div class="contact-row">
                            <i class="fa-solid fa-envelope"></i>
                            <div>
                                <span>Email Address</span>
                                <p>{{ $profile->user->email ?? 'N/A' }}</p>
                            </div>
                        </div>

                        <div class="contact-row">
                            <i class="fa-solid fa-phone"></i>
                            <div>
                                <span>Phone Number</span>
                                <p>{{ $profile->phone ?? 'N/A' }}</p>
                            </div>
                        </div>

                        <div class="contact-row">
                            <i class="fa-solid fa-location-dot"></i>
                            <div>
                                <span>Location</span>
                                <p>
                                    @if($profile->city || $profile->state)
                                        {{ $profile->city }}{{ $profile->city && $profile->state ? ', ' : '' }}{{ $profile->state }}
                                    @else
                                        Not provided
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Social Links --}}
                    @if($profile->linkedin_url || $profile->portfolio_url)
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        @if($profile->linkedin_url)
                            <a href="{{ $profile->linkedin_url }}" target="_blank" class="social-link"><i class="fa-brands fa-linkedin" style="color: #0a66c2;"></i> LinkedIn</a>
                        @endif
                        @if($profile->portfolio_url)
                            <a href="{{ $profile->portfolio_url }}" target="_blank" class="social-link"><i class="fa-solid fa-globe"></i> Portfolio</a>
                        @endif
                    </div>
                    @endif

                    {{-- RESUME DOWNLOAD --}}
                    <div class="resume-box">
                        <i class="fa-solid fa-file-pdf" style="font-size: 24px; color: #ef4444; margin-bottom: 10px;"></i>
                        @if($application->resume_path)
                            <p style="font-size: 13px; color: #1e3a8a; font-weight: 600; margin: 0;">Resume Attached</p>
                            <div class="resume-btns">
                                <a href="{{ asset('storage/' . $application->resume_path) }}" target="_blank" class="btn-view">View File</a>
                                <a href="{{ asset('storage/' . $application->resume_path) }}" download="{{ str_replace(' ', '_', $profile->full_name) }}_Resume.pdf" class="btn-down"><i class="fa-solid fa-download"></i> DL</a>
                            </div>
                        @else
                            <p style="font-size: 13px; color: #64748b; margin: 0; font-style: italic;">No resume uploaded.</p>
                        @endif
                    </div>

{{-- QUICK ACTIONS --}}
                    <div style="margin-top: 30px; text-align: left;">
                        <span class="info-label" style="margin-bottom: 10px;">Change Application Status</span>
                        
                        {{-- Triggers the Interview Modal --}}
                        <button type="button" class="btn-action btn-interview" onclick="document.getElementById('interviewModal').style.display='flex'">
                            <i class="fa-solid fa-comments"></i> Schedule Interview
                        </button>

                        {{-- HIRE BUTTON: Locked until Evaluation is saved --}}
                        <form action="{{ route('admin.applicants.updateStatus', $application->application_id) }}" method="POST">
                            @csrf <input type="hidden" name="status" value="Hired">
                            
                            @if(is_null($application->overall_score))
                                <button type="button" class="btn-action" style="background: #cbd5e1; color: #475569; cursor: not-allowed;" onclick="alert('You must fill out and save the HR Interview Evaluation below before passing this candidate.')">
                                    <i class="fa-solid fa-lock"></i> Candidate Passed
                                </button>
                            @else
                                <button type="submit" class="btn-action btn-hire"><i class="fa-solid fa-check"></i> Candidate Passed</button>
                            @endif
                        </form>

                        {{-- REJECT BUTTON --}}
                        <form action="{{ route('admin.applicants.updateStatus', $application->application_id) }}" method="POST">
                            @csrf <input type="hidden" name="status" value="Rejected">
                            <button type="submit" class="btn-action btn-reject" onclick="return confirm('Reject this applicant?')"><i class="fa-solid fa-xmark"></i> Reject</button>
                        </form>
                    </div>

                </div>

                {{-- ==========================================
                     RIGHT CONTENT: FULL CANDIDATE PROFILE
                =========================================== --}}
                <div class="content-card">
                    
                    {{-- Profile Summary & Cover Letter --}}
                    <div class="info-card">
                        <h3 class="section-title"><i class="fa-solid fa-user" style="color: #2563eb;"></i> Candidate Overview</h3>
                        
                        @if($application->cover_letter)
                            <div style="background: #f8fafc; padding: 15px; border-left: 3px solid #2563eb; border-radius: 4px; margin-bottom: 20px;">
                                <span class="info-label">Cover Letter Note:</span>
                                <p style="font-size: 13px; color: #334155; line-height: 1.6; margin: 0; white-space: pre-line;">"{{ $application->cover_letter }}"</p>
                            </div>
                        @endif

                        @if($profile->personal_summary)
                            <span class="info-label">Profile Summary:</span>
                            <p style="font-size: 14px; color: #1e293b; line-height: 1.6; margin: 0;">{{ $profile->personal_summary }}</p>
                        @else
                            <p style="font-size: 13px; color: #94a3b8; font-style: italic; margin: 0;">Candidate did not provide a personal summary.</p>
                        @endif
                    </div>

                    {{-- Experience Section --}}
                    <div class="info-card">
                        <h3 class="section-title"><i class="fa-solid fa-briefcase" style="color: #2563eb;"></i> Work Experience</h3>
                        
                        @if($profile->experiences && $profile->experiences->count() > 0)
                            @foreach($profile->experiences->sortByDesc('start_date') as $exp)
                                <div class="history-item">
                                    <div class="history-title">{{ $exp->job_title }}</div>
                                    <div class="history-sub">{{ $exp->company_name }}</div>
                                    <div class="history-date">
                                        {{ \Carbon\Carbon::parse($exp->start_date)->format('M Y') }} - 
                                        {{ $exp->is_current ? 'Present' : \Carbon\Carbon::parse($exp->end_date)->format('M Y') }}
                                    </div>
                                    @if($exp->description)
                                        <div class="history-desc">{{ $exp->description }}</div>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <p style="font-size: 13px; color: #94a3b8; font-style: italic; margin: 0;">No work experience provided.</p>
                        @endif
                    </div>

                    {{-- Education Section --}}
                    <div class="info-card">
                        <h3 class="section-title"><i class="fa-solid fa-graduation-cap" style="color: #2563eb;"></i> Education</h3>
                        
                        @if($profile->educations && $profile->educations->count() > 0)
                            @foreach($profile->educations->sortByDesc('start_date') as $edu)
                                <div class="history-item">
                                    <div class="history-title">{{ $edu->degree_title }} @if($edu->field_of_study) ({{ $edu->field_of_study }}) @endif</div>
                                    <div class="history-sub">{{ $edu->institution_name }}</div>
                                    <div class="history-date">
                                        {{ $edu->start_date ? \Carbon\Carbon::parse($edu->start_date)->format('Y') : '' }} - 
                                        {{ $edu->is_current ? 'Present' : ($edu->end_date ? \Carbon\Carbon::parse($edu->end_date)->format('Y') : 'N/A') }}
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p style="font-size: 13px; color: #94a3b8; font-style: italic; margin: 0;">No education history provided.</p>
                        @endif
                    </div>

                    {{-- Skills & Languages --}}
                    <div class="info-card" style="display: flex; gap: 30px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 250px;">
                            <h3 class="section-title" style="margin-bottom: 15px;"><i class="fa-solid fa-laptop-code" style="color: #2563eb;"></i> Skills</h3>
                            @if($profile->skills()->count() > 0)
                                @foreach($profile->skills()->get() as $skill)
                                    <div class="tag-pill">
                                        {{ $skill->skill_name }} <span class="tag-muted">({{ $skill->proficiency }})</span>
                                    </div>
                                @endforeach
                            @else
                                <p style="font-size: 13px; color: #94a3b8; font-style: italic; margin: 0;">No skills listed.</p>
                            @endif
                        </div>

                        <div style="flex: 1; min-width: 250px;">
                            <h3 class="section-title" style="margin-bottom: 15px;"><i class="fa-solid fa-language" style="color: #2563eb;"></i> Languages</h3>
                            @if($profile->languages()->count() > 0)
                                @foreach($profile->languages()->get() as $lang)
                                    <div class="tag-pill">
                                        {{ $lang->language_name }} <span class="tag-muted">({{ $lang->proficiency }})</span>
                                    </div>
                                @endforeach
                            @else
                                <p style="font-size: 13px; color: #94a3b8; font-style: italic; margin: 0;">No languages listed.</p>
                            @endif
                        </div>
                    </div>

                    {{-- HR EVALUATION SECTION --}}
                    <div class="info-card" style="border-top: 4px solid #1e293b;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <h3 class="section-title" style="border: none; margin: 0;"><i class="fa-solid fa-clipboard-list" style="color: #1e293b;"></i> HR Interview Evaluation</h3>
                            
                            {{-- UI Warning Badge if locked --}}
                            @if(is_null($application->interview_datetime))
                                <span style="background: #fee2e2; color: #dc2626; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; border: 1px solid #fecaca;">
                                    <i class="fa-solid fa-lock"></i> Locked: Schedule Interview First
                                </span>
                            @endif
                        </div>
                        
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">Record the candidate's performance for future reference.</p>

                        <form action="{{ route('admin.applicants.evaluate', $application->application_id) }}" method="POST">
                            @csrf
                            
                            {{-- Determine if we should disable the form --}}
                            @php
                                $isLocked = is_null($application->interview_datetime) ? 'disabled' : '';
                                $lockedBg = is_null($application->interview_datetime) ? 'background-color: #f1f5f9; cursor: not-allowed;' : '';
                            @endphp

                            <div class="eval-grid">
                                <div>
                                    <label class="info-label">Technical / Test Score (0-100) <span style="color:#dc2626">*</span></label>
                                    <input type="number" name="test_score" class="eval-input" value="{{ $application->test_score }}" placeholder="e.g. 85" min="0" max="100" required {{ $isLocked }} style="{{ $lockedBg }}">
                                </div>
                                <div>
                                    <label class="info-label">Interview Score (0-100) <span style="color:#dc2626">*</span></label>
                                    <input type="number" name="interview_score" class="eval-input" value="{{ $application->interview_score }}" placeholder="e.g. 90" min="0" max="100" required {{ $isLocked }} style="{{ $lockedBg }}">
                                </div>
                            </div>

                            <div style="margin-top: 15px;">
                                <label class="info-label">Total Computed Score</label>
                                <input type="number" class="eval-input" value="{{ $application->overall_score }}" disabled style="background: #e2e8f0; color: #64748b; font-weight: 600; cursor: not-allowed;">
                                <small style="color: #94a3b8; font-size: 11px;">Calculated automatically upon saving.</small>
                            </div>

                            <div style="margin-top: 15px;">
                                <label class="info-label">HR Notes & Observations <span style="color:#dc2626">*</span></label>
                                <textarea name="notes" rows="4" class="eval-input" placeholder="Enter comments regarding the candidate's culture fit, salary expectations, etc..." required {{ $isLocked }} style="{{ $lockedBg }}">{{ $application->evaluation_notes }}</textarea>
                            </div>

                            <div style="text-align: right; margin-top: 20px;">
                                <button type="submit" class="btn-action" style="background: {{ is_null($application->interview_datetime) ? '#94a3b8' : '#0f172a' }}; width: auto; padding: 12px 30px; display: inline-flex; {{ $lockedBg }}" {{ $isLocked }}>
                                    <i class="fa-solid fa-floppy-disk"></i> Save Evaluation
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </main>
    </div>

    {{-- =======================================================
         INTERVIEW SCHEDULING MODAL 
    ======================================================== --}}
    <style>
        .hrms-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center; }
        .hrms-modal-box { background: #fff; width: 500px; border-radius: 12px; padding: 30px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); animation: slideUp 0.3s ease-out; }
        .hrms-modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .hrms-input { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; font-size: 14px; margin-top: 5px; }
        .hrms-input:focus { border-color: #2563eb; outline: none; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
    </style>

    <div id="interviewModal" class="hrms-modal-overlay">
        <div class="hrms-modal-box">
            <div class="hrms-modal-header">
                <h3 style="margin: 0; font-size: 18px; color: #0f172a;"><i class="fa-solid fa-calendar-check" style="color: #2563eb;"></i> Schedule Interview</h3>
                <button onclick="document.getElementById('interviewModal').style.display='none'" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
            </div>
            <form action="{{ route('admin.applicants.schedule', $application->application_id) }}" method="POST">
                @csrf
                <div style="margin-bottom: 15px;">
                    <label style="font-size: 13px; font-weight: 600; color: #334155;">Interview Date & Time</label>
                    <input type="datetime-local" name="interview_datetime" class="hrms-input" value="{{ $application->interview_datetime ? \Carbon\Carbon::parse($application->interview_datetime)->format('Y-m-d\TH:i') : '' }}" required>
                </div>
                <div style="margin-bottom: 25px;">
                    <label style="font-size: 13px; font-weight: 600; color: #334155;">Location or Meeting Link</label>
                    <input type="text" name="interview_location" class="hrms-input" placeholder="e.g., https://zoom.us/j/12345 or Meeting Room B" value="{{ $application->interview_location }}" required>
                </div>
                <div style="text-align: right;">
                    <button type="button" onclick="document.getElementById('interviewModal').style.display='none'" style="background: #fff; color: #475569; border: 1px solid #cbd5e1; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; margin-right: 10px;">Cancel</button>
                    <button type="submit" style="background: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer;">Save Schedule</button>
                </div>
            </form>
        </div>
    </div>



    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Target the datetime-local input
            const datetimeInput = document.querySelector('input[name="interview_datetime"]');
            
            if (datetimeInput) {
                // Get the exact current date and time in the correct timezone format
                const now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                
                // Slice it to match the 'YYYY-MM-DDTHH:MM' format required by HTML5
                const minDatetime = now.toISOString().slice(0, 16);
                
                // Lock the calendar so past times are grayed out
                datetimeInput.setAttribute('min', minDatetime);
            }
        });
    </script>
</body>
</html>