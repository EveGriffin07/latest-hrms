<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Team OT Approvals - HRMS</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    main { padding:20px 24px; }
    .page-header { display:flex; justify-content:space-between; align-items:flex-end; gap:12px; margin-bottom:12px; }
    .page-title { margin:0; font-size:1.35rem; }
    .page-subtitle { margin:2px 0 0; color:#64748b; font-size:0.9rem; }

    .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:14px 16px; margin-bottom:14px; }
    .card .section-title { margin:0 0 12px; font-size:1.05rem; font-weight:600; color:#0f172a; display:flex; align-items:center; gap:8px; }
    .card .section-title i { color:#6366f1; opacity:0.9; }
    .empty-state { text-align:center; padding:32px 24px; color:#94a3b8; font-size:13px; background:#f8fafc; border-radius:12px; margin-top:8px; border:1px dashed #e2e8f0; }
    .empty-state i { font-size:28px; margin-bottom:8px; opacity:0.6; display:block; }
    .table-wrap { overflow-x:auto; border-radius:12px; border:1px solid #e2e8f0; margin-top:8px; }
    .ot-table { width:100%; border-collapse:collapse; font-size:13px; background:#fff; }
    .ot-table thead th { background:linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%); color:#475569; font-weight:600; padding:12px 14px; font-size:12px; text-transform:uppercase; letter-spacing:0.04em; border-bottom:2px solid #e2e8f0; }
    .ot-table tbody td { padding:12px 14px; vertical-align:middle; color:#334155; border-bottom:1px solid #f1f5f9; }
    .progress-badge { padding:4px 10px; border-radius:999px; font-size:11px; font-weight:600; background:#e0e7ff; color:#4338ca; }
    .notice { padding:10px 14px; border-radius:10px; margin-bottom:12px; font-size:13px; }
    .notice.success { background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
    .notice.error { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
    .notice.info { background:#e0e7ff; color:#3730a3; border:1px solid #c7d2fe; }

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

    /* Compact summary chips (fallback) */
    .summary-row { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:12px; }
    .summary-chip {
      padding:6px 10px;
      border-radius:999px;
      background:#f8fafc;
      border:1px solid #e5e7eb;
      display:inline-flex;
      align-items:center;
      gap:6px;
      font-size:12px;
      color:#475569;
    }
    .summary-chip .num { font-weight:700; color:#0f172a; }

    .toolbar {
      display:flex;
      flex-wrap:wrap;
      gap:8px;
      align-items:center;
      margin-bottom:6px;
      font-size:12px;
    }
    .toolbar input,
    .toolbar select {
      padding:6px 10px;
      border:1px solid #e5e7eb;
      border-radius:8px;
      font-size:12px;
      min-height:32px;
    }

    table { width:100%; border-collapse:collapse; font-size:13px; }
    th, td { padding:8px 10px; border-bottom:1px solid #e5e7eb; text-align:left; vertical-align:top; }
    thead th { background:#0f172a; color:#e2e8f0; font-weight:500; }

    .employee-cell strong { display:block; font-weight:600; color:#0f172a; }
    .employee-meta { font-size:11px; color:#6b7280; }
    .reason-text { font-size:12px; color:#111827; max-width:320px; }

    .status-badge {
      padding:3px 8px;
      border-radius:999px;
      font-size:11px;
      font-weight:600;
      display:inline-flex;
      align-items:center;
      gap:4px;
    }
    .status-pending { background:#fef3c7; color:#92400e; }
    .status-approved { background:#dcfce7; color:#166534; }
    .status-rejected { background:#fee2e2; color:#991b1b; }
    .status-other { background:#e5e7eb; color:#374151; }

    .btn-sm {
      padding:6px 10px;
      font-size:12px;
      border-radius:8px;
      border:none;
      cursor:pointer;
      margin:0 2px;
      display:inline-flex;
      align-items:center;
      gap:4px;
    }
    .btn-approve { background:#16a34a; color:#fff; }
    .btn-reject { background:#dc2626; color:#fff; }
    .btn-outline {
      background:#fff;
      border:1px solid #e5e7eb;
      color:#374151;
    }
    .bulk-actions {
      display:flex;
      flex-wrap:wrap;
      gap:12px;
      align-items:center;
      margin-bottom:12px;
      font-size:14px;
      padding:10px 0;
    }
    .bulk-actions .ot-selected-count {
      color:#6b7280;
      font-weight:500;
    }
    .bulk-actions .btn-ot-approve {
      padding:10px 20px;
      border-radius:999px;
      border:none;
      cursor:pointer;
      font-size:14px;
      font-weight:700;
      color:#fff;
      background:#22c55e;
      font-family:inherit;
    }
    .bulk-actions .btn-ot-approve:hover { background:#16a34a; }
    .bulk-actions .btn-ot-reject {
      padding:10px 20px;
      border-radius:999px;
      border:none;
      cursor:pointer;
      font-size:14px;
      font-weight:700;
      color:#fff;
      background:#f87171;
      font-family:inherit;
    }
    .bulk-actions .btn-ot-reject:hover { background:#ef4444; }

    table input[type="checkbox"]#select-all,
    table input[type="checkbox"].row-check { transform:scale(1.6); cursor:pointer; accent-color:#6366f1; }

    tr.row-no-proof { background:#fef2f2 !important; }
    .proof-badge { font-size:11px; padding:2px 6px; border-radius:4px; font-weight:600; }
    .proof-badge.has { background:#dcfce7; color:#166534; }
    .proof-badge.none { background:#fef2f2; color:#991b1b; }

    /* Modal / side panel */
    .overlay {
      position:fixed;
      inset:0;
      background:rgba(15,23,42,0.55);
      display:none;
      align-items:center;
      justify-content:center;
      z-index:1000;
    }
    .overlay.open { display:flex; }
    .panel {
      width:100%;
      max-width:420px;
      background:#fff;
      border-radius:14px;
      box-shadow:0 20px 45px rgba(15,23,42,0.35);
      padding:18px 18px 16px;
    }
    .panel-header {
      display:flex;
      justify-content:space-between;
      align-items:center;
      margin-bottom:10px;
    }
    .panel-title { margin:0; font-size:1rem; }
    .panel-close {
      background:none;
      border:none;
      cursor:pointer;
      color:#6b7280;
      font-size:16px;
    }
    .panel-body { font-size:13px; color:#4b5563; }
    .panel-body label { display:block; font-size:12px; margin:8px 0 4px; font-weight:600; }
    .panel-body input,
    .panel-body textarea {
      width:100%;
      padding:8px 10px;
      border-radius:8px;
      border:1px solid #e5e7eb;
      font-size:13px;
    }
    .panel-body textarea { min-height:80px; resize:vertical; }
    .panel-footer {
      margin-top:14px;
      display:flex;
      justify-content:flex-end;
      gap:8px;
    }
    .panel-footer .btn-sm { min-width:80px; justify-content:center; }
    /* Bulk action modals: clear error when user types */
    #bulkRejectReason:focus ~ .bulk-modal-error { display:none !important; }
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
      <div class="page-header">
        <div>
          <div class="breadcrumb">Supervisor · Team OT Approvals</div>
          <h2 class="page-title">Team OT Approvals</h2>
          <p class="page-subtitle">Approve or reject overtime claims from your team. Approved claims are sent directly to admin for final approval.</p>
        </div>
      </div>

      @if(session('success'))
        <div class="notice success">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div class="notice error">{{ session('error') }}</div>
      @endif
      @if(session('message'))
        <div class="notice info">{{ session('message') }}</div>
      @endif

      <div class="ot-summary-cards">
        <div class="ot-summary-card pending-admin">
          <span class="num">{{ $pendingAdminCount ?? 0 }}</span>
          <span class="label">Pending Admin</span>
        </div>
        <div class="ot-summary-card flagged-pending">
          <span class="num" id="ot-pending-count">{{ $flaggedPendingCount ?? 0 }}</span>
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
          <form method="GET" action="{{ route('employee.overtime_inbox.index') }}" class="toolbar" style="margin-bottom:12px;">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name/ID/code">
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
          <form method="POST" action="{{ route('employee.overtime_inbox.bulk') }}" id="bulkForm">
            @csrf
            <div class="bulk-actions">
              <span class="ot-selected-count" id="ot-selected-count">0 selected</span>
              <button type="button" class="btn-ot-approve js-bulk-approve">Approve selected</button>
              <button type="button" class="btn-ot-reject js-bulk-reject">Reject selected</button>
            </div>
            <input type="hidden" name="action" id="bulk_action_value" value="">
            <input type="hidden" name="reject_reason" id="bulk_reject_reason">
            <input type="hidden" name="q" value="{{ request('q') }}">
            <input type="hidden" name="department" value="{{ request('department') }}">
            <input type="hidden" name="start" value="{{ request('start') }}">
            <input type="hidden" name="end" value="{{ request('end') }}">
            <div class="table-wrap">
              <table class="ot-table">
                <thead>
                  <tr>
                    <th style="width:42px"><input type="checkbox" id="select-all" title="Select all"></th>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Hours / Rate</th>
                    <th>Location</th>
                    <th>Reason</th>
                    <th>Submitted</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($pendingClaims as $c)
                    <tr class="{{ $c->hasNoProofFlag() ? 'row-no-proof' : '' }}">
                      <td><input type="checkbox" name="ids[]" value="{{ $c->id }}" class="row-check"></td>
                      <td class="employee-cell">
                        <strong>{{ $c->employee->user->name ?? '—' }}</strong>
                        <div class="employee-meta">
                          {{ $c->employee->employee_code ?? '' }}
                          · {{ $c->employee->department->department_name ?? '—' }}
                        </div>
                      </td>
                      <td>{{ $c->date?->format('Y-m-d') }}</td>
                      <td>
                        {{ number_format($c->hours, 2) }} h
                        <div class="employee-meta">Rate {{ (float) $c->rate_type }}x</div>
                      </td>
                      <td>{{ $c->location_type ?? 'INSIDE' }}</td>
                      <td>
                        @if(($c->location_type ?? 'INSIDE') === \App\Models\OvertimeClaim::LOCATION_OUTSIDE)
                          @if($c->proof_image_path)
                            <span class="proof-badge has">Has proof</span>
                          @else
                            <span class="proof-badge none">NO PROOF</span>
                          @endif
                        @else
                          <span class="reason-text">{{ Str::limit($c->reason ?? '—', 80) }}</span>
                        @endif
                      </td>
                      <td>{{ $c->submitted_at ? $c->submitted_at->format('M j, H:i') : '—' }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </form>
        @endif
      </div>

      {{-- OT you approved or rejected --}}
      <div class="card">
        <h3 class="section-title"><i class="fa-solid fa-clipboard-list"></i> OT you approved or rejected</h3>
        @if($actedClaims->isEmpty())
          <div class="empty-state"><i class="fa-solid fa-clipboard-check"></i> No OT approved or rejected by you yet.</div>
        @else
          <form method="GET" action="{{ route('employee.overtime_inbox.index') }}" class="toolbar" style="margin-bottom:12px;">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name/ID/code">
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
          <div class="table-wrap">
            <table class="ot-table">
              <thead>
                <tr>
                  <th>Employee</th>
                  <th>Department</th>
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
                    <td>
                      <strong>{{ $c->employee->user->name ?? '—' }}</strong>
                      <div class="employee-meta">{{ $c->employee->employee_code ?? '' }}</div>
                    </td>
                    <td>{{ $c->employee->department->department_name ?? '—' }}</td>
                    <td>{{ $c->date?->format('Y-m-d') }}</td>
                    <td>{{ number_format($c->getEffectiveApprovedHours(), 2) }} h</td>
                    <td class="reason-text">{{ Str::limit($c->reason ?? '—', 50) }}</td>
                    <td>
                      @if(in_array($c->status, [\App\Models\OvertimeClaim::STATUS_SUPERVISOR_APPROVED, \App\Models\OvertimeClaim::STATUS_ADMIN_PENDING, \App\Models\OvertimeClaim::STATUS_ADMIN_APPROVED], true))
                        <span class="status-badge status-approved">Approved by you</span>
                      @else
                        <span class="status-badge status-rejected">Rejected by you</span>
                      @endif
                    </td>
                    <td>
                      <span class="progress-badge">{{ $c->getProgressLabelForSupervisor() }}</span>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </main>
  </div>

  {{-- Approve confirmation modal --}}
  <div class="overlay" id="bulkApproveOverlay" aria-hidden="true">
    <div class="panel" role="dialog" aria-labelledby="bulkApproveTitle">
      <div class="panel-header">
        <h3 class="panel-title" id="bulkApproveTitle"><i class="fa-solid fa-circle-check" style="color:#16a34a;"></i> Approve selected claims</h3>
      </div>
      <div class="panel-body">
        <p style="margin:0;">Approve all selected claims and send to admin?</p>
      </div>
      <div class="panel-footer">
        <button type="button" class="btn-sm btn-outline js-close-bulk-modal" data-overlay="bulkApproveOverlay">Cancel</button>
        <button type="button" class="btn-sm btn-approve" id="bulkApproveConfirm"><i class="fa-solid fa-check"></i> Approve</button>
      </div>
    </div>
  </div>

  {{-- Reject modal (reason required) --}}
  <div class="overlay" id="bulkRejectOverlay" aria-hidden="true">
    <div class="panel" role="dialog" aria-labelledby="bulkRejectTitle">
      <div class="panel-header">
        <h3 class="panel-title" id="bulkRejectTitle"><i class="fa-solid fa-circle-xmark" style="color:#dc2626;"></i> Reject selected claims</h3>
      </div>
      <div class="panel-body">
        <label for="bulkRejectReason">Common rejection reason</label>
        <textarea id="bulkRejectReason" rows="3" placeholder="Enter a common rejection reason for all selected claims..." maxlength="500"></textarea>
        <p id="bulkRejectError" class="bulk-modal-error" style="display:none; margin:6px 0 0; font-size:12px; color:#dc2626;"></p>
      </div>
      <div class="panel-footer">
        <button type="button" class="btn-sm btn-outline js-close-bulk-modal" data-overlay="bulkRejectOverlay">Cancel</button>
        <button type="button" class="btn-sm btn-reject" id="bulkRejectConfirm"><i class="fa-solid fa-xmark"></i> Reject</button>
      </div>
    </div>
  </div>

  <script>
    (function () {
      var selectAll = document.getElementById('select-all');
      var checks = document.querySelectorAll('.row-check');
      function updateOtSelectedCount() {
        var el = document.getElementById('ot-selected-count');
        if (!el) return;
        var total = document.querySelectorAll('#bulkForm .row-check').length;
        var n = document.querySelectorAll('#bulkForm .row-check:checked').length;
        el.textContent = n + ' selected';
      }

      if (selectAll) {
        selectAll.addEventListener('change', function () {
          checks.forEach(function (cb) { cb.checked = selectAll.checked; });
          updateOtSelectedCount();
        });
      }
      document.querySelectorAll('#bulkForm .row-check').forEach(function (cb) {
        cb.addEventListener('change', updateOtSelectedCount);
      });
      updateOtSelectedCount();

      function anyClaimSelected() {
        return Array.prototype.some.call(
          document.querySelectorAll('#bulkForm .row-check'),
          function (cb) { return cb.checked; }
        );
      }

      function openOverlay(id) {
        document.getElementById(id).classList.add('open');
        document.getElementById(id).setAttribute('aria-hidden', 'false');
      }
      function closeOverlay(id) {
        document.getElementById(id).classList.remove('open');
        document.getElementById(id).setAttribute('aria-hidden', 'true');
      }

      document.querySelectorAll('.js-close-bulk-modal').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var id = btn.getAttribute('data-overlay');
          if (id) closeOverlay(id);
        });
      });

      document.querySelectorAll('.overlay[id^="bulk"]').forEach(function (ov) {
        ov.addEventListener('click', function (e) {
          if (e.target === ov) closeOverlay(ov.id);
        });
      });

      document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('.overlay[id^="bulk"].open').forEach(function (ov) {
          closeOverlay(ov.id);
        });
      });

      var bulkForm = document.getElementById('bulkForm');
      if (bulkForm) {
        document.querySelector('.js-bulk-approve')?.addEventListener('click', function () {
          if (!anyClaimSelected()) {
            alert('Please select at least one claim.');
            return;
          }
          document.getElementById('bulk_action_value').value = 'approve';
          openOverlay('bulkApproveOverlay');
        });

        document.querySelector('.js-bulk-reject')?.addEventListener('click', function () {
          if (!anyClaimSelected()) {
            alert('Please select at least one claim.');
            return;
          }
          document.getElementById('bulkRejectReason').value = '';
          document.getElementById('bulkRejectError').style.display = 'none';
          document.getElementById('bulkRejectError').textContent = '';
          openOverlay('bulkRejectOverlay');
        });

        document.getElementById('bulkApproveConfirm')?.addEventListener('click', function () {
          closeOverlay('bulkApproveOverlay');
          document.getElementById('bulk_action_value').value = 'approve';
          bulkForm.submit();
        });

        document.getElementById('bulkRejectConfirm')?.addEventListener('click', function () {
          var reason = (document.getElementById('bulkRejectReason').value || '').trim();
          var errEl = document.getElementById('bulkRejectError');
          if (!reason) {
            errEl.textContent = 'Please enter a rejection reason.';
            errEl.style.display = 'block';
            return;
          }
          closeOverlay('bulkRejectOverlay');
          document.getElementById('bulk_reject_reason').value = reason;
          document.getElementById('bulk_action_value').value = 'reject';
          bulkForm.submit();
        });
      }
    })();
  </script>
</body>
</html>
