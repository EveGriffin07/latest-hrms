@extends('applicant.layout')

@section('content')

<style>
  /* Base Layout */
  .page-header { margin-bottom: 30px; }
  .page-title { font-size: 24px; font-weight: 700; color: #111827; margin-bottom: 5px; }
  .page-subtitle { color: #6b7280; font-size: 15px; }

  /* Table Styling */
  .applications-container { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); overflow: hidden; }
  .applications-table { width: 100%; border-collapse: collapse; text-align: left; }
  .applications-table th { background: #f8fafc; padding: 16px 20px; font-size: 13px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb; }
  .applications-table td { padding: 16px 20px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
  .applications-table tbody tr.main-row:hover { background: #f8fafc; }
  
  /* Status Badges */
  .status-badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
  .badge-applied { background: #e0e7ff; color: #3730a3; }
  .badge-review { background: #fef3c7; color: #92400e; }
  .badge-interview { background: #fce7f3; color: #9d174d; }
  .badge-hired { background: #dcfce7; color: #166534; }
  .badge-rejected { background: #fee2e2; color: #b91c1c; }

  /* Buttons */
  .btn-view { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; background: #eff6ff; color: #2563eb; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 600; transition: 0.2s; border: 1px solid #bfdbfe; cursor: pointer; }
  .btn-view:hover { background: #2563eb; color: #fff; }

  /* Details Expandable Row */
  .app-details-row { display: none; }
  .app-details-cell { padding: 0 !important; background: #f8fafc; border-bottom: 2px solid #e5e7eb !important; }
  
  .app-details-card { padding: 25px 30px; }
  .app-details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
  
  @media (max-width: 900px) { .app-details-grid { grid-template-columns: 1fr; } }

  .detail-section { background: #fff; padding: 20px; border-radius: 10px; border: 1px solid #e5e7eb; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
  .detail-section h4 { font-size: 14px; font-weight: 600; color: #1e3a8a; margin: 0 0 15px; display: flex; align-items: center; gap: 8px; }

  .app-kv { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #e5e7eb; font-size: 13px; }
  .app-kv:last-child { border-bottom: 0; }
  .app-kv .k { color: #64748b; font-weight: 500; }
  .app-kv .v { color: #0f172a; font-weight: 600; text-align: right; }

  /* Timeline */
  .timeline { display: flex; flex-direction: column; gap: 15px; margin-top: 10px; }
  .step { display: flex; gap: 12px; align-items: flex-start; position: relative; }
  .step:not(:last-child)::after { content: ''; position: absolute; left: 6px; top: 20px; bottom: -15px; width: 2px; background: #e2e8f0; }
  .dot { width: 14px; height: 14px; border-radius: 50%; background: #cbd5e1; margin-top: 3px; position: relative; z-index: 2; border: 3px solid #f8fafc; box-sizing: content-box; }
  .step.done .dot { background: #10b981; }
  .step.active .dot { background: #3b82f6; box-shadow: 0 0 0 3px #bfdbfe; }
  .step.rejected .dot { background: #ef4444; }
  .step .txt { font-size: 13px; }
  .step .txt .t { font-weight: 600; color: #1e293b; }
  .step .txt .d { color: #64748b; font-size: 12px; margin-top: 2px; }
</style>

<div class="page-header">
  <h2 class="page-title">My Applications</h2>
  <p class="page-subtitle">View all your submitted job applications and track their progress.</p>
</div>

<div class="applications-container">

  <table class="applications-table">
    <thead>
      <tr>
        <th>Position Applied</th>
        <th>Department</th>
        <th>Date Applied</th>
        <th>Current Status</th>
        <th>Action</th>
      </tr>
    </thead>

    <tbody>
        @forelse($applications as $app)
            {{-- THE MAIN ROW --}}
            <tr class="main-row">
                <td>
                    <div style="font-weight: 600; color: #1e293b; font-size: 15px;">
                        {{ $app->job->job_title ?? 'Job Removed / Closed' }}
                    </div>
                    <div style="color: #64748b; font-size: 12px; margin-top: 4px;">
                        Job ID: #{{ $app->job_id }}
                    </div>
                </td>
                <td style="color: #4b5563; font-weight: 500;">
                    <i class="fa-solid fa-building" style="color: #9ca3af; margin-right: 5px;"></i> {{ $app->job->department ?? '-' }}
                </td>
                <td style="color: #4b5563;">
                    {{ $app->created_at->format('d M Y') }}
                </td>
                <td>
                    @php
                        // Map database statuses to UI Badges
                        $stage = $app->app_stage ?? 'Applied';
                        $statusClass = match($stage) {
                            'Applied' => 'badge-applied',
                            'Reviewing', 'Shortlisted' => 'badge-review',
                            'Interview' => 'badge-interview',
                            'Hired', 'Offered' => 'badge-hired',
                            'Rejected' => 'badge-rejected',
                            default => 'badge-applied'
                        };
                    @endphp
                    <span class="status-badge {{ $statusClass }}">{{ $stage }}</span>
                </td>
                <td>
                    <button class="btn-view js-view-application" data-target="details-{{ $app->application_id ?? $loop->index }}">
                        View Details <i class="fa-solid fa-chevron-down" style="font-size: 10px;"></i>
                    </button>
                </td>
            </tr>

            {{-- THE HIDDEN EXPANDABLE DETAILS ROW --}}
            <tr id="details-{{ $app->application_id ?? $loop->index }}" class="app-details-row">
                <td colspan="5" class="app-details-cell">
                    <div class="app-details-card">
                        <div class="app-details-grid">
                            
                            {{-- LEFT COLUMN: Submission Details --}}
                            <div class="detail-section">
                                <h4><i class="fa-solid fa-file-lines"></i> Submission Summary</h4>
                                
                                <div class="app-kv">
                                    <span class="k">Location</span>
                                    <span class="v">{{ $app->job->location ?? 'N/A' }}</span>
                                </div>
                                <div class="app-kv">
                                    <span class="k">Job Type</span>
                                    <span class="v">{{ $app->job->job_type ?? 'N/A' }}</span>
                                </div>
                                <div class="app-kv">
                                    <span class="k">Submitted On</span>
                                    <span class="v">{{ $app->created_at->format('d M Y, h:i A') }}</span>
                                </div>

                                <div style="margin-top: 20px;">
                                    <h5 style="font-size: 12px; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Attached Resume</h5>
                                    @if($app->resume_path)
                                        <a href="{{ asset('storage/' . $app->resume_path) }}" target="_blank" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 15px; background: #f1f5f9; color: #334155; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 600; border: 1px solid #e2e8f0;">
                                            <i class="fa-solid fa-file-pdf" style="color: #ef4444; font-size: 16px;"></i> View Submitted Resume
                                        </a>
                                    @else
                                        <p style="font-size: 13px; color: #94a3b8; font-style: italic;">No resume file attached.</p>
                                    @endif
                                </div>

                                @if($app->cover_letter)
                                <div style="margin-top: 20px;">
                                    <h5 style="font-size: 12px; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Cover Letter Message</h5>
                                    <div style="background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 13px; color: #475569; max-height: 100px; overflow-y: auto; white-space: pre-line;">
                                        "{{ $app->cover_letter }}"
                                    </div>
                                </div>
                                @endif
                            </div>

                            {{-- RIGHT COLUMN: Status Timeline & Interview Alert --}}
                            <div class="detail-section">
                                <h4><i class="fa-solid fa-bars-progress"></i> Application Tracker</h4>
                                
                                {{-- NEW: INTERVIEW SCHEDULING ALERT --}}
                                @if($app->app_stage === 'Interview' && $app->interview_datetime)
                                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-left: 4px solid #2563eb; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                                        <h5 style="color: #1e3a8a; font-size: 14px; margin: 0 0 5px 0;"><i class="fa-solid fa-calendar-day"></i> Interview Scheduled!</h5>
                                        <p style="margin: 0; font-size: 13px; color: #1e40af;">
                                            <strong>Date & Time:</strong> {{ \Carbon\Carbon::parse($app->interview_datetime)->format('l, d M Y - h:i A') }}<br>
                                            <strong>Location/Link:</strong> 
                                            @if(filter_var($app->interview_location, FILTER_VALIDATE_URL))
                                                <a href="{{ $app->interview_location }}" target="_blank" style="color: #2563eb; font-weight: 600;">Join Meeting</a>
                                            @else
                                                {{ $app->interview_location }}
                                            @endif
                                        </p>
                                        <p style="margin: 8px 0 0 0; font-size: 12px; color: #64748b;">If you need to reschedule, please contact HR directly.</p>
                                    </div>
                                @endif

                                <div class="timeline">
                                    {{-- Step 1: Applied (Always Done) --}}
                                    <div class="step done">
                                        <div class="dot"></div>
                                        <div class="txt">
                                            <div class="t">Application Submitted</div>
                                            <div class="d">We successfully received your application.</div>
                                            <div class="d" style="color: #94a3b8;">{{ $app->created_at->format('d M Y') }}</div>
                                        </div>
                                    </div>

                                    {{-- Step 2: Under Review --}}
                                    @php
                                        $isReviewDone = in_array($stage, ['Interview', 'Hired', 'Offered', 'Rejected']);
                                        $isReviewActive = in_array($stage, ['Reviewing', 'Shortlisted']);
                                        $reviewClass = $isReviewDone ? 'done' : ($isReviewActive ? 'active' : '');
                                    @endphp
                                    <div class="step {{ $reviewClass }}">
                                        <div class="dot"></div>
                                        <div class="txt">
                                            <div class="t">Under Review</div>
                                            <div class="d">HR is currently reviewing your profile and skills.</div>
                                        </div>
                                    </div>

                                    {{-- Step 3: Interview --}}
                                    @php
                                        $isInterviewDone = in_array($stage, ['Hired', 'Offered']);
                                        $isInterviewActive = ($stage === 'Interview');
                                        $interviewClass = $isInterviewDone ? 'done' : ($isInterviewActive ? 'active' : '');
                                    @endphp
                                    <div class="step {{ $interviewClass }}">
                                        <div class="dot"></div>
                                        <div class="txt">
                                            <div class="t">Interview Stage</div>
                                            <div class="d">You have been selected for an interview.</div>
                                        </div>
                                    </div>

                                    {{-- Step 4: Final Decision --}}
                                    @php
                                        $finalClass = '';
                                        $finalTitle = 'Final Decision';
                                        $finalDesc = 'Awaiting final outcome.';
                                        
                                        if (in_array($stage, ['Hired', 'Offered'])) {
                                            $finalClass = 'done';
                                            $finalTitle = 'Offer Extended!';
                                            $finalDesc = 'Congratulations! Check your email for offer details.';
                                        } elseif ($stage === 'Rejected') {
                                            $finalClass = 'rejected';
                                            $finalTitle = 'Application Unsuccessful';
                                            $finalDesc = 'Unfortunately, we will not be moving forward at this time.';
                                        }
                                    @endphp
                                    <div class="step {{ $finalClass }}">
                                        <div class="dot"></div>
                                        <div class="txt">
                                            <div class="t">{{ $finalTitle }}</div>
                                            <div class="d">{{ $finalDesc }}</div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" style="text-align: center; padding: 60px 20px; color: #64748b; background: #fff;">
                    <i class="fa-solid fa-folder-open" style="font-size: 32px; margin-bottom: 15px; color: #cbd5e1;"></i>
                    <p style="font-size: 16px; font-weight: 500; color: #334155; margin-bottom: 5px;">No Applications Found</p>
                    <p style="margin-bottom: 15px;">You haven't applied for any open positions yet.</p>
                    <a href="{{ route('applicant.jobs') }}" style="background: #2563eb; color: #fff; padding: 8px 20px; border-radius: 6px; text-decoration: none; font-weight: 500; display: inline-block;">
                        Browse Available Jobs
                    </a>
                </td>
            </tr>
        @endforelse
    </tbody>
  </table>

</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    // Select all view buttons
    const viewButtons = document.querySelectorAll('.js-view-application');
    
    viewButtons.forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();

        // Get the target ID
        const targetId = this.getAttribute('data-target');
        const targetRow = document.getElementById(targetId);
        if (!targetRow) return;

        // Check if the current row is already open
        const isOpen = targetRow.style.display === 'table-row';

        // Close ALL detail rows first
        document.querySelectorAll('.app-details-row').forEach(row => {
            row.style.display = 'none';
        });

        // Reset ALL button icons to point down
        document.querySelectorAll('.js-view-application i').forEach(icon => {
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        });

        // If it was NOT open, open it and change its icon to point up
        if (!isOpen) {
            targetRow.style.display = 'table-row';
            const icon = this.querySelector('i');
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
        }
      });
    });
  });
</script>

@endsection