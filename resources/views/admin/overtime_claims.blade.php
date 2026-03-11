<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OT Claims - Admin - HRMS</title>
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
    .btn-hold { background:#f59e0b; color:#fff; }
    tr.row-no-proof { background:#fef2f2 !important; }
    .proof-badge { font-size:11px; padding:2px 6px; border-radius:4px; font-weight:600; }
    .proof-badge.has { background:#dcfce7; color:#166534; }
    .proof-badge.none { background:#fef2f2; color:#991b1b; }
  </style>
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info"><a href="{{ route('admin.profile') }}" style="text-decoration:none;color:inherit;"><i class="fa-regular fa-bell"></i> &nbsp; HR Admin</a></div>
  </header>
  <div class="container">
    @include('admin.layout.sidebar')
    <main>
      <div class="breadcrumb">Payroll · OT Claims (Final Approval)</div>
      <h2 style="margin:0 0 4px;">OT Claims</h2>
      <p style="margin:0; color:#64748b;">Supervisor-approved claims. Approve (payroll), reject, or put on hold.</p>

      @if(session('success'))
        <div class="notice success" style="padding:10px; background:#dcfce7; color:#166534; border-radius:10px; margin-bottom:12px;">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div class="notice error" style="padding:10px; background:#fee2e2; color:#991b1b; border-radius:10px; margin-bottom:12px;">{{ session('error') }}</div>
      @endif

      <div class="grid-cards">
        <div class="mini-card"><div class="num">{{ $pending }}</div><div class="label">Pending</div></div>
        <div class="mini-card"><div class="num">{{ $approved }}</div><div class="label">Approved</div></div>
        <div class="mini-card"><div class="num">{{ $rejected }}</div><div class="label">Rejected</div></div>
        <div class="mini-card"><div class="num">{{ $onHold }}</div><div class="label">On Hold</div></div>
      </div>

      <div class="card">
        <form method="GET" action="{{ route('admin.payroll.overtime_claims') }}" class="toolbar">
          <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name/ID">
          <select name="status">
            <option value="{{ \App\Models\OvertimeClaim::STATUS_ADMIN_PENDING }}" {{ request('status', \App\Models\OvertimeClaim::STATUS_ADMIN_PENDING) === \App\Models\OvertimeClaim::STATUS_ADMIN_PENDING ? 'selected' : '' }}>Pending</option>
            <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>All</option>
          </select>
          <select name="department">
            <option value="">All Depts</option>
            @foreach($departments as $d)
              <option value="{{ $d->department_id }}" {{ request('department') == $d->department_id ? 'selected' : '' }}>{{ $d->department_name }}</option>
            @endforeach
          </select>
          <input type="date" name="start" value="{{ request('start') }}">
          <input type="date" name="end" value="{{ request('end') }}">
          <button type="submit" class="btn btn-primary">Filter</button>
        </form>

        <table>
          <thead>
            <tr>
              <th>Employee</th>
              <th>Dept</th>
              <th>Date</th>
              <th>Hours</th>
              <th>Approved Hrs</th>
              <th>Location</th>
              <th>Proof</th>
              <th>Payout</th>
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
                <td>{{ number_format($c->getEffectiveApprovedHours(), 2) }}</td>
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
                <td>{{ $c->status === \App\Models\OvertimeClaim::STATUS_ADMIN_PENDING ? number_format(\App\Http\Controllers\AdminOvertimeClaimController::computePayout($c), 2) : '—' }}</td>
                <td><span class="status">{{ $c->status }}</span></td>
                <td>{{ $c->submitted_at ? $c->submitted_at->format('M j, H:i') : '—' }}</td>
                <td>
                  @if($c->status === \App\Models\OvertimeClaim::STATUS_ADMIN_PENDING)
                    <form method="POST" action="{{ route('admin.payroll.overtime_claims.approve', $c) }}" style="display:inline;">
                      @csrf
                      <input type="text" name="remark" placeholder="Remark (optional)" style="width:90px; padding:4px;">
                      <button type="submit" class="btn-sm btn-approve">Post to Payroll</button>
                    </form>
                    <form method="POST" action="{{ route('admin.payroll.overtime_claims.reject', $c) }}" style="display:inline;">
                      @csrf
                      <input type="text" name="remark" placeholder="Reason (required)" required style="width:90px; padding:4px;">
                      <button type="submit" class="btn-sm btn-reject">Reject</button>
                    </form>
                    <form method="POST" action="{{ route('admin.payroll.overtime_claims.hold', $c) }}" style="display:inline;">
                      @csrf
                      <input type="text" name="remark" placeholder="What info needed" required style="width:90px; padding:4px;">
                      <button type="submit" class="btn-sm btn-hold">On Hold</button>
                    </form>
                  @else
                    —
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="11">No claims found.</td></tr>
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
