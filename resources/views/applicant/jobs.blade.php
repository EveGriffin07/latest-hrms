@extends('applicant.layout')

@section('content')

{{-- Custom CSS for Pagination and the New Success Pop-up (Toast) --}}
<style>
    /* Pagination Styles */
    nav[role="navigation"] { display: flex; justify-content: space-between; align-items: center; margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 20px; }
    nav[role="navigation"] p { font-size: 14px; color: #64748b; margin: 0; }
    nav[role="navigation"] > div { display: flex; gap: 5px; }
    nav[role="navigation"] span.relative.z-0.inline-flex { display: inline-flex; box-shadow: 0 1px 2px rgba(0,0,0,0.05); border-radius: 6px; }
    nav[role="navigation"] a, nav[role="navigation"] span[aria-disabled] { padding: 8px 14px; font-size: 14px; font-weight: 500; color: #374151; background: #fff; border: 1px solid #d1d5db; text-decoration: none; display: flex; align-items: center; justify-content: center; }
    nav[role="navigation"] a:hover { background: #f8fafc; color: #2563eb; }
    nav[role="navigation"] span[aria-current="page"] span { background: #2563eb; color: #fff; border-color: #2563eb; padding: 8px 14px; font-size: 14px; font-weight: 600; border: 1px solid #2563eb; display: block; }
    nav[role="navigation"] svg { width: 16px; height: 16px; }

    /* Success Toast Pop-up Styles */
    .toast-popup {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: #fff;
        border-left: 4px solid #10b981; /* Emerald Green */
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);
        padding: 16px 24px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 15px;
        z-index: 9999;
        /* Animation: Slide down, pause, fade out */
        animation: toastSlideDownFadeOut 4.5s ease-in-out forwards;
    }
    .toast-popup i { color: #10b981; font-size: 24px; }
    .toast-content { display: flex; flex-direction: column; }
    .toast-title { font-weight: 600; color: #0f172a; font-size: 15px; }
    .toast-message { color: #64748b; font-size: 14px; margin-top: 2px; }
    .toast-close { background: none; border: none; color: #94a3b8; font-size: 20px; cursor: pointer; padding: 0; margin-left: 15px; }
    .toast-close:hover { color: #0f172a; }

    @keyframes toastSlideDownFadeOut {
        0% { top: -50px; opacity: 0; }
        10% { top: 20px; opacity: 1; } /* Fully visible */
        85% { top: 20px; opacity: 1; } /* Stay visible */
        100% { top: 0px; opacity: 0; visibility: hidden; } /* Fade up and away */
    }
</style>

{{-- =======================================================
     SUCCESS TOAST POP-UP
======================================================== --}}
@if(session('success'))
    <div id="successToast" class="toast-popup">
        <i class="fa-solid fa-circle-check"></i>
        <div class="toast-content">
            <span class="toast-title">Success!</span>
            <span class="toast-message">{{ session('success') }}</span>
        </div>
        <button class="toast-close" onclick="document.getElementById('successToast').style.display='none'">&times;</button>
    </div>
@endif

{{-- PAGE HEADER --}}
<div style="margin-bottom: 30px;">
    <h2 class="page-title" style="font-size: 24px; font-weight: 700; color: #111827; margin-bottom: 5px;">Find Your Next Role</h2>
    <p class="page-subtitle" style="color: #6b7280; font-size: 15px;">Discover open positions and apply directly through the HRMS portal.</p>
</div>

{{-- ERROR ALERTS --}}
@if ($errors->any())
    <div style="background: #fee2e2; color: #b91c1c; padding: 12px 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #f87171;">
        <i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}
    </div>
@endif

{{-- =======================================================
     SECTION 1: SMART RECOMMENDATIONS
======================================================== --}}
<div style="background: linear-gradient(to right, #eff6ff, #f8fafc); border: 1px solid #bfdbfe; border-radius: 12px; padding: 25px; margin-bottom: 40px;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="font-size: 18px; font-weight: 600; color: #1e3a8a;">
            <i class="fa-solid fa-star" style="color: #f59e0b; margin-right: 8px;"></i> Recommended For You
        </h3>
        @if($industryInterest)
            <span style="background: #dbeafe; color: #1e40af; font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 20px;">Based on your interest: {{ $industryInterest }}</span>
        @endif
    </div>

    @if(!$industryInterest)
        {{-- Profile Incomplete --}}
        <div style="text-align: center; padding: 20px 0;">
            <p style="color: #475569; margin-bottom: 15px;">We can recommend jobs perfectly tailored to you if you tell us your industry preference.</p>
            <a href="{{ route('applicant.profile') }}" style="background: #2563eb; color: #fff; padding: 8px 20px; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 500; transition: 0.2s;">Update Profile to get Matches</a>
        </div>
    @elseif($recommendedJobs->isEmpty())
        {{-- Profile Complete, but no matching open jobs --}}
        <div style="text-align: center; padding: 20px 0; color: #64748b;">
            <i class="fa-solid fa-magnifying-glass" style="font-size: 24px; margin-bottom: 10px; opacity: 0.5;"></i>
            <p>We don't have any open roles in <strong>{{ $industryInterest }}</strong> right now. Check out our other open positions below!</p>
        </div>
    @else
        {{-- We have matches! --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
            @foreach($recommendedJobs as $job)
                <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border-top: 4px solid #2563eb;">
                    <h4 style="font-size: 16px; font-weight: 600; color: #111827; margin-bottom: 5px;">{{ $job->job_title }}</h4>
                    <p style="color: #4b5563; font-size: 14px; margin-bottom: 15px;"><i class="fa-solid fa-building" style="margin-right: 5px; color: #9ca3af;"></i> {{ $job->department }}</p>
                    
                    <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                        <span style="background: #f1f5f9; color: #475569; font-size: 12px; padding: 4px 10px; border-radius: 4px; font-weight: 500;">{{ $job->job_type }}</span>
                        <span style="background: #f1f5f9; color: #475569; font-size: 12px; padding: 4px 10px; border-radius: 4px; font-weight: 500;"><i class="fa-solid fa-location-dot"></i> {{ $job->location }}</span>
                    </div>

                    <a href="{{ route('applicant.jobs.show', $job->job_id) }}" style="display: block; text-align: center; background: #eff6ff; color: #2563eb; text-decoration: none; padding: 8px 0; border-radius: 6px; font-size: 14px; font-weight: 600; border: 1px solid #bfdbfe;">View Details</a>
                </div>
            @endforeach
        </div>
    @endif
</div>


{{-- =======================================================
     SECTION 2: ALL OPEN POSITIONS & SEARCH FORM
======================================================== --}}
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
    <h3 style="font-size: 18px; font-weight: 600; color: #111827;">Explore All Open Roles</h3>
    
    {{-- Backend Search & Filter Form --}}
    <form method="GET" action="{{ route('applicant.jobs') }}" style="display: flex; gap: 10px; align-items: center;">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title or department..." style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; outline: none; width: 220px;">
        
        <select name="type" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; outline: none; background: #fff;">
            <option value="all" {{ request('type') == 'all' ? 'selected' : '' }}>All Job Types</option>
            <option value="Full-Time" {{ request('type') == 'Full-Time' ? 'selected' : '' }}>Full-time</option>
            <option value="Part-Time" {{ request('type') == 'Part-Time' ? 'selected' : '' }}>Part-time</option>
            <option value="Contract" {{ request('type') == 'Contract' ? 'selected' : '' }}>Contract</option>
            <option value="Internship" {{ request('type') == 'Internship' ? 'selected' : '' }}>Internship</option>
        </select>
        
        <button type="submit" style="background: #2563eb; color: #fff; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600;">Filter</button>
        
        @if(request('search') || (request('type') && request('type') != 'all'))
            <a href="{{ route('applicant.jobs') }}" style="padding: 8px 16px; background: #f1f5f9; color: #475569; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 500;">Clear</a>
        @endif
    </form>
</div>

<div class="jobs-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">

    @forelse($allJobs as $job)
        <div class="job-card" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 25px; transition: 0.2s box-shadow;">
            
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                <h3 style="font-size: 17px; font-weight: 600; color: #111827;">{{ $job->job_title }}</h3>
                
                {{-- Dynamic Badge Logic --}}
                @php
                    $badgeColors = match($job->job_type) {
                        'Full-Time'  => 'background: #dcfce7; color: #166534;',
                        'Part-Time'  => 'background: #fef3c7; color: #92400e;', 
                        'Contract'   => 'background: #f3e8ff; color: #6b21a8;',
                        'Internship' => 'background: #e0e7ff; color: #3730a3;',
                        default      => 'background: #f1f5f9; color: #475569;'
                    };
                @endphp
                <span style="{{ $badgeColors }} padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; white-space: nowrap;">{{ $job->job_type }}</span>
            </div>

            <p style="color: #4b5563; font-size: 14px; margin-bottom: 5px;"><i class="fa-solid fa-building" style="width: 16px; color: #9ca3af;"></i> {{ $job->department }}</p>
            <p style="color: #6b7280; font-size: 13px; margin-bottom: 25px;"><i class="fa-solid fa-location-dot" style="width: 16px; color: #9ca3af;"></i> {{ $job->location }}</p>

            <div style="display: flex; gap: 10px;">
                <a href="{{ route('applicant.jobs.show', $job->job_id) }}" style="flex: 1; text-align: center; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; color: #374151; font-size: 14px; font-weight: 500; text-decoration: none;">Details</a>

                @if(\Carbon\Carbon::parse($job->closing_date)->isPast() || $job->job_status === 'Closed')
                    <button disabled style="flex: 1; text-align: center; padding: 10px; background: #f3f4f6; color: #9ca3af; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: not-allowed;">Closed</button>
                @else
                    <a href="{{ route('applicant.jobs.apply', $job->job_id) }}" style="flex: 1; text-align: center; padding: 10px; background: #2563eb; color: #fff; border-radius: 6px; font-size: 14px; font-weight: 600; text-decoration: none;">Apply Now</a>
                @endif
            </div>
        </div>
    @empty
        <div style="grid-column: 1 / -1; text-align: center; padding: 50px; background: #fff; border: 1px dashed #cbd5e1; border-radius: 12px;">
            <i class="fa-solid fa-folder-open" style="font-size: 40px; color: #94a3b8; margin-bottom: 15px;"></i>
            <h3 style="font-size: 18px; color: #334155; margin-bottom: 5px;">No Positions Found</h3>
            <p style="color: #64748b;">We couldn't find any job positions matching your search criteria.</p>
        </div>
    @endforelse

</div>

{{-- THE MAGICAL PAGINATION BUTTONS --}}
{{ $allJobs->links() }}

{{-- Auto-remove the toast from the DOM after animation finishes --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toast = document.getElementById('successToast');
        if (toast) {
            // Completely hide it after the 4.5s animation completes so it doesn't block clicks
            setTimeout(() => {
                toast.style.display = 'none';
            }, 4500);
        }
    });
</script>

@endsection