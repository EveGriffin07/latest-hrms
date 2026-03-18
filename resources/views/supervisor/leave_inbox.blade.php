<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Team Leave Approvals - HRMS</title>
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
    .summary-chip .num {
      font-weight: 700;
      font-size: 1.15rem;
      color: #0f172a;
    }

    /* Tables */
    .table-wrap { overflow-x: auto; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 16px; }
    .leave-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
      background: #fff;
    }
    .leave-table thead th {
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
    .leave-table thead th:first-child { border-radius: 12px 0 0 0; }
    .leave-table thead th:last-child { border-radius: 0 12px 0 0; }
    .leave-table tbody tr {
      transition: background 0.15s ease;
      border-bottom: 1px solid #f1f5f9;
    }
    .leave-table tbody tr:hover { background: #f8fafc; }
    .leave-table tbody tr:last-child { border-bottom: none; }
    .leave-table tbody td {
      padding: 14px 16px;
      vertical-align: middle;
      color: #334155;
    }
    .leave-table tbody td strong { display: block; font-weight: 600; color: #0f172a; margin-bottom: 2px; }
    .employee-meta { font-size: 11px; color: #94a3b8; font-weight: 500; }
    .reason-text { color: #64748b; max-width: 280px; font-size: 12px; }

    .btn-sm {
      padding: 8px 14px;
      font-size: 12px;
      font-weight: 600;
      border-radius: 10px;
      border: none;
      cursor: pointer;
      margin: 0 4px 0 0;
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
    .btn-approve:hover { box-shadow: 0 4px 12px rgba(22,163,74,0.4); }
    .btn-reject {
      background: linear-gradient(180deg, #ef4444 0%, #dc2626 100%);
      color: #fff;
      box-shadow: 0 2px 8px rgba(220,38,38,0.3);
    }
    .btn-reject:hover { box-shadow: 0 4px 12px rgba(220,38,38,0.35); }
    .btn-outline {
      background: #fff;
      border: 1px solid #e2e8f0;
      color: #475569;
    }
    .btn-outline:hover { background: #f8fafc; }

    .section-desc { margin: 0 0 12px; color: #64748b; font-size: 13px; line-height: 1.5; }
    .overlay {
      position: fixed; inset: 0; background: rgba(15,23,42,0.5); backdrop-filter: blur(4px);
      display: none; align-items: center; justify-content: center; z-index: 1000;
    }
    .overlay.open { display: flex; }
    .panel {
      width: 100%; max-width: 420px; background: #fff; border-radius: 16px;
      box-shadow: 0 24px 48px rgba(15,23,42,0.2); padding: 24px; border: 1px solid #e2e8f0;
    }
    .panel-title { margin: 0 0 8px; font-size: 1.1rem; font-weight: 600; color: #0f172a; }
    .panel-body label { display: block; font-size: 12px; font-weight: 600; margin: 12px 0 6px; color: #475569; }
    .panel-body textarea {
      width: 100%; min-height: 88px; padding: 12px 14px; border: 1px solid #e2e8f0; border-radius: 10px;
      font-size: 13px; resize: vertical; font-family: inherit; transition: border-color 0.15s;
    }
    .panel-body textarea:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }
    .panel-footer { margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px; }

    .notice { padding: 12px 16px; border-radius: 12px; margin-bottom: 16px; font-size: 13px; font-weight: 500; }
    .notice.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .notice.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

    .status-badge {
      padding: 5px 10px; border-radius: 999px; font-size: 11px; font-weight: 600;
      display: inline-block;
    }
    .status-badge.status-approved { background: #dcfce7; color: #166534; }
    .status-badge.status-pending { background: #fef3c7; color: #92400e; }
    .status-badge.status-rejected { background: #fee2e2; color: #991b1b; }
    .status-badge.status-other { background: #e0e7ff; color: #4338ca; }

    .empty-state {
      text-align: center; padding: 32px 24px; color: #94a3b8; font-size: 13px;
      background: #f8fafc; border-radius: 12px; margin-top: 12px; border: 1px dashed #e2e8f0;
    }
    .empty-state i { font-size: 28px; margin-bottom: 8px; opacity: 0.6; display: block; }
    footer { margin-top: 28px; color: #94a3b8; font-size: 12px; }

    .employee-balance-trigger {
      background: none; border: none; padding: 0; text-align: left; cursor: pointer; font-family: inherit; width: 100%;
    }
    .employee-balance-trigger:hover { text-decoration: underline; color: #6366f1; }
    .employee-balance-trigger strong { font-weight: 600; }
    .balance-card-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.5); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 1001; }
    .balance-card-overlay.open { display: flex; }
    .balance-card {
      background: #fff; border-radius: 16px; padding: 28px; width: 94%; max-width: 720px;
      box-shadow: 0 24px 48px rgba(15,23,42,0.2); border: 1px solid #e2e8f0;
      aspect-ratio: 4 / 3; display: flex; flex-direction: column; overflow: hidden;
    }
    .balance-card-header { flex-shrink: 0; margin-bottom: 12px; }
    .balance-card h4 { margin: 0 0 4px; font-size: 1.1rem; color: #0f172a; }
    .balance-card .balance-code { font-size: 12px; color: #64748b; }
    .balance-card-body { flex: 1; overflow-y: auto; min-height: 0; }
    .balance-card-loading { color: #64748b; padding: 16px 0; }
    .balance-card-error { color: #b91c1c; padding: 16px 0; font-size: 13px; }
    .balance-type-cards { display: flex; flex-direction: column; gap: 10px; }
    .balance-type-card {
      background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px; font-size: 13px;
    }
    .balance-type-card .bal-type-name { font-weight: 600; color: #0f172a; margin-bottom: 8px; }
    .balance-type-card .bal-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
    .balance-type-card .bal-row:last-child { margin-bottom: 0; }
    .balance-type-card .bal-remaining { font-weight: 600; color: #166534; }
    .balance-card-close { flex-shrink: 0; margin-top: 12px; padding: 8px 16px; border-radius: 10px; border: 1px solid #e2e8f0; background: #fff; cursor: pointer; font-size: 13px; }
    .balance-card-close:hover { background: #f8fafc; }

    .decision-toggle { position: relative; display: block; margin-bottom: 16px; min-height: 48px; }
    .decision-toggle .btn-decision { position: absolute; top: 50%; transform: translate(-50%, -50%); width: 25%; box-sizing: border-box; padding: 12px 16px; border-radius: 12px; font-size: 15px; font-weight: 600; cursor: pointer; border: 2px solid #cbd5e1; background: #e2e8f0; color: #475569; font-family: inherit; text-align: center; }
    .decision-toggle .btn-decision[data-mode="quick"] { left: 25%; }
    .decision-toggle .btn-decision[data-mode="normal"] { left: 75%; }
    .decision-toggle .btn-decision:hover { background: #cbd5e1; border-color: #94a3b8; color: #334155; }
    .decision-toggle .btn-decision.active { background: #6366f1; color: #fff; border-color: #6366f1; }
    .leave-table tbody tr.tr-coming-7 { background: #fef9c3; }
    .leave-table tbody tr.tr-coming-7:hover { background: #fef3c7; }
    .leave-table tbody tr.pending-row-hidden { display: none; }
    .leave-table input[type="checkbox"].pending-row-cb,
    .leave-table thead input[type="checkbox"]#supervisor-select-all { transform: scale(1.6); cursor: pointer; accent-color: #6366f1; }
    .badge-within-7 { display: inline-block; margin-left: 6px; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 600; background: #f59e0b; color: #fff; }
    .bulk-actions { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
    .bulk-actions .btn-bulk { padding: 8px 16px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; font-family: inherit; }
    .bulk-actions .btn-bulk:disabled { opacity: 0.5; cursor: not-allowed; }
    .bulk-actions .btn-bulk-approve { background: #16a34a; color: #fff; }
    .bulk-actions .btn-bulk-reject { background: #dc2626; color: #fff; }
    .bulk-actions .selected-count { font-size: 13px; color: #64748b; }

    /* Reject modal (match admin) */
    .reject-modal-sheet {
      background: #fff; border-radius: 12px; padding: 20px 24px; max-width: 520px; width: 92%;
      box-shadow: 0 20px 45px rgba(15,23,42,0.18); border: 1px solid #e2e8f0;
    }
    .reject-modal-title { margin: 0 0 8px; font-size: 1.15rem; font-weight: 700; color: #0f172a; }
    .reject-modal-desc { margin: 0 0 12px; font-size: 14px; color: #64748b; }
    .reject-quick-wrap { margin-bottom: 12px; }
    .reject-quick-label { font-size: 0.9rem; font-weight: 600; color: #0f172a; margin-bottom: 8px; }
    .reject-quick-btns { display: flex; flex-wrap: wrap; gap: 8px; }
    .reject-chip {
      padding: 8px 14px; border-radius: 999px; font-size: 13px; font-weight: 500; cursor: pointer;
      background: #fef9c3; color: #854d0e; border: 1px solid #fde68a; font-family: inherit;
      transition: background 0.15s, border-color 0.15s;
    }
    .reject-chip:hover { background: #fef3c7; border-color: #fcd34d; }
    .reject-quick-actions { margin-top: 8px; }
    .reject-reason-textarea {
      width: 100%; min-height: 110px; padding: 12px 14px; border: 1px solid #d1d5db; border-radius: 10px;
      font-size: 13px; font-family: inherit; resize: vertical; display: block; margin-bottom: 4px;
    }
    .reject-reason-textarea:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,0.2); }
    .reject-error-msg { color: #b91c1c; font-size: 13px; margin-top: 6px; min-height: 18px; }
    .reject-modal-footer { margin-top: 14px; display: flex; justify-content: flex-end; gap: 10px; }
    .reject-modal-footer .btn-reject:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
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
          <div class="breadcrumb">Supervisor · Team Leave Approvals</div>
          <h2 class="page-title">Team Leave Approvals</h2>
          <p class="page-subtitle">Approve or reject leave requests from your team. Approved requests are sent directly to admin for final approval.</p>
        </div>
      </div>

      @if(session('success'))
        <div class="notice success">{{ session('success') }}</div>
      @endif
      @if($errors->any())
        <div class="notice error">{{ $errors->first() }}</div>
      @endif

      <div class="card">
        <div class="summary-row">
          <span class="summary-chip">
            <span class="num">{{ $totalCount }}</span>
            <span>Total</span>
          </span>
          <span class="summary-chip">
            <span class="num" id="supervisor-pending-count">{{ $pendingAtSupervisor->count() }}</span>
            <span>Pending your approval</span>
          </span>
          <span class="summary-chip">
            <span class="num">{{ $approvedCount }}</span>
            <span>Approved</span>
          </span>
          <span class="summary-chip">
            <span class="num">{{ $rejectedCount }}</span>
            <span>Rejected</span>
          </span>
        </div>
      </div>

      {{-- Pending at supervisor --}}
      <div class="card">
        <h3 class="section-title"><i class="fa-solid fa-inbox"></i> Pending your approval</h3>
        @if($pendingAtSupervisor->isEmpty())
          <div class="empty-state"><i class="fa-solid fa-inbox"></i> No leave requests pending your approval.</div>
        @else
          @php
            $today = \Illuminate\Support\Carbon::today();
            $endWindow = $today->copy()->addDays(7);
          @endphp
          <div class="decision-toggle">
            <button type="button" class="btn-decision active" data-mode="quick" aria-pressed="true">Quick decision</button>
            <button type="button" class="btn-decision" data-mode="normal" aria-pressed="false">Normal decision</button>
          </div>
          <div class="bulk-actions" id="supervisor-bulk-actions">
            <span class="selected-count" id="supervisor-selected-count">0 selected</span>
            <button type="button" class="btn-bulk btn-bulk-approve" id="supervisor-bulk-approve" disabled>Approve selected</button>
            <button type="button" class="btn-bulk btn-bulk-reject" id="supervisor-bulk-reject" disabled>Reject selected</button>
          </div>
          <div class="table-wrap">
            <table class="leave-table">
              <thead>
                <tr>
                  <th style="width:42px"><input type="checkbox" id="supervisor-select-all" title="Select all" aria-label="Select all"></th>
                  <th>Employee</th>
                  <th>Department</th>
                  <th>Type</th>
                  <th>Period</th>
                  <th>Days</th>
                  <th>Reason</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($pendingAtSupervisor as $req)
                  @php
                    $start = $req->start_date->copy()->startOfDay();
                    $end = $req->end_date->copy()->startOfDay();
                    $isComing7 = $start->lte($endWindow) && $end->gte($today);
                  @endphp
                  <tr class="pending-row {{ $isComing7 ? 'tr-coming-7' : '' }}" data-coming-7-days="{{ $isComing7 ? '1' : '0' }}" data-leave-id="{{ $req->leave_request_id }}">
                    <td><input type="checkbox" class="pending-row-cb" value="{{ $req->leave_request_id }}" aria-label="Select row"></td>
                    <td>
                      <button type="button" class="employee-balance-trigger" data-employee-id="{{ $req->employee->employee_id }}" data-employee-name="{{ $req->employee->user->name ?? '—' }}" data-employee-code="{{ $req->employee->employee_code ?? '' }}" title="View leave balance">
                        <strong>{{ $req->employee->user->name ?? '—' }}</strong>
                        <span class="employee-meta">{{ $req->employee->employee_code ?? '' }}</span>
                      </button>
                    </td>
                    <td>{{ $req->employee->department->department_name ?? '—' }}</td>
                    <td>{{ $req->leaveType->leave_name ?? '—' }}</td>
                    <td>
                      {{ $req->start_date->format('Y-m-d') }} to {{ $req->end_date->format('Y-m-d') }}
                      @if($isComing7)<span class="badge-within-7" title="Within 7 days">Within 7 days</span>@endif
                    </td>
                    <td>{{ $req->total_days }}</td>
                    <td class="reason-text">{{ Str::limit($req->reason ?? '—', 40) }}</td>
                    <td>
                      <form method="POST" class="supervisor-approve-form" data-leave-id="{{ $req->leave_request_id }}" action="{{ route('supervisor.leave.approve', $req) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn-sm btn-approve"><i class="fa-solid fa-check"></i> Approve</button>
                      </form>
                      <button type="button" class="btn-sm btn-reject" data-id="{{ $req->leave_request_id }}" data-employee="{{ $req->employee->user->name ?? 'Employee' }}">
                        <i class="fa-solid fa-times"></i> Reject
                      </button>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>

      {{-- All leave you approved or rejected --}}
      <div class="card">
        <h3 class="section-title"><i class="fa-solid fa-clipboard-list"></i> Leave you approved or rejected</h3>
        @if($actedByMe->isEmpty())
          <div class="empty-state"><i class="fa-solid fa-clipboard-check"></i> No leave approved or rejected by you yet.</div>
        @else
          <div class="table-wrap">
            <table class="leave-table">
              <thead>
                <tr>
                  <th>Employee</th>
                  <th>Department</th>
                  <th>Type</th>
                  <th>Period</th>
                  <th>Days</th>
                  <th>Your action</th>
                </tr>
              </thead>
              <tbody>
                @foreach($actedByMe as $req)
                  <tr>
                    <td>
                      <strong>{{ $req->employee->user->name ?? '—' }}</strong>
                      <span class="employee-meta">{{ $req->employee->employee_code ?? '' }}</span>
                    </td>
                    <td>{{ $req->employee->department->department_name ?? '—' }}</td>
                    <td>{{ $req->leaveType->leave_name ?? '—' }}</td>
                    <td>{{ $req->start_date->format('Y-m-d') }} to {{ $req->end_date->format('Y-m-d') }}</td>
                    <td>{{ $req->total_days }}</td>
                    <td>
                      @if($req->leave_status === \App\Models\LeaveRequest::STATUS_REJECTED)
                        <span class="status-badge status-rejected">Rejected by you</span>
                        @if($req->reject_reason)
                          <div class="employee-meta" style="margin-top:4px;">{{ Str::limit($req->reject_reason, 40) }}</div>
                        @endif
                      @elseif($req->leave_status === \App\Models\LeaveRequest::STATUS_APPROVED)
                        <span class="status-badge status-approved">Approved → Approved by admin</span>
                      @elseif($req->leave_status === \App\Models\LeaveRequest::STATUS_PENDING_ADMIN)
                        <span class="status-badge status-pending">Approved → With admin</span>
                      @else
                        <span class="status-badge status-other">Approved → With admin</span>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>

      <footer>© {{ date('Y') }} Web-Based HRMS.</footer>
    </main>
  </div>

  {{-- Leave balance pop-out card (4:3 ratio, card form) --}}
  <div class="balance-card-overlay" id="balance-card-overlay">
    <div class="balance-card">
      <div class="balance-card-header">
        <h4 id="balance-card-name">—</h4>
        <div class="balance-code" id="balance-card-code">—</div>
      </div>
      <div class="balance-card-body" id="balance-card-body">
        <div class="balance-card-loading" id="balance-card-loading">Loading leave balance…</div>
        <div class="balance-card-error" id="balance-card-error" style="display:none;"></div>
        <div class="balance-type-cards" id="balance-type-cards" style="display:none;"></div>
      </div>
      <button type="button" class="balance-card-close" id="balance-card-close">Close</button>
    </div>
  </div>

  {{-- Reject modal (same structure as admin reject) --}}
  <div class="overlay" id="reject-overlay">
    <div class="reject-modal-sheet">
      <h3 class="reject-modal-title">Reject Leave</h3>
      <p class="reject-modal-desc">Provide a reason. This will be visible to the employee.</p>
      <form id="reject-form" method="POST" action="">
        @csrf
        <div class="reject-quick-wrap">
          <div class="reject-quick-label">Quick replies</div>
          <div id="reject-quick" class="reject-quick-btns"></div>
          <div class="reject-quick-actions">
            <button type="button" id="reject-clear" class="btn-sm btn-outline">Clear</button>
          </div>
        </div>
        <textarea id="reject-reason" name="reject_reason" required placeholder="e.g. Missing supporting document" class="reject-reason-textarea"></textarea>
        <div id="reject-error" class="reject-error-msg"></div>
        <div class="reject-modal-footer">
          <button type="button" id="reject-cancel" class="btn-sm btn-outline">Cancel</button>
          <button type="submit" id="reject-submit" class="btn-sm btn-reject" disabled>Reject</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const overlay = document.getElementById('reject-overlay');
      const form = document.getElementById('reject-form');
      const reason = document.getElementById('reject-reason');
      const cancelBtn = document.getElementById('reject-cancel');
      const clearBtn = document.getElementById('reject-clear');
      const submitBtn = document.getElementById('reject-submit');
      const rejectQuick = document.getElementById('reject-quick');
      const rejectError = document.getElementById('reject-error');

      const QUICK_REPLIES = [
        'Missing supporting document.',
        'Apply earlier / notice period not met.',
        'Date range invalid / conflict with schedule.',
      ];

      rejectQuick.innerHTML = QUICK_REPLIES.map(function (text) {
        return '<button type="button" class="reject-chip" data-reply="' + text.replace(/"/g, '&quot;') + '">' + text + '</button>';
      }).join('');

      function toggleRejectSubmit() {
        var hasText = reason.value.trim().length > 0;
        submitBtn.disabled = !hasText;
        rejectError.textContent = '';
      }

      rejectQuick.querySelectorAll('.reject-chip').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var text = btn.getAttribute('data-reply') || btn.textContent;
          reason.value = text;
          toggleRejectSubmit();
          reason.focus();
        });
      });

      if (clearBtn) {
        clearBtn.addEventListener('click', function () {
          reason.value = '';
          toggleRejectSubmit();
          reason.focus();
        });
      }

      reason.addEventListener('input', toggleRejectSubmit);

      // Quick vs Normal decision toggle (default: Quick = coming 7 days only)
      (function () {
        var toggleWrap = document.querySelector('.card .decision-toggle');
        if (!toggleWrap) return;
        var rows = document.querySelectorAll('.leave-table tbody tr.pending-row');
        var quickBtn = toggleWrap.querySelector('[data-mode="quick"]');
        var normalBtn = toggleWrap.querySelector('[data-mode="normal"]');
        function setMode(mode) {
          var isQuick = mode === 'quick';
          quickBtn.classList.toggle('active', isQuick);
          quickBtn.setAttribute('aria-pressed', isQuick ? 'true' : 'false');
          normalBtn.classList.toggle('active', !isQuick);
          normalBtn.setAttribute('aria-pressed', !isQuick ? 'true' : 'false');
          rows.forEach(function (tr) {
            var coming7 = tr.getAttribute('data-coming-7-days') === '1';
            if (isQuick) {
              tr.classList.toggle('pending-row-hidden', !coming7);
            } else {
              tr.classList.remove('pending-row-hidden');
            }
          });
        }
        quickBtn.addEventListener('click', function () { setMode('quick'); });
        normalBtn.addEventListener('click', function () { setMode('normal'); });
        setMode('quick');
      })();

      // Multi-select and bulk actions (supervisor)
      (function () {
        var selectAll = document.getElementById('supervisor-select-all');
        var bulkApprove = document.getElementById('supervisor-bulk-approve');
        var bulkReject = document.getElementById('supervisor-bulk-reject');
        var selectedCountEl = document.getElementById('supervisor-selected-count');
        var rejectForm = document.getElementById('reject-form');
        var baseRejectUrl = "{{ url('supervisor/leave') }}";

        function getVisibleCheckboxes() {
          return Array.prototype.filter.call(document.querySelectorAll('.pending-row-cb'), function (cb) {
            return cb.closest('tr') && !cb.closest('tr').classList.contains('pending-row-hidden');
          });
        }

        function updateBulkState() {
          var visible = getVisibleCheckboxes();
          var checked = visible.filter(function (cb) { return cb.checked; });
          var n = checked.length;
          selectedCountEl.textContent = n + ' selected';
          bulkApprove.disabled = n === 0;
          bulkReject.disabled = n === 0;
          selectAll.checked = visible.length > 0 && checked.length === visible.length;
          selectAll.indeterminate = checked.length > 0 && checked.length < visible.length;
        }

        if (selectAll) {
          selectAll.addEventListener('change', function () {
            getVisibleCheckboxes().forEach(function (cb) { cb.checked = selectAll.checked; });
            updateBulkState();
          });
        }
        document.querySelectorAll('.pending-row-cb').forEach(function (cb) {
          cb.addEventListener('change', updateBulkState);
        });
        document.querySelector('.leave-table tbody').addEventListener('change', function (e) {
          if (e.target.classList.contains('pending-row-cb')) updateBulkState();
        });

        bulkApprove.addEventListener('click', function () {
          var ids = getVisibleCheckboxes().filter(function (cb) { return cb.checked; }).map(function (cb) { return cb.value; });
          if (ids.length === 0) return;
          bulkApprove.disabled = true;
          var done = 0;
          function runNext() {
            if (done >= ids.length) { updateBulkState(); return; }
            var id = ids[done];
            var row = document.querySelector('tr.pending-row[data-leave-id="' + id + '"]');
            var form = row && row.querySelector('form.supervisor-approve-form');
            if (!form) { done++; runNext(); return; }
            fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' } })
              .then(function (r) { if (!r.ok) throw new Error('Failed'); return r.text(); })
              .then(function () {
                if (row) row.remove();
                decrementSupervisorPendingCount();
                done++;
                runNext();
              })
              .catch(function () { bulkApprove.disabled = false; alert('One or more approvals failed.'); });
          }
          runNext();
        });

        bulkReject.addEventListener('click', function () {
          var ids = getVisibleCheckboxes().filter(function (cb) { return cb.checked; }).map(function (cb) { return cb.value; });
          if (ids.length === 0) return;
          rejectForm.dataset.bulkIds = ids.join(',');
          rejectForm.dataset.leaveId = '';
          reason.value = '';
          rejectError.textContent = '';
          toggleRejectSubmit();
          overlay.classList.add('open');
          reason.focus();
        });

        form.addEventListener('submit', function (e) {
          if (e.target !== rejectForm) return;
          e.preventDefault();
          if (!reason.value.trim()) {
            rejectError.textContent = 'Reason is required.';
            return;
          }
          var bulkIds = (rejectForm.dataset.bulkIds || '').split(',').filter(Boolean);
          if (bulkIds.length > 0) {
            var submitBtn = rejectForm.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;
            var idx = 0;
            function doNext() {
              if (idx >= bulkIds.length) {
                overlay.classList.remove('open');
                rejectForm.dataset.bulkIds = '';
                bulkIds.forEach(function (id) {
                  var row = document.querySelector('tr.pending-row[data-leave-id="' + id + '"]');
                  if (row) row.remove();
                  decrementSupervisorPendingCount();
                });
                updateBulkState();
                if (submitBtn) submitBtn.disabled = false;
                return;
              }
              var id = bulkIds[idx];
              var formData = new FormData();
              formData.append('_token', document.querySelector('input[name="_token"]').value);
              formData.append('reject_reason', reason.value.trim());
              fetch(baseRejectUrl + '/' + id + '/reject', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { if (!r.ok) throw new Error('Failed'); return r.text(); })
                .then(function () { idx++; doNext(); })
                .catch(function () { if (submitBtn) submitBtn.disabled = false; rejectError.textContent = 'Reject failed for one or more.'); });
            }
            doNext();
            return;
          }
          var leaveId = rejectForm.dataset.leaveId;
          var row = leaveId ? document.querySelector('tr.pending-row[data-leave-id="' + leaveId + '"]') : null;
          var submitBtn = rejectForm.querySelector('button[type="submit"]');
          if (submitBtn) submitBtn.disabled = true;
          fetch(rejectForm.action, { method: 'POST', body: new FormData(rejectForm), headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { if (!r.ok) throw new Error('Failed'); return r.text(); })
            .then(function () {
              overlay.classList.remove('open');
              if (row) row.remove();
              decrementSupervisorPendingCount();
              updateBulkState();
            })
            .catch(function () { if (submitBtn) submitBtn.disabled = false; rejectError.textContent = 'Reject failed. Try again.'; });
        });
        updateBulkState();
      })();

      document.querySelectorAll('.btn-reject[data-id]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var id = this.getAttribute('data-id');
          form.action = "{{ url('supervisor/leave') }}/" + id + "/reject";
          form.dataset.leaveId = id;
          form.dataset.bulkIds = '';
          reason.value = '';
          rejectError.textContent = '';
          toggleRejectSubmit();
          overlay.classList.add('open');
          reason.focus();
        });
      });

      function decrementSupervisorPendingCount() {
        var el = document.getElementById('supervisor-pending-count');
        if (el) { var n = parseInt(el.textContent, 10); el.textContent = isNaN(n) ? 0 : Math.max(0, n - 1); }
      }

      document.querySelectorAll('form.supervisor-approve-form').forEach(function (approveForm) {
        approveForm.addEventListener('submit', function (e) {
          e.preventDefault();
          var form = this;
          var row = form.closest('tr');
          var submitBtn = form.querySelector('button[type="submit"]');
          if (submitBtn) submitBtn.disabled = true;
          fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { if (!r.ok) throw new Error('Failed'); return r.text(); })
            .then(function () {
              if (row) row.remove();
              decrementSupervisorPendingCount();
            })
            .catch(function () { if (submitBtn) submitBtn.disabled = false; alert('Approval failed. Please try again.'); });
        });
      });

      cancelBtn.addEventListener('click', function () {
        overlay.classList.remove('open');
      });
      overlay.addEventListener('click', function (e) {
        if (e.target === overlay) overlay.classList.remove('open');
      });

      form.addEventListener('submit', function (e) {
        if (!reason.value.trim()) {
          e.preventDefault();
          rejectError.textContent = 'Reason is required.';
          return;
        }
      });

      // Leave balance pop-out card
      var balanceOverlay = document.getElementById('balance-card-overlay');
      var balanceName = document.getElementById('balance-card-name');
      var balanceCode = document.getElementById('balance-card-code');
      var balanceLoading = document.getElementById('balance-card-loading');
      var balanceError = document.getElementById('balance-card-error');
      var balanceTypeCards = document.getElementById('balance-type-cards');
      var balanceClose = document.getElementById('balance-card-close');
      var balanceUrl = "{{ url('supervisor/leave/employee') }}";
      document.querySelectorAll('.employee-balance-trigger').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var empId = this.getAttribute('data-employee-id');
          var empName = this.getAttribute('data-employee-name') || '—';
          var empCode = this.getAttribute('data-employee-code') || '';
          balanceName.textContent = empName;
          balanceCode.textContent = empCode ? empCode : '—';
          balanceLoading.style.display = 'block';
          balanceError.style.display = 'none';
          balanceError.textContent = '';
          balanceTypeCards.style.display = 'none';
          balanceTypeCards.innerHTML = '';
          balanceOverlay.classList.add('open');
          fetch(balanceUrl + '/' + empId + '/balance', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(new Error('Failed to load')); })
            .then(function (data) {
              balanceLoading.style.display = 'none';
              if (data.balances && data.balances.length) {
                balanceTypeCards.innerHTML = data.balances.map(function (b) {
                  return '<div class="balance-type-card">' +
                    '<div class="bal-type-name">' + (b.type || '—') + '</div>' +
                    '<div class="bal-row"><span>Entitlement</span><span>' + (b.total ?? '—') + '</span></div>' +
                    '<div class="bal-row"><span>Used</span><span>' + (b.used ?? '—') + '</span></div>' +
                    '<div class="bal-row"><span>Pending</span><span>' + (b.pending ?? '—') + '</span></div>' +
                    '<div class="bal-row"><span class="bal-remaining">Remaining</span><span class="bal-remaining">' + (b.remaining ?? '—') + '</span></div>' +
                    '</div>';
                }).join('');
                balanceTypeCards.style.display = 'flex';
              } else {
                balanceError.textContent = 'No leave balance data.';
                balanceError.style.display = 'block';
              }
            })
            .catch(function (err) {
              balanceLoading.style.display = 'none';
              balanceError.textContent = err.message || 'Could not load leave balance.';
              balanceError.style.display = 'block';
            });
        });
      });
      balanceClose.addEventListener('click', function () { balanceOverlay.classList.remove('open'); });
      balanceOverlay.addEventListener('click', function (e) { if (e.target === balanceOverlay) balanceOverlay.classList.remove('open'); });
    });
  </script>
</body>
</html>
