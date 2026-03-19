<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  @php 
    $role = strtolower(Auth::user()->role ?? 'employee');
    $isSupervisor = ($role === 'supervisor' || $role === 'manager');
    $dashboardLabel = $isSupervisor ? 'Supervisor Dashboard' : 'Employee Dashboard'; 
  @endphp
  <title>{{ $dashboardLabel }} - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <link rel="stylesheet" href="{{ asset('css/hrms-theme.css') }}">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <style>
    body { background:#f3f6fb; }
    .dashboard-shell { display:flex; min-height:calc(100vh - 64px); }
    .dashboard-main { flex:1; padding:28px 32px; max-width:100%; margin:0 auto; }

    .hero {
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap:14px;
      margin-bottom:16px;
    }
    .breadcrumb { font-size:12px; color:#9ca3af; margin-bottom:6px; }
    .hero-title { font-size:21px; font-weight:700; color:#0f172a; }
    .hero-subtitle { color:#6b7280; font-size:13px; margin-top:4px; }
    .hero-actions { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
    .chip {
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:8px 12px;
      border-radius:999px;
      background:#f1f5f9;
      color:#0f172a;
      border:1px solid #e2e8f0;
      font-weight:600;
      box-shadow:0 8px 16px rgba(148,163,184,0.22);
    }
    .chip i { color:#2563eb; }
    .pill-btn {
      border:none;
      background:linear-gradient(135deg, #1f78f0, #3a66ff);
      color:#fff;
      padding:10px 16px;
      border-radius:999px;
      font-weight:700;
      box-shadow:0 12px 28px rgba(49,130,246,0.3);
      cursor:pointer;
      display:inline-flex;
      align-items:center;
      gap:8px;
      transition:transform 0.1s ease, box-shadow 0.15s ease;
    }
    .pill-btn:hover { transform:translateY(-1px); box-shadow:0 16px 34px rgba(37,99,235,0.32); }

    .kpi-grid {
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(210px,1fr));
      gap:12px;
      margin-bottom:16px;
    }
    .kpi-card {
      background:#fff;
      border:1px solid #e5e7eb;
      border-radius:14px;
      padding:14px;
      display:flex;
      gap:12px;
      align-items:flex-start;
      box-shadow:0 6px 16px rgba(15,23,42,0.06);
      min-height:88px;
    }
    .kpi-icon {
      width:40px; height:40px;
      border-radius:12px;
      display:grid; place-items:center;
      color:#0f172a;
      background:#e0e7ff;
      flex-shrink:0;
      box-shadow:0 8px 18px rgba(99,102,241,0.22);
    }
    /* Dynamic Colors based on status can be handled here if needed */
    .kpi-card.present .kpi-icon { background:#dcfce7; color:#15803d; box-shadow:0 8px 18px rgba(34,197,94,0.2); }
    .kpi-card.absent .kpi-icon { background:#fee2e2; color:#ef4444; box-shadow:0 8px 18px rgba(239,68,68,0.2); }
    
    .kpi-card:nth-child(2) .kpi-icon { background:#e0f2fe; color:#0ea5e9; box-shadow:0 8px 18px rgba(14,165,233,0.2); }
    .kpi-card:nth-child(3) .kpi-icon { background:#fef9c3; color:#d97706; box-shadow:0 8px 18px rgba(234,179,8,0.2); }
    .kpi-card:nth-child(4) .kpi-icon { background:#ede9fe; color:#7c3aed; box-shadow:0 8px 18px rgba(124,58,237,0.2); }
    
    .kpi-card.present .kpi-value { color:#0f172a; }
    .kpi-card.leave .kpi-value { color:#0ea5e9; }
    .kpi-card.training .kpi-value { color:#059669; }
    .kpi-card.payslip .kpi-value { color:#7c3aed; }
    
    .kpi-label { font-size:12px; text-transform:uppercase; letter-spacing:0.05em; color:#9ca3af; }
    .kpi-value { font-size:22px; font-weight:800; color:#0f172a; line-height:1.2; }
    .kpi-meta { display:flex; align-items:center; gap:6px; color:#16a34a; font-weight:600; font-size:13px; }
    .kpi-meta i { color:inherit; }
    .meta-blue { color:#1d4ed8; }
    .meta-green { color:#16a34a; }
    .meta-purple { color:#7c3aed; }
    .kpi-meta .muted { color:#6b7280; font-weight:500; }
    .status-dot {
      width:8px; height:8px; border-radius:50%;
      background:#16a34a;
      box-shadow:0 0 0 6px rgba(22,163,74,0.12);
    }

    /* --- UPDATED: New Announcement CSS --- */
    .announcement-panel {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
    }
    .announcement-list { display: flex; flex-direction: column; gap: 12px; }
    .ann-item {
        display: flex; gap: 16px; padding: 16px; border-radius: 12px;
        border: 1px solid #f1f5f9; background: #fff;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s;
        cursor: pointer; text-decoration: none;
    }
    .ann-item:hover {
        border-color: #cbd5e1; transform: translateY(-2px); box-shadow: 0 8px 12px -3px rgba(0, 0, 0, 0.05);
    }
    .ann-date-box {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        width: 60px; height: 60px; background: #eff6ff; border-radius: 12px;
        color: #2563eb; flex-shrink: 0;
    }
    .ann-date-day { font-size: 20px; font-weight: 800; line-height: 1; }
    .ann-date-month { font-size: 11px; font-weight: 700; text-transform: uppercase; margin-top: 4px; opacity: 0.8; }
    
    .ann-content { flex: 1; }
    .ann-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
    .ann-title { font-size: 15px; font-weight: 700; color: #0f172a; margin: 0; }
    .ann-excerpt {
        font-size: 13px; color: #64748b; line-height: 1.5; margin: 0;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .priority-badge {
        font-size: 11px; padding: 4px 10px; border-radius: 99px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .badge-high { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
    .badge-normal { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
    /* --- END UPDATED CSS --- */

    .analytics-row {
      display:grid;
      grid-template-columns:2fr 1fr;
      gap:12px;
    }
    @media (max-width:1080px) { .analytics-row { grid-template-columns:1fr; } }

    .panel {
      background:#fff;
      border:1px solid #e5e7eb;
      border-radius:14px;
      padding:16px;
      box-shadow:0 10px 26px rgba(15,23,42,0.08);
    }
    .panel header { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
    .panel title { font-weight:700; color:#0f172a; font-size:15px; }
    .panel .caption { color:#9ca3af; font-size:12px; }
    .panel .label {
      display:flex;
      align-items:center;
      gap:8px;
      font-weight:700;
      color:#0f172a;
      font-size:14px;
    }
    .panel .label .dot {
      width:10px; height:10px; border-radius:50%;
      background:#2563eb;
      box-shadow:0 0 0 6px rgba(37,99,235,0.08);
      display:inline-block;
    }

    .chart-shell { margin-top:6px; background:#f8fafc; border-radius:12px; padding:12px; border:1px solid #e5e7eb; }
    .chart-shell svg { width:100%; height:220px; }

    .donut-wrap {
      display:flex;
      gap:14px;
      align-items:center;
      flex-wrap:wrap;
    }
    .donut {
      --a:45;
      --s:25;
      --c:15;
      --e:10;
      --o:5;
      width:180px; height:180px;
      border-radius:50%;
      background:conic-gradient(
        #1d4ed8 0 calc(var(--a)*1%),
        #f97316 calc(var(--a)*1%) calc((var(--a)+var(--s))*1%),
        #22c55e calc((var(--a)+var(--s))*1%) calc((var(--a)+var(--s)+var(--c))*1%),
        #facc15 calc((var(--a)+var(--s)+var(--c))*1%) calc((var(--a)+var(--s)+var(--c)+var(--e))*1%),
        #a855f7 calc((var(--a)+var(--s)+var(--c)+var(--e))*1%) 100%
      );
      position:relative;
      display:grid;
      place-items:center;
      box-shadow:0 10px 26px rgba(15,23,42,0.08);
    }
    .donut-hole {
      width:100px; height:100px;
      background:#fff;
      border-radius:50%;
      display:grid;
      place-items:center;
      text-align:center;
      border:1px solid #e5e7eb;
      box-shadow:inset 0 0 0 1px #f3f4f6;
    }
    .donut-hole .value { font-weight:800; font-size:22px; color:#0f172a; }
    .donut-hole .label { font-size:12px; color:#9ca3af; }

    .legend { list-style:none; padding:0; margin:0; display:grid; gap:8px; }
    .legend li { display:flex; align-items:center; gap:10px; color:#0f172a; font-weight:600; }
    .legend span.swatch { width:12px; height:12px; border-radius:4px; display:inline-block; }
    .legend .muted { color:#6b7280; font-weight:500; }

    /* Reports & analytics */
    .reports-card {
      background:#fff;
      border:1px solid #e5e7eb;
      border-radius:14px;
      box-shadow:0 14px 30px rgba(15,23,42,0.08);
      padding:18px;
      margin-bottom:16px;
    }
    .pill-row { display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
    .pill { padding:8px 12px; border-radius:999px; border:1px solid #d1d5db; background:#f8fafc; color:#0f172a; font-weight:600; cursor:pointer; }
    .pill.active { background:#1f78f0; color:#fff; border-color:#1f78f0; }
    .export-row { display:flex; gap:8px; }
    .export-btn { padding:8px 12px; border-radius:10px; border:1px solid #d1d5db; background:#fff; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:8px; }
    .filter-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:10px; margin-top:12px; }
    .mini-cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin-top:14px; }
    .mini-card { background:#f8fafc; border:1px solid #e5e7eb; border-radius:12px; padding:12px; }
    .mini-card h4 { margin:0 0 6px; font-size:13px; color:#4b5563; }
    .mini-card .value { font-size:20px; font-weight:800; color:#0f172a; }
    .mini-card .muted { margin:0; }
    .chart-box { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:14px; box-shadow:0 8px 20px rgba(15,23,42,0.06); width:100%; }
    .chart-canvas { width:100%; height:320px; display:block; }
    .chart-canvas.sm { height:300px; }
    .table-lite { width:100%; border-collapse:collapse; margin-top:10px; }
    .table-lite th, .table-lite td { padding:10px 12px; border-bottom:1px solid #e5e7eb; text-align:left; font-size:13px; }
    .table-lite thead th { background:#f8fafc; color:#0f172a; }
    .two-col { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    @media (max-width:960px) { .two-col { grid-template-columns:1fr; } }
    .report-switch { display:flex; gap:8px; flex-wrap:wrap; margin:12px 0; }
    .report-btn { padding:10px 14px; border-radius:12px; border:1px solid #d1d5db; background:#f8fafc; color:#0f172a; font-weight:700; cursor:pointer; box-shadow:0 8px 18px rgba(15,23,42,0.06); }
    .report-btn.active { background:#1f78f0; color:#fff; border-color:#1f78f0; box-shadow:0 12px 26px rgba(31,120,240,0.28); }
    .report-section { display:none; }
    .report-section.active { display:block; }
    footer { text-align:center; color:#94a3b8; font-size:12px; padding:18px 0 6px; }
  </style>
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
      <a href="{{ route($isSupervisor ? 'supervisor.profile' : 'employee.profile') }}" style="color:inherit; text-decoration:none;">
        <i class="fa-regular fa-bell"></i> &nbsp; {{ Auth::user()->name ?? 'User' }}
      </a>
    </div>
  </header>

  <div class="container dashboard-shell">
    
    @if($isSupervisor)
      @include('supervisor.layout.sidebar')
    @else
      @include('employee.layout.sidebar')
    @endif

    <main class="dashboard-main">
      <div class="hero">
        <div>
          <div class="breadcrumb">Home > {{ $dashboardLabel }}</div>
          <div class="hero-title">{{ $dashboardLabel }}</div>
          <div class="hero-subtitle">Personal overview of attendance, leave, payslips, training, and announcements.</div>
        </div>
        <div class="hero-actions">
          <div class="chip"><i class="fa-regular fa-calendar"></i> {{ \Carbon\Carbon::now()->format('d M Y') }}</div>
          <a class="pill-btn" href="{{ route('employee.face.verify.form') }}"><i class="fa-solid fa-user-check"></i> Face Verification</a>
          <a class="pill-btn" href="{{ route('employee.leave.apply') }}"><i class="fa-solid fa-plane-up"></i> Request Leave</a>
        </div>
      </div>

      

      <section class="kpi-grid">
        <article class="kpi-card {{ $todayAttendance ? 'present' : 'absent' }}">
          <div class="kpi-icon"><i class="fa-solid fa-user-check"></i></div>
          <div>
            <div class="kpi-label">Attendance Today</div>
            <div class="kpi-value">{{ $todayAttendance ? 'Present' : 'Absent' }}</div>
            <div class="kpi-meta meta-blue">
                <span class="status-dot"></span> 
                @if($todayAttendance && $todayAttendance->clock_in_time)
                    Clocked in {{ \Carbon\Carbon::parse($todayAttendance->clock_in_time)->format('h:i A') }}
                @else
                    Not clocked in
                @endif
            </div>
          </div>
        </article>

        <article class="kpi-card leave">
          <div class="kpi-icon"><i class="fa-solid fa-umbrella-beach"></i></div>
          <div>
            <div class="kpi-label">Leave Balance</div>
            <div class="kpi-value">{{ $leaveBalance }} days</div>
            <div class="kpi-meta meta-green"><i class="fa-solid fa-leaf"></i> Annual Remaining</div>
          </div>
        </article>

        <article class="kpi-card training">
          <div class="kpi-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
          <div>
            <div class="kpi-label">Upcoming Training</div>
            <div class="kpi-value">{{ $upcomingTrainings }} sessions</div>
            <div class="kpi-meta meta-green"><i class="fa-regular fa-calendar-check"></i> Check schedule</div>
          </div>
        </article>

        <article class="kpi-card payslip">
          <div class="kpi-icon"><i class="fa-solid fa-file-lines"></i></div>
          <div>
            <div class="kpi-label">Payslips</div>
            <div class="kpi-value">
                @if($latestPayslip)
                    {{ $latestPayslip->period->period_month ?? 'N/A' }}
                @else
                    None
                @endif
            </div>
            <div class="kpi-meta meta-purple">
                <i class="fa-solid fa-bolt"></i> 
                @if($latestPayslip)
                    Net: RM{{ number_format($latestPayslip->net_salary, 2) }}
                @else
                    No records
                @endif
            </div>
          </div>
        </article>
      </section>

      {{-- SUPERVISOR ACTION CENTER (Only visible to Supervisors) --}}
      @if($isSupervisor)
      <div style="background: white; border: 1px solid #e2e8f0; border-top: 4px solid #4f46e5; padding: 25px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
          <div>
            <h3 style="margin: 0 0 5px 0; color: #0f172a; font-size: 18px;"><i class="fa-solid fa-users-gear" style="color: #4f46e5;"></i> Manager Action Center</h3>
            <p style="margin: 0; color: #64748b; font-size: 14px;">Quick access to your team management tools and pending approvals.</p>
          </div>
        </div>
        
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
          <a href="{{ Route::has('manager.onboarding.index') ? route('manager.onboarding.index') : '#' }}" class="btn-sm" style="background: #f8fafc; color: #0f172a; border: 1px solid #cbd5e1; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s;">
            <i class="fa-solid fa-list-check" style="color: #0ea5e9;"></i> Team Onboarding
          </a>
          
          <a href="#" onclick="document.getElementById('requisitionModal').style.display='flex'; return false;" class="btn-sm" style="background: #f8fafc; color: #0f172a; border: 1px solid #cbd5e1; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s;">
            <i class="fa-solid fa-user-plus" style="color: #8b5cf6;"></i> Job Requisition
          </a>

          <a href="{{ route('supervisor.appraisal.inbox') }}" class="btn-sm" style="background: #f8fafc; color: #0f172a; border: 1px solid #cbd5e1; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s;">
            <i class="fa-solid fa-star-half-stroke" style="color: #f59e0b;"></i> Manage Team KPIs
          </a>

          <div style="width: 1px; background: #e2e8f0; margin: 0 5px;"></div>

          <a href="{{ route('employee.overtime_inbox.index') }}" class="btn-sm" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-inbox"></i> Check Approvals Inbox
          </a>
        </div>
      </div>
      @endif

      {{-- ANNOUNCEMENT SECTION --}}
      <section class="announcement-panel">
        <header style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <div style="font-weight:700; color:#0f172a; font-size:15px;">
                <i class="fa-solid fa-bullhorn" style="color:#f59e0b;"></i> Latest Announcements
            </div>
        </header>

        <div class="announcement-list">
            @forelse($announcements as $ann)
            <a href="#" class="ann-item">
                <div class="ann-date-box">
                    <span class="ann-date-day">{{ \Carbon\Carbon::parse($ann->publish_at)->format('d') }}</span>
                    <span class="ann-date-month">{{ \Carbon\Carbon::parse($ann->publish_at)->format('M') }}</span>
                </div>
                <div class="ann-content">
                    <div class="ann-header">
                        <h4 class="ann-title">{{ $ann->title }}</h4>
                        @php $p = strtolower($ann->priority); @endphp
                        @if($p == 'critical' || $p == 'urgent')
                            <span class="priority-badge badge-high" style="background:#fee2e2; color:#dc2626; border-color:#fecaca;">{{ $ann->priority }}</span>
                        @elseif($p == 'high')
                            <span class="priority-badge badge-high" style="background:#ffedd5; color:#c2410c; border-color:#fed7aa;">{{ $ann->priority }}</span>
                        @else
                            <span class="priority-badge badge-normal">{{ $ann->priority }}</span>
                        @endif
                    </div>
                    <p class="ann-excerpt">{{ Str::limit($ann->content, 120) }}</p>
                </div>
            </a>
            @empty
            <div style="text-align:center; padding:40px; background:#f8fafc; border-radius:12px; border:1px dashed #cbd5e1;">
                <i class="fa-regular fa-folder-open" style="font-size:32px; color:#cbd5e1; margin-bottom:12px;"></i>
                <p style="color:#64748b; font-size:14px; margin:0;">No announcements posted yet.</p>
            </div>
            @endforelse
        </div>
      </section>

      {{-- CHARTS & METRICS --}}
      <div class="analytics-row">
        <section class="panel">
          <header>
            <div class="label"><span class="dot"></span> Attendance (Last 7 Days)</div>
          </header>
          <div class="chart-shell">
            <canvas id="chart-attendance-7d" class="chart-canvas"></canvas>
          </div>
        </section>

        {{-- === DYNAMIC LEAVE BREAKDOWN CALCULATIONS === --}}
        @php
            $leaveRows = collect($reportLeave['rows'] ?? []);
            $totalUsed = $leaveRows->sum('used');
            
            $usedAnnual = $leaveRows->where('type', 'Annual')->sum('used');
            $usedSick = $leaveRows->where('type', 'Sick')->sum('used');
            $usedCasual = $leaveRows->where('type', 'Casual')->sum('used');
            $usedEmergency = $leaveRows->where('type', 'Emergency')->sum('used');
            $usedOther = $totalUsed - ($usedAnnual + $usedSick + $usedCasual + $usedEmergency);

            $pctAnnual = $totalUsed > 0 ? ($usedAnnual / $totalUsed) * 100 : 0;
            $pctSick = $totalUsed > 0 ? ($usedSick / $totalUsed) * 100 : 0;
            $pctCasual = $totalUsed > 0 ? ($usedCasual / $totalUsed) * 100 : 0;
            $pctEmergency = $totalUsed > 0 ? ($usedEmergency / $totalUsed) * 100 : 0;
            $pctOther = $totalUsed > 0 ? ($usedOther / $totalUsed) * 100 : 0;
        @endphp

        <section class="panel">
          <header>
            <div class="label"><span class="dot" style="background:#1d4ed8; box-shadow:0 0 0 6px rgba(29,78,216,0.08);"></span> Leave Breakdown</div>
          </header>
          <div class="donut-wrap">
            @if($totalUsed > 0)
              <div class="donut" style="--a:{{ $pctAnnual }}; --s:{{ $pctSick }}; --c:{{ $pctCasual }}; --e:{{ $pctEmergency }}; --o:{{ $pctOther }};">
            @else
              <div class="donut" style="background: #f1f5f9;">
            @endif
              <div class="donut-hole">
                <div class="value">{{ rtrim(rtrim(number_format($totalUsed, 1), '0'), '.') }}</div>
                <div class="label">days used</div>
              </div>
            </div>
            <ul class="legend">
              <li><span class="swatch" style="background:#1d4ed8;"></span> Annual <span class="muted" style="margin-left:auto;">{{ rtrim(rtrim(number_format($usedAnnual, 1), '0'), '.') }} days</span></li>
              <li><span class="swatch" style="background:#f97316;"></span> Sick <span class="muted" style="margin-left:auto;">{{ rtrim(rtrim(number_format($usedSick, 1), '0'), '.') }} days</span></li>
              <li><span class="swatch" style="background:#22c55e;"></span> Casual <span class="muted" style="margin-left:auto;">{{ rtrim(rtrim(number_format($usedCasual, 1), '0'), '.') }} days</span></li>
              <li><span class="swatch" style="background:#facc15;"></span> Emergency <span class="muted" style="margin-left:auto;">{{ rtrim(rtrim(number_format($usedEmergency, 1), '0'), '.') }} days</span></li>
              <li><span class="swatch" style="background:#a855f7;"></span> Other <span class="muted" style="margin-left:auto;">{{ rtrim(rtrim(number_format($usedOther, 1), '0'), '.') }} days</span></li>
            </ul>
        </div>
      </section>
      </div>

      {{-- REPORTS DATA --}}
      <section class="reports-card">
        <div style="display:flex; justify-content:space-between; gap:10px; align-items:center; flex-wrap:wrap;">
          <div>
            <h3 style="margin:0;">Central Reports & Analytics</h3>
          </div>
        </div>

        <div class="report-switch">
          <button class="report-btn active" data-section="overtime">Overtime</button>
          <button class="report-btn" data-section="leave">Leave</button>
          <button class="report-btn" data-section="predictive">Predictive</button>
        </div>

        <div class="mini-cards">
          <div class="mini-card">
            <h4>Attendance Rate</h4>
            <div class="value">{{ $reportAttendance['attendance_rate'] ?? 0 }}%</div>
          </div>
          <div class="mini-card">
            <h4>Overtime Hours</h4>
            <div class="value">{{ $reportOvertime['total_hours'] ?? 0 }}h</div>
          </div>
          <div class="mini-card">
            <h4>Leave Remaining</h4>
            @php $leaveRemaining = isset($reportLeave['rows']) ? (float) collect($reportLeave['rows'])->sum('remaining') : 0; @endphp
            <div class="value">{{ rtrim(rtrim(number_format($leaveRemaining, 1), '0'), '.') }}</div>
          </div>
          <div class="mini-card">
            <h4>Risk Signal</h4>
            <div class="value">{{ $reportPredictive['attendance_risk_label'] ?? 'Low' }}</div>
          </div>
        </div>
      </section>

      <section class="reports-card report-section active" id="section-overtime">
        <h4 style="margin:0 0 6px;"><i class="fa-solid fa-clock"></i> Overtime Cost</h4>
        <div class="chart-box" style="margin-bottom:12px; min-height:220px;">
          <canvas id="chart-overtime" class="chart-canvas sm"></canvas>
        </div>
      </section>
      
      <section class="reports-card report-section" id="section-leave">
        <h4 style="margin:0 0 6px;"><i class="fa-solid fa-umbrella-beach"></i> Leave Usage</h4>
        <div class="chart-box" style="margin-bottom:12px; min-height:220px;">
          <canvas id="chart-leave-usage" class="chart-canvas sm"></canvas>
        </div>
      </section>
      
      <section class="reports-card report-section" id="section-predictive">
        <h4 style="margin:0 0 6px;"><i class="fa-solid fa-bolt"></i> Predictive Signals</h4>
        <div class="mini-cards" style="margin-top:0;">
          <div class="mini-card">
            <h4>Attendance Risk</h4>
            <div class="value">{{ $reportPredictive['attendance_risk_label'] ?? 'Low' }}</div>
          </div>
          <div class="mini-card">
            <h4>Projected OT Cost</h4>
            <div class="value">RM{{ number_format((int) ($reportPredictive['projected_ot_cost'] ?? 0)) }}</div>
          </div>
        </div>
      </section>

      <footer>&copy; 2026 Web-Based HRMS. All Rights Reserved.</footer>
    </main>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const reportButtons = document.querySelectorAll('.report-btn');
      const reportSections = document.querySelectorAll('.report-section');
      let overtimeChartCreated = false;
      let leaveChartCreated = false;
      
      function maybeCreateOvertimeChart() {
        if (overtimeChartCreated) return;
        const ctx3 = document.getElementById('chart-overtime');
        if (!ctx3 || typeof Chart === 'undefined') return;
        new Chart(ctx3, {
          type: 'line',
          data: { labels: otLabels, datasets: [{ label: 'OT Cost', data: otCost, borderColor: '#1f78f0', backgroundColor: 'rgba(31,120,240,0.15)', fill: true, tension: 0.35 }] },
          options: { responsive: true, maintainAspectRatio: false }
        });
        overtimeChartCreated = true;
      }

      function maybeCreateLeaveChart() {
        if (leaveChartCreated) return;
        const ctx = document.getElementById('chart-leave-usage');
        if (!ctx || typeof Chart === 'undefined') return;
        new Chart(ctx, {
          type: 'bar',
          data: { labels: leaveChartLabels, datasets: [{ label: 'Used', data: leaveChartUsed, backgroundColor: '#38bdf8' }, { label: 'Remaining', data: leaveChartRemaining, backgroundColor: '#22c55e' }] },
          options: { responsive: true, maintainAspectRatio: false }
        });
        leaveChartCreated = true;
      }

      reportButtons.forEach(btn => {
        btn.addEventListener('click', () => {
          const target = btn.getAttribute('data-section');
          reportButtons.forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          reportSections.forEach(sec => sec.classList.toggle('active', sec.id === `section-${target}`));
          if (target === 'overtime') maybeCreateOvertimeChart();
          if (target === 'leave') maybeCreateLeaveChart();
        });
      });

      @php
        $chartTrendLabels = $reportAttendance['trend_labels'] ?? ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
        $chartTrendPresent = $reportAttendance['trend_present'] ?? [0,0,0,0,0,0,0];
        $chartOtLabels = $reportOvertime['labels'] ?? [];
        $chartOtCost = $reportOvertime['cost_data'] ?? [];
        $chartLeaveLabels = $reportLeave['labels'] ?? [];
        $chartLeaveUsed = $reportLeave['used'] ?? [];
        $chartLeaveRemaining = $reportLeave['remaining'] ?? [];
      @endphp
      
      const trendLabels = @json($chartTrendLabels);
      const trendPresent = @json($chartTrendPresent);
      const otLabels = @json($chartOtLabels);
      const otCost = @json($chartOtCost);
      const leaveChartLabels = @json($chartLeaveLabels);
      const leaveChartUsed = @json($chartLeaveUsed);
      const leaveChartRemaining = @json($chartLeaveRemaining);

      maybeCreateOvertimeChart();

      const ctx1 = document.getElementById('chart-attendance-7d');
      if (ctx1 && typeof Chart !== 'undefined') {
        new Chart(ctx1, {
          type: 'line',
          data: { labels: trendLabels, datasets: [{ label: 'Present', data: trendPresent, fill: true, backgroundColor: 'rgba(37,99,235,0.15)', borderColor: '#2563eb', tension: 0.35 }] },
          options: { responsive: true, maintainAspectRatio: false }
        });
      }
    });
  </script>
</body>
</html>