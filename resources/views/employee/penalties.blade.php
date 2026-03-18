<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Penalties - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    .penalty-layout main {
      padding: 20px 24px 32px;
    }
    .penalty-header {
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap:12px;
      margin-bottom:12px;
    }
    .penalty-header h2 {
      margin:0;
      font-size:1.4rem;
      color:#0f172a;
    }
    .penalty-header .subtitle {
      margin:4px 0 0;
      font-size:13px;
      color:#64748b;
    }
    .penalty-filters {
      display:flex;
      flex-wrap:wrap;
      gap:10px 14px;
      margin:0 0 14px;
      align-items:flex-end;
      font-size:13px;
    }
    .penalty-filters > div {
      display:flex;
      flex-direction:column;
      gap:4px;
    }
    .penalty-filters label {
      font-weight:500;
      color:#475569;
    }
    .penalty-filters input,
    .penalty-filters select {
      min-width:150px;
      padding:6px 8px;
      border-radius:8px;
      border:1px solid #cbd5f5;
      font-size:13px;
    }
    .penalty-summary-chips {
      display:flex;
      flex-wrap:wrap;
      gap:8px;
      margin-bottom:10px;
    }
    .penalty-chip {
      display:inline-flex;
      align-items:center;
      gap:6px;
      padding:4px 10px;
      border-radius:999px;
      background:#f1f5f9;
      font-size:12px;
      color:#475569;
    }
    .penalty-chip .num {
      font-weight:600;
      color:#0f172a;
    }
    .hr-table.penalty-table th,
    .hr-table.penalty-table td {
      font-size:13px;
      white-space:nowrap;
    }
    .hr-table.penalty-table td.reason-col {
      max-width:260px;
      white-space:normal;
    }
    .status-pill {
      display:inline-flex;
      align-items:center;
      padding:2px 10px;
      border-radius:999px;
      font-size:11px;
      font-weight:600;
    }
    .status-recorded { background:#e0f2fe; color:#0369a1; }
    .status-pending_employee_request { background:#fef3c7; color:#92400e; }
    .status-pending_supervisor_review { background:#e0f2fe; color:#1d4ed8; }
    .status-rejected_by_supervisor { background:#fee2e2; color:#b91c1c; }
    .status-pending_admin_review { background:#ede9fe; color:#5b21b6; }
    .status-approved_by_admin { background:#dcfce7; color:#166534; }
    .status-rejected_by_admin { background:#fee2e2; color:#b91c1c; }
    .status-cancelled { background:#e2e8f0; color:#475569; }
    .status-pill span.dot {
      width:6px;
      height:6px;
      border-radius:999px;
      background:currentColor;
      margin-right:6px;
    }
    .penalty-empty {
      text-align:center;
      padding:28px 16px;
      color:#94a3b8;
      font-size:14px;
    }
    .penalty-empty i {
      font-size:24px;
      display:block;
      margin-bottom:6px;
      color:#cbd5f5;
    }
    .btn-appeal {
      border-radius:999px;
      padding:4px 10px;
      font-size:11px;
      border:1px solid #0ea5e9;
      color:#0ea5e9;
      background:#ecfeff;
      cursor:pointer;
      display:inline-flex;
      align-items:center;
      gap:4px;
    }
    .btn-appeal[disabled] {
      opacity:.5;
      cursor:default;
    }
    .appeal-badge {
      font-size:11px;
      color:#64748b;
    }
    .penalty-group-note {
      font-size:12px;
      color:#64748b;
      margin-bottom:6px;
    }
    .penalty-group-note strong {
      color:#0f172a;
    }
    .btn-appeal.btn-disabled {
      opacity: .55;
      pointer-events: none;
      cursor: default;
    }
    .modal-overlay {
      position:fixed;
      inset:0;
      background:rgba(0,0,0,.45);
      display:none;
      align-items:center;
      justify-content:center;
      z-index:1200;
      padding:16px;
    }
    .modal-sheet {
      background:#fff;
      border-radius:14px;
      width:min(720px, 96vw);
      box-shadow:0 24px 70px rgba(15,23,42,.25);
      overflow:hidden;
    }
    .modal-head {
      padding:14px 18px;
      border-bottom:1px solid #e2e8f0;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
    }
    .modal-head h3 {
      margin:0;
      font-size:1.05rem;
      color:#0f172a;
    }
    .modal-close {
      border:0;
      background:transparent;
      font-size:18px;
      color:#64748b;
      cursor:pointer;
      padding:4px 8px;
    }
    .modal-body {
      padding:16px 18px;
    }
    .modal-grid {
      display:grid;
      grid-template-columns: 1fr 1fr;
      gap:12px;
      margin-bottom:12px;
    }
    .modal-kv {
      background:#f8fafc;
      border:1px solid #e2e8f0;
      border-radius:12px;
      padding:10px 12px;
      font-size:13px;
      color:#334155;
    }
    .modal-kv .k { color:#64748b; font-size:12px; }
    .modal-field { margin-bottom:10px; }
    .modal-field label { display:block; font-size:12px; color:#475569; margin-bottom:4px; font-weight:600; }
    .modal-field textarea, .modal-field input[type="file"] {
      width:100%;
      border:1px solid #cbd5f5;
      border-radius:12px;
      padding:10px;
      font-size:13px;
    }
    .modal-actions {
      display:flex;
      justify-content:flex-end;
      gap:10px;
      margin-top:14px;
    }
    .btn-ghost {
      border-radius:999px;
      padding:8px 14px;
      font-size:12px;
      border:1px solid #cbd5f5;
      background:#fff;
      color:#334155;
      cursor:pointer;
    }
    .btn-primary {
      border-radius:999px;
      padding:8px 14px;
      font-size:12px;
      border:1px solid #0ea5e9;
      background:#0ea5e9;
      color:#fff;
      cursor:pointer;
      font-weight:700;
    }
    .req-badge {
      display:inline-flex;
      align-items:center;
      gap:6px;
      padding:2px 10px;
      border-radius:999px;
      font-size:11px;
      font-weight:700;
      border:1px solid #e2e8f0;
      background:#fff;
      color:#334155;
    }
    .req-badge.pending { background:#fef3c7; border-color:#fde68a; color:#92400e; }
    .req-badge.clarify { background:#e0f2fe; border-color:#bae6fd; color:#075985; }
    .req-badge.submitted { background:#ede9fe; border-color:#ddd6fe; color:#5b21b6; }
    .req-badge.approved { background:#dcfce7; border-color:#bbf7d0; color:#166534; }
    .req-badge.rejected { background:#fee2e2; border-color:#fecaca; color:#b91c1c; }
  </style>
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
      <a href="{{ route('employee.profile') }}" style="color:inherit; text-decoration:none;">
        <i class="fa-regular fa-bell"></i> &nbsp; {{ Auth::user()->name ?? 'Employee' }}
      </a>
    </div>
  </header>
  <div class="container">
    @include('employee.layout.sidebar')
    <main class="penalty-layout">
      <div class="breadcrumb">Attendance · Penalty Removal</div>
      <div class="penalty-header">
        <div>
          <h2>My Penalties</h2>
          <p class="subtitle">All attendance-related penalties for your profile, including Late, Absent, and Early Leave. You can request removal for eligible items.</p>
        </div>
      </div>

      {{-- Small summary by status (recorded / approved / rejected) --}}
      @php
        $totalPenalties = $penalties->count();
        $approvedPenalties = $penalties->filter(function ($p) {
            return strtolower($p->status ?? '') === 'approved';
        })->count();
        $rejectedPenalties = $penalties->filter(function ($p) {
            return strtolower($p->status ?? '') === 'rejected';
        })->count();
        $recordedPenalties = $totalPenalties - $approvedPenalties - $rejectedPenalties;
        // Still group by date for the helper note below the filters.
        $byDate = $penalties->groupBy(function ($p) {
            $src = $p->assigned_at ?? $p->attendance?->date;
            return $src ? \Carbon\Carbon::parse($src)->format('Y-m-d') : 'unknown';
        });
      @endphp
      @if($totalPenalties > 0)
        <div class="penalty-summary-chips">
          <div class="penalty-chip">
            <span class="num">{{ $totalPenalties }}</span>
            <span>total penalties</span>
          </div>
          <div class="penalty-chip">
            <span class="num">{{ $recordedPenalties }}</span>
            <span>recorded</span>
          </div>
          <div class="penalty-chip">
            <span class="num">{{ $approvedPenalties }}</span>
            <span>approved</span>
          </div>
          <div class="penalty-chip">
            <span class="num">{{ $rejectedPenalties }}</span>
            <span>rejected</span>
          </div>
        </div>
      @endif

      {{-- Filter row --}}
      <div class="card" style="margin-bottom:12px;">
        <div class="penalty-filters">
          <div>
            <label for="filter-date-from">Date from</label>
            <input type="date" id="filter-date-from" name="date_from">
          </div>
          <div>
            <label for="filter-date-to">Date to</label>
            <input type="date" id="filter-date-to" name="date_to">
          </div>
          <div>
            <label for="filter-type">Penalty type</label>
            <select id="filter-type" name="type">
              <option value="">All</option>
              <option value="late">Late</option>
              <option value="absent">Absent</option>
              <option value="early_leave">Early Leave</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div>
            <label for="filter-status">Status</label>
            <select id="filter-status" name="status">
              <option value="">All</option>
              <option value="recorded">Recorded</option>
              <option value="pending_employee_request">Pending Employee Request</option>
              <option value="pending_supervisor_review">Pending Supervisor Review</option>
              <option value="rejected_by_supervisor">Rejected by Supervisor</option>
              <option value="pending_admin_review">Pending Admin Review</option>
              <option value="approved_by_admin">Approved by Admin</option>
              <option value="rejected_by_admin">Rejected by Admin</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
        </div>
      </div>

      {{-- Removal request records (like Leave → Recent Requests) --}}
      <div class="card" style="margin-bottom:12px;">
        <div class="card-title" style="margin-bottom:10px; font-weight:700; color:#0f172a;">Removal Requests</div>
        <div class="table-wrap" style="overflow-x:auto;">
          <table style="width:100%; border-collapse:collapse;">
            <thead style="background:#0f172a; color:#0ea5e9;">
              <tr>
                <th style="padding:12px 14px; border-bottom:1px solid #e5e7eb; text-align:left;">Penalty</th>
                <th style="padding:12px 14px; border-bottom:1px solid #e5e7eb; text-align:left;">Date</th>
                <th style="padding:12px 14px; border-bottom:1px solid #e5e7eb; text-align:left;">Type</th>
                <th style="padding:12px 14px; border-bottom:1px solid #e5e7eb; text-align:left;">Status</th>
                <th style="padding:12px 14px; border-bottom:1px solid #e5e7eb; text-align:left;">Submitted</th>
                <th style="padding:12px 14px; border-bottom:1px solid #e5e7eb; text-align:left;">Action</th>
              </tr>
            </thead>
            <tbody>
              @php
                $requests = $removalRequests ?? collect();
              @endphp
              @forelse($requests as $r)
                @php
                  $pen = $r->penalty;
                  $dateSource = $pen?->assigned_at ?? $pen?->attendance?->date;
                  $pDate = $dateSource ? \Carbon\Carbon::parse($dateSource)->format('Y-m-d') : '—';
                  $pType = $pen?->penalty_type
                    ? ucfirst(str_replace('_', ' ', $pen->penalty_type))
                    : ucfirst(str_replace('_', ' ', (string) ($pen?->penalty_name ?? '—')));
                  $statusLabel = match ($r->status) {
                    \App\Models\PenaltyRemovalRequest::STATUS_PENDING_SUPERVISOR => 'Pending (with supervisor)',
                    \App\Models\PenaltyRemovalRequest::STATUS_PENDING_ADMIN => 'Pending admin review',
                    \App\Models\PenaltyRemovalRequest::STATUS_APPROVED_ADMIN => 'Approved',
                    \App\Models\PenaltyRemovalRequest::STATUS_REJECTED_SUPERVISOR => 'Rejected (by supervisor)',
                    \App\Models\PenaltyRemovalRequest::STATUS_REJECTED_ADMIN => 'Rejected (by admin)',
                    \App\Models\PenaltyRemovalRequest::STATUS_CANCELLED_EMPLOYEE => 'Cancelled',
                    default => ucfirst(str_replace('_', ' ', (string) $r->status)),
                  };
                  $statusClass = match ($r->status) {
                    \App\Models\PenaltyRemovalRequest::STATUS_PENDING_SUPERVISOR, \App\Models\PenaltyRemovalRequest::STATUS_PENDING_ADMIN => 'pending',
                    \App\Models\PenaltyRemovalRequest::STATUS_APPROVED_ADMIN => 'approved',
                    \App\Models\PenaltyRemovalRequest::STATUS_REJECTED_SUPERVISOR, \App\Models\PenaltyRemovalRequest::STATUS_REJECTED_ADMIN, \App\Models\PenaltyRemovalRequest::STATUS_CANCELLED_EMPLOYEE => 'rejected',
                    default => 'pending',
                  };
                  $canCancel = $r->status === \App\Models\PenaltyRemovalRequest::STATUS_PENDING_SUPERVISOR;
                @endphp
                <tr>
                  <td style="padding:12px 14px; border-bottom:1px solid #e5e7eb;">P-{{ str_pad((string) $r->penalty_id, 4, '0', STR_PAD_LEFT) }}</td>
                  <td style="padding:12px 14px; border-bottom:1px solid #e5e7eb;">{{ $pDate }}</td>
                  <td style="padding:12px 14px; border-bottom:1px solid #e5e7eb;">{{ $pType }}</td>
                  <td style="padding:12px 14px; border-bottom:1px solid #e5e7eb;">
                    <span class="status {{ $statusClass }}">{{ $statusLabel }}</span>
                  </td>
                  <td style="padding:12px 14px; border-bottom:1px solid #e5e7eb;">{{ $r->submitted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                  <td style="padding:12px 14px; border-bottom:1px solid #e5e7eb;">
                    @if($canCancel)
                      <form method="POST" action="{{ route('employee.penalties.removal_request.cancel', $r) }}" onsubmit="return confirm('Cancel this pending removal request?');">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-small" style="background:#ef4444;border-color:#ef4444;">Cancel</button>
                      </form>
                    @else
                      <span class="muted" style="color:#94a3b8;">—</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" style="text-align:center; color:#94a3b8; padding:14px;">No penalty removal requests yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        @if($totalPenalties === 0)
          <div class="penalty-empty">
            <i class="fa-regular fa-clipboard"></i>
            No penalties found.
          </div>
        @else
          @if($byDate->count() > 0)
            <div class="penalty-group-note">
              Showing individual penalty records. Some dates may have more than one penalty (for example: Late and Early Leave on the same day).
            </div>
          @endif
          <table class="hr-table penalty-table" id="penalty-table">
            <thead>
              <tr>
                <th>Penalty ID</th>
                <th>Attendance Date</th>
                <th>Penalty Type</th>
                <th class="reason-col">Reason</th>
                <th>Status</th>
                <th>Supervisor Review Status</th>
                <th>Admin Approval Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @foreach($penalties as $p)
                @php
                  // Show date: prefer assigned_at (set for all penalties), then attendance date.
                  $dateSource = $p->assigned_at ?? $p->attendance?->date;
                  $attendanceDate = $dateSource ? \Carbon\Carbon::parse($dateSource)->format('Y-m-d') : null;
                  // Try to infer a simple penalty type from name; this will be replaced by a real column later.
                  $rawType = strtolower($p->penalty_name ?? '');
                  if (str_contains($rawType, 'late')) {
                      $penaltyType = 'late';
                  } elseif (str_contains($rawType, 'absent')) {
                      $penaltyType = 'absent';
                  } elseif (str_contains($rawType, 'early')) {
                      $penaltyType = 'early_leave';
                  } else {
                      $penaltyType = 'other';
                  }
                  // Map legacy status to extended flow; this will be replaced by dedicated columns later.
                  $baseStatus = strtolower($p->status ?? 'pending');
                  $logicalStatus = match ($baseStatus) {
                      'approved' => 'approved_by_admin',
                      'rejected' => 'rejected_by_admin',
                      default     => 'recorded',
                  };
                  $supervisorStatus = null; // placeholder for future supervisor flow
                  $adminStatus = $logicalStatus;
                  $canRequestRemoval = in_array($logicalStatus, ['recorded', 'pending_employee_request', 'pending_supervisor_review'], true);
                  $statusClass = 'status-' . str_replace(' ', '_', $logicalStatus);
                  $req = $p->activeRemovalRequest;
                  $reqStatus = $req?->status;
                  $hasActiveReq = (bool) $req;
                @endphp
                <tr
                  data-date="{{ $attendanceDate ?? '' }}"
                  data-type="{{ $penaltyType }}"
                  data-status="{{ $logicalStatus }}"
                >
                  <td>P-{{ str_pad((string) $p->penalty_id, 4, '0', STR_PAD_LEFT) }}</td>
                  <td>{{ $attendanceDate ?? '—' }}</td>
                  <td>{{ ucfirst(str_replace('_', ' ', $penaltyType)) }}</td>
                  <td class="reason-col">{{ $p->penalty_name }}</td>
                  <td>
                    <span class="status-pill {{ $statusClass }}">
                      <span class="dot"></span>
                      {{ ucfirst(str_replace('_', ' ', $logicalStatus)) }}
                    </span>
                  </td>
                  <td>
                    @if($supervisorStatus)
                      <span class="appeal-badge">{{ ucfirst(str_replace('_', ' ', $supervisorStatus)) }}</span>
                    @else
                      <span class="appeal-badge">—</span>
                    @endif
                  </td>
                  <td>
                    <span class="appeal-badge">
                      {{ ucfirst(str_replace('_', ' ', $adminStatus)) }}
                    </span>
                  </td>
                  <td>
                    @if($hasActiveReq)
                      @php
                        $legacyPendingAdmin = 'submitted_to_admin';
                        $label = match ($reqStatus) {
                          \App\Models\PenaltyRemovalRequest::STATUS_PENDING_SUPERVISOR => 'Pending supervisor review',
                          \App\Models\PenaltyRemovalRequest::STATUS_PENDING_ADMIN, $legacyPendingAdmin => 'Pending admin review',
                          \App\Models\PenaltyRemovalRequest::STATUS_APPROVED_ADMIN => 'Approved by admin',
                          \App\Models\PenaltyRemovalRequest::STATUS_REJECTED_SUPERVISOR, \App\Models\PenaltyRemovalRequest::STATUS_REJECTED_ADMIN => 'Rejected',
                          default => ucfirst(str_replace('_', ' ', (string) $reqStatus)),
                        };
                        $cls = match (true) {
                          in_array($reqStatus, [\App\Models\PenaltyRemovalRequest::STATUS_PENDING_SUPERVISOR], true) => 'pending',
                          in_array($reqStatus, [\App\Models\PenaltyRemovalRequest::STATUS_PENDING_ADMIN, $legacyPendingAdmin], true) => 'submitted',
                          in_array($reqStatus, [\App\Models\PenaltyRemovalRequest::STATUS_APPROVED_ADMIN], true) => 'approved',
                          in_array($reqStatus, [\App\Models\PenaltyRemovalRequest::STATUS_REJECTED_SUPERVISOR, \App\Models\PenaltyRemovalRequest::STATUS_REJECTED_ADMIN], true) => 'rejected',
                          default => '',
                        };
                      @endphp
                      <span class="req-badge {{ $cls }}">{{ $label }}</span>
                    @elseif($canRequestRemoval)
                      <button
                        type="button"
                        class="btn-appeal js-open-removal"
                        data-penalty-id="{{ $p->penalty_id }}"
                        data-penalty-code="P-{{ str_pad((string) $p->penalty_id, 4, '0', STR_PAD_LEFT) }}"
                        data-date="{{ $attendanceDate ?? '—' }}"
                        data-type="{{ ucfirst(str_replace('_', ' ', $penaltyType)) }}"
                        data-reason="{{ e($p->penalty_name) }}"
                      >
                        <i class="fa-regular fa-paper-plane"></i> Request removal
                      </button>
                    @else
                      <span class="appeal-badge">Not eligible</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
          <div class="penalty-empty" id="penalty-empty-filter" style="display:none;">
            <i class="fa-regular fa-clipboard"></i>
            No penalties found for the selected filters.
          </div>
        @endif
      </div>
      @if($totalPenalties > 0)
        <script>
          document.addEventListener('DOMContentLoaded', function () {
            const table = document.getElementById('penalty-table');
            if (!table) return;
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            const dateFrom = document.getElementById('filter-date-from');
            const dateTo = document.getElementById('filter-date-to');
            const typeSel = document.getElementById('filter-type');
            const statusSel = document.getElementById('filter-status');
            const emptyFilter = document.getElementById('penalty-empty-filter');

            function applyFilters() {
              const fromVal = dateFrom && dateFrom.value ? dateFrom.value : null;
              const toVal = dateTo && dateTo.value ? dateTo.value : null;
              const typeVal = typeSel ? (typeSel.value || '') : '';
              const statusVal = statusSel ? (statusSel.value || '') : '';
              let visibleCount = 0;

              rows.forEach(tr => {
                const rowDate = tr.getAttribute('data-date') || '';
                const rowType = tr.getAttribute('data-type') || '';
                const rowStatus = tr.getAttribute('data-status') || '';

                let show = true;
                if (fromVal && rowDate && rowDate < fromVal) show = false;
                if (toVal && rowDate && rowDate > toVal) show = false;
                if (typeVal && rowType !== typeVal) show = false;
                if (statusVal && rowStatus !== statusVal) show = false;

                tr.style.display = show ? '' : 'none';
                if (show) visibleCount++;
              });

              if (emptyFilter) {
                emptyFilter.style.display = visibleCount === 0 ? 'block' : 'none';
              }
            }

            if (dateFrom) dateFrom.addEventListener('change', applyFilters);
            if (dateTo) dateTo.addEventListener('change', applyFilters);
            if (typeSel) typeSel.addEventListener('change', applyFilters);
            if (statusSel) statusSel.addEventListener('change', applyFilters);

            // Removal request modal (employee)
            const modal = document.getElementById('removal-modal');
            const closeBtn = document.getElementById('removal-close');
            const cancelBtn = document.getElementById('removal-cancel');
            const form = document.getElementById('removal-form');
            const pid = document.getElementById('removal-penalty-id');
            const head = document.getElementById('removal-head');
            const kvDate = document.getElementById('removal-kv-date');
            const kvType = document.getElementById('removal-kv-type');
            const kvReason = document.getElementById('removal-kv-reason');

            function openModal(btn) {
              if (!modal || !form) return;
              const penaltyId = btn.getAttribute('data-penalty-id');
              const code = btn.getAttribute('data-penalty-code');
              const date = btn.getAttribute('data-date');
              const type = btn.getAttribute('data-type');
              const reason = btn.getAttribute('data-reason');
              if (pid) pid.value = penaltyId || '';
              if (head) head.textContent = `Request removal — ${code || ''}`;
              if (kvDate) kvDate.textContent = date || '—';
              if (kvType) kvType.textContent = type || '—';
              if (kvReason) kvReason.textContent = reason || '—';
              form.setAttribute('action', `{{ url('/employee/penalties') }}/${penaltyId}/removal-request`);
              modal.style.display = 'flex';
            }
            function closeModal() {
              if (!modal) return;
              modal.style.display = 'none';
            }
            document.querySelectorAll('.js-open-removal').forEach(btn => {
              btn.addEventListener('click', () => openModal(btn));
            });
            if (closeBtn) closeBtn.addEventListener('click', closeModal);
            if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
            if (modal) {
              modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
              });
            }
          });
        </script>
      @endif

      {{-- Removal request modal --}}
      <div class="modal-overlay" id="removal-modal" aria-hidden="true">
        <div class="modal-sheet" role="dialog" aria-modal="true">
          <div class="modal-head">
            <h3 id="removal-head">Request removal</h3>
            <button type="button" class="modal-close" id="removal-close" aria-label="Close">×</button>
          </div>
          <div class="modal-body">
            <div class="modal-grid">
              <div class="modal-kv"><div class="k">Date</div><div id="removal-kv-date">—</div></div>
              <div class="modal-kv"><div class="k">Penalty type</div><div id="removal-kv-type">—</div></div>
              <div class="modal-kv" style="grid-column:1 / -1;"><div class="k">Penalty reason</div><div id="removal-kv-reason">—</div></div>
            </div>

            <form id="removal-form" method="POST" enctype="multipart/form-data">
              @csrf
              <input type="hidden" id="removal-penalty-id" name="penalty_id" value="">

              <div class="modal-field">
                <label for="request_reason">Appeal reason / explanation</label>
                <textarea id="request_reason" name="request_reason" required minlength="10" maxlength="2000" placeholder="Explain why this penalty should be removed (min 10 characters)."></textarea>
              </div>

              <div class="modal-field">
                <label for="attachment">Optional attachment / proof</label>
                <input id="attachment" name="attachment" type="file" accept=".jpg,.jpeg,.png,.pdf">
              </div>

              <div class="modal-actions">
                <button type="button" class="btn-ghost" id="removal-cancel">Cancel</button>
                <button type="submit" class="btn-primary"><i class="fa-regular fa-paper-plane"></i> Submit request</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <footer>© 2025 Web-Based HRMS. All Rights Reserved.</footer>
    </main>
  </div>
</body>
</html>

