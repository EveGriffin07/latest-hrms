<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OT Claims Inbox - HRMS</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    main { padding:24px; }
    .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:16px; }
    .grid-cards { display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:12px; margin-bottom:16px; }
    .mini-card { background:#f8fafc; border-radius:10px; padding:12px; text-align:center; }
    .mini-card .num { font-size:24px; font-weight:700; color:#0f172a; }
    .mini-card .label { font-size:12px; color:#64748b; }
    .toolbar { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:12px; }
    .toolbar input, .toolbar select { padding:8px 12px; border:1px solid #e5e7eb; border-radius:8px; }
    table { width:100%; border-collapse:collapse; }
    th, td { padding:10px 12px; border-bottom:1px solid #e5e7eb; text-align:left; }
    thead th { background:#0f172a; color:#e2e8f0; }
    .status { padding:4px 8px; border-radius:999px; font-size:12px; font-weight:600; }
    .btn-sm { padding:6px 10px; font-size:12px; border-radius:8px; border:none; cursor:pointer; margin:0 2px; }
    .btn-approve { background:#22c55e; color:#fff; }
    .btn-reject { background:#ef4444; color:#fff; }
    .btn-return { background:#f59e0b; color:#fff; }
    tr.row-no-proof { background:#fef2f2 !important; }
    .proof-badge { font-size:11px; padding:2px 6px; border-radius:4px; font-weight:600; }
    .proof-badge.has { background:#dcfce7; color:#166534; }
    .proof-badge.none { background:#fef2f2; color:#991b1b; }
  </style>
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info"><i class="fa-regular fa-bell"></i> &nbsp; <a href="{{ route('employee.profile') }}" style="color:inherit; text-decoration:none;">{{ Auth::user()->name ?? 'User' }}</a></div>
  </header>
  <div class="container">
    @include('employee.layout.sidebar')
    <main>
      <div class="breadcrumb">Employee · OT Claims Inbox</div>
      <h2 style="margin:0 0 4px;">OT Claims Inbox</h2>
      <p style="margin:0; color:#64748b;">Approve, reject, or return claims from your team.</p>

      @if(session('success'))
        <div style="padding:10px; background:#dcfce7; color:#166534; border-radius:10px; margin-bottom:12px;">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div style="padding:10px; background:#fee2e2; color:#991b1b; border-radius:10px; margin-bottom:12px;">{{ session('error') }}</div>
      @endif
      @if(session('message'))
        <div style="padding:10px; background:#e0e7ff; color:#3730a3; border-radius:10px; margin-bottom:12px;">{{ session('message') }}</div>
      @endif

      <div class="grid-cards">
        <div class="mini-card"><div class="num">{{ $total }}</div><div class="label">Total</div></div>
        <div class="mini-card"><div class="num">{{ $pending }}</div><div class="label">Pending</div></div>
        <div class="mini-card"><div class="num">{{ $approved }}</div><div class="label">Approved</div></div>
        <div class="mini-card"><div class="num">{{ $rejected }}</div><div class="label">Rejected</div></div>
      </div>

      {{-- OT Requests (OvertimeRecord from subordinates) --}}
      <div class="card" style="margin-bottom:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:12px;">
          <h3 style="margin:0; font-size:1.1rem;">
            <i class="fa-solid fa-inbox"></i> OT Requests
            @if(($otRequestsPendingCount ?? 0) > 0)
              <span class="status" style="background:#fef3c7; color:#92400e; margin-left:8px;">{{ $otRequestsPendingCount }} pending</span>
            @endif
          </h3>
          <a href="{{ route('employee.overtime_requests.index') }}" class="btn btn-primary" style="text-decoration:none;">
            <i class="fa-solid fa-list"></i> View all OT Requests
          </a>
        </div>
        <p style="margin:0 0 12px; color:#64748b; font-size:14px;">Overtime requests from your team waiting for your approval. Approve or reject, then send the summary to admin.</p>
        @if(($otRequests ?? collect())->isEmpty())
          <p style="margin:0; color:#94a3b8;">No OT requests pending your approval.</p>
        @else
          <table>
            <thead>
              <tr>
                <th>Employee</th>
                <th>Department</th>
                <th>Date</th>
                <th>Hours</th>
                <th>Reason</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @foreach($otRequests as $r)
                <tr>
                  <td>{{ $r->employee->user->name ?? '—' }}<br><small>{{ $r->employee->employee_code ?? '' }}</small></td>
                  <td>{{ $r->employee->department->department_name ?? '—' }}</td>
                  <td>{{ $r->date?->format('Y-m-d') }}</td>
                  <td>{{ number_format($r->hours, 2) }}</td>
                  <td>{{ Str::limit($r->reason ?? '—', 25) }}</td>
                  <td>
                    <form method="POST" action="{{ route('employee.overtime_requests.approve', $r) }}" style="display:inline;">
                      @csrf
                      <button type="submit" class="btn-sm btn-approve">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('employee.overtime_requests.reject', $r) }}" style="display:inline;" onsubmit="return confirm('Reject this OT request?');">
                      @csrf
                      <button type="submit" class="btn-sm btn-reject">Reject</button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
          @if($otRequestsPendingCount > $otRequests->count())
            <p style="margin:12px 0 0; font-size:13px; color:#64748b;">Showing latest {{ $otRequests->count() }} of {{ $otRequestsPendingCount }}. <a href="{{ route('employee.overtime_requests.index') }}">View all</a></p>
          @endif
        @endif
      </div>

      <div class="card">
        <form method="GET" action="{{ route('employee.overtime_inbox.index') }}" class="toolbar">
          <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name/ID">
          <select name="status">
            <option value="{{ \App\Models\OvertimeClaim::STATUS_SUBMITTED_TO_SUPERVISOR }}" {{ request('status', \App\Models\OvertimeClaim::STATUS_SUBMITTED_TO_SUPERVISOR) === \App\Models\OvertimeClaim::STATUS_SUBMITTED_TO_SUPERVISOR ? 'selected' : '' }}>Pending me</option>
            <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>All</option>
          </select>
          <select name="department">
            <option value="">All Depts</option>
            @foreach($departments as $d)
              <option value="{{ $d->department_id }}" {{ request('department') == $d->department_id ? 'selected' : '' }}>{{ $d->department_name }}</option>
            @endforeach
          </select>
          <input type="date" name="start" value="{{ request('start') }}" placeholder="From">
          <input type="date" name="end" value="{{ request('end') }}" placeholder="To">
          <button type="submit" class="btn btn-primary">Filter</button>
        </form>

        <table>
          <thead>
            <tr>
              <th>Employee</th>
              <th>Dept</th>
              <th>Date</th>
              <th>Hours</th>
              <th>Location</th>
              <th>Proof</th>
              <th>Status</th>
              <th>Submitted</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($claims as $c)
              <tr class="{{ $c->hasNoProofFlag() ? 'row-no-proof' : '' }}">
                <td>{{ $c->employee->user->name ?? '—' }}<br><small>{{ $c->employee->employee_code ?? '' }}</small></td>
                <td>{{ $c->employee->department->department_name ?? '—' }}</td>
                <td>{{ $c->date?->format('Y-m-d') }}</td>
                <td>{{ number_format($c->hours, 2) }}</td>
                <td>{{ $c->location_type ?? 'INSIDE' }}</td>
                <td>
                  @if(($c->location_type ?? 'INSIDE') === \App\Models\OvertimeClaim::LOCATION_OUTSIDE)
                    @if($c->proof_image_path)
                      <span class="proof-badge has">Has proof</span>
                    @else
                      <span class="proof-badge none">NO PROOF</span>
                    @endif
                  @else
                    <span class="proof-badge">N/A</span>
                  @endif
                </td>
                <td><span class="status">{{ $c->status }}</span></td>
                <td>{{ $c->submitted_at ? $c->submitted_at->format('M j, H:i') : '—' }}</td>
                <td>
                  @if($c->status === \App\Models\OvertimeClaim::STATUS_SUBMITTED_TO_SUPERVISOR)
                    <form method="POST" action="{{ route('employee.overtime_inbox.approve', $c) }}" style="display:inline;">
                      @csrf
                      <input type="number" step="0.25" min="0" max="24" name="approved_hours" placeholder="Hours" value="{{ $c->hours }}" style="width:60px; padding:4px;" title="Approved hours (edit if unjustified)">
                      <input type="text" name="remark" placeholder="Remark (optional)" style="width:100px; padding:4px; margin-right:4px;">
                      <button type="submit" class="btn-sm btn-approve">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('employee.overtime_inbox.reject', $c) }}" style="display:inline;">
                      @csrf
                      <input type="text" name="remark" placeholder="Reason (required)" required style="width:100px; padding:4px;">
                      <button type="submit" class="btn-sm btn-reject">Reject</button>
                    </form>
                    <form method="POST" action="{{ route('employee.overtime_inbox.return', $c) }}" style="display:inline;">
                      @csrf
                      <input type="text" name="remark" placeholder="What to correct" required style="width:100px; padding:4px;">
                      <button type="submit" class="btn-sm btn-return">Return</button>
                    </form>
                  @else
                    —
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="9">No claims found.</td></tr>
            @endforelse
          </tbody>
        </table>

        <div style="margin-top:12px;">
          {{ $claims->withQueryString()->links() }}
        </div>
      </div>
    </main>
  </div>
</body>
</html>
