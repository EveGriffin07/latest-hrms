<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OT Claims - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    main { padding:2rem; }
    .card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:16px; box-shadow:0 8px 18px rgba(15,23,42,0.08); margin-bottom:16px; }
    .breadcrumb { color:#94a3b8; margin-bottom:8px; }
    .subtitle { color:#64748b; margin-bottom:1rem; }
    .table-wrap { overflow-x:auto; }
    table { width:100%; border-collapse:collapse; margin-top:10px; }
    th, td { padding:10px 12px; border-bottom:1px solid #e5e7eb; text-align:left; }
    thead { background:#0f172a; color:#38bdf8; }
    .status { padding:4px 10px; border-radius:999px; font-size:0.85rem; font-weight:700; display:inline-block; }
    .notice { padding:10px 14px; border-radius:10px; margin-bottom:12px; }
    .notice.success { background:#dcfce7; color:#166534; }
    .notice.error { background:#fee2e2; color:#991b1b; }
  </style>
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info"><span><i class="fa-regular fa-bell"></i> &nbsp; <a href="{{ route('employee.profile') }}" style="color:inherit; text-decoration:none;">{{ Auth::user()->name ?? 'Employee' }}</a></span></div>
  </header>
  <div class="container">
    @include('employee.layout.sidebar')
    <main>
      <div class="breadcrumb">Attendance · OT Claims</div>
      <h2 style="margin:0 0 .3rem 0; color:#0ea5e9;">OT Claims</h2>
      <p class="subtitle">Submit and track overtime claims (Supervisor → Admin approval).</p>

      @if(session('success'))
        <div class="notice success">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div class="notice error">{{ session('error') }}</div>
      @endif

      <div class="card" style="margin-bottom:16px;">
        <a href="{{ route('employee.ot_claims.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Claim OT</a>
      </div>

      <div class="card">
        <h3 style="margin:0 0 10px 0;">My OT Claims</h3>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Date</th>
                <th>Hours</th>
                <th>Status</th>
                <th>Submitted</th>
                <th>Supervisor remark</th>
                <th>Admin remark</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($claims as $c)
                <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td>{{ $c->date?->format('Y-m-d') }}</td>
                  <td>{{ number_format($c->hours, 2) }}</td>
                  <td><span class="status">{{ $c->status }}</span></td>
                  <td>{{ $c->submitted_at ? $c->submitted_at->format('M j, Y H:i') : '—' }}</td>
                  <td>{{ $c->supervisor_remark ? Str::limit($c->supervisor_remark, 30) : '—' }}</td>
                  <td>{{ $c->admin_remark ? Str::limit($c->admin_remark, 30) : '—' }}</td>
                  <td>
                    @if($c->isEditableByEmployee())
                      <a href="{{ route('employee.ot_claims.edit', $c) }}" class="btn btn-secondary btn-small">Edit</a>
                    @elseif($c->isCancellableByEmployee())
                      <form method="POST" action="{{ route('employee.ot_claims.cancel', $c) }}" style="display:inline;" onsubmit="return confirm('Cancel this claim?');">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-small">Cancel</button>
                      </form>
                    @else
                      —
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="8" style="text-align:center; color:#94a3b8;">No OT claims yet. <a href="{{ route('employee.ot_claims.create') }}">Claim OT</a></td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
