<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Attendance - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    main { padding: 2rem; }
    .breadcrumb { font-size:.85rem; color:#94a3b8; margin-bottom:1rem; }
    h2 { color:#0ea5e9; margin:0 0 .4rem 0; }
    .subtitle { color:#64748b; margin-bottom:1.2rem; }
    .card-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px; margin-bottom:18px; }
    .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:14px; box-shadow:0 8px 20px rgba(15,23,42,.06); }
    .card .label { font-size:12px; color:#94a3b8; text-transform:uppercase; letter-spacing:.02em; }
    .card .value { font-size:22px; font-weight:700; color:#0f172a; }
    table { width:100%; border-collapse:collapse; }
    thead { background:#0f172a; color:#38bdf8; }
    th, td { padding:12px 14px; border-bottom:1px solid #e5e7eb; text-align:left; }
    tbody tr:hover { background:#f8fafc; }
    .status { padding:4px 10px; border-radius:999px; font-size:.85rem; }
    .present { background:#dcfce7; color:#166534; }
    .late { background:#fef9c3; color:#854d0e; }
    .absent { background:#fee2e2; color:#991b1b; }
    .chips { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px; }
    .chip { background:#e0f2fe; color:#0369a1; padding:6px 10px; border-radius:999px; font-size:.9rem; }
  </style>
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
      <span><i class="fa-regular fa-bell"></i> &nbsp; <a href="{{ route('employee.profile') }}" style="color:inherit; text-decoration:none;">{{ Auth::user()->name ?? 'Employee' }}</a></span>
    </div>
  </header>

  <div class="container">
    @include('employee.layout.sidebar')

    <main>
      <div class="breadcrumb">Attendance · My Log & Overtime</div>
      <h2>My Attendance</h2>
      <p class="subtitle">Quick view of today’s status, the last 30 days, and recent logs.</p>

      @php
        $todayIn = $todayRecord?->clock_in_time ? \Carbon\Carbon::parse($todayRecord->clock_in_time)->format('H:i') : '—';
        $todayOut = $todayRecord?->clock_out_time ? \Carbon\Carbon::parse($todayRecord->clock_out_time)->format('H:i') : '—';
        $todayStatusLabel = ucfirst($todayStatus ?? 'absent');
      @endphp

      <div class="card-grid">
        <div class="card">
          <div class="label">Today</div>
          <div class="value">{{ $todayStatusLabel }}</div>
          <div class="chips">
            <span class="chip">In: {{ $todayIn }}</span>
            <span class="chip">Out: {{ $todayOut }}</span>
          </div>
        </div>
        <div class="card">
          <div class="label">Late Arrivals (30d)</div>
          <div class="value">{{ $lateCount }}</div>
        </div>
        <div class="card">
          <div class="label">Overtime Hours (30d)</div>
          <div class="value">{{ number_format($overtimeHours, 1) }}h</div>
        </div>
        <div class="card">
          <div class="label">Absences (30d)</div>
          <div class="value">{{ $absentCount }}</div>
        </div>
      </div>

      <div class="card" style="margin-bottom:14px;">
        <div class="label" style="margin-bottom:8px;">Recent Attendance</div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Date</th>
                <th>In</th>
                <th>Out</th>
                <th>Status</th>
                <th>Reason</th>
              </tr>
            </thead>
            <tbody>
              @forelse($recentAttendance as $row)
                <tr>
                  <td>{{ $row['date'] }}</td>
                  <td>{{ $row['in'] ? \Carbon\Carbon::parse($row['in'])->format('H:i') : '—' }}</td>
                  <td>{{ $row['out'] ? \Carbon\Carbon::parse($row['out'])->format('H:i') : '—' }}</td>
                  <td>
                    @php $s = $row['status']; @endphp
                    <span class="status {{ $s === 'present' ? 'present' : ($s === 'late' ? 'late' : ($s === 'absent' ? 'absent' : '')) }}">
                      {{ ucfirst($s) }}
                    </span>
                  </td>
                  <td>{{ $row['reason'] ?? '—' }}</td>
                </tr>
              @empty
                <tr><td colspan="5">No attendance data yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <div class="label" style="margin-bottom:8px;">Overtime Requests</div>
        <table>
          <thead>
            <tr><th>Date</th><th>Hours</th><th>Status</th><th>Note</th></tr>
          </thead>
          <tbody>
            @forelse($recentOvertime as $ot)
              <tr>
                <td>{{ $ot->date?->format('Y-m-d') }}</td>
                <td>{{ number_format($ot->hours, 1) }}</td>
                <td>
                  @php $st = strtolower($ot->ot_status ?? ''); @endphp
                  <span class="status {{ $st === 'approved' ? 'present' : ($st === 'pending' ? 'late' : 'absent') }}">
                    {{ ucfirst($ot->ot_status) }}
                  </span>
                </td>
                <td>{{ $ot->reason ?? '—' }}</td>
              </tr>
            @empty
              <tr><td colspan="4">No overtime records yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </main>
  </div>
</body>
</html>
