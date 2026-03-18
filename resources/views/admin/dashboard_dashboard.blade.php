<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - HRMS</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .reports-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:16px; box-shadow:0 8px 18px rgba(15,23,42,0.08); margin-top:16px; }
    .report-switch { display:flex; gap:8px; flex-wrap:wrap; margin:12px 0; }
    .report-btn { padding:10px 14px; border-radius:12px; border:1px solid #d1d5db; background:#f8fafc; color:#0f172a; font-weight:700; cursor:pointer; }
    .report-btn.active { background:#1f78f0; color:#fff; border-color:#1f78f0; }
    .report-section { display:none; }
    .report-section.active { display:block; }
    .export-row { display:flex; gap:10px; align-items:center; justify-content:flex-end; flex-wrap:wrap; }
    .export-btn { padding:8px 12px; border-radius:10px; border:1px solid #d1d5db; background:#fff; color:#0f172a; font-weight:700; cursor:pointer; }
    .export-btn:hover { background:#f8fafc; }
    .filter-row { display:flex; gap:12px; flex-wrap:wrap; margin-top:10px; }
    .filter-row label { display:block; font-size:12px; color:#475569; margin-bottom:6px; font-weight:700; }
    .filter-row select, .filter-row input { padding:8px 10px; border-radius:10px; border:1px solid #d1d5db; background:#fff; color:#0f172a; min-width:160px; }
    .chart-box { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:14px; width:100%; }
    .chart-canvas { width:100%; height:300px; display:block; }
    .mini-cards { display:grid; grid-template-columns:repeat(4, minmax(200px, 1fr)); gap:10px; margin-top:12px; }
    @media (max-width:1100px) { .mini-cards { grid-template-columns:repeat(2, minmax(200px, 1fr)); } }
    @media (max-width:700px) { .mini-cards { grid-template-columns:1fr; } }
    .mini-card { border:1px solid #e5e7eb; border-radius:14px; padding:12px 14px; background:#fff; }
    .mini-card h4 { margin:0 0 6px; font-size:13px; color:#475569; }
    .mini-card .value { font-size:20px; font-weight:800; color:#0f172a; }
    .muted { color:#64748b; font-size:12px; }
    .table-lite { width:100%; border-collapse:collapse; font-size:13px; }
    .table-lite th, .table-lite td { padding:10px 12px; border-bottom:1px solid #e5e7eb; text-align:left; }
    .table-lite thead th { background:#f8fafc; color:#0f172a; }
    .two-col { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    @media (max-width:960px) { .two-col { grid-template-columns:1fr; } }
  </style>
</head>
<body>

  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
      <a href="{{ route('admin.profile') }}" style="text-decoration: none; color: inherit;">
        <i class="fa-regular fa-bell"></i> &nbsp; {{ Auth::user()->name }}
      </a>
    </div>
  </header>

  <div class="container">

    @include('admin.layout.sidebar')

    <main>

      <div class="dashboard-top">
        <div>
          <h2>Admin Dashboard</h2>
          <p class="subtitle">Overview of recruitment, appraisal, training, onboarding and announcements.</p>
        </div>
        <div class="dashboard-top-right">
          <div class="dashboard-date">
            <i class="fa-regular fa-calendar"></i>
            <span>{{ \Carbon\Carbon::now()->format('d M Y') }}</span>
          </div>
          <a href="{{ route('admin.announcements.index') }}" class="btn btn-primary" style="text-decoration: none;">
            <i class="fa-solid fa-plus"></i> Quick Action
          </a>
        </div>
      </div>

      <div class="dashboard-metrics">
        <div class="metric-card">
          <div class="metric-icon metric-icon-blue">
            <i class="fa-solid fa-users"></i>
          </div>
          <div class="metric-content">
            <span class="metric-label">Total Employees</span>
            <span class="metric-value">{{ $totalEmployees }}</span>
            <span class="metric-trend"><i class="fa-solid fa-arrow-up"></i> +{{ $newEmployeesThisMonth }} this month</span>
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-icon metric-icon-orange">
            <i class="fa-solid fa-briefcase"></i>
          </div>
          <div class="metric-content">
            <span class="metric-label">Active Job Posts</span>
            <span class="metric-value">{{ $activeJobPosts }}</span>
            <span class="metric-trend"><i class="fa-solid fa-arrow-up"></i> Open roles</span>
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-icon metric-icon-green">
            <i class="fa-solid fa-graduation-cap"></i>
          </div>
          <div class="metric-content">
            <span class="metric-label">Ongoing Training</span>
            <span class="metric-value">{{ $ongoingTraining }}</span>
            <span class="metric-trend"><i class="fa-solid fa-arrow-right"></i> Active programs</span>
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-icon metric-icon-red">
            <i class="fa-solid fa-chart-line"></i>
          </div>
          <div class="metric-content">
            <span class="metric-label">Pending Reviews</span>
            <span class="metric-value">{{ $pendingReviews }}</span>
            <span class="metric-trend"><i class="fa-solid fa-circle-exclamation"></i> Need attention</span>
          </div>
        </div>
      </div>

      <div class="dashboard-main-grid">

        <div class="dashboard-main-left">

          <div class="analytics-grid">

            <div class="panel analytics-card">
              <div class="panel-header">
                <h3><i class="fa-solid fa-chart-area"></i> Employee Growth</h3>
              </div>
              <div class="chart-container" style="height: 200px; position: relative;">
                  <canvas id="employeeGrowthChart"></canvas>
              </div>
            </div>

            <div class="panel analytics-card">
              <div class="panel-header">
                <h3><i class="fa-solid fa-chart-pie"></i> Department Dist.</h3>
              </div>
              <div class="chart-container" style="height: 200px; position: relative;">
                  <canvas id="deptDistChart"></canvas>
              </div>
            </div>

          </div>

          <div class="module-grid">

            <div class="panel module-card">
              <div class="panel-header">
                <h3><i class="fa-solid fa-briefcase"></i> Recruitment</h3>
              </div>
              <ul class="module-list">
                <li><span>Active Job Posts</span><strong>{{ $activeJobPosts }}</strong></li>
                <li><span>New Applicants (Week)</span><strong>{{ $newApplicants }}</strong></li>
                <li><span>Interviews Scheduled</span><strong>{{ $interviewsScheduled }}</strong></li>
              </ul>
              <a href="#" class="module-link">Go to Recruitment</a>
            </div>

            <div class="panel module-card">
              <div class="panel-header">
                <h3><i class="fa-solid fa-chart-line"></i> Appraisal</h3>
              </div>
              <ul class="module-list">
                <li><span>Pending Reviews</span><strong>{{ $pendingReviews }}</strong></li>
                <li><span>Completed This Cycle</span><strong>{{ $completedReviews }}</strong></li>
                <li><span>Average KPI Score</span><strong>{{ number_format($avgKpiScore, 1) }}%</strong></li>
              </ul>
              <a href="#" class="module-link">Go to Appraisal</a>
            </div>

            <div class="panel module-card">
              <div class="panel-header">
                <h3><i class="fa-solid fa-graduation-cap"></i> Training</h3>
              </div>
              <ul class="module-list">
                <li><span>Ongoing Trainings</span><strong>{{ $ongoingTraining }}</strong></li>
                <li><span>Completed Trainings</span><strong>{{ $completedTrainings }}</strong></li>
                <li><span>Total Participants</span><strong>{{ $totalParticipants }}</strong></li>
              </ul>
              <a href="#" class="module-link">Go to Training</a>
            </div>

          </div>

          <!-- Central Reports & Analytics (Admin: all employees) -->
          <section class="reports-card">
            <div style="display:flex; justify-content:space-between; gap:10px; align-items:center; flex-wrap:wrap;">
              <div>
                <h3 style="margin:0;">Central Reports &amp; Analytics</h3>
                <p class="muted" style="margin:4px 0 0;">Attendance, overtime, leave, and predictive signals for all employees.</p>
              </div>
              <div class="export-row">
                <button class="export-btn" type="button"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
                <button class="export-btn" type="button"><i class="fa-regular fa-file-pdf"></i> Export PDF</button>
              </div>
            </div>

            <div class="report-switch">
              <button class="report-btn active" data-section="overtime">Overtime</button>
              <button class="report-btn" data-section="leave">Leave</button>
              <button class="report-btn" data-section="predictive">Predictive</button>
            </div>

            @php
              $selectedMonth = request('month');
              $monthNow = \Carbon\Carbon::today()->startOfMonth();
              $monthOptions = collect(range(0, 11))->map(function ($i) use ($monthNow) {
                $m = $monthNow->copy()->subMonths($i);
                return ['value' => $m->format('Y-m'), 'label' => $m->format('F Y')];
              });
              $fallbackMonth = $monthOptions->first()['value'] ?? \Carbon\Carbon::today()->format('Y-m');
              $selectedMonth = (is_string($selectedMonth) && preg_match('/^\\d{4}-\\d{2}$/', $selectedMonth)) ? $selectedMonth : $fallbackMonth;
              $fromVal = $reportAttendance['period_start'] ?? \Carbon\Carbon::today()->subDays(29)->format('Y-m-d');
              $toVal = $reportAttendance['period_end'] ?? \Carbon\Carbon::today()->format('Y-m-d');
            @endphp
            <form method="GET" class="filter-row" id="monthFilterForm">
              <div>
                <label>Month</label>
                <select name="month" id="monthSelect">
                  @foreach($monthOptions as $opt)
                    <option value="{{ $opt['value'] }}" @selected($selectedMonth === $opt['value'])>{{ $opt['label'] }}</option>
                  @endforeach
                </select>
              </div>
              <div>
                <label>From</label>
                <input type="date" value="{{ $fromVal }}" readonly>
              </div>
              <div>
                <label>To</label>
                <input type="date" value="{{ $toVal }}" readonly>
              </div>
            </form>

            <div class="mini-cards">
              <div class="mini-card">
                <h4>Attendance Rate</h4>
                <div class="value">{{ $reportAttendance['attendance_rate'] ?? 0 }}%</div>
                <p class="muted">Present vs total days (All employees)</p>
              </div>
              <div class="mini-card">
                <h4>Overtime Hours</h4>
                <div class="value">{{ $reportOvertime['total_hours'] ?? 0 }}h</div>
                <p class="muted">Approved OT (last 12 months)</p>
              </div>
              <div class="mini-card">
                <h4>Leave Used</h4>
                <div class="value">{{ isset($reportLeave['rows']) ? (int) round(collect($reportLeave['rows'])->sum('used')) : 0 }}</div>
                <p class="muted">Approved days ({{ $reportLeave['year'] ?? now()->year }})</p>
              </div>
              <div class="mini-card">
                <h4>Risk Signal</h4>
                <div class="value">{{ $reportPredictive['attendance_risk_label'] ?? 'Low' }}</div>
                <p class="muted">Late {{ $reportAttendance['late_count'] ?? 0 }}, Absent {{ $reportAttendance['absent_count'] ?? 0 }}</p>
              </div>
            </div>

            <section class="reports-card report-section active" id="section-overtime" style="box-shadow:none; margin-top:12px;">
              <h4 style="margin:0 0 6px;"><i class="fa-solid fa-clock"></i> Overtime Cost (All employees)</h4>
              <p class="muted" style="margin:0 0 10px;">Approved OT hours and cost by month.</p>
              <div class="chart-box" style="margin-bottom:12px; min-height:220px;">
                <canvas id="admin-chart-overtime" class="chart-canvas"></canvas>
              </div>
              <table class="table-lite">
                <thead><tr><th>Month</th><th>Hours</th><th>Cost</th></tr></thead>
                <tbody>
                  @forelse(($reportOvertime['table_rows'] ?? []) as $row)
                    <tr><td>{{ $row['month'] }}</td><td>{{ $row['hours'] }}h</td><td>RM{{ number_format($row['cost']) }}</td></tr>
                  @empty
                    <tr><td colspan="3" class="muted">No approved overtime in the last 12 months.</td></tr>
                  @endforelse
                </tbody>
              </table>
            </section>

            <section class="reports-card report-section" id="section-leave" style="box-shadow:none; margin-top:12px;">
              <h4 style="margin:0 0 6px;"><i class="fa-solid fa-umbrella-beach"></i> Leave Usage (All employees)</h4>
              <p class="muted" style="margin:0 0 10px;">Usage by type with a compact bar chart and balances table ({{ $reportLeave['year'] ?? now()->year }}).</p>
              <div class="chart-box" style="margin-bottom:12px; min-height:220px;">
                <canvas id="admin-chart-leave" class="chart-canvas"></canvas>
              </div>
              <table class="table-lite">
                <thead><tr><th>Type</th><th>Used</th><th>Remaining</th></tr></thead>
                <tbody>
                  @forelse(($reportLeave['rows'] ?? []) as $row)
                    <tr><td>{{ $row['type'] }}</td><td>{{ rtrim(rtrim(number_format((float) $row['used'], 2), '0'), '.') }}</td><td>{{ rtrim(rtrim(number_format((float) $row['remaining'], 2), '0'), '.') }}</td></tr>
                  @empty
                    <tr><td colspan="3" class="muted">No leave data for this year.</td></tr>
                  @endforelse
                </tbody>
              </table>
            </section>

            <section class="reports-card report-section" id="section-predictive" style="box-shadow:none; margin-top:12px;">
              <h4 style="margin:0 0 6px;"><i class="fa-solid fa-bolt"></i> Predictive Signals</h4>
              <p class="muted" style="margin:0 0 12px;">Rule-based scoring to flag attendance risk, OT projection, and leave shortage.</p>
              <div class="mini-cards" style="margin-top:0;">
                <div class="mini-card">
                  <h4>Attendance Risk</h4>
                  <div class="value">{{ $reportPredictive['attendance_risk_label'] ?? 'Low' }}</div>
                  <p class="muted">Score {{ $reportPredictive['attendance_risk_score'] ?? 0 }} (last 30 days)</p>
                </div>
                <div class="mini-card">
                  <h4>Projected OT Cost</h4>
                  <div class="value">RM{{ number_format((int) ($reportPredictive['projected_ot_cost'] ?? 0)) }}</div>
                  <p class="muted">Avg last 3 months (All employees)</p>
                </div>
                <div class="mini-card">
                  <h4>Leave Signal</h4>
                  <div class="value">{{ $reportPredictive['leave_signal'] ?? 'OK' }}</div>
                  <p class="muted">Types at/below 0 remaining</p>
                </div>
              </div>
            </section>
          </section>

        </div>

        <div class="dashboard-main-right">

          <div class="panel announcement-widget">
            <div class="panel-header">
              <h3><i class="fa-solid fa-calendar-check"></i> Upcoming Interviews</h3>
            </div>
            <ul class="announcement-list">
              @forelse($upcomingInterviews as $interview)
              <li>
                <div class="announcement-title">
                    {{ $interview->job->job_title ?? 'Job' }} – {{ $interview->applicant->full_name ?? 'Applicant' }}
                </div>
                <div class="announcement-meta">
                    {{ $interview->updated_at->format('d M Y') }} • Online
                </div>
              </li>
              @empty
              <li style="padding: 10px; text-align: center; color: #999;">No interviews scheduled.</li>
              @endforelse
            </ul>
          </div>

          <div class="panel announcement-widget">
            <div class="panel-header">
              <h3><i class="fa-solid fa-bullhorn"></i> Latest Announcements</h3>
            </div>
            <ul class="announcement-list">
              @forelse($announcements as $announce)
              <li>
                <div class="announcement-title">{{ $announce->title }}</div>
                <div class="announcement-meta">
                    Posted {{ $announce->publish_at ? $announce->publish_at->diffForHumans() : $announce->created_at->diffForHumans() }}
                </div>
              </li>
              @empty
               <li style="padding: 10px; text-align: center; color: #999;">No announcements found.</li>
              @endforelse
            </ul>
            <a href="{{ route('admin.announcements.index') }}" class="module-link">View All Announcements</a>
          </div>

        </div>

      </div>

      <footer>© 2025 Web-Based HRMS. All Rights Reserved.</footer>

    </main>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Central Reports & Analytics (Admin: all employees)
        (function() {
            const reportButtons = document.querySelectorAll('.report-btn');
            const reportSections = document.querySelectorAll('.report-section');
            if (!reportButtons.length) return;

            const otLabels = @json($reportOvertime['labels'] ?? []);
            const otCost = @json($reportOvertime['cost_data'] ?? []);

            const leaveLabels = @json($reportLeave['labels'] ?? []);
            const leaveUsed = @json($reportLeave['used'] ?? []);
            const leaveRemaining = @json($reportLeave['remaining'] ?? []);

            let overtimeChartCreated = false;
            let leaveChartCreated = false;

            function maybeCreateOvertimeChart() {
                if (overtimeChartCreated) return;
                const el = document.getElementById('admin-chart-overtime');
                if (!el || typeof Chart === 'undefined') return;
                new Chart(el, {
                    type:'line',
                    data:{
                        labels: otLabels.length ? otLabels : ['No data'],
                        datasets:[{
                            label:'OT Cost (RM)',
                            data: otCost.length ? otCost : [0],
                            borderColor:'#1f78f0',
                            backgroundColor:'rgba(31,120,240,0.15)',
                            tension:0.35,
                            fill:true,
                            pointRadius:5
                        }]
                    },
                    options:{
                        responsive:true,
                        maintainAspectRatio:false,
                        plugins:{ legend:{ display:false } },
                        scales:{ y:{ beginAtZero:true, grid:{ color:'#e5e7eb' } }, x:{ grid:{ display:false } } }
                    }
                });
                overtimeChartCreated = true;
            }

            function maybeCreateLeaveChart() {
                if (leaveChartCreated) return;
                const el = document.getElementById('admin-chart-leave');
                if (!el || typeof Chart === 'undefined') return;
                new Chart(el, {
                    type:'bar',
                    data:{
                        labels: leaveLabels.length ? leaveLabels : ['No data'],
                        datasets:[
                          { label:'Used', data: leaveUsed.length ? leaveUsed : [0], backgroundColor:'#38bdf8' },
                          { label:'Remaining', data: leaveRemaining.length ? leaveRemaining : [0], backgroundColor:'#22c55e' }
                        ]
                    },
                    options:{
                        responsive:true,
                        maintainAspectRatio:false,
                        plugins:{ legend:{ display:true, position:'top' } },
                        scales:{ y:{ beginAtZero:true, grid:{ color:'#e5e7eb' } }, x:{ grid:{ display:false } } }
                    }
                });
                leaveChartCreated = true;
            }

            // Overtime is visible by default
            maybeCreateOvertimeChart();

            // Month selector auto-submit
            (function () {
                const sel = document.getElementById('monthSelect');
                const form = document.getElementById('monthFilterForm');
                if (sel && form) {
                    sel.addEventListener('change', function () {
                        form.submit();
                    });
                }
            })();

            reportButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const target = btn.getAttribute('data-section');
                    reportButtons.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    let matched = false;
                    reportSections.forEach(sec => {
                        const active = sec.id === `section-${target}`;
                        sec.classList.toggle('active', active);
                        if (active) matched = true;
                    });
                    if (!matched) reportSections.forEach(sec => sec.classList.remove('active'));
                    if (target === 'overtime') maybeCreateOvertimeChart();
                    if (target === 'leave') maybeCreateLeaveChart();
                });
            });
        })();

        // 1. Employee Growth Chart
        const ctxGrowth = document.getElementById('employeeGrowthChart');
        if (ctxGrowth) {
            new Chart(ctxGrowth, {
                type: 'line',
                data: {
                    labels: @json($growthLabels), 
                    datasets: [{
                        label: 'New Hires',
                        data: @json($growthData),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, grid: { display: false } } }
                }
            });
        }

        // 2. Department Distribution Chart
        const ctxDept = document.getElementById('deptDistChart');
        if (ctxDept) {
            new Chart(ctxDept, {
                type: 'doughnut',
                data: {
                    labels: @json($deptLabels),
                    datasets: [{
                        data: @json($deptData),
                        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { position: 'right', labels: { boxWidth: 10, usePointStyle: true } } 
                    }
                }
            });
        }
    });
  </script>

</body>
</html>