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
    .tabs { display:flex; gap:4px; margin-bottom:16px; border-bottom:1px solid #e5e7eb; }
    .tabs a { padding:10px 16px; text-decoration:none; color:#64748b; font-weight:500; border-bottom:2px solid transparent; margin-bottom:-1px; }
    .tabs a:hover { color:#0f172a; }
    .tabs a.active { color:#0ea5e9; border-bottom-color:#0ea5e9; }
    .tabs .badge { font-size:11px; padding:2px 6px; border-radius:999px; background:#e2e8f0; color:#475569; margin-left:6px; }
    .grid-cards { display:grid; grid-template-columns:repeat(auto-fit, minmax(100px, 1fr)); gap:8px; margin-bottom:16px; }
    .mini-card { background:#f8fafc; border-radius:8px; padding:8px 12px; text-align:center; }
    .mini-card .num { font-size:18px; font-weight:700; color:#0f172a; }
    .mini-card .label { font-size:11px; color:#64748b; }
    .toolbar { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px; align-items:center; }
    .toolbar input, .toolbar select { padding:6px 10px; border:1px solid #e5e7eb; border-radius:8px; font-size:13px; }
    table { width:100%; border-collapse:collapse; font-size:13px; }
    th, td { padding:8px 10px; border-bottom:1px solid #e5e7eb; text-align:left; }
    thead th { background:#0f172a; color:#e2e8f0; font-weight:500; }
    .btn-sm { padding:8px 14px; font-size:12px; border-radius:8px; border:none; cursor:pointer; margin:0 2px; display:inline-flex; align-items:center; gap:6px; }
    .btn-approve { background:#16a34a; color:#fff; }
    .btn-reject { background:#dc2626; color:#fff; }
    .btn-hold { background:#f59e0b; color:#fff; }
    .btn-outline { background:#fff; border:1px solid #e5e7eb; color:#374151; }
    tr.row-no-proof { background:#fef2f2 !important; }
    .proof-badge { font-size:11px; padding:2px 6px; border-radius:4px; font-weight:600; }
    .proof-badge.has { background:#dcfce7; color:#166534; }
    .proof-badge.none { background:#fef2f2; color:#991b1b; }
    .overlay { position:fixed; inset:0; background:rgba(15,23,42,0.5); display:none; align-items:center; justify-content:center; z-index:1000; }
    .overlay.open { display:flex; }
    .panel { width:100%; max-width:560px; max-height:90vh; overflow:auto; background:#fff; border-radius:16px; box-shadow:0 24px 48px rgba(0,0,0,0.18); padding:0; }
    .panel-header { padding:20px 24px 16px; border-bottom:1px solid #e5e7eb; }
    .panel-header h3 { margin:0; font-size:1.2rem; font-weight:600; color:#0f172a; }
    .panel-body { padding:20px 24px; font-size:13px; }
    .panel-section { margin-bottom:16px; padding:14px 16px; background:#f8fafc; border-radius:10px; font-size:13px; line-height:1.5; }
    .panel-section:last-of-type { margin-bottom:0; }
    .panel-section strong { display:block; margin-bottom:8px; color:#475569; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.03em; }
    .panel-section div { color:#334155; }
    .modal-divider { height:1px; background:#e5e7eb; margin:20px 0; }
    .modal-action-block { padding:16px 20px; border-radius:12px; margin-bottom:16px; }
    .modal-action-block:last-child { margin-bottom:0; }
    .modal-action-block.post { background:linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border:1px solid #bbf7d0; }
    .modal-action-block.reject { background:#fef2f2; border:1px solid #fecaca; }
    .modal-action-block h4 { margin:0 0 12px; font-size:13px; font-weight:600; color:#0f172a; display:flex; align-items:center; gap:8px; }
    .modal-action-block h4 .icon { font-size:14px; }
    .modal-field { margin-bottom:12px; }
    .modal-field:last-child { margin-bottom:0; }
    .modal-field label { display:block; margin-bottom:6px; font-size:12px; font-weight:600; color:#374151; }
    .modal-field input[type="password"],
    .modal-field textarea { width:100%; padding:10px 12px; border:1px solid #e5e7eb; border-radius:8px; font-size:13px; font-family:inherit; resize:vertical; min-height:44px; }
    .modal-field textarea { min-height:72px; }
    .modal-field input:focus,
    .modal-field textarea:focus { outline:none; border-color:#16a34a; box-shadow:0 0 0 3px rgba(22,163,74,0.15); }
    .modal-actions { display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end; margin-top:14px; }
    .panel-footer { padding:16px 24px 20px; display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; border-top:1px solid #e5e7eb; background:#fafafa; border-radius:0 0 16px 16px; }
    .panel-footer .btn-sm { min-width:100px; justify-content:center; }
    /* OT summary cards (Pending Admin, Flagged Pending, Approved, Rejected) */
    .ot-summary-cards { display:flex; flex-wrap:wrap; gap:16px; margin-bottom:16px; }
    .ot-summary-card {
      flex:1 1 140px;
      min-width:120px;
      padding:16px 20px;
      border-radius:12px;
      text-align:center;
    }
    .ot-summary-card .num { display:block; font-size:1.75rem; font-weight:700; margin-bottom:4px; }
    .ot-summary-card .label { display:block; font-size:12px; font-weight:600; }
    .ot-summary-card.pending-admin { background:#dbeafe; color:#1e40af; }
    .ot-summary-card.pending-admin .num { color:#1d4ed8; }
    .ot-summary-card.flagged-pending { background:#fef9c3; color:#a16207; }
    .ot-summary-card.flagged-pending .num { color:#b45309; }
    .ot-summary-card.approved { background:#dcfce7; color:#166534; }
    .ot-summary-card.approved .num { color:#15803d; }
    .ot-summary-card.rejected { background:#fee2e2; color:#b91c1c; }
    .ot-summary-card.rejected .num { color:#dc2626; }

    .summary-row { display:flex; flex-wrap:wrap; gap:12px; margin-bottom:12px; }
    .summary-chip { padding:10px 16px; border-radius:12px; display:inline-flex; align-items:center; gap:8px; font-size:13px; color:#475569; background:#fff; border:1px solid #e2e8f0; }
    .summary-chip .num { font-weight:700; font-size:1.15rem; color:#0f172a; }
    .card .section-title { margin:0 0 12px; font-size:1.05rem; font-weight:600; color:#0f172a; display:flex; align-items:center; gap:8px; }
    .card .section-title i { color:#6366f1; opacity:0.9; }
    .empty-state { text-align:center; padding:32px 24px; color:#94a3b8; font-size:13px; background:#f8fafc; border-radius:12px; margin-top:8px; border:1px dashed #e2e8f0; }
    .empty-state i { font-size:28px; margin-bottom:8px; opacity:0.6; display:block; }
    .table-wrap { overflow-x:auto; border-radius:12px; border:1px solid #e2e8f0; margin-top:8px; }
    .ot-table { width:100%; border-collapse:collapse; font-size:13px; background:#fff; }
    .ot-table thead th { background:linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%); color:#475569; font-weight:600; padding:12px 14px; font-size:12px; text-transform:uppercase; letter-spacing:0.04em; border-bottom:2px solid #e2e8f0; }
    .ot-table tbody td { padding:12px 14px; vertical-align:middle; color:#334155; border-bottom:1px solid #f1f5f9; }
    .employee-cell strong { display:block; font-weight:600; color:#0f172a; }
    .employee-meta { font-size:11px; color:#6b7280; }
    .reason-text { font-size:12px; color:#64748b; max-width:320px; }
    .progress-badge { padding:4px 10px; border-radius:999px; font-size:11px; font-weight:600; background:#e0e7ff; color:#4338ca; }
    .status-badge { padding:3px 8px; border-radius:999px; font-size:11px; font-weight:600; }
    .status-approved { background:#dcfce7; color:#166534; }
    .status-rejected { background:#fee2e2; color:#991b1b; }
    .status-hold { background:#fef3c7; color:#92400e; }
    .bulk-actions { display:flex; flex-wrap:wrap; gap:12px; align-items:center; margin-bottom:12px; font-size:14px; padding:10px 0; }
    .bulk-actions .ot-selected-count { color:#6b7280; font-weight:500; }
    .bulk-actions .btn-ot-approve { padding:10px 20px; border-radius:999px; border:none; cursor:pointer; font-size:14px; font-weight:700; color:#fff; background:#22c55e; font-family:inherit; }
    .bulk-actions .btn-ot-approve:hover { background:#16a34a; }
    .bulk-actions .btn-ot-reject { padding:10px 20px; border-radius:999px; border:none; cursor:pointer; font-size:14px; font-weight:700; color:#fff; background:#f87171; font-family:inherit; }
    .bulk-actions .btn-ot-reject:hover { background:#ef4444; }
    table input[type="checkbox"]#admin-ot-select-all,
    table input[type="checkbox"].admin-ot-row-check { transform:scale(1.6); cursor:pointer; accent-color:#6366f1; }
    .tabs a.active { color:#6366f1; border-bottom-color:#6366f1; }
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
      <div class="breadcrumb">Payroll · OT Claims</div>
      <h2 style="margin:0 0 4px;">OT Claims</h2>
      <p style="margin:0; color:#64748b;">Approve or reject OT claims. Approved claims are posted to payroll. Re-enter your password when approving.</p>

      @if(session('success'))
        <div class="notice success" style="padding:10px; background:#dcfce7; color:#166534; border-radius:10px; margin-bottom:12px;">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div class="notice error" style="padding:10px; background:#fee2e2; color:#991b1b; border-radius:10px; margin-bottom:12px;">{{ session('error') }}</div>
      @endif
      @if($errors->any())
        <div class="notice error" style="padding:10px; background:#fee2e2; color:#991b1b; border-radius:10px; margin-bottom:12px;">
          @foreach($errors->all() as $err) {{ $err }} @endforeach
        </div>
      @endif

      <div class="ot-summary-cards">
        <div class="ot-summary-card pending-admin">
          <span class="num">{{ $pendingCount ?? 0 }}</span>
          <span class="label">Pending Admin</span>
        </div>
        <div class="ot-summary-card flagged-pending">
          <span class="num">{{ $exceptionsCount ?? 0 }}</span>
          <span class="label">Flagged Pending</span>
        </div>
        <div class="ot-summary-card approved">
          <span class="num">{{ $approvedCount ?? 0 }}</span>
          <span class="label">Approved</span>
        </div>
        <div class="ot-summary-card rejected">
          <span class="num">{{ $rejectedCount ?? 0 }}</span>
          <span class="label">Rejected (Sup + Admin)</span>
        </div>
      </div>

      {{-- Pending your approval --}}
      <div class="card">
        <h3 class="section-title"><i class="fa-solid fa-inbox"></i> Pending your approval</h3>
        @if($pendingClaims->isEmpty())
          <div class="empty-state"><i class="fa-solid fa-inbox"></i> No OT claims pending your approval.</div>
        @else
          <div class="tabs" style="margin-bottom:12px;">
            @php $qparams = request()->only('q','department','start','end'); @endphp
            <a href="{{ route('admin.payroll.overtime_claims', ['queue' => 'all'] + $qparams) }}" class="{{ ($queue ?? 'all') === 'all' ? 'active' : '' }}">All <span class="badge">{{ $pendingCount }}</span></a>
            <a href="{{ route('admin.payroll.overtime_claims', ['queue' => 'payroll-ready'] + $qparams) }}" class="{{ ($queue ?? '') === 'payroll-ready' ? 'active' : '' }}">Payroll-ready <span class="badge">{{ $payrollReadyCount ?? 0 }}</span></a>
            <a href="{{ route('admin.payroll.overtime_claims', ['queue' => 'exceptions'] + $qparams) }}" class="{{ ($queue ?? '') === 'exceptions' ? 'active' : '' }}">Exceptions <span class="badge">{{ $exceptionsCount ?? 0 }}</span></a>
          </div>
          <form method="GET" action="{{ route('admin.payroll.overtime_claims') }}" class="toolbar" style="margin-bottom:12px;">
            <input type="hidden" name="queue" value="{{ $queue ?? 'all' }}">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name/ID">
            <select name="department">
              <option value="">All Depts</option>
              @foreach($departments as $d)
                <option value="{{ $d->department_id }}" {{ request('department') == $d->department_id ? 'selected' : '' }}>{{ $d->department_name }}</option>
              @endforeach
            </select>
            <input type="date" name="start" value="{{ request('start') }}" placeholder="From">
            <input type="date" name="end" value="{{ request('end') }}" placeholder="To">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
          </form>
          <form method="POST" action="{{ route('admin.payroll.overtime_claims.bulk_approve') }}" id="adminOtBulkApproveForm" style="display:none;">
            @csrf
            <input type="hidden" name="queue" value="{{ $queue ?? 'all' }}">
            <input type="hidden" name="q" value="{{ request('q') }}">
            <input type="hidden" name="department" value="{{ request('department') }}">
            <input type="hidden" name="start" value="{{ request('start') }}">
            <input type="hidden" name="end" value="{{ request('end') }}">
            <input type="hidden" name="password" id="admin_ot_bulk_password">
            <input type="hidden" name="remark" id="admin_ot_bulk_remark">
          </form>
          <form method="POST" action="{{ route('admin.payroll.overtime_claims.bulk_reject') }}" id="adminOtBulkRejectForm">
            @csrf
            <input type="hidden" name="queue" value="{{ $queue ?? 'all' }}">
            <input type="hidden" name="q" value="{{ request('q') }}">
            <input type="hidden" name="department" value="{{ request('department') }}">
            <input type="hidden" name="start" value="{{ request('start') }}">
            <input type="hidden" name="end" value="{{ request('end') }}">
            <input type="hidden" name="remark" id="admin_ot_bulk_reject_remark">
          </form>
          <div class="bulk-actions">
            <span class="ot-selected-count" id="admin-ot-selected-count">0 selected</span>
            <button type="button" class="btn-ot-approve js-admin-bulk-approve">Approve selected</button>
            <button type="button" class="btn-ot-reject js-admin-bulk-reject">Reject selected</button>
          </div>
          <div class="table-wrap">
            <table class="ot-table">
              <thead>
                <tr>
                  <th style="width:42px"><input type="checkbox" id="admin-ot-select-all" title="Select all"></th>
                  <th>Employee</th>
                  <th>Dept</th>
                  <th>Supervisor</th>
                  <th>Date</th>
                  <th>Hours</th>
                  <th>Approved Hrs</th>
                  <th>Location</th>
                  <th>Payout</th>
                  <th>Submitted</th>
                </tr>
              </thead>
              <tbody>
                @foreach($pendingClaims as $c)
                  <tr class="{{ $c->hasNoProofFlag() ? 'row-no-proof' : '' }}">
                    <td><input type="checkbox" name="ids[]" form="adminOtBulkApproveForm" value="{{ $c->id }}" class="admin-ot-row-check"></td>
                    <td class="employee-cell">
                      <strong>{{ $c->employee->user->name ?? '—' }}</strong>
                      <div class="employee-meta">{{ $c->employee->employee_code ?? '' }}</div>
                    </td>
                    <td>{{ $c->employee->department->department_name ?? '—' }}</td>
                    <td>{{ $c->supervisor->name ?? '—' }}</td>
                    <td>{{ $c->date?->format('Y-m-d') }}</td>
                    <td>{{ number_format($c->hours, 2) }}</td>
                    <td>{{ number_format($c->getEffectiveApprovedHours(), 2) }}</td>
                    <td>{{ $c->location_type ?? 'INSIDE' }}</td>
                    <td>{{ number_format(\App\Http\Controllers\AdminOvertimeClaimController::computePayout($c), 2) }}</td>
                    <td>{{ $c->submitted_at ? $c->submitted_at->format('M j, H:i') : '—' }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>

      {{-- OT you approved or rejected --}}
      <div class="card">
        <h3 class="section-title"><i class="fa-solid fa-clipboard-list"></i> OT you approved or rejected</h3>
        @if($actedClaims->isEmpty())
          <div class="empty-state"><i class="fa-solid fa-clipboard-check"></i> No OT approved or rejected by you yet.</div>
        @else
          <form method="GET" action="{{ route('admin.payroll.overtime_claims') }}" class="toolbar" style="margin-bottom:12px;">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name/ID">
            <select name="department">
              <option value="">All Depts</option>
              @foreach($departments as $d)
                <option value="{{ $d->department_id }}" {{ request('department') == $d->department_id ? 'selected' : '' }}>{{ $d->department_name }}</option>
              @endforeach
            </select>
            <input type="date" name="start" value="{{ request('start') }}">
            <input type="date" name="end" value="{{ request('end') }}">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
          </form>
          <div class="table-wrap">
            <table class="ot-table">
              <thead>
                <tr>
                  <th>Employee</th>
                  <th>Dept</th>
                  <th>Supervisor</th>
                  <th>Date</th>
                  <th>Hours</th>
                  <th>Reason</th>
                  <th>Your action</th>
                  <th>Progress</th>
                </tr>
              </thead>
              <tbody>
                @foreach($actedClaims as $c)
                  <tr>
                    <td class="employee-cell">
                      <strong>{{ $c->employee->user->name ?? '—' }}</strong>
                      <div class="employee-meta">{{ $c->employee->employee_code ?? '' }}</div>
                    </td>
                    <td>{{ $c->employee->department->department_name ?? '—' }}</td>
                    <td>{{ $c->supervisor->name ?? '—' }}</td>
                    <td>{{ $c->date?->format('Y-m-d') }}</td>
                    <td>{{ number_format($c->getEffectiveApprovedHours(), 2) }} h</td>
                    <td class="reason-text">{{ Str::limit($c->reason ?? '—', 50) }}</td>
                    <td>
                      @if($c->status === \App\Models\OvertimeClaim::STATUS_ADMIN_APPROVED)
                        <span class="status-badge status-approved">Approved by you</span>
                      @elseif($c->status === \App\Models\OvertimeClaim::STATUS_ADMIN_REJECTED)
                        <span class="status-badge status-rejected">Rejected by you</span>
                      @else
                        <span class="status-badge status-hold">On hold</span>
                      @endif
                    </td>
                    <td><span class="progress-badge">{{ $c->getProgressLabelForAdmin() }}</span></td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </main>
  </div>

  {{-- Approve: password card --}}
  <div class="overlay" id="adminOtApproveOverlay" aria-hidden="true">
    <div class="panel" role="dialog" aria-modal="true" style="max-width:420px;">
      <div class="panel-header">
        <h3><i class="fa-solid fa-lock" style="color:#16a34a; margin-right:8px;"></i> Approve & post to payroll</h3>
      </div>
      <div class="panel-body">
        <p style="margin:0 0 14px; color:#64748b;">Re-enter your admin password to approve the selected claim(s) and post to payroll.</p>
        <div class="modal-field">
          <label for="admin_ot_password">Password</label>
          <input type="password" id="admin_ot_password" autocomplete="current-password" placeholder="Your admin password">
        </div>
        <div class="modal-field">
          <label for="admin_ot_remark">Remark (optional)</label>
          <textarea id="admin_ot_remark" rows="2" placeholder="Note for records…" maxlength="500"></textarea>
        </div>
        <p id="admin_ot_password_error" style="display:none; margin:8px 0 0; font-size:13px; color:#dc2626;"></p>
      </div>
      <div class="panel-footer">
        <button type="button" class="btn-sm btn-outline" data-close="adminOtApproveOverlay">Cancel</button>
        <button type="button" class="btn-sm btn-approve" id="admin_ot_approve_confirm"><i class="fa-solid fa-check"></i> Approve</button>
      </div>
    </div>
  </div>

  {{-- Reject: reason card --}}
  <div class="overlay" id="adminOtRejectOverlay" aria-hidden="true">
    <div class="panel" role="dialog" aria-modal="true" style="max-width:420px;">
      <div class="panel-header">
        <h3><i class="fa-solid fa-times-circle" style="color:#dc2626; margin-right:8px;"></i> Reject selected</h3>
      </div>
      <div class="panel-body">
        <div class="modal-field">
          <label for="admin_ot_reject_remark">Reason for rejection <span style="color:#dc2626;">*</span></label>
          <textarea id="admin_ot_reject_remark" rows="3" placeholder="Enter reason…" maxlength="500"></textarea>
        </div>
        <p id="admin_ot_reject_error" style="display:none; margin:8px 0 0; font-size:13px; color:#dc2626;"></p>
      </div>
      <div class="panel-footer">
        <button type="button" class="btn-sm btn-outline" data-close="adminOtRejectOverlay">Cancel</button>
        <button type="button" class="btn-sm btn-reject" id="admin_ot_reject_confirm"><i class="fa-solid fa-xmark"></i> Reject</button>
      </div>
    </div>
  </div>

  <script>
  (function() {
    function closeOverlay(id) {
      var el = document.getElementById(id);
      if (el) { el.classList.remove('open'); el.setAttribute('aria-hidden', 'true'); }
    }
    document.querySelectorAll('[data-close]').forEach(function(btn) {
      btn.addEventListener('click', function() { closeOverlay(this.getAttribute('data-close')); });
    });
    document.querySelectorAll('.overlay').forEach(function(el) {
      el.addEventListener('click', function(e) { if (e.target === el) closeOverlay(el.id); });
    });

    var approveOverlay = document.getElementById('adminOtApproveOverlay');
    var rejectOverlay = document.getElementById('adminOtRejectOverlay');

    function updateAdminOtSelectedCount() {
      var el = document.getElementById('admin-ot-selected-count');
      if (!el) return;
      var checked = document.querySelectorAll('.admin-ot-row-check:checked');
      el.textContent = checked.length + ' selected';
    }
    var selectAll = document.getElementById('admin-ot-select-all');
    var rowChecks = document.querySelectorAll('.admin-ot-row-check');
    if (selectAll) {
      selectAll.addEventListener('change', function() {
        rowChecks.forEach(function(cb) { cb.checked = selectAll.checked; });
        updateAdminOtSelectedCount();
      });
    }
    rowChecks.forEach(function(cb) { cb.addEventListener('change', updateAdminOtSelectedCount); });
    updateAdminOtSelectedCount();

    document.querySelector('.js-admin-bulk-approve')?.addEventListener('click', function() {
      var checked = document.querySelectorAll('.admin-ot-row-check:checked');
      if (!checked.length) { alert('Please select at least one claim.'); return; }
      document.getElementById('admin_ot_password').value = '';
      document.getElementById('admin_ot_remark').value = '';
      document.getElementById('admin_ot_password_error').style.display = 'none';
      approveOverlay.classList.add('open');
      approveOverlay.setAttribute('aria-hidden', 'false');
    });

    document.getElementById('admin_ot_approve_confirm')?.addEventListener('click', function() {
      var pwd = (document.getElementById('admin_ot_password').value || '').trim();
      var errEl = document.getElementById('admin_ot_password_error');
      if (!pwd) {
        errEl.textContent = 'Please enter your password.';
        errEl.style.display = 'block';
        return;
      }
      var form = document.getElementById('adminOtBulkApproveForm');
      form.querySelector('#admin_ot_bulk_password').value = pwd;
      form.querySelector('#admin_ot_bulk_remark').value = (document.getElementById('admin_ot_remark').value || '').trim();
      form.submit();
    });

    document.querySelector('.js-admin-bulk-reject')?.addEventListener('click', function() {
      var checked = document.querySelectorAll('.admin-ot-row-check:checked');
      if (!checked.length) { alert('Please select at least one claim.'); return; }
      document.getElementById('admin_ot_reject_remark').value = '';
      document.getElementById('admin_ot_reject_error').style.display = 'none';
      rejectOverlay.classList.add('open');
      rejectOverlay.setAttribute('aria-hidden', 'false');
    });

    document.getElementById('admin_ot_reject_confirm')?.addEventListener('click', function() {
      var remark = (document.getElementById('admin_ot_reject_remark').value || '').trim();
      var errEl = document.getElementById('admin_ot_reject_error');
      if (!remark) {
        errEl.textContent = 'Please enter a rejection reason.';
        errEl.style.display = 'block';
        return;
      }
      var form = document.getElementById('adminOtBulkRejectForm');
      var ids = [];
      document.querySelectorAll('.admin-ot-row-check:checked').forEach(function(cb) {
        ids.push(cb.value);
      });
      var existingIds = form.querySelectorAll('input[name="ids[]"]');
      existingIds.forEach(function(inp) { inp.remove(); });
      ids.forEach(function(id) {
        var inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'ids[]';
        inp.value = id;
        form.appendChild(inp);
      });
      form.querySelector('#admin_ot_bulk_reject_remark').value = remark;
      form.submit();
    });
  })();
  </script>
</body>
</html>
