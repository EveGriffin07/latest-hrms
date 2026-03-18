<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Send Approval Summary to Admin - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    main { padding:24px; }
    .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:16px; }
    .toolbar { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:12px; align-items:center; }
    .toolbar input { padding:8px 12px; border:1px solid #e5e7eb; border-radius:8px; }
    table { width:100%; border-collapse:collapse; }
    th, td { padding:10px 12px; border-bottom:1px solid #e5e7eb; text-align:left; }
    thead th { background:#0f172a; color:#e2e8f0; }
    .btn { padding:8px 14px; font-size:14px; border-radius:8px; border:none; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; font-weight:600; }
    .btn-primary { background:#2563eb; color:#fff; }
    .btn-primary:hover { background:#1d4ed8; }
    .btn-secondary { background:#64748b; color:#fff; }
    .form-check { display:flex; align-items:center; gap:8px; }
    .form-check input { width:18px; height:18px; }
    table input[type="checkbox"]#selectAll,
    table input[type="checkbox"].row-select,
    table input[type="checkbox"].flag-checkbox { transform:scale(1.6); cursor:pointer; accent-color:#6366f1; }
    .remark-input { width:100%; max-width:220px; padding:6px 10px; border:1px solid #e5e7eb; border-radius:6px; font-size:13px; }
    .help { color:#64748b; font-size:13px; margin-top:8px; }
    .badge { font-size:12px; padding:4px 8px; border-radius:999px; font-weight:600; }
    .badge-info { background:#e0f2fe; color:#0369a1; }
  </style>
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info"><i class="fa-regular fa-bell"></i> &nbsp;
      <a href="{{ route('supervisor.profile') }}" style="color:inherit; text-decoration:none;">
        {{ Auth::user()->name ?? 'Supervisor' }}
      </a>
    </div>
  </header>
  <div class="container">
    @include('supervisor.layout.sidebar')
    <main>
      <div class="breadcrumb">Supervisor · OT Requests · Send Summary to Admin</div>
      <h2 style="margin:0 0 4px;">Send Approval Summary to Admin</h2>
      <p style="margin:0; color:#64748b;">Review your approved OT requests, optionally flag items that need admin attention, then send the full summary to admin.</p>

      @if(session('success'))
        <div style="padding:10px; background:#dcfce7; color:#166534; border-radius:10px; margin-bottom:12px;">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div style="padding:10px; background:#fee2e2; color:#991b1b; border-radius:10px; margin-bottom:12px;">{{ session('error') }}</div>
      @endif
      @if(session('info'))
        <div style="padding:10px; background:#e0f2fe; color:#0369a1; border-radius:10px; margin-bottom:12px;">{{ session('info') }}</div>
      @endif

      <div class="card">
        <form method="GET" action="{{ route('employee.overtime_requests.approval_summary') }}" class="toolbar">
          <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name or code">
          <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-search"></i> Search</button>
          @if($totalToSend > 0)
            <span class="badge badge-info">{{ $totalToSend }} ready to send</span>
          @endif
        </form>

        <form method="POST" action="{{ route('employee.overtime_requests.send_summary') }}" id="sendSummaryForm">
          @csrf
          <table>
            <thead>
              <tr>
                <th style="width:36px;"><input type="checkbox" id="selectAll" title="Select all"></th>
                <th>Employee</th>
                <th>Department</th>
                <th>Date</th>
                <th>Hours</th>
                <th>Reason</th>
                <th>Flag for admin</th>
                <th>Remark (if flagged)</th>
              </tr>
            </thead>
            <tbody>
              @forelse($records as $r)
                <tr>
                  <td>
                    <input type="checkbox" name="ids[]" value="{{ $r->ot_id }}" class="row-select">
                  </td>
                  <td>{{ $r->employee->user->name ?? '—' }}<br><small>{{ $r->employee->employee_code ?? '' }}</small></td>
                  <td>{{ $r->employee->department->department_name ?? '—' }}</td>
                  <td>{{ $r->date?->format('Y-m-d') }}</td>
                  <td>{{ number_format($r->hours, 2) }}</td>
                  <td>{{ Str::limit($r->reason ?? '—', 30) }}</td>
                  <td>
                    <label class="form-check">
                      <input type="hidden" name="flags[{{ $r->ot_id }}]" value="0">
                      <input type="checkbox" name="flags[{{ $r->ot_id }}]" value="1" class="flag-checkbox" data-ot-id="{{ $r->ot_id }}">
                      <span>Needs review</span>
                    </label>
                  </td>
                  <td>
                    <input type="text" class="remark-input" name="remarks[{{ $r->ot_id }}]" placeholder="e.g. Missing proof" maxlength="500">
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" style="text-align:center; color:#94a3b8; padding:24px;">
                    No approved OT requests waiting to be sent. Approve requests from <a href="{{ route('employee.overtime_requests.index') }}">OT Requests</a> first, then return here to send the summary to admin.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
          @if($records->isNotEmpty())
            <p class="help">Check &quot;Flag for admin&quot; for any request that has an issue (e.g. missing proof, over limit). Admin will see these in &quot;Needs attention&quot; and can approve or reject.</p>
            <div style="margin-top:16px;">
              <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Send Summary to Admin</button>
              <a href="{{ route('employee.overtime_requests.index') }}" class="btn btn-secondary" style="margin-left:8px;">Back to OT Requests</a>
            </div>
          @endif
        </form>
        @if($records->hasPages())
          <div style="margin-top:12px;">{{ $records->links() }}</div>
        @endif
      </div>
    </main>
  </div>
  <script>
    document.getElementById('selectAll')?.addEventListener('change', function() {
      document.querySelectorAll('.row-select').forEach(cb => cb.checked = this.checked);
    });
    document.getElementById('sendSummaryForm')?.addEventListener('submit', function(e) {
      const checked = document.querySelectorAll('.row-select:checked');
      if (checked.length === 0) {
        e.preventDefault();
        alert('Please select at least one request to send.');
      }
    });
  </script>
</body>
</html>
