<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Penalty Removal Requests - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    body { font-family: 'Poppins', sans-serif; }
    main { padding: 24px 28px; background: #f8fafc; min-height: 100vh; }
    .page-header { margin-bottom: 20px; }
    .breadcrumb { font-size: 12px; color: #94a3b8; margin-bottom: 4px; letter-spacing: 0.02em; }
    .page-title { margin: 0; font-size: 1.5rem; font-weight: 700; color: #0f172a; letter-spacing: -0.02em; }
    .page-subtitle { margin: 6px 0 0; color: #64748b; font-size: 0.9rem; line-height: 1.5; max-width: 560px; }

    .card {
      background: #fff;
      border-radius: 16px;
      padding: 20px 24px;
      margin-bottom: 20px;
      box-shadow: 0 1px 3px rgba(15,23,42,0.06);
      border: 1px solid #e2e8f0;
    }
    .card .section-title {
      margin: 0 0 4px;
      font-size: 1.05rem;
      font-weight: 600;
      color: #0f172a;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .card .section-title i { color: #6366f1; opacity: 0.9; }

    .summary-row { display: flex; flex-wrap: wrap; gap: 12px; }
    .summary-chip {
      padding: 10px 16px;
      border-radius: 12px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      color: #475569;
      background: #fff;
      border: 1px solid #e2e8f0;
      box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }
    .summary-chip a { color: inherit; text-decoration: none; }
    .summary-chip a:hover { text-decoration: underline; }
    .summary-chip .num { font-weight: 700; font-size: 1.15rem; color: #0f172a; }
    .summary-chip.active { background: #e0e7ff; border-color: #6366f1; color: #4338ca; }
    .summary-chip.active .num { color: #4338ca; }

    .table-wrap { overflow-x: auto; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 16px; }
    .data-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
      background: #fff;
    }
    .data-table thead th {
      background: linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%);
      color: #475569;
      font-weight: 600;
      text-align: left;
      padding: 14px 16px;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      border-bottom: 2px solid #e2e8f0;
    }
    .data-table thead th:first-child { border-radius: 12px 0 0 0; }
    .data-table thead th:last-child { border-radius: 0 12px 0 0; }
    .data-table tbody tr { border-bottom: 1px solid #f1f5f9; }
    .data-table tbody tr:hover { background: #f8fafc; }
    .data-table tbody td { padding: 14px 16px; vertical-align: middle; color: #334155; }
    .employee-meta { font-size: 11px; color: #94a3b8; }
    .reason-text { color: #64748b; max-width: 240px; font-size: 12px; }

    .btn-sm {
      padding: 8px 14px;
      font-size: 12px;
      font-weight: 600;
      border-radius: 10px;
      border: none;
      cursor: pointer;
      margin: 0 4px 4px 0;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: transform 0.1s ease, box-shadow 0.15s ease;
      font-family: inherit;
    }
    .btn-sm:hover { transform: translateY(-1px); }
    .btn-approve {
      background: linear-gradient(180deg, #22c55e 0%, #16a34a 100%);
      color: #fff;
      box-shadow: 0 2px 8px rgba(22,163,74,0.35);
    }
    .btn-reject {
      background: linear-gradient(180deg, #ef4444 0%, #dc2626 100%);
      color: #fff;
      box-shadow: 0 2px 8px rgba(220,38,38,0.3);
    }
    .btn-outline {
      background: #fff;
      border: 1px solid #e2e8f0;
      color: #475569;
    }
    .btn-outline:hover { background: #f8fafc; }

    .overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.5); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 1000; }
    .overlay.open { display: flex; }
    .panel {
      width: 100%; max-width: 440px; background: #fff; border-radius: 16px;
      box-shadow: 0 24px 48px rgba(15,23,42,0.2); padding: 24px; border: 1px solid #e2e8f0;
    }
    .panel-title { margin: 0 0 8px; font-size: 1.1rem; font-weight: 600; color: #0f172a; }
    .panel-body label { display: block; font-size: 12px; font-weight: 600; margin: 12px 0 6px; color: #475569; }
    .panel-body textarea {
      width: 100%; min-height: 88px; padding: 12px 14px; border: 1px solid #e2e8f0; border-radius: 10px;
      font-size: 13px; resize: vertical; font-family: inherit;
    }
    .panel-body textarea:focus { outline: none; border-color: #6366f1; }
    .panel-footer { margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px; }

    .notice { padding: 12px 16px; border-radius: 12px; margin-bottom: 16px; font-size: 13px; font-weight: 500; }
    .notice.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .notice.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

    .status-badge {
      padding: 5px 10px; border-radius: 999px; font-size: 11px; font-weight: 600;
      display: inline-block;
    }
    .status-badge.status-pending { background: #fef3c7; color: #92400e; }
    .status-badge.status-clarification { background: #fed7aa; color: #9a3412; }
    .status-badge.status-submitted { background: #dbeafe; color: #1e40af; }
    .status-badge.status-rejected { background: #fee2e2; color: #991b1b; }
    .status-badge.status-other { background: #e0e7ff; color: #4338ca; }

    .empty-state {
      text-align: center; padding: 32px 24px; color: #94a3b8; font-size: 13px;
      background: #f8fafc; border-radius: 12px; margin-top: 12px; border: 1px dashed #e2e8f0;
    }
    .empty-state i { font-size: 28px; margin-bottom: 8px; opacity: 0.6; display: block; }

    .filter-links { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
    .filter-links a {
      padding: 8px 14px; border-radius: 10px; font-size: 13px; font-weight: 500;
      background: #f1f5f9; color: #475569; text-decoration: none; border: 1px solid #e2e8f0;
    }
    .filter-links a:hover { background: #e2e8f0; color: #334155; }
    .filter-links a.active { background: #6366f1; color: #fff; border-color: #6366f1; }

    .filters {
      display: flex;
      flex-wrap: wrap;
      gap: 10px 12px;
      align-items: flex-end;
      margin: 10px 0 14px;
    }
    .filters .field { display: flex; flex-direction: column; gap: 4px; }
    .filters label { font-size: 12px; font-weight: 600; color: #475569; }
    .filters input, .filters select {
      min-width: 170px;
      padding: 8px 10px;
      border-radius: 10px;
      border: 1px solid #e2e8f0;
      font-size: 13px;
      background: #fff;
      color: #0f172a;
    }
    .filters .actions { display:flex; gap:10px; align-items:center; }
    .btn-filter {
      padding: 8px 14px;
      border-radius: 10px;
      border: 1px solid #6366f1;
      background: #6366f1;
      color: #fff;
      font-weight: 700;
      cursor: pointer;
      font-size: 12px;
    }
    .btn-clear {
      padding: 8px 14px;
      border-radius: 10px;
      border: 1px solid #e2e8f0;
      background: #fff;
      color: #475569;
      font-weight: 700;
      text-decoration: none;
      font-size: 12px;
    }
    .btn-clear:hover { background:#f8fafc; }
  </style>
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
      <a href="{{ route('supervisor.profile') }}" style="color:inherit; text-decoration:none;">
        <i class="fa-regular fa-bell"></i> &nbsp; {{ Auth::user()->name ?? 'Supervisor' }}
      </a>
    </div>
  </header>
  <div class="container">
    @include('supervisor.layout.sidebar')
    <main>
      <div class="page-header">
        <div>
          <div class="breadcrumb">Supervisor · Attendance · Penalty Removal Requests</div>
          <h2 class="page-title">Penalty Removal Requests</h2>
          <p class="page-subtitle">Review penalty removal requests from your team. Approve to forward to admin for final decision, or reject with a reason.</p>
        </div>
      </div>

      @if(session('success'))
        <div class="notice success">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div class="notice error">{{ session('error') }}</div>
      @endif
      @if($errors->any())
        <div class="notice error">{{ $errors->first() }}</div>
      @endif

      <div class="card">
        <div class="summary-row">
          <a href="{{ route('supervisor.penalty_removal.index') }}" class="summary-chip">
            <span class="num">{{ $counts['pending'] + $counts['submitted'] + $counts['rejected'] }}</span>
            <span>Total</span>
          </a>
          <a href="{{ request()->fullUrlWithQuery(['status' => \App\Models\PenaltyRemovalRequest::STATUS_PENDING_SUPERVISOR]) }}" class="summary-chip {{ request('status') === \App\Models\PenaltyRemovalRequest::STATUS_PENDING_SUPERVISOR ? 'active' : '' }}">
            <span class="num">{{ $counts['pending'] }}</span>
            <span>Pending your approval</span>
          </a>
          <a href="{{ request()->fullUrlWithQuery(['status' => \App\Models\PenaltyRemovalRequest::STATUS_PENDING_ADMIN]) }}" class="summary-chip">
            <span class="num">{{ $counts['submitted'] }}</span>
            <span>Pending admin review</span>
          </a>
          <a href="{{ request()->fullUrlWithQuery(['status' => \App\Models\PenaltyRemovalRequest::STATUS_REJECTED_SUPERVISOR]) }}" class="summary-chip">
            <span class="num">{{ $counts['rejected'] }}</span>
            <span>Rejected by you</span>
          </a>
        </div>
      </div>

      <div class="card">
        <h3 class="section-title"><i class="fa-solid fa-list"></i> Requests</h3>
        <div class="filter-links">
          <a href="{{ route('supervisor.penalty_removal.index') }}" class="{{ !request('status') ? 'active' : '' }}">All</a>
          <a href="{{ request()->fullUrlWithQuery(['status' => \App\Models\PenaltyRemovalRequest::STATUS_PENDING_SUPERVISOR]) }}" class="{{ request('status') === \App\Models\PenaltyRemovalRequest::STATUS_PENDING_SUPERVISOR ? 'active' : '' }}">Pending</a>
          <a href="{{ request()->fullUrlWithQuery(['status' => \App\Models\PenaltyRemovalRequest::STATUS_PENDING_ADMIN]) }}" class="{{ request('status') === \App\Models\PenaltyRemovalRequest::STATUS_PENDING_ADMIN ? 'active' : '' }}">Pending admin review</a>
          <a href="{{ request()->fullUrlWithQuery(['status' => \App\Models\PenaltyRemovalRequest::STATUS_REJECTED_SUPERVISOR]) }}" class="{{ request('status') === \App\Models\PenaltyRemovalRequest::STATUS_REJECTED_SUPERVISOR ? 'active' : '' }}">Rejected</a>
        </div>

        <form method="GET" action="{{ route('supervisor.penalty_removal.index') }}" class="filters">
          <div class="field">
            <label for="q">Search (Name/ID)</label>
            <input id="q" name="q" value="{{ request('q') }}" placeholder="e.g. EMP007 or Sarah Lee">
          </div>
          <div class="field">
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
          <div class="field">
            <label for="reason">Reason</label>
            <input id="reason" name="reason" value="{{ request('reason') }}" placeholder="Search in appeal reason">
          </div>
          <div class="field">
            <label for="start">Start Date</label>
            <input id="start" type="date" name="start" value="{{ request('start') }}">
          </div>
          <div class="field">
            <label for="end">End Date</label>
            <input id="end" type="date" name="end" value="{{ request('end') }}">
          </div>
          <div class="actions">
            <button type="submit" class="btn-filter">Filter</button>
            <a class="btn-clear" href="{{ route('supervisor.penalty_removal.index', ['status' => request('status')]) }}">Clear</a>
          </div>
        </form>

        @if($requests->isEmpty())
          <div class="empty-state"><i class="fa-solid fa-inbox"></i> No penalty removal requests match the current filter.</div>
        @else
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Employee</th>
                  <th>Penalty</th>
                  <th>Date</th>
                  <th>Type</th>
                  <th>Reason (penalty)</th>
                  <th>Appeal reason</th>
                  <th>Attachment</th>
                  <th>Status</th>
                  <th>Your note</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($requests as $req)
                  @php
                    $penalty = $req->penalty;
                    $emp = $req->employee;
                    $dateSource = $penalty->assigned_at ?? $penalty->attendance?->date;
                    $attendanceDate = $dateSource ? \Carbon\Carbon::parse($dateSource)->format('Y-m-d') : '—';
                    $canAct = $req->status === \App\Models\PenaltyRemovalRequest::STATUS_PENDING_SUPERVISOR;
                    $legacyPendingAdmin = 'submitted_to_admin';
                    $statusClass = match($req->status) {
                      \App\Models\PenaltyRemovalRequest::STATUS_PENDING_SUPERVISOR => 'status-pending',
                      \App\Models\PenaltyRemovalRequest::STATUS_PENDING_ADMIN, $legacyPendingAdmin => 'status-submitted',
                      \App\Models\PenaltyRemovalRequest::STATUS_REJECTED_SUPERVISOR => 'status-rejected',
                      default => 'status-other',
                    };
                  @endphp
                  <tr>
                    <td>
                      <strong>{{ $emp->user->name ?? '—' }}</strong>
                      <div class="employee-meta">{{ $emp->department->department_name ?? '—' }}</div>
                    </td>
                    <td>P-{{ str_pad((string) $penalty->penalty_id, 4, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $attendanceDate }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $penalty->penalty_type ?? $penalty->penalty_name ?? '—')) }}</td>
                    <td><span class="reason-text">{{ $penalty->rejection_remark ?? '—' }}</span></td>
                    <td><span class="reason-text">{{ $req->request_reason ?? '—' }}</span></td>
                    <td>
                      @if($req->attachment_path)
                        <a href="{{ asset('storage/' . $req->attachment_path) }}" target="_blank" rel="noopener">View</a>
                      @else
                        —
                      @endif
                    </td>
                    @php
                      $statusText = match($req->status) {
                        \App\Models\PenaltyRemovalRequest::STATUS_PENDING_SUPERVISOR => 'Pending supervisor review',
                        \App\Models\PenaltyRemovalRequest::STATUS_PENDING_ADMIN, $legacyPendingAdmin => 'Pending admin review',
                        \App\Models\PenaltyRemovalRequest::STATUS_REJECTED_SUPERVISOR => 'Rejected by supervisor',
                        \App\Models\PenaltyRemovalRequest::STATUS_APPROVED_ADMIN => 'Approved by admin',
                        \App\Models\PenaltyRemovalRequest::STATUS_REJECTED_ADMIN => 'Rejected by admin',
                        \App\Models\PenaltyRemovalRequest::STATUS_CANCELLED_EMPLOYEE => 'Cancelled by employee',
                        default => ucfirst(str_replace('_', ' ', (string) $req->status)),
                      };
                    @endphp
                    <td><span class="status-badge {{ $statusClass }}">{{ $statusText }}</span></td>
                    <td><span class="reason-text">{{ $req->supervisor_note ?? '—' }}</span></td>
                    <td>
                      @if($canAct)
                        <button type="button" class="btn-sm btn-approve btn-approve-open" data-action="{{ route('supervisor.penalty_removal.approve', $req) }}" title="Approve & forward to admin"><i class="fa-solid fa-check"></i> Approve</button>
                        <button type="button" class="btn-sm btn-reject btn-reject-open" data-action="{{ route('supervisor.penalty_removal.reject', $req) }}" title="Reject (reason required)"><i class="fa-solid fa-times"></i> Reject</button>
                      @else
                        —
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div style="margin-top:16px;">
            {{ $requests->withQueryString()->links() }}
          </div>
        @endif
      </div>
    </main>
  </div>

  {{-- Approve modal (optional note) --}}
  <div id="approve-overlay" class="overlay">
    <div class="panel">
      <h4 class="panel-title">Approve & forward to admin</h4>
      <p style="margin:0 0 12px; font-size:13px; color:#64748b;">Optional note for admin:</p>
      <form id="approve-form" method="POST" action="">
        @csrf
        <div class="panel-body">
          <label for="approve-note">Supervisor note (optional)</label>
          <textarea id="approve-note" name="supervisor_note" rows="3" placeholder="e.g. Verified with team lead."></textarea>
        </div>
        <div class="panel-footer">
          <button type="button" class="btn-sm btn-outline" id="approve-cancel">Cancel</button>
          <button type="submit" class="btn-sm btn-approve">Approve & forward</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Reject modal (required reason, like leave) --}}
  <div id="reject-overlay" class="overlay">
    <div class="panel">
      <h4 class="panel-title">Reject request</h4>
      <p style="margin:0 0 12px; font-size:13px; color:#64748b;">Provide a reason. This will be visible to the employee.</p>
      <form id="reject-form" method="POST" action="">
        @csrf
        <div class="panel-body">
          <label for="reject-note">Reason (required) *</label>
          <textarea id="reject-note" name="supervisor_note" rows="3" required maxlength="500" placeholder="e.g. Supporting evidence not provided"></textarea>
        </div>
        <div class="panel-footer">
          <button type="button" class="btn-sm btn-outline" id="reject-cancel">Cancel</button>
          <button type="submit" class="btn-sm btn-reject">Reject</button>
        </div>
      </form>
    </div>
  </div>

  <script>
  (function() {
    var approveOverlay = document.getElementById('approve-overlay');
    var approveForm = document.getElementById('approve-form');
    var rejectOverlay = document.getElementById('reject-overlay');
    var rejectForm = document.getElementById('reject-form');

    document.querySelectorAll('.btn-approve-open').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var action = btn.getAttribute('data-action');
        if (action) {
          approveForm.action = action;
          approveForm.querySelector('#approve-note').value = '';
          approveOverlay.classList.add('open');
        }
      });
    });
    document.getElementById('approve-cancel').addEventListener('click', function() { approveOverlay.classList.remove('open'); });

    document.querySelectorAll('.btn-reject-open').forEach(function(btn) {
      btn.addEventListener('click', function() {
        rejectForm.action = btn.getAttribute('data-action');
        rejectForm.querySelector('#reject-note').value = '';
        rejectOverlay.classList.add('open');
      });
    });
    document.getElementById('reject-cancel').addEventListener('click', function() { rejectOverlay.classList.remove('open'); });
  })();
  </script>
</body>
</html>
