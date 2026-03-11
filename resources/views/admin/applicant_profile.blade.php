<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Details - HRMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
    
    <style>
        .details-container { display: flex; gap: 30px; margin-top: 20px; align-items: flex-start; }
        
        /* Sidebar Card (Left) */
        .sidebar-card { flex: 1; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center; }

        /* Content Card (Right) */
        .content-card { flex: 2; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }

        /* Status Badges */
        .status-badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 14px; font-weight: 600; margin-top: 10px; }
        .status-applied { background: #e0f2fe; color: #0284c7; }
        .status-interview { background: #fef3c7; color: #d97706; }
        .status-hired { background: #dcfce7; color: #166534; }
        .status-rejected { background: #fee2e2; color: #dc2626; }

        /* Module List */
        .module-list { list-style: none; padding: 0; margin: 0 0 30px 0; }
        .module-list li { display: flex; justify-content: space-between; padding: 12px 0; font-size: 14px; border-bottom: 1px dashed #e5e7eb; }
        .module-list li:last-child { border-bottom: none; }
        .module-list span { color: #6b7280; font-weight: 500; }
        .module-list strong { color: #1f2937; font-weight: 600; text-align: right; }

        /* Buttons */
        .btn-action { width: 100%; padding: 12px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 10px; transition: 0.2s; text-align: center; color: white; }
        .btn-hire { background: #16a34a; } .btn-hire:hover { background: #15803d; }
        .btn-reject { background: #dc2626; } .btn-reject:hover { background: #b91c1c; }
        .btn-interview { background: #d97706; } .btn-interview:hover { background: #b45309; }
        
        /* Resume Buttons */
        .btn-view-pdf { background:#111827; color:#fff; padding:6px 12px; border-radius:6px; font-size:12px; text-decoration:none; display:inline-flex; align-items:center; gap:5px; }
        .btn-download-pdf { background:#2563eb; color:#fff; padding:6px 12px; border-radius:6px; font-size:12px; text-decoration:none; display:inline-flex; align-items:center; gap:5px; margin-left: 5px; }

        /* Scoring Section */
        .evaluation-box { margin-top: 20px; padding-top: 20px; border-top: 2px solid #f3f4f6; }
        .eval-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; }
        .eval-input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        .info-label { font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 5px; }
    </style>
</head>
<body>

    <header>
        <div class="title">Web-Based HRMS</div>
    </header>

    <div class="container">
        @include('admin.layout.sidebar')

        <main>
            <div class="breadcrumb">
                <a href="{{ route('admin.applicants.index') }}">Applicants</a> > {{ $application->applicant->full_name }}
            </div>

            @if(session('success'))
                <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fa-solid fa-check-circle"></i> {{ session('success') }}
                </div>
            @endif

            <div class="details-container">
                
                {{-- LEFT SIDEBAR: AVATAR & ACTIONS --}}
                <div class="sidebar-card">
                    
                    {{-- AVATAR --}}
                    <div class="avatar-container" style="margin: 0 auto 15px auto; width: 120px; height: 120px; display: flex; justify-content: center; align-items: center;">
                        @if($application->applicant->avatar_path)
                            <img src="{{ asset('storage/' . $application->applicant->avatar_path) }}" 
                                 style="width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 4px solid #f3f4f6;">
                        @else
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($application->applicant->full_name) }}&background=2563eb&color=fff&size=128" 
                                 style="width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 4px solid #f3f4f6;">
                        @endif
                    </div>

                    <h3>{{ $application->applicant->full_name }}</h3>
                    <div class="status-badge status-{{ strtolower($application->app_stage) }}">
                        {{ $application->app_stage }}
                    </div>

                    {{-- QUICK ACTIONS --}}
                    <div style="margin-top: 30px; text-align: left;">
                        <p class="info-label">Quick Actions</p>
                        
                        <form action="{{ route('admin.applicants.updateStatus', $application->application_id) }}" method="POST">
                            @csrf <input type="hidden" name="status" value="Interview">
                            <button class="btn-action btn-interview">Schedule Interview</button>
                        </form>

                        <form action="{{ route('admin.applicants.updateStatus', $application->application_id) }}" method="POST">
                            @csrf <input type="hidden" name="status" value="Hired">
                            <button class="btn-action btn-hire">Hire Candidate</button>
                        </form>

                        <form action="{{ route('admin.applicants.updateStatus', $application->application_id) }}" method="POST">
                            @csrf <input type="hidden" name="status" value="Rejected">
                            <button class="btn-action btn-reject" onclick="return confirm('Reject this applicant?')">Reject</button>
                        </form>
                    </div>

                </div>

                {{-- RIGHT CONTENT: DETAILED INFO & SCORING --}}
                <div class="content-card">
                    <h3 style="margin-bottom: 20px; border-bottom: 2px solid #f3f4f6; padding-bottom: 10px;">Application Information</h3>

                    <ul class="module-list">
                        <li><span>Position Applied</span> <strong>{{ $application->job->job_title ?? 'Unknown Job' }}</strong></li>
                        <li><span>Department</span> <strong>{{ $application->job->department ?? 'N/A' }}</strong></li>
                        <li><span>Email Address</span> <strong>{{ $application->applicant->user->email ?? 'N/A' }}</strong></li>
                        <li><span>Phone Number</span> <strong>{{ $application->applicant->phone ?? 'N/A' }}</strong></li>
                        <li><span>Applied Date</span> <strong>{{ $application->created_at->format('d M Y') }}</strong></li>
                        
                        {{-- RESUME ROW (FIXED) --}}
                        <li>
                            <span>Resume</span>
                            <div>
                                @if($application->resume_path)
                                    {{-- View Button --}}
                                    <a href="{{ asset('storage/' . $application->resume_path) }}" target="_blank" class="btn-view-pdf">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
                                    
                                    {{-- 
                                        FIX: Added specific filename to 'download' attribute 
                                        and 'target="_blank"' to prevent page crash on failure 
                                    --}}
                                    <a href="{{ asset('storage/' . $application->resume_path) }}" 
                                       download="{{ str_replace(' ', '_', $application->applicant->full_name) }}_Resume.pdf" 
                                       target="_blank"
                                       class="btn-download-pdf">
                                        <i class="fa-solid fa-download"></i> Download
                                    </a>
                                @else
                                    <span style="color: #999; font-style: italic;">No resume uploaded</span>
                                @endif
                            </div>
                        </li>
                    </ul>

                    {{-- SCORING SECTION --}}
                    <div class="evaluation-box">
                        <h3 style="font-size: 16px; margin-bottom: 15px;">Interview Evaluation</h3>
                        
                        <form action="{{ route('admin.applicants.evaluate', $application->application_id) }}" method="POST">
                            @csrf
                            <div class="eval-grid">
                                <div>
                                    <label class="info-label">Test Score (0-100)</label>
                                    <input type="number" name="test_score" class="eval-input" value="{{ $application->test_score }}" placeholder="0">
                                </div>
                                <div>
                                    <label class="info-label">Interview Score (0-100)</label>
                                    <input type="number" name="interview_score" class="eval-input" value="{{ $application->interview_score }}" placeholder="0">
                                </div>
                            </div>

                            <div style="margin-top: 15px;">
                                <label class="info-label">Overall Score</label>
                                <input type="number" class="eval-input" value="{{ $application->overall_score }}" disabled style="background: #f9fafb;">
                            </div>

                            <div style="margin-top: 15px;">
                                <label class="info-label">Evaluation Notes</label>
                                <textarea name="notes" rows="3" class="eval-input" placeholder="Interviewer comments...">{{ $application->evaluation_notes }}</textarea>
                            </div>

                            <div style="text-align: right; margin-top: 15px;">
                                <button type="submit" class="btn-action" style="background: #1e293b; width: auto; padding: 10px 25px;">
                                    <i class="fa-solid fa-floppy-disk"></i> Save Evaluation
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </main>
    </div>
</body>

</div> {{-- End Container --}}

    {{-- 1. Add SweetAlert CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- 2. Check for Session Success & Trigger Popup --}}
    @if(session('success'))
    <script>
        Swal.fire({
            title: "Success!",
            text: "{{ session('success') }}",
            icon: "success",
            confirmButtonColor: "#16a34a", // Nice Green Color
            confirmButtonText: "Great!"
        });
    </script>
    @endif
    
    {{-- 3. Check for Errors (e.g. Applicant already exists) --}}
    @if(session('error'))
    <script>
        Swal.fire({
            title: "Error",
            text: "{{ session('error') }}",
            icon: "error",
            confirmButtonColor: "#dc2626",
            confirmButtonText: "Okay"
        });
    </script>
    @endif

</body>

</html>