<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OT Requests - HRMS</title>
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
    .btn-sm { padding:6px 10px; font-size:12px; border-radius:8px; border:none; cursor:pointer; text-decoration:none; display:inline-block; margin:0 2px; }
    .btn-approve { background:#22c55e; color:#fff; }
    .btn-reject { background:#ef4444; color:#fff; }
    .badge { font-size:12px; padding:4px 8px; border-radius:999px; font-weight:600; }
    .badge-pending { background:#fef9c3; color:#854d0e; }
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
      <div class="breadcrumb">Supervisor · OT Requests</div>
      <h2 style="margin:0 0 4px;">OT Requests</h2>
      <p style="margin:0; color:#64748b;">Approve or reject overtime requests from your team. After you approve, HR will do final approval.</p>

      @if(session('success'))
        <div style="padding:10px; background:#dcfce7; color:#166534; border-radius:10px; margin-bottom:12px;">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div style="padding:10px; background:#fee2e2; color:#991b1b; border-radius:10px; margin-bottom:12px;">{{ session('error') }}</div>
      @endif

      <div class="card">
        <div style="display:flex; gap:12px; margin-bottom:12px; flex-wrap:wrap; align-items:center;">
          <a href="{{ route('employee.overtime_requests.index', ['tab' => 'pending'] + request()->only('q')) }}" class="btn-sm {{ ($tab ?? 'pending') === 'pending' ? 'btn-approve' : '' }}" style="text-decoration:none; {{ ($tab ?? 'pending') !== 'pending' ? 'background:#e5e7eb; color:#374151;' : '' }}">Pending</a>
          <a href="{{ route('employee.overtime_requests.index', ['tab' => 'reviewed'] + request()->only('q')) }}" class="btn-sm {{ ($tab ?? '') === 'reviewed' ? 'btn-approve' : '' }}" style="text-decoration:none; {{ ($tab ?? '') !== 'reviewed' ? 'background:#e5e7eb; color:#374151;' : '' }}">Reviewed</a>
        </div>
        <form method="GET" action="{{ route('employee.overtime_requests.index') }}" class="toolbar">
          <input type="hidden" name="tab" value="{{ $tab ?? 'pending' }}">
          <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name or code">
          <button type="submit" class="btn btn-primary btn-sm">Search</button>
          @if(($tab ?? 'pending') === 'pending' && $pendingCount > 0)
            <span class="badge badge-pending">{{ $pendingCount }} pending</span>
          @endif
        </form>

        <table>
          <thead>
            <tr>
              <th>Employee</th>
              <th>Date</th>
              <th>Hours</th>
              <th>Reason</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($records as $r)
              <tr>
                <td>{{ $r->employee->user->name ?? '—' }}<br><small>{{ $r->employee->employee_code ?? '' }}</small></td>
                <td>{{ $r->date?->format('Y-m-d') }}</td>
                <td>{{ number_format($r->hours, 2) }}</td>
                <td>{{ $r->reason ?? '—' }}</td>
                <td>
                  @if($r->final_status === \App\Models\OvertimeRecord::FINAL_PENDING_SUPERVISOR)
                    <span class="badge badge-pending">Pending</span>
                  @elseif($r->final_status === \App\Models\OvertimeRecord::FINAL_PENDING_ADMIN)
                    <span class="badge" style="background:#dbeafe; color:#1e40af;">Pending admin</span>
                    @if($r->flagged_for_admin)
                      <br><small style="color:#b45309;">Flagged</small>
                    @endif
                  @else
                    <span class="badge" style="background:#fee2e2; color:#991b1b;">Rejected</span>
                  @endif
                </td>
                <td>
                  @if($r->final_status === \App\Models\OvertimeRecord::FINAL_PENDING_SUPERVISOR)
                    <form method="POST" action="{{ route('employee.overtime_requests.approve', $r) }}" style="display:inline;">
                      @csrf
                      <button type="submit" class="btn-sm btn-approve">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('employee.overtime_requests.reject', $r) }}" style="display:inline;" onsubmit="return confirm('Reject this OT request?');">
                      @csrf
                      <button type="submit" class="btn-sm btn-reject">Reject</button>
                    </form>
                  @elseif($r->final_status === \App\Models\OvertimeRecord::FINAL_PENDING_ADMIN)
                    <button type="button" class="btn-sm btn-issue" style="background:#f59e0b; color:#fff;" data-ot-id="{{ $r->ot_id }}" data-reason="{{ e($r->admin_review_remark ?? '') }}">Mark Issue</button>
                  @else
                    —
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="6" style="text-align:center; color:#94a3b8;">
                @if(($tab ?? 'pending') === 'reviewed')
                  No reviewed requests.
                @else
                  No OT requests pending your approval.
                @endif
              </td></tr>
            @endforelse
          </tbody>
        </table>
        @if($records->hasPages())
          <div style="margin-top:12px;">{{ $records->links() }}</div>
        @endif
      </div>

      {{-- Mark Issue modal --}}
      <div id="markIssueModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:12px; padding:20px; max-width:420px; width:100%;">
          <h3 style="margin:0 0 12px;">Mark issue for admin</h3>
          <p style="margin:0 0 12px; color:#64748b; font-size:14px;">Admin will review this request carefully.</p>
          <form id="markIssueForm" method="POST" action="">
            @csrf
            <label style="display:block; margin-bottom:6px; font-weight:600;">Reason (required)</label>
            <textarea name="issue_reason" id="markIssueReason" required rows="3" style="width:100%; padding:8px; border:1px solid #e5e7eb; border-radius:8px;" maxlength="500"></textarea>
            <div style="margin-top:12px; display:flex; gap:8px; justify-content:flex-end;">
              <button type="button" class="btn-sm" style="background:#e5e7eb; color:#374151;" onclick="document.getElementById('markIssueModal').style.display='none';">Cancel</button>
              <button type="submit" class="btn-sm btn-approve">Save</button>
            </div>
          </form>
        </div>
      </div>
      <script>
        document.querySelectorAll('.btn-issue').forEach(function(btn) {
          btn.addEventListener('click', function() {
            var id = this.dataset.otId;
            var reason = (this.dataset.reason || '').replace(/&quot;/g, '"');
            document.getElementById('markIssueReason').value = reason;
            document.getElementById('markIssueForm').action = "{{ url('employee/overtime-requests') }}/" + id + "/mark-issue";
            document.getElementById('markIssueModal').style.display = 'flex';
          });
        });
      </script>
    </main>
  </div>
</body>
</html>
