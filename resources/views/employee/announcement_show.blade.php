<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $announcement->title }} - Announcement</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms-theme.css') }}">
  <style>
    body { background: #f3f6fb; font-family: 'Poppins', sans-serif; }
    
    /* Layout Structure */
    .dashboard-shell { display: flex; min-height: calc(100vh - 64px); }
    .dashboard-main { flex: 1; padding: 32px; max-width: 100%; }

    /* Main Card */
    .announcement-card { 
        background: #fff; 
        border-radius: 16px; 
        padding: 40px; 
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); 
        border: 1px solid #e2e8f0; 
        min-height: 500px;
    }

    /* Navigation Bar */
    .nav-row { margin-bottom: 24px; }
    .back-btn { 
        display: inline-flex; align-items: center; gap: 8px; 
        color: #64748b; text-decoration: none; font-weight: 600; 
        font-size: 14px; background: #fff; padding: 10px 18px; 
        border-radius: 8px; border: 1px solid #e2e8f0;
        transition: all 0.2s;
    }
    .back-btn:hover { color: #0f172a; border-color: #cbd5e1; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

    /* Announcement Header */
    .ann-header { border-bottom: 1px solid #e2e8f0; padding-bottom: 24px; margin-bottom: 30px; }
    
    .title-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; }
    .title-row h1 { margin: 0; font-size: 32px; font-weight: 700; color: #0f172a; line-height: 1.3; }

    /* Meta Information */
    .meta-row { display: flex; gap: 24px; align-items: center; font-size: 14px; color: #64748b; margin-top: 16px; }
    .meta-item { display: flex; align-items: center; gap: 8px; }
    .meta-item i { color: #94a3b8; }

    /* Priority Badges */
    .badge { padding: 6px 14px; border-radius: 99px; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
    .badge-critical { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
    .badge-high { background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; }
    .badge-normal { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

    /* Content Typography */
    .ann-content { 
        line-height: 1.8; 
        color: #334155; 
        font-size: 16px; 
        white-space: pre-line; /* Keeps your paragraphs */
    }

    /* Remarks / Notes Box */
    .remarks-box {
        margin-top: 50px;
        background: #fffbeb;
        border: 1px solid #fcd34d;
        border-radius: 12px;
        padding: 24px;
        display: flex;
        gap: 16px;
    }
    .remarks-icon { color: #d97706; font-size: 20px; margin-top: 2px; }
    .remarks-text h5 { margin: 0 0 6px 0; color: #92400e; font-size: 15px; font-weight: 700; }
    .remarks-text p { margin: 0; color: #b45309; font-size: 14px; line-height: 1.5; }

  </style>
</head>
<body>

  {{-- HEADER: Using default styling (no inline styles) to keep your blue theme --}}
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
        <i class="fa-regular fa-bell"></i> &nbsp; <a href="{{ route('employee.profile') }}" style="color:inherit; text-decoration:none;">{{ Auth::user()->name }}</a>
    </div>
  </header>

  <div class="container dashboard-shell">
    
    {{-- SIDEBAR: Matches your dashboard --}}
    @include('employee.layout.sidebar')

    {{-- MAIN CONTENT --}}
    <main class="dashboard-main">
        
        <div class="nav-row">
            <a href="{{ route('employee.dashboard') }}" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <div class="announcement-card">
            
            <div class="ann-header">
                <div class="title-row">
                    <h1>{{ $announcement->title }}</h1>
                    
                    {{-- Priority Badge Logic --}}
                    @php $p = strtolower($announcement->priority); @endphp
                    @if($p == 'critical' || $p == 'urgent')
                        <span class="badge badge-critical"><i class="fa-solid fa-fire"></i> {{ $announcement->priority }}</span>
                    @elseif($p == 'high')
                        <span class="badge badge-high">{{ $announcement->priority }}</span>
                    @else
                        <span class="badge badge-normal">{{ $announcement->priority }}</span>
                    @endif
                </div>

                <div class="meta-row">
                    <span class="meta-item"><i class="fa-regular fa-calendar"></i> {{ \Carbon\Carbon::parse($announcement->publish_at)->format('d F Y') }}</span>
                    <span style="color:#cbd5e1;">|</span>
                    <span class="meta-item"><i class="fa-regular fa-clock"></i> {{ \Carbon\Carbon::parse($announcement->publish_at)->format('h:i A') }}</span>
                    <span style="color:#cbd5e1;">|</span>
                    <span class="meta-item"><i class="fa-solid fa-user-pen"></i> Posted by Admin</span>
                </div>
            </div>

            <div class="ann-content">
                {!! nl2br(e($announcement->content)) !!}
            </div>

            @if(!empty($announcement->remarks))
            <div class="remarks-box">
                <div class="remarks-icon"><i class="fa-solid fa-circle-info"></i></div>
                <div class="remarks-text">
                    <h5>Additional Notes / Remarks:</h5>
                    <p>{{ $announcement->remarks }}</p>
                </div>
            </div>
            @endif

        </div>

        <footer style="text-align:center; color:#94a3b8; font-size:12px; margin-top:40px;">
            &copy; 2026 Web-Based HRMS. All Rights Reserved.
        </footer>

    </main>
  </div>

</body>
</html>