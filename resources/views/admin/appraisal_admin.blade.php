<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Performance Appraisals - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .metric-card { background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 20px; transition: 0.2s; }
    .metric-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
    .metric-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
    .metric-info h4 { margin: 0 0 5px 0; font-size: 13px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .metric-info p { margin: 0; font-size: 26px; font-weight: 700; color: #0f172a; }

    .section-container { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.02); overflow: hidden; margin-bottom: 30px; }
    .section-header { padding: 20px 25px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
    .section-header h3 { margin: 0; font-size: 16px; color: #0f172a; display: flex; align-items: center; gap: 10px; }

    .hr-table { width: 100%; border-collapse: collapse; text-align: left; }
    .hr-table th { background: #fff; padding: 14px 25px; font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
    .hr-table td { padding: 16px 25px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #1e293b; }
    .hr-table tbody tr:hover { background: #f8fafc; }

    .user-stack { display: flex; align-items: center; gap: 12px; }
    .avatar-sm { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; }

    .score-badge { padding: 6px 12px; border-radius: 6px; font-weight: 700; font-size: 13px; display: inline-block; text-align: center; min-width: 50px; }
    .score-excellent { background: #dcfce7; color: #166534; }
    .score-good { background: #e0f2fe; color: #0369a1; }
    .score-average { background: #fef3c7; color: #b45309; }
    .score-poor { background: #fee2e2; color: #b91c1c; }

    .status-pill { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .status-completed { background: #dcfce7; color: #166534; }
    .status-pending { background: #fef3c7; color: #b45309; }
    .status-draft { background: #f1f5f9; color: #475569; }
  </style>
</head>

<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
        <a href="{{ route('admin.profile') }}" style="text-decoration: none; color: inherit;">
            <i class="fa-regular fa-bell"></i> &nbsp; {{ Auth::user()->name ?? 'HR Admin' }}
        </a>
    </div>
  </header>

  <div class="container">
    @include('admin.layout.sidebar')

    <main>
      <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:30px;">
          <div>
              <div class="breadcrumb" style="color: #64748b; font-size: 14px; margin-bottom: 5px;">Home > <span style="color: #0f172a; font-weight: 500;">Performance</span></div>
              <h2 style="margin:0; font-size:28px; color:#0f172a;">Performance Appraisals</h2>
              <p style="color: #64748b; margin-top: 5px; font-size: 15px;">Monitor employee reviews, core competencies, and company-wide KPI scores.</p>
          </div>
          <a href="{{ route('admin.appraisal.add-kpi') }}" style="background: #2563eb; color: #fff; padding: 12px 24px; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 14px; box-shadow: 0 4px 6px rgba(37,99,235,0.2);">
            <i class="fa-solid fa-plus"></i> Initiate New Review
          </a>
      </div>

      {{-- KEY HR METRICS --}}
      <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-icon" style="background: #eff6ff; color: #3b82f6;"><i class="fa-solid fa-users-viewfinder"></i></div>
            <div class="metric-info"><h4>Total Reviews (YTD)</h4><p>124</p></div>
        </div>
        <div class="metric-card">
            <div class="metric-icon" style="background: #fffbeb; color: #f59e0b;"><i class="fa-solid fa-hourglass-half"></i></div>
            <div class="metric-info"><h4>Pending Manager Review</h4><p>18</p></div>
        </div>
        <div class="metric-card">
            <div class="metric-icon" style="background: #f0fdf4; color: #22c55e;"><i class="fa-solid fa-check-double"></i></div>
            <div class="metric-info"><h4>Completed Appraisals</h4><p>106</p></div>
        </div>
        <div class="metric-card">
            <div class="metric-icon" style="background: #f8fafc; color: #475569;"><i class="fa-solid fa-star"></i></div>
            <div class="metric-info"><h4>Company Avg Score</h4><p>4.2 <span style="font-size: 14px; color: #94a3b8; font-weight: 500;">/ 5.0</span></p></div>
        </div>
      </div>

      {{-- LATEST APPRAISALS TABLE --}}
      <div class="section-container">
        <div class="section-header">
            <h3><i class="fa-solid fa-list-check" style="color: #2563eb;"></i> Recent Employee Appraisals</h3>
            <div style="display: flex; gap: 10px;">
                <select style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; outline: none;">
                    <option value="">All Departments</option>
                    <option value="IT">IT & Engineering</option>
                    <option value="HR">Human Resources</option>
                </select>
                <select style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; outline: none;">
                    <option value="">Mid-Year 2026</option>
                    <option value="">Annual 2025</option>
                </select>
            </div>
        </div>

        <table class="hr-table">
          <thead>
            <tr>
              <th>Employee</th>
              <th>Review Period</th>
              <th>Evaluator (Manager)</th>
              <th style="text-align: center;">Overall Score</th>
              <th>Status</th>
              <th style="text-align: right;">Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($appraisals as $appraisal)
            <tr>
              <td>
                <div class="user-stack">
                  <img src="https://ui-avatars.com/api/?name={{ urlencode($appraisal->employee->user->name ?? 'Unknown') }}&background=E0F2FE&color=0F172A" class="avatar-sm">
                  <div>
                    <strong style="color: #0f172a; font-size: 14px;">{{ $appraisal->employee->user->name ?? 'Unknown' }}</strong><br>
                    <span style="font-size: 12px; color: #64748b;">{{ $appraisal->employee->position->position_name ?? 'N/A' }}</span>
                  </div>
                </div>
              </td>
              <td style="color: #475569; font-weight: 500;">{{ $appraisal->review_period }}</td>
              <td style="color: #475569;">{{ $appraisal->evaluator->user->name ?? 'N/A' }}</td>
              
              <td style="text-align: center;">
                @if($appraisal->overall_score)
                    <span class="score-badge 
                        {{ $appraisal->overall_score >= 4.0 ? 'score-excellent' : 
                           ($appraisal->overall_score >= 3.0 ? 'score-good' : 
                           ($appraisal->overall_score >= 2.0 ? 'score-average' : 'score-poor')) }}">
                        {{ number_format($appraisal->overall_score, 1) }}
                    </span>
                @else
                    <span style="color: #94a3b8; font-size: 13px; font-weight: 500;">--</span>
                @endif
              </td>
              
              <td>
                @if($appraisal->status == 'pending_self_eval')
                    <span class="status-pill status-draft">Awaiting Employee</span>
                @elseif($appraisal->status == 'pending_manager')
                    <span class="status-pill status-pending">Pending Manager</span>
                @else
                    <span class="status-pill status-completed">Completed</span>
                @endif
              </td>
              
              <td style="text-align: right;">
                <a href="#" style="color: #2563eb; font-weight: 600; text-decoration: none; font-size: 13px;">View Details <i class="fa-solid fa-arrow-right" style="margin-left: 4px;"></i></a>
              </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px 20px; color: #64748b;">
                    <i class="fa-solid fa-folder-open" style="font-size: 32px; color: #cbd5e1; margin-bottom: 10px; display: block;"></i>
                    No appraisals have been initiated yet.
                </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <footer>© 2026 Web-Based HRMS. All Rights Reserved.</footer>
    </main>
  </div>
</body>
</html>