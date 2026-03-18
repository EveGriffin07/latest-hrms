<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Learning - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    /* PAGE HEADER */
    .page-header { background: linear-gradient(to right, #1e293b, #0f172a); color: white; padding: 40px; border-radius: 16px; margin-bottom: 30px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
    .page-title { font-size: 28px; font-weight: 700; margin: 0 0 5px 0; }
    .page-subtitle { color: #94a3b8; font-size: 15px; margin: 0; }

    /* CARDS FOR UPCOMING TRAINING */
    .section-title { font-size: 18px; font-weight: 600; color: #0f172a; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .training-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; margin-bottom: 40px; }
    .training-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.02); overflow: hidden; transition: 0.2s; display: flex; flex-direction: column; }
    .training-card:hover { transform: translateY(-4px); box-shadow: 0 12px 20px -5px rgba(0,0,0,0.08); border-color: #cbd5e1; }
    
    .t-header { padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
    .t-badge { font-size: 11px; font-weight: 700; padding: 5px 12px; border-radius: 99px; text-transform: uppercase; letter-spacing: 0.5px; }
    .badge-online { background: #e0f2fe; color: #0284c7; }
    .badge-onsite { background: #dcfce7; color: #166534; }

    .t-body { padding: 20px; flex: 1; }
    .t-title { font-size: 17px; font-weight: 600; color: #0f172a; margin-bottom: 12px; display: block; line-height: 1.4; }
    .t-info { font-size: 13px; color: #475569; margin-bottom: 8px; display: flex; align-items: center; gap: 10px; }
    .t-info i { color: #94a3b8; width: 14px; text-align: center; }
    
    .t-footer { padding: 15px 20px; border-top: 1px dashed #e2e8f0; background: white; text-align: right; }
    .btn-view { font-size: 13px; font-weight: 600; color: #2563eb; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: #eff6ff; border-radius: 6px; transition: 0.2s; }
    .btn-view:hover { background: #2563eb; color: white; }

    /* TABLE FOR HISTORY */
    .history-section { background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .custom-table { width: 100%; border-collapse: collapse; }
    .custom-table th { text-align: left; padding: 14px 16px; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; background: #f8fafc; border-bottom: 1px solid #e2e8f0; letter-spacing: 0.5px; }
    .custom-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; vertical-align: middle; }
    .custom-table tbody tr:hover { background: #f8fafc; }
    
    .status-pill { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
    .pill-pass { background: #dcfce7; color: #166534; }
    .pill-fail { background: #fee2e2; color: #991b1b; }
    .pill-pending { background: #fef3c7; color: #b45309; }
    
    .empty-state { text-align: center; padding: 50px 20px; color: #94a3b8; font-size: 15px; border: 2px dashed #e2e8f0; border-radius: 12px; }
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

    <main class="content">
      
      @if(session('success'))
        <div style="background:#dcfce7; color:#166534; padding:15px; border-radius:8px; margin-bottom:20px; border: 1px solid #bbf7d0; font-weight: 500;">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
      @endif

      @if(session('error'))
        <div style="background:#fee2e2; color:#b91c1c; padding:15px; border-radius:8px; margin-bottom:20px; border: 1px solid #fecaca; font-weight: 500;">
            <i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}
        </div>
      @endif

      <div class="page-header">
        <h1 class="page-title">My Learning Portal</h1>
        <p class="page-subtitle">Track your required upskilling programs and compliance training.</p>
      </div>

      {{-- SECTION 1: UPCOMING / ACTIVE --}}
      <h3 class="section-title"><i class="fa-solid fa-rocket" style="color: #2563eb;"></i> Active & Upcoming Programs</h3>
      
      @if($upcoming->count() > 0)
        <div class="training-grid">
            @foreach($upcoming as $enrollment)
            <div class="training-card">
                <div class="t-header">
                    <span class="t-badge {{ $enrollment->training->mode == 'Online' ? 'badge-online' : 'badge-onsite' }}">
                        {{ $enrollment->training->mode }}
                    </span>
                    <span style="font-size:13px; color:#475569; font-weight:600;">
                        {{ \Carbon\Carbon::parse($enrollment->training->start_date)->format('d M Y') }}
                    </span>
                </div>
                <div class="t-body">
                    <span class="t-title">{{ $enrollment->training->training_name }}</span>
                    <div class="t-info"><i class="fa-solid fa-user-tie"></i> {{ $enrollment->training->provider }}</div>
                    
                    @if($enrollment->training->mode == 'Online')
                        <div class="t-info"><i class="fa-solid fa-video"></i> Virtual Session</div>
                    @else
                        <div class="t-info"><i class="fa-solid fa-location-dot"></i> {{ Str::limit($enrollment->training->location, 30) }}</div>
                    @endif
                    
                    <div class="t-info"><i class="fa-regular fa-clock"></i> 
                        {{ $enrollment->training->start_time ? \Carbon\Carbon::parse($enrollment->training->start_time)->format('h:i A') : 'Time TBD' }}
                    </div>
                </div>
                
                <div class="t-footer">
                    <a href="{{ route('employee.training.show', $enrollment->training->training_id) }}" class="btn-view">
                        Access Program <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
      @else
        <div class="empty-state" style="margin-bottom: 40px;">
            <i class="fa-solid fa-mug-hot" style="font-size: 32px; margin-bottom: 15px; color: #cbd5e1;"></i><br>
            <strong style="color: #334155;">You're all caught up!</strong><br>
            You have no mandatory upcoming training sessions.
        </div>
      @endif

      {{-- SECTION 2: HISTORY --}}
      <div class="history-section">
        <h3 class="section-title" style="margin: 0 0 20px 0;"><i class="fa-solid fa-clock-rotate-left" style="color: #64748b;"></i> Training History</h3>
        
        @if($history->count() > 0)
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Program Title</th>
                    <th>Completion Date</th>
                    <th>Result / Status</th>
                    <th>Instructor Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($history as $record)
                <tr>
                    <td>
                        <strong style="color: #0f172a;">{{ $record->training->training_name }}</strong><br>
                        <span style="font-size: 12px; color: #64748b;">{{ $record->training->provider }}</span>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($record->training->end_date)->format('d M Y') }}</td>
                    <td>
                        {{-- DYNAMIC LOGIC: If date passed but HR hasn't graded yet --}}
                        @if($record->completion_status == 'completed')
                            <span class="status-pill pill-pass"><i class="fa-solid fa-check"></i> Passed</span>
                        @elseif($record->completion_status == 'failed')
                            <span class="status-pill pill-fail"><i class="fa-solid fa-xmark"></i> Failed / Absent</span>
                        @else
                            <span class="status-pill pill-pending"><i class="fa-regular fa-hourglass-half"></i> Awaiting Review</span>
                        @endif
                    </td>
                    <td style="font-style: italic; color: {{ $record->remarks ? '#334155' : '#94a3b8' }};">
                        {{ $record->remarks ?? 'Pending instructor feedback' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
            <div class="empty-state" style="border: none; padding: 30px;">
                <i class="fa-solid fa-folder-open" style="font-size: 24px; color: #cbd5e1; margin-bottom: 10px;"></i><br>
                No historical records found.
            </div>
        @endif
      </div>

      <footer style="margin-top: 40px; text-align: center; color: #94a3b8; font-size: 13px;">
        &copy; 2026 Web-Based HRMS. All Rights Reserved.
      </footer>
    </main>
  </div>

</body>
</html>