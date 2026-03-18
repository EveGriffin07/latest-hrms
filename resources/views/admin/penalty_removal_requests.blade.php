<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Penalty Removal Requests - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    main { padding: 2rem; }
    .breadcrumb { font-size:.85rem; color:#94a3b8; margin-bottom:1rem; }
    h2 { color:#38bdf8; margin:0 0 .25rem 0; }
    .subtitle { color:#94a3b8; margin-bottom:1.5rem; }

    .summary, .filters, .table-wrap { background:#fff; color:#111827; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,.08); }
    .summary { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px; padding:16px; margin-bottom:16px; }
    .summary .card { border:1px solid #edf2f7; border-radius:10px; text-align:center; padding:16px; text-decoration:none; color:inherit; }
    .summary .card:hover { border-color:#38bdf8; box-shadow:0 6px 18px rgba(56,189,248,.15); }
    .summary .card.active { background:#e0f2fe; border-color:#38bdf8; }
    .summary .card h3 { font-size:.95rem; color:#6b7280; margin:0 0 6px; }
    .summary .card p { font-size:1.4rem; font-weight:600; color:#111827; margin:0; }

    .table-wrap { overflow:hidden; border:1px solid #e5e7eb; }
    table { width:100%; border-collapse:collapse; }
    thead { background:#0f172a; color:#38bdf8; }
    th, td { padding:12px 14px; border-bottom:1px solid #e5e7eb; text-align:left; vertical-align:top; }
    tbody tr:hover { background:#f8fafc; }

    .muted { color:#64748b; font-size:12px; }
    .status { padding:4px 8px; border-radius:999px; font-size:.8rem; white-space:nowrap; display:inline-block; }
    .pending  { background:#fef3c7; color:#92400e; }
    .approved { background:#dcfce7; color:#166534; }
    .rejected { background:#fee2e2; color:#991b1b; }

    .btn-xs { padding:6px 10px; font-size:.85rem; border-radius:8px; border:1px solid #d1d5db; background:#fff; cursor:pointer; }
    .btn-approve { background:#22c55e; border-color:#22c55e; color:#fff; }
    .btn-reject  { background:#ef4444; border-color:#ef4444; color:#fff; }
    .btn-outline { background:#fff; color:#111827; }
    .btn-muted { background:#f8fafc; color:#475569; border-color:#e5e7eb; }
    .btn-xs:disabled { opacity:.6; cursor:not-allowed; }

    .filters { padding:16px; margin-bottom:16px; }
    .filters .row { display:flex; gap:12px; flex-wrap:wrap; }
    .filters .split { flex:1 1 220px; }
    .filters label { display:block; font-size:.85rem; color:#6b7280; margin-bottom:6px; }
    .filters input, .filters select, .filters button {
      border:1px solid #d1d5db; background:#fff; color:#111827;
      border-radius:8px; padding:8px 10px; font-size:.92rem;
    }
    .filters .btn { cursor:pointer; }
    .filters .btn-primary { background:#38bdf8; border-color:#38bdf8; color:#0f172a; }
    .filters .btn-ghost { background:#fff; color:#111827; }

    .backdrop { position:fixed; inset:0; background:rgba(15,23,42,.55); display:none; align-items:center; justify-content:center; z-index:50; }
    .backdrop.open { display:flex; }
    .dialog { width:min(520px, 92vw); background:#fff; color:#111827; border-radius:14px; box-shadow:0 10px 30px rgba(0,0,0,.35); overflow:hidden; }
    .dialog header { padding:12px 16px; background:#0f172a; color:#e2e8f0; font-weight:600; }
    .dialog .body { padding:16px; }
    .dialog label { display:block; font-size:12px; color:#6b7280; font-weight:600; margin:0 0 6px; }
    .dialog textarea { width:100%; min-height:110px; padding:10px 12px; border:1px solid #d1d5db; border-radius:10px; font-size:14px; resize:vertical; }
    .dialog .actions { display:flex; gap:8px; justify-content:flex-end; padding:12px 16px; border-top:1px solid #e5e7eb; }
  </style>
</head>

<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
      <a href="{{ route('admin.profile') }}" style="text-decoration: none; color: inherit;">
        <i class="fa-regular fa-bell"></i> &nbsp; {{ Auth::user()->name ?? 'HR Admin' }}
      </a>
    </div>
  </header>

  <div class="container">
    @include('admin.layout.sidebar')
    <main>
      <div class="breadcrumb">Home > Attendance > Penalty Removal Requests</div>
      <h2>Penalty Removal Requests</h2>
      <p class="subtitle">Final decision inbox for supervisor-forwarded penalty removal requests.</p>

      @if(session('success'))
        <div class="notice success">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div class="notice error">{{ session('error') }}</div>
      @endif
      @if($errors->any())
        <div class="notice error">{{ $errors->first() }}</div>
      @endif

      <section class="summary">
        <a class="card {{ ($status ?? '') === \App\Models\PenaltyRemovalRequest::STATUS_SUBMITTED_ADMIN ? 'active' : '' }}"
           href="{{ route('admin.attendance.penalty_removal_requests.index', ['status' => \App\Models\PenaltyRemovalRequest::STATUS_SUBMITTED_ADMIN]) }}">
          <h3>Pending</h3>
          <p>{{ $counts['pending'] ?? 0 }}</p>
        </a>
        <a class="card {{ ($status ?? '') === \App\Models\PenaltyRemovalRequest::STATUS_APPROVED_ADMIN ? 'active' : '' }}"
           href="{{ route('admin.attendance.penalty_removal_requests.index', ['status' => \App\Models\PenaltyRemovalRequest::STATUS_APPROVED_ADMIN]) }}">
          <h3>Approved</h3>
          <p>{{ $counts['approved'] ?? 0 }}</p>
        </a>
        <a class="card {{ ($status ?? '') === \App\Models\PenaltyRemovalRequest::STATUS_REJECTED_ADMIN ? 'active' : '' }}"
           href="{{ route('admin.attendance.penalty_removal_requests.index', ['status' => \App\Models\PenaltyRemovalRequest::STATUS_REJECTED_ADMIN]) }}">
          <h3>Rejected</h3>
          <p>{{ $counts['rejected'] ?? 0 }}</p>
        </a>
      </section>

      <section class="filters">
        <form method="GET" action="{{ route('admin.attendance.penalty_removal_requests.index') }}">
          <div class="row">
            <div class="split">
              <label for="q">Search (Name/ID)</label>
              <input id="q" name="q" value="{{ request('q') }}" placeholder="e.g. EMP007 or Sarah Lee">
            </div>
            <div class="split">
              <label for="department">Department</label>
              <select id="department" name="department">
                <option value="">All</option>
                @foreach(($departments ?? []) as $d)
                  <option value="{{ $d->department_id }}" {{ (string) request('department') === (string) $d->department_id ? 'selected' : '' }}>
                    {{ $d->department_name }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="split">
              <label for="reason">Reason</label>
              <input id="reason" name="reason" value="{{ request('reason') }}" placeholder="Search in appeal reason">
            </div>
            <div class="split">
              <label for="status">Status</label>
              <select id="status" name="status">
                <option value="{{ \App\Models\PenaltyRemovalRequest::STATUS_SUBMITTED_ADMIN }}" {{ request('status', \App\Models\PenaltyRemovalRequest::STATUS_SUBMITTED_ADMIN) === \App\Models\PenaltyRemovalRequest::STATUS_SUBMITTED_ADMIN ? 'selected' : '' }}>Pending</option>
                <option value="{{ \App\Models\PenaltyRemovalRequest::STATUS_APPROVED_ADMIN }}" {{ request('status') === \App\Models\PenaltyRemovalRequest::STATUS_APPROVED_ADMIN ? 'selected' : '' }}>Approved</option>
                <option value="{{ \App\Models\PenaltyRemovalRequest::STATUS_REJECTED_ADMIN }}" {{ request('status') === \App\Models\PenaltyRemovalRequest::STATUS_REJECTED_ADMIN ? 'selected' : '' }}>Rejected</option>
              </select>
            </div>
            <div class="split">
              <label for="start">Start Date</label>
              <input id="start" type="date" name="start" value="{{ request('start') }}">
            </div>
            <div class="split">
              <label for="end">End Date</label>
              <input id="end" type="date" name="end" value="{{ request('end') }}">
            </div>
            <div class="split" style="display:flex; align-items:flex-end; gap:8px; flex:1 1 220px;">
              <button type="submit" class="btn btn-primary"><i class="fa-solid fa-rotate"></i> Filter</button>
              <a class="btn btn-ghost" href="{{ route('admin.attendance.penalty_removal_requests.index', ['status' => \App\Models\PenaltyRemovalRequest::STATUS_SUBMITTED_ADMIN]) }}" style="text-decoration:none; display:inline-flex; align-items:center; gap:8px;">
                Clear
              </a>
            </div>
          </div>
        </form>
      </section>

      <section class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Penalty</th>
              <th>Employee</th>
              <th>Department</th>
              <th>Appeal</th>
              <th>Supervisor note</th>
              <th>Attachment</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($requests as $r)
              @php
                $emp = $r->employee;
                $user = $emp?->user;
                $dept = $emp?->department;
                $pen = $r->penalty;
                $dateSource = $pen?->assigned_at ?? $pen?->attendance?->date;
                $pDate = $dateSource ? \Carbon\Carbon::parse($dateSource)->format('Y-m-d') : '—';
                $pid = 'P-' . str_pad((string) $r->penalty_id, 4, '0', STR_PAD_LEFT);
                $pType = $pen?->penalty_type ? ucfirst(str_replace('_',' ', $pen->penalty_type)) : ($pen?->penalty_name ?? 'Penalty');
                $statusClass = match($r->status) {
                  \App\Models\PenaltyRemovalRequest::STATUS_SUBMITTED_ADMIN => 'pending',
                  \App\Models\PenaltyRemovalRequest::STATUS_APPROVED_ADMIN => 'approved',
                  \App\Models\PenaltyRemovalRequest::STATUS_REJECTED_ADMIN => 'rejected',
                  default => 'pending',
                };
                $statusLabel = match($r->status) {
                  \App\Models\PenaltyRemovalRequest::STATUS_SUBMITTED_ADMIN => 'Pending',
                  \App\Models\PenaltyRemovalRequest::STATUS_APPROVED_ADMIN => 'Approved',
                  \App\Models\PenaltyRemovalRequest::STATUS_REJECTED_ADMIN => 'Rejected',
                  default => ucfirst(str_replace('_',' ', (string) $r->status)),
                };
                $canAct = $r->status === \App\Models\PenaltyRemovalRequest::STATUS_SUBMITTED_ADMIN;
              @endphp
              <tr>
                <td>
                  <strong>{{ $pid }}</strong><br>
                  <span class="muted">{{ $pType }} · {{ $pDate }}</span>
                </td>
                <td>
                  <strong>{{ $user?->name ?? 'Unknown' }}</strong><br>
                  <span class="muted">{{ $emp?->employee_code ?? ('EMP-'.$r->employee_id) }}</span>
                </td>
                <td>{{ $dept?->department_name ?? 'N/A' }}</td>
                <td>{{ $r->request_reason ?? '—' }}</td>
                <td>{{ $r->supervisor_note ?? '—' }}</td>
                <td>
                  @if($r->attachment_path)
                    <a href="{{ asset('storage/' . $r->attachment_path) }}" target="_blank" rel="noopener">View</a>
                  @else
                    <span class="muted">—</span>
                  @endif
                </td>
                <td><span class="status {{ $statusClass }}">{{ $statusLabel }}</span></td>
                <td>
                  @if($canAct)
                    <button
                      type="button"
                      class="btn-xs btn-approve js-open-approve"
                      data-action="{{ route('admin.attendance.penalty_removal_requests.approve', $r) }}"
                      data-emp="{{ $user?->name ?? 'Employee' }}"
                      data-pid="{{ $pid }}"
                      data-type="{{ $pType }}"
                      data-date="{{ $pDate }}"
                    >
                      Approve
                    </button>
                    <button type="button" class="btn-xs btn-reject js-open-reject"
                      data-action="{{ route('admin.attendance.penalty_removal_requests.reject', $r) }}"
                      data-emp="{{ $user?->name ?? 'Employee' }}"
                      data-pid="{{ $pid }}"
                      data-type="{{ $pType }}"
                      data-date="{{ $pDate }}"
                    >Reject</button>
                  @else
                    <span class="muted">—</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="8" style="text-align:center; color:#94a3b8; padding:14px;">No penalty removal requests.</td></tr>
            @endforelse
          </tbody>
        </table>
      </section>

      <div style="margin-top:14px;">
        {{ $requests->links() }}
      </div>
    </main>
  </div>

  <div id="reject-backdrop" class="backdrop">
    <div class="dialog">
      <header>Reject request</header>
      <form id="reject-form" method="POST" action="">
        @csrf
        <div class="body">
          <div class="muted" id="reject-context" style="margin-bottom:10px;"></div>
          <label for="admin_note">Reason (required)</label>
          <textarea id="admin_note" name="admin_note" required maxlength="500" placeholder="e.g. Not sufficient justification / policy not met"></textarea>
        </div>
        <div class="actions">
          <button type="button" class="btn-xs btn-outline" id="reject-cancel">Cancel</button>
          <button type="submit" class="btn-xs btn-reject">Reject</button>
        </div>
      </form>
    </div>
  </div>

  <div id="approve-backdrop" class="backdrop">
    <div class="dialog">
      <header>Approve request</header>
      <form id="approve-form" method="POST" action="">
        @csrf
        <div class="body">
          <div class="muted" id="approve-context" style="margin-bottom:10px;"></div>
          <div style="background:#f8fafc; border:1px solid #e5e7eb; border-radius:10px; padding:10px 12px; margin-bottom:12px;">
            <div style="font-weight:600; color:#0f172a; margin-bottom:4px;">This will remove the penalty</div>
            <div class="muted">The penalty will be marked as removed and the employee will see it as approved.</div>
          </div>
          <label for="approve_note">Admin note (optional)</label>
          <textarea id="approve_note" name="admin_note" maxlength="500" placeholder="Optional note for audit (not required)"></textarea>
        </div>
        <div class="actions">
          <button type="button" class="btn-xs btn-outline" id="approve-cancel">Cancel</button>
          <button type="submit" class="btn-xs btn-approve" id="approve-submit">Approve & remove</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    (function () {
      var rejectBackdrop = document.getElementById('reject-backdrop');
      var rejectForm = document.getElementById('reject-form');
      var rejectCancel = document.getElementById('reject-cancel');
      var rejectCtx = document.getElementById('reject-context');
      var rejectNote = document.getElementById('admin_note');

      var approveBackdrop = document.getElementById('approve-backdrop');
      var approveForm = document.getElementById('approve-form');
      var approveCancel = document.getElementById('approve-cancel');
      var approveCtx = document.getElementById('approve-context');
      var approveNote = document.getElementById('approve_note');
      var approveSubmit = document.getElementById('approve-submit');

      document.querySelectorAll('.js-open-reject').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var action = btn.getAttribute('data-action');
          var emp = btn.getAttribute('data-emp') || 'Employee';
          var pid = btn.getAttribute('data-pid') || 'Penalty';
          var type = btn.getAttribute('data-type') || '';
          var date = btn.getAttribute('data-date') || '';
          rejectForm.action = action;
          if (rejectCtx) rejectCtx.textContent = emp + ' · ' + pid + (type || date ? (' · ' + type + (date ? (' · ' + date) : '')) : '');
          if (rejectNote) rejectNote.value = '';
          rejectBackdrop.classList.add('open');
        });
      });

      document.querySelectorAll('.js-open-approve').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var action = btn.getAttribute('data-action');
          var emp = btn.getAttribute('data-emp') || 'Employee';
          var pid = btn.getAttribute('data-pid') || 'Penalty';
          var type = btn.getAttribute('data-type') || '';
          var date = btn.getAttribute('data-date') || '';
          approveForm.action = action;
          if (approveCtx) approveCtx.textContent = emp + ' · ' + pid + (type || date ? (' · ' + type + (date ? (' · ' + date) : '')) : '');
          if (approveNote) approveNote.value = '';
          if (approveSubmit) approveSubmit.disabled = false;
          approveBackdrop.classList.add('open');
        });
      });

      function closeModal(which) {
        if (which === 'reject' && rejectBackdrop) rejectBackdrop.classList.remove('open');
        if (which === 'approve' && approveBackdrop) approveBackdrop.classList.remove('open');
      }

      if (rejectCancel) rejectCancel.addEventListener('click', function () { closeModal('reject'); });
      if (approveCancel) approveCancel.addEventListener('click', function () { closeModal('approve'); });

      // Click outside to close
      if (rejectBackdrop) rejectBackdrop.addEventListener('click', function (e) { if (e.target === rejectBackdrop) closeModal('reject'); });
      if (approveBackdrop) approveBackdrop.addEventListener('click', function (e) { if (e.target === approveBackdrop) closeModal('approve'); });

      // ESC to close
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
          closeModal('reject');
          closeModal('approve');
        }
      });

      // Prevent double submit (approve)
      if (approveForm) {
        approveForm.addEventListener('submit', function () {
          if (approveSubmit) approveSubmit.disabled = true;
        });
      }
    })();
  </script>
</body>
</html>

