@extends('applicant.layout')

@section('content')

<h2 class="page-title">My Profile</h2>
<p class="page-subtitle">Manage your personal information, professional experience, skills, and resume.</p>

{{-- SUCCESS & ERROR MESSAGES --}}
@if(session('success'))
<div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #bbf7d0;">
    <i class="fa-solid fa-check-circle"></i> {{ session('success') }}
</div>
@endif

@if ($errors->any())
<div style="background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
    <ul>
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- =========================================================
     THE INVISIBLE MAIN FORM (HTML5 Magic)
     ========================================================= --}}
<form id="mainProfileForm" action="{{ route('applicant.profile.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
</form>

<div class="profile-container" style="display: flex; gap: 30px; align-items: flex-start; margin-bottom: 40px;">

    {{-- LEFT SIDEBAR: Avatar & Links --}}
    <div class="profile-sidebar" style="flex: 1; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center; position: sticky; top: 20px;">
        <div class="avatar-container" style="margin: 0 auto 20px auto; width: 130px; height: 130px; display: flex; justify-content: center; align-items: center; position: relative;">
            @if($profile->avatar_path)
                <img src="{{ asset('storage/' . $profile->avatar_path) }}" id="avatarPreview" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid #f3f4f6;">
            @else
                <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=2563eb&color=fff" id="avatarPreview" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid #f3f4f6;">
            @endif
        </div>
        
        <input type="file" name="avatar" id="avatarInput" accept="image/*" style="display: none;" form="mainProfileForm">
        
        <button type="button" class="btn-upload" onclick="document.getElementById('avatarInput').click();" style="background: #2563eb; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; margin-bottom: 10px;">
            <i class="fa-solid fa-camera"></i> Update Photo
        </button>
        <p style="font-size: 12px; color: #888; margin-bottom: 20px;">JPG or PNG • Max 2MB</p>

        <h3 style="font-weight: 600; font-size: 18px; margin-bottom: 5px;">{{ $user->name }}</h3>
        <p style="color: #666; font-size: 14px; margin-bottom: 25px;">Applicant Profile</p>

        <div style="text-align: left; border-top: 1px solid #eee; padding-top: 20px;">
            <h4 style="font-size: 14px; color: #374151; margin-bottom: 15px;"><i class="fa-solid fa-link"></i> Professional Links</h4>
            <div style="margin-bottom: 15px;">
                <label style="font-size: 13px; color: #6b7280; display: block; margin-bottom: 5px;">LinkedIn URL</label>
                <input type="url" name="linkedin_url" value="{{ old('linkedin_url', $profile->linkedin_url ?? '') }}" placeholder="https://linkedin.com/in/..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;" form="mainProfileForm">
            </div>
            <div>
                <label style="font-size: 13px; color: #6b7280; display: block; margin-bottom: 5px;">GitHub / Portfolio URL</label>
                <input type="url" name="portfolio_url" value="{{ old('portfolio_url', $profile->portfolio_url ?? '') }}" placeholder="https://github.com/..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;" form="mainProfileForm">
            </div>
        </div>
    </div>

    {{-- RIGHT CONTENT --}}
    <div class="profile-content" style="flex: 3; background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">

        <h2 style="font-size: 20px; font-weight: 600; color: #111827; margin-bottom: 25px; border-bottom: 2px solid #f3f4f6; padding-bottom: 10px;">General Details</h2>

        <div class="card-section" style="margin-bottom: 35px;">
            <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 14px;">Full Name</label>
                    <input type="text" value="{{ $user->name }}" readonly style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; background: #f9fafb; cursor: not-allowed; color: #6b7280;">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 14px;">Email Address</label>
                    <input type="email" value="{{ $user->email }}" readonly style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; background: #f9fafb; cursor: not-allowed; color: #6b7280;">
                </div>
            </div>
            <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 14px;">Phone Number</label>
                    {{-- Added phone-only class --}}
                    <input type="text" name="phone" class="phone-only" value="{{ old('phone', $profile->phone ?? '') }}" placeholder="+60 12-345 6789" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;" form="mainProfileForm">
                </div>
            </div>

            <h4 style="font-size: 15px; color: #4b5563; margin-top: 20px; margin-bottom: 10px;">Home Address</h4>
            <div class="form-group" style="margin-bottom: 15px;">
                <input type="text" name="address_line_1" value="{{ old('address_line_1', $profile->address_line_1 ?? '') }}" placeholder="Address Line 1 (e.g. No 12, Jalan Indah 1)" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 10px;" form="mainProfileForm">
                <input type="text" name="address_line_2" value="{{ old('address_line_2', $profile->address_line_2 ?? '') }}" placeholder="Address Line 2 (Optional)" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;" form="mainProfileForm">
            </div>
            <div class="form-row" style="display: flex; gap: 20px;">
                <div class="form-group" style="flex: 1;">
                    {{-- Added letters-only class --}}
                    <input type="text" name="city" class="letters-only" value="{{ old('city', $profile->city ?? '') }}" placeholder="City (e.g. Petaling Jaya)" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;" form="mainProfileForm">
                </div>
                
                <div class="form-group autocomplete-wrapper" style="flex: 1;">
                    {{-- Added letters-only class --}}
                    <input type="text" name="state" id="stateInput" class="autocomplete-input letters-only" value="{{ old('state', $profile->state ?? '') }}" placeholder="Type or select a state..." form="mainProfileForm" autocomplete="off">
                    <ul class="autocomplete-list" id="stateList">
                        <li class="autocomplete-item">Johor</li>
                        <li class="autocomplete-item">Kedah</li>
                        <li class="autocomplete-item">Kelantan</li>
                        <li class="autocomplete-item">Kuala Lumpur</li>
                        <li class="autocomplete-item">Labuan</li>
                        <li class="autocomplete-item">Melaka</li>
                        <li class="autocomplete-item">Negeri Sembilan</li>
                        <li class="autocomplete-item">Pahang</li>
                        <li class="autocomplete-item">Penang</li>
                        <li class="autocomplete-item">Perak</li>
                        <li class="autocomplete-item">Perlis</li>
                        <li class="autocomplete-item">Putrajaya</li>
                        <li class="autocomplete-item">Sabah</li>
                        <li class="autocomplete-item">Sarawak</li>
                        <li class="autocomplete-item">Selangor</li>
                        <li class="autocomplete-item">Terengganu</li>
                    </ul>
                </div>

                <div class="form-group" style="flex: 1;">
                    {{-- MODIFIED: Added numbers-only class to the postcode input --}}
                    <input type="text" name="postcode" class="numbers-only" value="{{ old('postcode', $profile->postcode ?? '') }}" placeholder="Postcode (e.g. 46000)" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;" form="mainProfileForm">
                </div>
            </div>
        </div>

        <div class="card-section" style="margin-bottom: 35px;">
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 14px; color: #4b5563;">Personal Summary</label>
                <textarea name="personal_summary" rows="4" placeholder="Briefly describe your professional background, key strengths, and career goals..." style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; resize: vertical; font-family: inherit;" form="mainProfileForm">{{ old('personal_summary', $profile->personal_summary ?? '') }}</textarea>
            </div>
            <div class="form-row" style="display: flex; gap: 20px;">
                
                <div class="form-group autocomplete-wrapper" style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 14px; color: #4b5563;">Industry Interest</label>
                    {{-- Added letters-only class --}}
                    <input type="text" name="industry_interest" id="industryInput" class="autocomplete-input letters-only" value="{{ old('industry_interest', $profile->industry_interest ?? '') }}" placeholder="Type or select an industry..." form="mainProfileForm" autocomplete="off">
                    <ul class="autocomplete-list" id="industryList">
                        <li class="autocomplete-item">Information Technology</li>
                        <li class="autocomplete-item">Accounting & Finance</li>
                        <li class="autocomplete-item">Human Resources</li>
                        <li class="autocomplete-item">Marketing</li>
                        <li class="autocomplete-item">Engineering</li>
                        <li class="autocomplete-item">Healthcare</li>
                        <li class="autocomplete-item">Education</li>
                        <li class="autocomplete-item">Business Administration</li>
                        <li class="autocomplete-item">Logistics & Supply Chain</li>
                        <li class="autocomplete-item">Arts & Design</li>
                    </ul>
                </div>

                <div class="form-group" style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 14px; color: #4b5563;">Licenses & Certifications</label>
                    <input type="text" name="licenses_certifications" value="{{ old('licenses_certifications', $profile->licenses_certifications ?? '') }}" placeholder="E.g., AWS Certified Developer, ACCA" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;" form="mainProfileForm">
                </div>
            </div>
        </div>

        <div style="margin: 40px 0; border-top: 2px dashed #e5e7eb;"></div>

        <h2 style="font-size: 20px; font-weight: 600; color: #111827; margin-bottom: 25px; border-bottom: 2px solid #f3f4f6; padding-bottom: 10px;">Professional History</h2>

        {{-- EXPERIENCE CARDS --}}
        <div class="card-section" style="margin-bottom: 35px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="font-size: 16px; color: #1f2937; font-weight: 600;"><i class="fa-solid fa-briefcase" style="color: #2563eb; margin-right: 8px;"></i> Experience</h3>
                <button type="button" onclick="document.getElementById('experienceModal').style.display='block'" style="background: none; border: none; color: #2563eb; font-weight: 600; cursor: pointer; font-size: 15px;">+ Add</button>
            </div>
            @foreach($profile->experiences()->get() as $exp)
            <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 15px; display: flex; justify-content: space-between;">
                <div>
                    <h4 style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 4px;">{{ $exp->job_title }}</h4>
                    <p style="font-size: 14px; color: #4b5563; margin-bottom: 4px;">{{ $exp->company_name }}</p>
                    <p style="font-size: 13px; color: #6b7280; margin-bottom: 8px;">{{ \Carbon\Carbon::parse($exp->start_date)->format('M Y') }} - {{ $exp->is_current ? 'Present' : (\Carbon\Carbon::parse($exp->end_date)->format('M Y')) }}</p>
                    @if($exp->description)<p style="font-size: 14px; color: #374151; white-space: pre-line;">{{ $exp->description }}</p>@endif
                </div>
                <form action="{{ route('applicant.experience.destroy', $exp->id) }}" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" style="background: #fef2f2; border: none; padding: 8px; border-radius: 4px; color: #dc2626; cursor: pointer;" onclick="return confirm('Delete this experience?')"><i class="fa-solid fa-trash"></i></button>
                </form>
            </div>
            @endforeach
            @if($profile->experiences()->count() == 0)
                <p style="color: #9ca3af; font-size: 14px; font-style: italic;">No experience added yet.</p>
            @endif
        </div>

        {{-- EDUCATION CARDS --}}
        <div class="card-section" style="margin-bottom: 35px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="font-size: 16px; color: #1f2937; font-weight: 600;">
                    <i class="fa-solid fa-graduation-cap" style="color: #2563eb; margin-right: 8px;"></i> 
                    Education <span style="color: #dc2626;" title="Required">*</span>
                </h3>
                <button type="button" onclick="document.getElementById('educationModal').style.display='block'" style="background: none; border: none; color: #2563eb; font-weight: 600; cursor: pointer; font-size: 15px;">+ Add</button>
            </div>
            @foreach($profile->educations()->get() as $edu)
            <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 15px; display: flex; justify-content: space-between;">
                <div>
                    <h4 style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 4px;">{{ $edu->degree_title }} @if($edu->field_of_study) ({{ $edu->field_of_study }}) @endif</h4>
                    <p style="font-size: 14px; color: #4b5563; margin-bottom: 4px;">{{ $edu->institution_name }}</p>
                    <p style="font-size: 13px; color: #6b7280;">{{ $edu->start_date ? \Carbon\Carbon::parse($edu->start_date)->format('Y') : '' }} - {{ $edu->is_current ? 'Present' : ($edu->end_date ? \Carbon\Carbon::parse($edu->end_date)->format('Y') : 'N/A') }}</p>
                </div>
                <form action="{{ route('applicant.education.destroy', $edu->id) }}" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" style="background: #fef2f2; border: none; padding: 8px; border-radius: 4px; color: #dc2626; cursor: pointer;" onclick="return confirm('Delete this education?')"><i class="fa-solid fa-trash"></i></button>
                </form>
            </div>
            @endforeach
            @if($profile->educations()->count() == 0)
                <p style="color: #9ca3af; font-size: 14px; font-style: italic;">No education added yet.</p>
            @endif
        </div>

        {{-- SKILLS CARDS --}}
        <div class="card-section" style="margin-bottom: 35px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="font-size: 16px; color: #1f2937; font-weight: 600;">
                    <i class="fa-solid fa-laptop-code" style="color: #2563eb; margin-right: 8px;"></i> 
                    Skills <span style="color: #dc2626;" title="Required">*</span>
                </h3>
                <button type="button" onclick="document.getElementById('skillModal').style.display='block'" style="background: none; border: none; color: #2563eb; font-weight: 600; cursor: pointer; font-size: 15px;">+ Add</button>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                @foreach($profile->skills()->get() as $skill)
                <div style="border: 1px solid #e5e7eb; background: #f9fafb; border-radius: 30px; padding: 8px 16px; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 14px; font-weight: 500; color: #374151;">{{ $skill->skill_name }} <span style="color: #9ca3af; font-weight: normal;">({{ $skill->proficiency }})</span></span>
                    <form action="{{ route('applicant.skill.destroy', $skill->id) }}" method="POST" style="margin: 0;">
                        @csrf @method('DELETE')
                        <button type="submit" style="background: none; border: none; color: #dc2626; cursor: pointer; padding: 0;"><i class="fa-solid fa-xmark"></i></button>
                    </form>
                </div>
                @endforeach
            </div>
            @if($profile->skills()->count() == 0)
                <p style="color: #9ca3af; font-size: 14px; font-style: italic;">No skills added yet.</p>
            @endif
        </div>

        {{-- LANGUAGE CARDS --}}
        <div class="card-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="font-size: 16px; color: #1f2937; font-weight: 600;">
                    <i class="fa-solid fa-language" style="color: #2563eb; margin-right: 8px;"></i> 
                    Languages <span style="color: #dc2626;" title="Required">*</span>
                </h3>
                <button type="button" onclick="document.getElementById('languageModal').style.display='block'" style="background: none; border: none; color: #2563eb; font-weight: 600; cursor: pointer; font-size: 15px;">+ Add</button>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 15px;">
                @foreach($profile->languages()->get() as $lang)
                <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px; width: calc(50% - 8px); display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h4 style="font-size: 15px; font-weight: 600; color: #1f2937;">{{ $lang->language_name }}</h4>
                        <p style="font-size: 13px; color: #6b7280; margin-top: 2px;">{{ $lang->proficiency }}</p>
                    </div>
                    <form action="{{ route('applicant.language.destroy', $lang->id) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" style="background: none; border: none; color: #9ca3af; cursor: pointer;"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
                @endforeach
            </div>
            @if($profile->languages()->count() == 0)
                <p style="color: #9ca3af; font-size: 14px; font-style: italic; margin-top: -5px;">No languages added yet.</p>
            @endif
        </div>


        <div style="margin: 40px 0; border-top: 2px dashed #e5e7eb;"></div>


        {{-- 3. RESUME UPLOAD & FINAL SAVE --}}
        <div class="card-section">
            <h3 style="margin-bottom: 20px; font-size: 20px; font-weight: 600; color: #111827;">Resume Upload</h3>
            <div class="resume-box" style="border: 2px dashed #cbd5e1; padding: 30px; border-radius: 8px; text-align: center; background: #f8fafc;">
                <p style="margin-bottom: 15px; color: #475569; font-weight: 500;">Upload your latest resume (PDF, DOC, DOCX)</p>
                
                <input type="file" name="resume" accept=".pdf,.doc,.docx" class="resume-input" style="margin-bottom: 10px;" form="mainProfileForm">
                
                @if($profile->resume_path)
                    <div class="resume-item" style="margin-top: 20px; background: #fff; padding: 15px; border-radius: 8px; display: flex; align-items: center; justify-content: space-between; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <i class="fa-solid fa-file-invoice" style="color: #ef4444; font-size: 28px;"></i>
                            <div style="text-align: left;">
                                <span style="display: block; font-weight: 600; color: #1e293b;">Current Resume Active</span>
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <a href="{{ asset('storage/' . $profile->resume_path) }}" target="_blank" style="padding: 6px 12px; background: #eff6ff; color: #2563eb; text-decoration: none; border-radius: 4px; font-weight: 500; font-size: 13px;">View File</a>
                            <a href="{{ route('applicant.resume.delete') }}" style="padding: 6px 12px; background: #fef2f2; color: #dc2626; text-decoration: none; border-radius: 4px; font-weight: 500; font-size: 13px;" onclick="return confirm('Remove your current resume?');">Delete</a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="form-actions" style="margin-top: 35px; text-align: right;">
            <p style="font-size: 13px; color: #6b7280; display: inline-block; margin-right: 15px;">Click to save General Details & your newly uploaded Resume.</p>
            <button type="submit" style="background: #2563eb; color: #fff; padding: 12px 30px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: 600; box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);" form="mainProfileForm">Save Profile</button>
        </div>

    </div>

