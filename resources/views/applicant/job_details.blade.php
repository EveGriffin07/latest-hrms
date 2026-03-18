@extends('applicant.layout')

@section('content')

{{-- BACK BUTTON --}}
<div style="margin-bottom: 20px;">
    <a href="{{ route('applicant.jobs') }}" style="color: #64748b; text-decoration: none; font-size: 14px; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; transition: color 0.2s;">
        <i class="fa-solid fa-arrow-left"></i> Back to All Jobs
    </a>
</div>

{{-- ERROR MESSAGES --}}
@if ($errors->any())
    <div style="background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #f87171;">
        <i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}
    </div>
@endif

@php
    // Check if the job is closed or past deadline
    $isClosed = \Carbon\Carbon::parse($job->closing_date)->isPast() || $job->job_status === 'Closed';
    
    // Dynamic Badge Colors
    $badgeColors = match($job->job_type) {
        'Full-Time'  => 'background: #dcfce7; color: #166534;',
        'Part-Time'  => 'background: #fef3c7; color: #92400e;', 
        'Contract'   => 'background: #f3e8ff; color: #6b21a8;',
        'Internship' => 'background: #e0e7ff; color: #3730a3;',
        default      => 'background: #f1f5f9; color: #475569;'
    };
@endphp

{{-- MAIN LAYOUT CONTAINER --}}
<div style="display: flex; gap: 30px; align-items: flex-start; flex-wrap: wrap;">

    {{-- LEFT COLUMN: Heavy Content (Description & Requirements) --}}
    <div style="flex: 1 1 65%; min-width: 300px;">
        
        {{-- Job Title Header --}}
        <div style="background: #fff; padding: 40px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px rgba(0,0,0,0.02); margin-bottom: 30px; position: relative; overflow: hidden;">
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 6px; background: #2563eb;"></div>
            
            <span style="{{ $badgeColors }} padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; display: inline-block; margin-bottom: 15px;">
                {{ $job->job_type }}
            </span>
            
            <h1 style="font-size: 28px; font-weight: 700; color: #111827; margin-bottom: 10px; line-height: 1.3;">
                {{ $job->job_title }}
            </h1>
            
            <p style="color: #4b5563; font-size: 16px; font-weight: 500;">
                <i class="fa-solid fa-building" style="color: #9ca3af; margin-right: 6px;"></i> {{ $job->department }} Department
            </p>
        </div>

        {{-- Job Description --}}
        <div style="background: #fff; padding: 40px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px rgba(0,0,0,0.02); margin-bottom: 30px;">
            <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin-bottom: 20px; border-bottom: 2px solid #f3f4f6; padding-bottom: 10px;">
                <i class="fa-solid fa-circle-info" style="color: #2563eb; margin-right: 8px;"></i> About the Role
            </h3>
            <div style="color: #374151; font-size: 15px; line-height: 1.8;">
                {!! nl2br(e($job->job_description)) !!}
            </div>
        </div>

        {{-- Requirements & Responsibilities --}}
        <div style="background: #fff; padding: 40px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
            <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin-bottom: 20px; border-bottom: 2px solid #f3f4f6; padding-bottom: 10px;">
                <i class="fa-solid fa-list-check" style="color: #2563eb; margin-right: 8px;"></i> Requirements & Responsibilities
            </h3>
            <div style="color: #374151; font-size: 15px; line-height: 1.8;">
                {!! nl2br(e($job->requirements)) !!}
            </div>
        </div>

    </div>

    {{-- RIGHT COLUMN: Sticky Summary & CTA Card --}}
    <div style="flex: 1 1 30%; min-width: 280px; position: sticky; top: 20px;">
        
        <div style="background: #f8fafc; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);">
            
            <h3 style="font-size: 16px; font-weight: 600; color: #1e293b; margin-bottom: 20px;">Job Summary</h3>

            <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 30px;">
                
                <div style="display: flex; gap: 12px; align-items: flex-start;">
                    <i class="fa-solid fa-location-dot" style="color: #64748b; font-size: 16px; margin-top: 2px; width: 16px; text-align: center;"></i>
                    <div>
                        <p style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;">Location</p>
                        <p style="font-size: 14px; color: #0f172a; font-weight: 500;">{{ $job->location }}</p>
                    </div>
                </div>

                @if($job->salary_range)
                <div style="display: flex; gap: 12px; align-items: flex-start;">
                    <i class="fa-solid fa-money-bill-wave" style="color: #64748b; font-size: 16px; margin-top: 2px; width: 16px; text-align: center;"></i>
                    <div>
                        <p style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;">Salary Range</p>
                        <p style="font-size: 14px; color: #0f172a; font-weight: 500;">{{ $job->salary_range }}</p>
                    </div>
                </div>
                @endif

                <div style="display: flex; gap: 12px; align-items: flex-start;">
                    <i class="fa-solid fa-calendar-plus" style="color: #64748b; font-size: 16px; margin-top: 2px; width: 16px; text-align: center;"></i>
                    <div>
                        <p style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;">Date Posted</p>
                        <p style="font-size: 14px; color: #0f172a; font-weight: 500;">{{ $job->created_at->format('d M Y') }}</p>
                        <p style="font-size: 12px; color: #94a3b8; margin-top: 2px;">{{ $job->created_at->diffForHumans() }}</p>
                    </div>
                </div>

                <div style="display: flex; gap: 12px; align-items: flex-start;">
                    <i class="fa-solid fa-hourglass-end" style="color: {{ $isClosed ? '#ef4444' : '#64748b' }}; font-size: 16px; margin-top: 2px; width: 16px; text-align: center;"></i>
                    <div>
                        <p style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;">Closing Date</p>
                        <p style="font-size: 14px; color: {{ $isClosed ? '#ef4444' : '#0f172a' }}; font-weight: 500;">
                            {{ \Carbon\Carbon::parse($job->closing_date)->format('d M Y') }}
                        </p>
                    </div>
                </div>

            </div>

            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 20px;">

            {{-- ACTION BUTTON --}}
            @if($isClosed)
                <div style="text-align: center;">
                    <button disabled style="width: 100%; padding: 14px; background: #e2e8f0; color: #64748b; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: not-allowed; display: flex; justify-content: center; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-lock"></i> Applications Closed
                    </button>
                    <p style="font-size: 13px; color: #64748b; margin-top: 10px;">This position is no longer accepting new applicants.</p>
                </div>
            @else
                <div style="text-align: center;">
                    <a href="{{ route('applicant.jobs.apply', $job->job_id) }}" style="display: block; width: 100%; padding: 14px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 8px; font-size: 15px; font-weight: 600; transition: background 0.2s; box-shadow: 0 4px 6px rgba(37,99,235,0.2);">
                        Apply for this Job
                    </a>
                    <p style="font-size: 13px; color: #64748b; margin-top: 10px;">
                        <i class="fa-solid fa-shield-halved"></i> Apply securely via HRMS Portal
                    </p>
                </div>
            @endif

        </div>
    </div>

</div>

@endsection