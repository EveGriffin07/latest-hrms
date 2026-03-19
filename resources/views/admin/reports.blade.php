@php
    // 1. Capture Filter Parameters (Defaults to current month/year)
    $selectedMonth = request('month', date('m'));
    $selectedYear = request('year', date('Y'));

    $kpis = [
        'employees' => 0,
        'active_jobs' => 0,
        'applicants' => 0,
        'pending_appraisals' => 0,
        'trainings' => 0,
        'onboarding' => 0,
    ];

    $chartData = [
        'appraisal_labels' => ['Excellent (>4.5)', 'Good (3.5-4.4)', 'Average (2.5-3.4)', 'Poor (<2.5)'],
        'appraisal_data' => [0, 0, 0, 0],
        'training_labels' => [],
        'training_data' => [],
        'recruitment_labels' => ['Applied', 'Reviewing', 'Interview', 'Hired', 'Rejected'],
        'recruitment_data' => [0, 0, 0, 0, 0],
        'onboarding_data' => [0, 0, 0] 
    ];

    try {
        // 1. New Hires this month
        if (class_exists('\App\Models\Employee')) {
            $kpis['employees'] = \App\Models\Employee::whereYear('hire_date', $selectedYear)
                ->whereMonth('hire_date', $selectedMonth)
                ->count();
        }

        // 2. Jobs posted this month
        if (class_exists('\App\Models\JobPost')) {
            $kpis['active_jobs'] = \App\Models\JobPost::whereYear('created_at', $selectedYear)
                ->whereMonth('created_at', $selectedMonth)
                ->count();
        }

        // 3. Appraisals created/period in this month
        if (class_exists('\App\Models\Appraisal')) {
            $kpis['pending_appraisals'] = \App\Models\Appraisal::where('status', 'pending_manager')
                ->whereYear('created_at', $selectedYear)
                ->whereMonth('created_at', $selectedMonth)
                ->count();
            
            $appraisals = \App\Models\Appraisal::where('status', 'completed')
                ->whereYear('created_at', $selectedYear)
                ->whereMonth('created_at', $selectedMonth)
                ->get();

            $chartData['appraisal_data'] = [
                $appraisals->where('overall_score', '>=', 4.5)->count(),
                $appraisals->whereBetween('overall_score', [3.5, 4.49])->count(),
                $appraisals->whereBetween('overall_score', [2.5, 3.49])->count(),
                $appraisals->where('overall_score', '<', 2.5)->count(),
            ];
        }

        // 4. Trainings starting this month
        if (class_exists('\App\Models\TrainingProgram')) {
            $kpis['trainings'] = \App\Models\TrainingProgram::whereYear('start_date', $selectedYear)
                ->whereMonth('start_date', $selectedMonth)
                ->count();

            $trainings = \App\Models\TrainingProgram::withCount(['enrollments' => function($q) use ($selectedYear, $selectedMonth) {
                    $q->whereYear('enrollment_date', $selectedYear)->whereMonth('enrollment_date', $selectedMonth);
                }])
                ->orderBy('enrollments_count', 'desc')
                ->take(6)->get();

            foreach($trainings as $t) {
                $chartData['training_labels'][] = \Illuminate\Support\Str::limit($t->training_name, 15);
                $chartData['training_data'][] = $t->enrollments_count;
            }
        }

        // 5. Applications received this month
        if (\Illuminate\Support\Facades\Schema::hasTable('applications')) {
            $kpis['applicants'] = \Illuminate\Support\Facades\DB::table('applications')
                ->whereYear('created_at', $selectedYear)
                ->whereMonth('created_at', $selectedMonth)
                ->count();

            $apps = \Illuminate\Support\Facades\DB::table('applications')
                ->whereYear('created_at', $selectedYear)
                ->whereMonth('created_at', $selectedMonth)
                ->select('app_stage', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
                ->groupBy('app_stage')
                ->pluck('count', 'app_stage')->toArray();

            $chartData['recruitment_data'] = [
                $apps['Applied'] ?? 0,
                $apps['Reviewing'] ?? 0,
                $apps['Interview'] ?? 0,
                $apps['Hired'] ?? 0,
                $apps['Rejected'] ?? 0,
            ];
        }

        // 6. Onboarding starting this month
        if (class_exists('\App\Models\Onboarding')) {
            $kpis['onboarding'] = \App\Models\Onboarding::whereYear('start_date', $selectedYear)
                ->whereMonth('start_date', $selectedMonth)
                ->count();

            $chartData['onboarding_data'] = [
                \App\Models\Onboarding::where('status', 'completed')->whereYear('start_date', $selectedYear)->whereMonth('start_date', $selectedMonth)->count(),
                \App\Models\Onboarding::where('status', 'in_progress')->whereYear('start_date', $selectedYear)->whereMonth('start_date', $selectedMonth)->count(),
                \App\Models\Onboarding::where('status', 'pending')->whereYear('start_date', $selectedYear)->whereMonth('start_date', $selectedMonth)->count(),
            ];
        }
    } catch(\Throwable $e) {
        \Illuminate\Support\Facades\Log::error("Dashboard Filter Error: " . $e->getMessage());
    }
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Global Analytics - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    /* Sleek, Reference-Matched Styling */
    body { background: #f3f4f6; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }
    .main-content { padding: 24px 32px; }

    /* Top Toolbar */
    .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
    .toolbar-title h2 { margin: 0; font-size: 20px; font-weight: 700; color: #111827; }
    .toolbar-title p { margin: 4px 0 0 0; font-size: 13px; color: #6b7280; }
    
    .toolbar-actions { display: flex; align-items: center; gap: 12px; }
    .date-picker-mock { display: flex; align-items: center; gap: 10px; background: #fff; border: 1px solid #d1d5db; padding: 8px 16px; border-radius: 8px; font-size: 13px; color: #374151; font-weight: 500; }
    .btn-action { background: #fff; border: 1px solid #d1d5db; color: #374151; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 8px; }
    .btn-action:hover { background: #f9fafb; border-color: #9ca3af; }
    .btn-primary { background: #4f46e5; color: white; border: none; }
    .btn-primary:hover { background: #4338ca; }

    /* KPI Grid (Top Row) */
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .kpi-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
    .kpi-label { font-size: 13px; font-weight: 600; color: #6b7280; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
    .kpi-value { font-size: 28px; font-weight: 700; color: #111827; line-height: 1; }

    /* Chart Grid (2x2 Layout) */
    .charts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .chart-panel { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
    .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .chart-title { font-size: 15px; font-weight: 700; color: #111827; margin: 0; }
    .chart-menu { color: #9ca3af; cursor: pointer; font-size: 18px; }
    
    .canvas-wrapper { position: relative; height: 300px; width: 100%; }

    @media (max-width: 1024px) {
        .charts-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
      <a href="{{ route('admin.profile') }}" style="color:inherit; text-decoration:none;">
        <i class="fa-regular fa-bell"></i> &nbsp; {{ Auth::user()->name ?? 'HR Admin' }}
      </a>
    </div>
  </header>

  <div class="container dashboard-shell">
    @include('admin.layout.sidebar')

    <main class="main-content">
      
      <div class="toolbar">
    <div class="toolbar-title">
        <h2>HR Analytics Dashboard</h2>
        <p>Viewing data for <strong>{{ date("F", mktime(0, 0, 0, $selectedMonth, 10)) }} {{ $selectedYear }}</strong></p>
    </div>
    <div class="toolbar-actions">
        <form action="{{ request()->url() }}" method="GET" style="display: flex; gap: 8px;">
            {{-- Month Selector --}}
            <select name="month" class="date-picker-mock" onchange="this.form.submit()">
                @foreach(range(1, 12) as $m)
                    <option value="{{ sprintf('%02d', $m) }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                        {{ date("F", mktime(0, 0, 0, $m, 10)) }}
                    </option>
                @endforeach
            </select>

            {{-- Year Selector --}}
            <select name="year" class="date-picker-mock" onchange="this.form.submit()">
                @foreach(range(date('Y'), date('Y') - 5) as $y)
                    <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>

            <button type="button" class="btn-action btn-primary" onclick="window.print()">
                <i class="fa-solid fa-file-arrow-down"></i> Export PDF
            </button>
        </form>
    </div>
</div>

      {{-- KPI Metric Row --}}
      <div class="kpi-grid">
          <div class="kpi-card">
              <div class="kpi-label">Total Workforce</div>
              <div class="kpi-value">{{ number_format($kpis['employees']) }}</div>
          </div>
          <div class="kpi-card">
              <div class="kpi-label">Active Jobs</div>
              <div class="kpi-value">{{ number_format($kpis['active_jobs']) }}</div>
          </div>
          <div class="kpi-card">
              <div class="kpi-label">Total Applicants</div>
              <div class="kpi-value">{{ number_format($kpis['applicants']) }}</div>
          </div>
          <div class="kpi-card">
              <div class="kpi-label">Pending Appraisals</div>
              <div class="kpi-value">{{ number_format($kpis['pending_appraisals']) }}</div>
          </div>
          <div class="kpi-card">
              <div class="kpi-label">Training Programs</div>
              <div class="kpi-value">{{ number_format($kpis['trainings']) }}</div>
          </div>
          <div class="kpi-card">
              <div class="kpi-label">Active Onboarding</div>
              <div class="kpi-value">{{ number_format($kpis['onboarding']) }}</div>
          </div>
      </div>

      {{-- Charts 2x2 Grid --}}
      <div class="charts-grid">
          
          {{-- Chart 1: Recruitment Pipeline --}}
          <div class="chart-panel">
              <div class="chart-header">
                  <h3 class="chart-title">Recruitment Pipeline</h3>
                  <i class="fa-solid fa-bars chart-menu"></i>
              </div>
              <div class="canvas-wrapper">
                  <canvas id="recruitmentChart"></canvas>
              </div>
          </div>

          {{-- Chart 2: Appraisal Scores --}}
          <div class="chart-panel">
              <div class="chart-header">
                  <h3 class="chart-title">Company Appraisal Distribution</h3>
                  <i class="fa-solid fa-bars chart-menu"></i>
              </div>
              <div class="canvas-wrapper">
                  <canvas id="appraisalChart"></canvas>
              </div>
          </div>

          {{-- Chart 3: Top Training Programs --}}
          <div class="chart-panel">
              <div class="chart-header">
                  <h3 class="chart-title">Top Training Enrollments</h3>
                  <i class="fa-solid fa-bars chart-menu"></i>
              </div>
              <div class="canvas-wrapper">
                  <canvas id="trainingChart"></canvas>
              </div>
          </div>

          {{-- Chart 4: Onboarding Status --}}
          <div class="chart-panel">
              <div class="chart-header">
                  <h3 class="chart-title">Onboarding Task Completion</h3>
                  <i class="fa-solid fa-bars chart-menu"></i>
              </div>
              <div class="canvas-wrapper">
                  <canvas id="onboardingChart"></canvas>
              </div>
          </div>

      </div>

    </main>
  </div>

  <script>
    // Safely load PHP data into Javascript
    const chartData = @json($chartData);

    // Global Chart Formatting to match the sleek reference image
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#6b7280';
    Chart.defaults.scale.grid.color = '#f3f4f6';
    Chart.defaults.plugins.tooltip.backgroundColor = '#111827';
    Chart.defaults.plugins.tooltip.padding = 12;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;

    document.addEventListener("DOMContentLoaded", function() {
        
        // 1. Recruitment Pipeline (Bar Chart)
        new Chart(document.getElementById('recruitmentChart'), {
            type: 'bar',
            data: {
                labels: chartData.recruitment_labels,
                datasets: [{
                    label: 'Applicants',
                    data: chartData.recruitment_data,
                    backgroundColor: '#4f46e5', // Indigo
                    borderRadius: 4,
                    barPercentage: 0.6
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, border: { display: false } }, x: { grid: { display: false } } }
            }
        });

        // 2. Appraisal Scores (Line/Area Chart for smooth look)
        new Chart(document.getElementById('appraisalChart'), {
            type: 'line',
            data: {
                labels: chartData.appraisal_labels,
                datasets: [{
                    label: 'Employees',
                    data: chartData.appraisal_data,
                    borderColor: '#10b981', // Green
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4, // Smooth curves
                    pointBackgroundColor: '#10b981',
                    pointRadius: 4
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, border: { display: false } }, x: { grid: { display: false } } }
            }
        });

        // 3. Training Programs (Bar Chart)
        new Chart(document.getElementById('trainingChart'), {
            type: 'bar',
            data: {
                labels: chartData.training_labels.length > 0 ? chartData.training_labels : ['No Data'],
                datasets: [{
                    label: 'Enrolled',
                    data: chartData.training_data.length > 0 ? chartData.training_data : [0],
                    backgroundColor: '#0ea5e9', // Light Blue
                    borderRadius: 4,
                    barPercentage: 0.6
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, border: { display: false } }, x: { grid: { display: false } } }
            }
        });

        // 4. Onboarding Status (Doughnut Chart)
        new Chart(document.getElementById('onboardingChart'), {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'In Progress', 'Pending'],
                datasets: [{
                    data: chartData.onboarding_data,
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'], // Green, Yellow, Red
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                cutout: '75%',
                plugins: { 
                    legend: { position: 'right', labels: { usePointStyle: true, padding: 20 } } 
                }
            }
        });
    });
  </script>
</body>
</html>