</div>

{{-- =========================================================
     MODALS & STYLES
     ========================================================= --}}
<style>
    .hrms-modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow-y: auto; }
    .hrms-modal-content { background-color: #fff; margin: 5% auto; padding: 30px; border-radius: 12px; width: 90%; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    .hrms-close { float: right; font-size: 24px; font-weight: bold; cursor: pointer; color: #9ca3af; line-height: 1; }
    .hrms-close:hover { color: #1f2937; }
    .modal-input { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; margin-top: 5px; font-family: inherit; }
    .modal-label { font-size: 14px; font-weight: 500; color: #374151; }

    /* Custom Autocomplete */
    .autocomplete-wrapper { position: relative; }
    .autocomplete-input { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; outline: none; transition: border-color 0.2s; font-family: inherit; }
    .autocomplete-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
    
    .autocomplete-list { position: absolute; top: calc(100% + 4px); left: 0; width: 100%; max-height: 220px; overflow-y: auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); z-index: 50; padding: 5px 0; list-style: none; margin: 0; display: none; }
    .autocomplete-list.active { display: block; }
    
    .autocomplete-item { padding: 10px 16px; cursor: pointer; font-size: 14px; color: #374151; transition: background 0.1s; }
    .autocomplete-item:hover { background: #f8fafc; color: #111827; }
    
    .autocomplete-list::-webkit-scrollbar { width: 6px; }
    .autocomplete-list::-webkit-scrollbar-track { background: transparent; }
    .autocomplete-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .autocomplete-list::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>

<div id="experienceModal" class="hrms-modal">
    <div class="hrms-modal-content">
        <span class="hrms-close" onclick="document.getElementById('experienceModal').style.display='none'">&times;</span>
        <h3 style="margin-bottom: 20px; font-size: 20px; color: #111827;">Add Experience</h3>
        <form action="{{ route('applicant.experience.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 15px;">
                <label class="modal-label">Job Title</label>
                <input type="text" name="job_title" class="modal-input" required placeholder="e.g. Software Engineer">
            </div>
            <div style="margin-bottom: 15px;">
                <label class="modal-label">Company Name</label>
                <input type="text" name="company_name" class="modal-input" required placeholder="e.g. Tech Corp">
            </div>
            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label class="modal-label">Start Date</label>
                    <input type="date" name="start_date" class="modal-input" required>
                </div>
                <div style="flex: 1;">
                    <label class="modal-label">End Date</label>
                    <input type="date" name="end_date" id="expEndDate" class="modal-input">
                </div>
            </div>
            <div style="margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="is_current" id="expCurrentCheck" onchange="document.getElementById('expEndDate').disabled = this.checked;">
                <label for="expCurrentCheck" style="font-size: 14px; color: #4b5563;">I currently work here</label>
            </div>
            <div style="margin-bottom: 20px;">
                <label class="modal-label">Description (Optional)</label>
                <textarea name="description" class="modal-input" rows="3" placeholder="Describe your responsibilities..."></textarea>
            </div>
            <button type="submit" style="background: #2563eb; color: #fff; padding: 12px; border: none; border-radius: 6px; cursor: pointer; width: 100%; font-weight: 600;">Save Experience</button>
        </form>
    </div>
</div>

<div id="educationModal" class="hrms-modal">
    <div class="hrms-modal-content">
        <span class="hrms-close" onclick="document.getElementById('educationModal').style.display='none'">&times;</span>
        <h3 style="margin-bottom: 20px; font-size: 20px; color: #111827;">Add Education</h3>
        <form action="{{ route('applicant.education.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 15px;">
                <label class="modal-label">Institution Name</label>
                <input type="text" name="institution_name" class="modal-input" required placeholder="e.g. University of Malaya">
            </div>
            <div style="margin-bottom: 15px;">
                <label class="modal-label">Degree Title</label>
                <input type="text" name="degree_title" class="modal-input" required placeholder="e.g. Bachelor's Degree">
            </div>
            <div style="margin-bottom: 15px;">
                <label class="modal-label">Field of Study</label>
                <input type="text" name="field_of_study" class="modal-input" placeholder="e.g. Computer Science">
            </div>
            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label class="modal-label">Start Date</label>
                    <input type="date" name="start_date" class="modal-input">
                </div>
                <div style="flex: 1;">
                    <label class="modal-label">End Date (or Expected)</label>
                    <input type="date" name="end_date" id="eduEndDate" class="modal-input">
                </div>
            </div>
            <button type="submit" style="background: #2563eb; color: #fff; padding: 12px; border: none; border-radius: 6px; cursor: pointer; width: 100%; font-weight: 600;">Save Education</button>
        </form>
    </div>
</div>

<div id="skillModal" class="hrms-modal">
    <div class="hrms-modal-content">
        <span class="hrms-close" onclick="document.getElementById('skillModal').style.display='none'">&times;</span>
        <h3 style="margin-bottom: 20px; font-size: 20px; color: #111827;">Add Skill</h3>
        <form action="{{ route('applicant.skill.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 15px;">
                <label class="modal-label">Skill</label>
                <input type="text" name="skill_name" class="modal-input" required placeholder="e.g. Laravel, Data Analysis">
            </div>
            <div style="margin-bottom: 20px;">
                <label class="modal-label">Proficiency</label>
                <select name="proficiency" class="modal-input">
                    <option value="Beginner">Beginner</option>
                    <option value="Intermediate">Intermediate</option>
                    <option value="Advanced">Advanced</option>
                    <option value="Expert">Expert</option>
                </select>
            </div>
            <button type="submit" style="background: #2563eb; color: #fff; padding: 12px; border: none; border-radius: 6px; cursor: pointer; width: 100%; font-weight: 600;">Save Skill</button>
        </form>
    </div>
</div>

<div id="languageModal" class="hrms-modal">
    <div class="hrms-modal-content">
        <span class="hrms-close" onclick="document.getElementById('languageModal').style.display='none'">&times;</span>
        <h3 style="margin-bottom: 20px; font-size: 20px; color: #111827;">Add Language</h3>
        <form action="{{ route('applicant.language.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 15px;">
                <label class="modal-label">Language</label>
                {{-- Added letters-only class --}}
                <input type="text" name="language_name" class="modal-input letters-only" required placeholder="e.g. English, Mandarin">
            </div>
            <div style="margin-bottom: 20px;">
                <label class="modal-label">Proficiency</label>
                <select name="proficiency" class="modal-input">
                    <option value="Native or Bilingual">Native or Bilingual</option>
                    <option value="Fluent">Fluent</option>
                    <option value="Conversational">Conversational</option>
                    <option value="Basic">Basic</option>
                </select>
            </div>
            <button type="submit" style="background: #2563eb; color: #fff; padding: 12px; border: none; border-radius: 6px; cursor: pointer; width: 100%; font-weight: 600;">Save Language</button>
        </form>
    </div>
</div>

<script>
    // 1. Avatar Preview
    document.getElementById('avatarInput').addEventListener('change', function(event){
        const [file] = event.target.files;
        if (file) {
            document.getElementById('avatarPreview').src = URL.createObjectURL(file);
        }
    });

    // 2. Close Modals on background click
    window.onclick = function(event) {
        if (event.target.classList.contains('hrms-modal')) {
            event.target.style.display = "none";
        }
    };

    // 3. Autocomplete Setup
    function setupAutocomplete(inputId, listId) {
        const input = document.getElementById(inputId);
        const list = document.getElementById(listId);
        const items = list.querySelectorAll('.autocomplete-item');

        input.addEventListener('focus', () => {
            list.classList.add('active');
            items.forEach(item => item.style.display = 'block');
        });

        input.addEventListener('input', () => {
            const filter = input.value.toLowerCase();
            let hasVisibleItems = false;

            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(filter)) {
                    item.style.display = 'block';
                    hasVisibleItems = true;
                } else {
                    item.style.display = 'none';
                }
            });

            if (hasVisibleItems) {
                list.classList.add('active');
            } else {
                list.classList.remove('active');
            }
        });

        items.forEach(item => {
            item.addEventListener('click', () => {
                input.value = item.textContent;
                list.classList.remove('active');
                // Trigger change event so SessionStorage catches the update
                input.dispatchEvent(new Event('change')); 
            });
        });

        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !list.contains(e.target)) {
                list.classList.remove('active');
            }
        });
    }

    setupAutocomplete('stateInput', 'stateList');
    setupAutocomplete('industryInput', 'industryList');

    // 4. JS VALIDATION: Instantly block invalid characters
    document.querySelectorAll('.letters-only').forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^a-zA-Z\s\-]/g, '');
        });
    });

    document.querySelectorAll('.phone-only').forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9\+\-\s]/g, '');
        });
    });

    // === ADDED: JS VALIDATION FOR NUMBERS ONLY ===
    document.querySelectorAll('.numbers-only').forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, ''); // Instantly removes letters/symbols
        });
    });

    // ==========================================================
    // 5. THE BUG FIX: AUTO-RECOVERY STATE (SESSION STORAGE)
    // ==========================================================
    document.addEventListener('DOMContentLoaded', function() {
        const mainInputs = document.querySelectorAll('[form="mainProfileForm"]');
        const storageKey = 'hrms_unsaved_profile_{{ $user->user_id }}';

        // A. Restore data if page just reloaded from a modal submission
        const savedState = sessionStorage.getItem(storageKey);
        if (savedState) {
            const parsed = JSON.parse(savedState);
            mainInputs.forEach(input => {
                // Ignore file inputs and only restore if we have saved text
                if (input.type !== 'file' && parsed[input.name] !== undefined) {
                    input.value = parsed[input.name];
                }
            });
        }

        // B. Save data silently whenever the user types something
        const saveState = () => {
            const currentState = {};
            mainInputs.forEach(input => {
                if (input.type !== 'file' && input.name) {
                    currentState[input.name] = input.value;
                }
            });
            sessionStorage.setItem(storageKey, JSON.stringify(currentState));
        };

        mainInputs.forEach(input => {
            input.addEventListener('input', saveState);
            input.addEventListener('change', saveState);
        });

        // C. Clear the memory ONLY when they actually click "Save Profile"
        const saveProfileBtn = document.querySelector('button[form="mainProfileForm"]');
        if (saveProfileBtn) {
            saveProfileBtn.addEventListener('click', () => {
                sessionStorage.removeItem(storageKey);
            });
        }
    });
</script>

@endsection