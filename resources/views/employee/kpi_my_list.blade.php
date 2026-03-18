<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My KPI Goals - HRMS</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <link rel="stylesheet" href="{{ asset('css/kpi_employee.css') }}">
</head>

<body>

  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
        <a href="{{ route('employee.profile') }}" style="text-decoration: none; color: inherit;">
            <i class="fa-regular fa-user"></i> &nbsp; {{ Auth::user()->name }}
        </a>
    </div>
  </header>

  <div class="container">
    {{-- Assuming you have an employee sidebar. If not, verify the path --}}
    @include('employee.layout.sidebar') 

    <main>
      <div class="breadcrumb">Home > My Performance > My KPIs</div>

      <h2>My Performance Goals</h2>
      <p class="subtitle">Track your assigned Key Performance Indicators and progress.</p>

      {{-- KPI Summary Cards --}}
      <div class="summary" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
        <div class="card" style="background:white; padding:20px; border-radius:10px; border:1px solid #e5e7eb;">
            <h3 style="font-size:14px; color:#6b7280; margin-bottom:5px;">Total Goals</h3>
            <p style="font-size:24px; font-weight:700; color:#111827;">{{ $kpis->count() }}</p>
        </div>
        <div class="card" style="background:white; padding:20px; border-radius:10px; border:1px solid #e5e7eb;">
            <h3 style="font-size:14px; color:#6b7280; margin-bottom:5px;">Pending / In Progress</h3>
            <p style="font-size:24px; font-weight:700; color:#f59e0b;">
                {{ $kpis->whereIn('kpi_status', ['pending', 'in_progress'])->count() }}
            </p>
        </div>
        <div class="card" style="background:white; padding:20px; border-radius:10px; border:1px solid #e5e7eb;">
            <h3 style="font-size:14px; color:#6b7280; margin-bottom:5px;">Completed</h3>
            <p style="font-size:24px; font-weight:700; color:#10b981;">
                {{ $kpis->where('kpi_status', 'completed')->count() }}
            </p>
        </div>
      </div>

      <div class="kpi-table-container">
        <table class="kpi-table">
          <thead>
            <tr>
              <th>KPI Title</th>
              <th>Target</th>
              <th>Deadline</th>
              <th>Status</th>
              <th>My Score</th>
              <th>Manager Comments</th>
            </tr>
          </thead>

          <tbody>
            @forelse($kpis as $kpi)
            <tr>
              <td>
                <span style="font-weight:600; color:#2563eb;">{{ $kpi->template->kpi_title }}</span>
                <div style="font-size: 12px; color: #666; margin-top:4px;">{{ $kpi->template->kpi_description }}</div>
              </td>
              
              <td>{{ $kpi->template->default_target }}</td>
              
              <td>{{ \Carbon\Carbon::parse($kpi->deadline)->format('d M Y') }}</td>
              
              <td>
                @if($kpi->kpi_status == 'completed')
                    <span class="kpi-badge kpi-completed">Completed</span>
                @elseif($kpi->kpi_status == 'in_progress')
                    <span class="kpi-badge kpi-in-progress">In Progress</span>
                @else
                    <span class="kpi-badge kpi-pending">Pending</span>
                @endif
              </td>

              <td>
                @if($kpi->actual_score)
                    <span style="font-weight:700; color:#111827;">{{ $kpi->actual_score }}%</span>
                @else
                    <span style="color:#9ca3af;">–</span>
                @endif
              </td>

              <td>
                 @if($kpi->comments)
                    <span style="font-style: italic; color:#4b5563;">"{{ Str::limit($kpi->comments, 40) }}"</span>
                 @else
                    <span style="color:#9ca3af;">No feedback yet</span>
                 @endif
              </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 25px; color:#6b7280;">
                    <i class="fa-solid fa-clipboard-list" style="margin-bottom:10px; font-size:20px;"></i><br>
                    You have no KPIs assigned yet.
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