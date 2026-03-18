<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Provisioning Management - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .metric-card { background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 20px; transition: 0.2s; }
    .metric-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
    .metric-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
    .metric-info h4 { margin: 0 0 5px 0; font-size: 13px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .metric-info p { margin: 0; font-size: 24px; font-weight: 700; color: #0f172a; }

    .section-container { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.02); overflow: hidden; }
    .hr-table { width: 100%; border-collapse: collapse; text-align: left; }
    .hr-table th { background: #f8fafc; padding: 16px 20px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
    .hr-table td { padding: 16px 20px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #1e293b; }
    .hr-table tbody tr:hover { background: #f8fafc; }

    .progress-wrapper { display: flex; align-items: center; gap: 10px; width: 100%; }
    .progress-bar { flex: 1; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; }
    .progress-fill { height: 100%; border-radius: 4px; background: #2563eb; transition: width 0.3s; }
    .progress-text { font-size: 12px; font-weight: 600; color: #475569; min-width: 35px; text-align: right; }

    .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
    .status-in_progress { background: #dbeafe; color: #1d4ed8; }
    .status-pending { background: #fef3c7; color: #b45309; }
    .status-completed { background: #dcfce7; color: #166534; }

    .btn-view { padding: 8px 16px; background: #fff; border: 1px solid #cbd5e1; color: #0f172a; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; }
    .btn-view:hover { background: #f1f5f9; border-color: #94a3b8; }
  </style>
</head>

<body>
<header>
  <div class="title">Web-Based HRMS</div>
  <div class="user-info">
    <a href="{{ route('admin.profile') }}" style="text-decoration: none; color: inherit;"><i class="fa-regular fa-user"></i> &nbsp; {{ Auth::user()->name ?? 'HR Admin' }}</a>
  </div>
</header>

<div class="container">
  @include('admin.layout.sidebar')

  <main>
    <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:30px;">
        <div>
            <div class="breadcrumb" style="color: #64748b; font-size: 14px; margin-bottom: 5px;">Home > <span style="color: #0f172a; font-weight: 500;">Provisioning</span></div>
            <h2 style="margin:0; font-size:28px; color:#0f172a;">Provisioning & Onboarding</h2>
            <p style="color: #64748b; margin-top: 5px; font-size: 15px;">Monitor hardware setup and induction progress for new hires.</p>
        </div>
        <a href="{{ route('admin.onboarding.add') }}" style="background: #2563eb; color: #fff; padding: 12px 24px; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 14px;">
          <i class="fa-solid fa-laptop-medical"></i> Setup New Hire
        </a>
    </div>

    @if(session('success'))
        <div style="background: #dcfce7; color: #166534; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #bbf7d0; font-weight: 500;">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif

    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-icon" style="background: #f1f5f9; color: #475569;"><i class="fa-solid fa-users"></i></div>
            <div class="metric-info"><h4>Total Processes</h4><p>{{ $stats['total'] }}</p></div>
        </div>
        <div class="metric-card">
            <div class="metric-icon" style="background: #eff6ff; color: #3b82f6;"><i class="fa-solid fa-gears"></i></div>
            <div class="metric-info"><h4>In Progress</h4><p>{{ $stats['in_progress'] }}</p></div>
        </div>
        <div class="metric-card">
            <div class="metric-icon" style="background: #fffbeb; color: #f59e0b;"><i class="fa-solid fa-clock"></i></div>
            <div class="metric-info"><h4>Pending Setup</h4><p>{{ $stats['pending'] }}</p></div>
        </div>
        <div class="metric-card">
            <div class="metric-icon" style="background: #f0fdf4; color: #22c55e;"><i class="fa-solid fa-shield-check"></i></div>
            <div class="metric-info"><h4>Fully Provisioned</h4><p>{{ $stats['completed'] }}</p></div>
        </div>
    </div>

    <div style="background: #fff; padding: 15px 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 20px; display: flex; gap: 15px; align-items: center;">
        <i class="fa-solid fa-filter" style="color: #94a3b8;"></i>
        <input type="text" id="searchInput" placeholder="Search employee name..." style="padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; width: 250px; outline: none;">
        <select id="statusFilter" style="padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; outline: none;">
            <option value="">All Statuses</option>
            <option value="In Progress">In Progress</option>
            <option value="Completed">Completed</option>
            <option value="Pending">Pending</option>
        </select>
    </div>

    <div class="section-container">
      <table class="hr-table">
        <thead>
        <tr>
          <th>Employee Details</th>
          <th>Timeline</th>
          <th style="width: 200px;">Task Progress</th>
          <th>Status</th>
          <th style="text-align: right;">Action</th>
        </tr>
        </thead>
        <tbody id="tableBody">
        @forelse($onboardings as $onboarding)
        <tr class="data-row">
          <td>
              <strong style="color: #0f172a; font-size: 15px;">{{ $onboarding->employee->user->name ?? 'N/A' }}</strong><br>
              <span style="font-size: 12px; color: #64748b;"><i class="fa-solid fa-building" style="margin-right:4px;"></i> {{ $onboarding->employee->department->department_name ?? 'Unassigned Dept' }}</span>
          </td>
          <td>
              <span style="font-size: 13px; color: #334155; font-weight: 500;">{{ \Carbon\Carbon::parse($onboarding->start_date)->format('d M Y') }}</span><br>
              <span style="font-size: 11px; color: #dc2626;">Due: {{ \Carbon\Carbon::parse($onboarding->end_date)->format('d M Y') }}</span>
          </td>
          <td>
            <div class="progress-wrapper">
              <div class="progress-bar"><div class="progress-fill" style="width:{{ $onboarding->progress }}%; background: {{ $onboarding->progress == 100 ? '#16a34a' : '#2563eb' }};"></div></div>
              <span class="progress-text">{{ $onboarding->progress }}%</span>
            </div>
          </td>
          <td>
              <span class="status-badge status-{{ $onboarding->status }}">
                  @if($onboarding->status == 'completed') <i class="fa-solid fa-check"></i> Provisioned
                  @elseif($onboarding->status == 'in_progress') <i class="fa-solid fa-spinner"></i> In Progress
                  @else <i class="fa-regular fa-clock"></i> Pending @endif
              </span>
          </td>
          <td style="text-align: right;">
            <a href="{{ route('admin.onboarding.checklist.show', $onboarding->onboarding_id) }}" class="btn-view">
                Task List <i class="fa-solid fa-arrow-right"></i>
            </a>
          </td>
        </tr>
        @empty
        <tr id="emptyRow">
            <td colspan="5" style="text-align:center; padding: 40px; color: #94a3b8;">
                <i class="fa-solid fa-clipboard-list" style="font-size: 24px; margin-bottom: 10px; color: #cbd5e1;"></i><br>
                No active provisioning processes found.
            </td>
        </tr>
        @endforelse
        </tbody>
      </table>
    </div>

    <footer style="text-align: center; margin-top: 40px; color: #94a3b8; font-size: 13px;">© 2026 Web-Based HRMS. All Rights Reserved.</footer>
  </main>
</div>

<script>
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const rows = document.querySelectorAll('.data-row');

    function filterTable() {
        const search = searchInput.value.toLowerCase();
        const status = statusFilter.value.toLowerCase();

        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            const rowStatus = row.querySelector('.status-badge').innerText.toLowerCase().trim();
            const matchSearch = text.includes(search);
            const matchStatus = status === "" || rowStatus.includes(status);
            row.style.display = matchSearch && matchStatus ? '' : 'none';
        });
    }

    searchInput.addEventListener('input', filterTable);
    statusFilter.addEventListener('change', filterTable);
</script>
</body>
</html>