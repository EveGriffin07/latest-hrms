<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Appraisal Inbox - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    .appraisal-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); overflow: hidden; }
    .card-header { background: #f8fafc; padding: 20px 25px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .card-title { margin: 0; font-size: 16px; color: #0f172a; font-weight: 600; display: flex; align-items: center; gap: 10px; }
    
    .status-pill { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .status-pending-self { background: #fef08a; color: #854d0e; }
    .status-pending-manager { background: #e0f2fe; color: #0369a1; }
    .status-completed { background: #dcfce7; color: #166534; }

    .matrix-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .matrix-table th { text-align: left; padding: 12px; font-size: 13px; color: #64748b; font-weight: 600; border-bottom: 2px solid #e2e8f0; }
    .matrix-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .matrix-select { width: 100%; max-width: 150px; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; }
    
    .textarea-custom { width: 100%; padding: 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 14px; outline: none; transition: 0.2s; resize: vertical; margin-bottom: 15px; }
  </style>
</head>
<body>
<header>
  <div class="title">Web-Based HRMS</div>
  <div class="user-info">
      <i class="fa-regular fa-bell"></i> &nbsp; <a href="{{ route('supervisor.profile') }}" style="color:inherit; text-decoration:none;">{{ Auth::user()->name }}</a>
  </div>
</header>

<div class="container dashboard-shell">
  @include('employee.layout.sidebar') {{-- Assuming supervisor shares the employee sidebar --}}

  <main style="flex:1; padding:28px 32px; max-width:100%;">
    
    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; padding:15px 20px; border-radius:8px; margin-bottom:20px; border:1px solid #bbf7d0; font-weight: 500;"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
    @endif

    <div style="margin-bottom: 30px;">
        <h2 style="margin:0; font-size:28px; color:#0f172a;">Appraisal Inbox</h2>
        <p style="color: #64748b; margin-top: 5px; font-size: 15px;">Review self-evaluations and grade your team's performance.</p>
    </div>

    @forelse($appraisals as $appraisal)
        <div class="appraisal-card">
            <div class="card-header">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($appraisal->employee->user->name ?? 'User') }}&background=E0F2FE&color=0F172A" style="width: 45px; height: 45px; border-radius: 50%;">
                    <div>
                        <h3 class="card-title">{{ $appraisal->employee->user->name ?? 'Unknown' }}</h3>
                        <span style="font-size: 13px; color: #64748b;">{{ $appraisal->employee->position->position_name ?? 'N/A' }} | {{ $appraisal->review_period }}</span>
                    </div>
                </div>
                
                @if($appraisal->status == 'pending_self_eval')
                    <span class="status-pill status-pending-self">Awaiting Employee</span>
                @elseif($appraisal->status == 'pending_manager')
                    <span class="status-pill status-pending-manager"><i class="fa-solid fa-pen"></i> Needs Your Review</span>
                @else
                    <span class="status-pill status-completed">Completed (Avg: {{ number_format($appraisal->overall_score, 1) }})</span>
                @endif
            </div>

            <div style="padding: 25px;">
                {{-- SCENARIO 1: Awaiting Employee (Manager can't do anything yet) --}}
                @if($appraisal->status == 'pending_self_eval')
                    <div style="background: #f8fafc; padding: 20px; border-radius: 8px; text-align: center; color: #64748b;">
                        <i class="fa-regular fa-clock" style="font-size: 24px; margin-bottom: 10px;"></i>
                        <p style="margin: 0;">Waiting for the employee to submit their self-evaluation.</p>
                    </div>

                {{-- SCENARIO 2: Ready for Manager to Score --}}
                @elseif($appraisal->status == 'pending_manager')
                    <div style="margin-bottom: 25px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #0f172a;">Employee's Self-Reflection:</h4>
                        <p style="color: #475569; font-size: 14px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin: 0; font-style: italic;">"{{ $appraisal->employee_comments }}"</p>
                    </div>

                    <form action="{{ route('supervisor.appraisal.score', $appraisal->appraisal_id) }}" method="POST">
                        @csrf
                        <table class="matrix-table">
                            <thead>
                                <tr>
                                    <th>Core Competency</th>
                                    <th>Description</th>
                                    <th>Score (1 = Poor, 5 = Excellent)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Attendance & Punctuality</strong></td>
                                    <td style="color: #64748b; font-size: 13px;">Reliability, minimal absences, and punctuality.</td>
                                    <td>
                                        <select name="score_attendance" class="matrix-select" required>
                                            <option value="">-- Score --</option>
                                            <option value="5">5.0 - Excellent</option>
                                            <option value="4">4.0 - Good</option>
                                            <option value="3">3.0 - Satisfactory</option>
                                            <option value="2">2.0 - Needs Improvement</option>
                                            <option value="1">1.0 - Unacceptable</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Teamwork & Collaboration</strong></td>
                                    <td style="color: #64748b; font-size: 13px;">Works well with peers and supports team goals.</td>
                                    <td>
                                        <select name="score_teamwork" class="matrix-select" required>
                                            <option value="">-- Score --</option>
                                            <option value="5">5.0 - Excellent</option><option value="4">4.0 - Good</option><option value="3">3.0 - Satisfactory</option><option value="2">2.0 - Needs Improvement</option><option value="1">1.0 - Unacceptable</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Productivity & Quality</strong></td>
                                    <td style="color: #64748b; font-size: 13px;">Meets deadlines and produces high-quality work.</td>
                                    <td>
                                        <select name="score_productivity" class="matrix-select" required>
                                            <option value="">-- Score --</option>
                                            <option value="5">5.0 - Excellent</option><option value="4">4.0 - Good</option><option value="3">3.0 - Satisfactory</option><option value="2">2.0 - Needs Improvement</option><option value="1">1.0 - Unacceptable</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Communication Skills</strong></td>
                                    <td style="color: #64748b; font-size: 13px;">Expresses ideas clearly and listens effectively.</td>
                                    <td>
                                        <select name="score_communication" class="matrix-select" required>
                                            <option value="">-- Score --</option>
                                            <option value="5">5.0 - Excellent</option><option value="4">4.0 - Good</option><option value="3">3.0 - Satisfactory</option><option value="2">2.0 - Needs Improvement</option><option value="1">1.0 - Unacceptable</option>
                                        </select>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #0f172a;">Final Manager Comments:</h4>
                        <textarea name="manager_comments" class="textarea-custom" rows="3" placeholder="Provide constructive feedback and justification for these scores..." required></textarea>

                        <div style="text-align: right;">
                            <button type="submit" style="background: #2563eb; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer;"><i class="fa-solid fa-check"></i> Finalize Appraisal</button>
                        </div>
                    </form>

                {{-- SCENARIO 3: Completed --}}
                @else
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px;">
                        <div style="background: #f8fafc; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid #e2e8f0;">
                            <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Attendance</div>
                            <div style="font-size: 20px; font-weight: 700; color: #0f172a;">{{ number_format($appraisal->score_attendance, 1) }}</div>
                        </div>
                        <div style="background: #f8fafc; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid #e2e8f0;">
                            <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Teamwork</div>
                            <div style="font-size: 20px; font-weight: 700; color: #0f172a;">{{ number_format($appraisal->score_teamwork, 1) }}</div>
                        </div>
                        <div style="background: #f8fafc; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid #e2e8f0;">
                            <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Productivity</div>
                            <div style="font-size: 20px; font-weight: 700; color: #0f172a;">{{ number_format($appraisal->score_productivity, 1) }}</div>
                        </div>
                        <div style="background: #f8fafc; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid #e2e8f0;">
                            <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Communication</div>
                            <div style="font-size: 20px; font-weight: 700; color: #0f172a;">{{ number_format($appraisal->score_communication, 1) }}</div>
                        </div>
                    </div>
                    <div>
                        <strong style="color: #0f172a; font-size: 14px;">Your Final Feedback:</strong>
                        <p style="color: #475569; font-size: 14px; margin: 5px 0 0 0;">{{ $appraisal->manager_comments }}</p>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div style="background: white; border-radius: 16px; border: 1px dashed #cbd5e1; text-align: center; padding: 60px 20px;">
            <div style="width: 80px; height: 80px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;"><i class="fa-solid fa-inbox" style="font-size: 32px; color: #94a3b8;"></i></div>
            <h3 style="margin: 0 0 10px 0; color: #0f172a;">Inbox Empty</h3>
            <p style="color:#64748b; margin: 0;">You have no pending appraisals to review.</p>
        </div>
    @endforelse

  </main>
</div>
</body>
</html>