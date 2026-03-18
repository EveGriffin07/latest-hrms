<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Appraisals - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    .appraisal-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); overflow: hidden; }
    .card-header { background: #f8fafc; padding: 20px 25px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .card-title { margin: 0; font-size: 18px; color: #0f172a; font-weight: 600; display: flex; align-items: center; gap: 10px; }
    .card-body { padding: 25px; }
    
    .status-pill { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .status-pending-self { background: #fef08a; color: #854d0e; border: 1px solid #fde047; }
    .status-pending-manager { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .status-completed { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }

    .score-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; padding-top: 20px; border-top: 1px dashed #cbd5e1; }
    .score-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; text-align: center; }
    .score-label { font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 5px; }
    .score-value { font-size: 22px; font-weight: 700; color: #0f172a; }

    .textarea-custom { width: 100%; padding: 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 14px; outline: none; transition: 0.2s; resize: vertical; margin-bottom: 15px; }
    .textarea-custom:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
  </style>
</head>
<body>
<header>
  <div class="title">Web-Based HRMS</div>
  <div class="user-info">
      <i class="fa-regular fa-bell"></i> &nbsp; <a href="{{ route('employee.profile') }}" style="color:inherit; text-decoration:none;">{{ Auth::user()->name }}</a>
  </div>
</header>

<div class="container dashboard-shell">
  @include('employee.layout.sidebar')

  <main style="flex:1; padding:28px 32px; max-width:100%;">
    
    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; padding:15px 20px; border-radius:8px; margin-bottom:20px; border:1px solid #bbf7d0; font-weight: 500;">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div style="background:#fee2e2; color:#b91c1c; padding:15px 20px; border-radius:8px; margin-bottom:20px; border:1px solid #fecaca; font-weight: 500;">
            <i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}
        </div>
    @endif

    <div style="margin-bottom: 30px;">
        <h2 style="margin:0; font-size:28px; color:#0f172a;">My Performance Appraisals</h2>
        <p style="color: #64748b; margin-top: 5px; font-size: 15px;">Complete your self-evaluations and view your finalized scores.</p>
    </div>

    @forelse($appraisals as $appraisal)
        <div class="appraisal-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-star-half-stroke" style="color: #2563eb;"></i> {{ $appraisal->review_period }}</h3>
                @if($appraisal->status == 'pending_self_eval')
                    <span class="status-pill status-pending-self"><i class="fa-solid fa-pen"></i> Action Required</span>
                @elseif($appraisal->status == 'pending_manager')
                    <span class="status-pill status-pending-manager"><i class="fa-solid fa-hourglass-half"></i> Pending Manager</span>
                @else
                    <span class="status-pill status-completed"><i class="fa-solid fa-check-double"></i> Completed</span>
                @endif
            </div>

            <div class="card-body">
                <div style="display: flex; gap: 40px; margin-bottom: 20px; font-size: 14px;">
                    <div><strong style="color: #64748b;">Evaluator (Manager):</strong> <span style="color: #0f172a; font-weight: 500;">{{ $appraisal->evaluator->user->name ?? 'HR Admin' }}</span></div>
                    <div><strong style="color: #64748b;">Initiated On:</strong> <span style="color: #0f172a; font-weight: 500;">{{ $appraisal->created_at->format('d M Y') }}</span></div>
                </div>

                {{-- SCENARIO 1: Employee needs to do their self-evaluation --}}
                @if($appraisal->status == 'pending_self_eval')
                    <div style="background: #f8fafc; border: 1px dashed #cbd5e1; padding: 20px; border-radius: 8px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 15px; color: #1e293b;">Self-Evaluation Reflection</h4>
                        <p style="color: #64748b; font-size: 13px; margin-bottom: 15px;">Please summarize your achievements, challenges faced, and areas you wish to improve before your manager grades your core competencies.</p>
                        
                        <form action="{{ route('employee.kpis.store-eval', $appraisal->appraisal_id) }}" method="POST">
                            @csrf
                            <textarea name="employee_comments" class="textarea-custom" rows="4" placeholder="I successfully delivered the X project on time. However, I want to improve my skills in Y..."></textarea>
                            <button type="submit" style="background: #2563eb; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-paper-plane"></i> Submit to Manager
                            </button>
                        </form>
                    </div>

                {{-- SCENARIO 2: Awaiting Manager or Completed --}}
                @else
                    <div style="margin-bottom: 20px;">
                        <h4 style="margin: 0 0 5px 0; font-size: 14px; color: #64748b;">Your Self-Reflection:</h4>
                        <p style="color: #0f172a; font-size: 14px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin: 0;">{{ $appraisal->employee_comments }}</p>
                    </div>

                    {{-- If completed, show the 1-5 scores! --}}
                    @if($appraisal->status == 'completed')
                        <div class="score-grid">
                            <div class="score-box">
                                <div class="score-label">Attendance</div>
                                <div class="score-value">{{ number_format($appraisal->score_attendance, 1) }}</div>
                            </div>
                            <div class="score-box">
                                <div class="score-label">Teamwork</div>
                                <div class="score-value">{{ number_format($appraisal->score_teamwork, 1) }}</div>
                            </div>
                            <div class="score-box">
                                <div class="score-label">Productivity</div>
                                <div class="score-value">{{ number_format($appraisal->score_productivity, 1) }}</div>
                            </div>
                            <div class="score-box">
                                <div class="score-label">Communication</div>
                                <div class="score-value">{{ number_format($appraisal->score_communication, 1) }}</div>
                            </div>
                        </div>

                        <div style="margin-top: 20px; background: #eff6ff; border: 1px solid #bfdbfe; padding: 20px; border-radius: 8px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h4 style="margin: 0; font-size: 15px; color: #1e3a8a;"><i class="fa-solid fa-comment-dots"></i> Manager's Final Feedback</h4>
                                <strong style="color: #1d4ed8; font-size: 16px;">Overall Score: {{ number_format($appraisal->overall_score, 1) }} / 5.0</strong>
                            </div>
                            <p style="color: #1e3a8a; font-size: 14px; margin: 0;">{{ $appraisal->manager_comments }}</p>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @empty
        <div style="background: white; border-radius: 16px; border: 1px dashed #cbd5e1; text-align: center; padding: 60px 20px;">
            <div style="width: 80px; height: 80px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
                <i class="fa-solid fa-file-contract" style="font-size: 32px; color: #94a3b8;"></i>
            </div>
            <h3 style="margin: 0 0 10px 0; color: #0f172a;">No Appraisals Yet</h3>
            <p style="color:#64748b; margin: 0;">You do not have any performance reviews scheduled at this time.</p>
        </div>
    @endforelse

    <footer style="text-align: center; margin-top: 40px; color: #94a3b8; font-size: 13px;">© 2026 Web-Based HRMS. All Rights Reserved.</footer>
  </main>
</div>

</body>
</html>