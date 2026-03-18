<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Leave Request - HRMS</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
<link rel="stylesheet" href="{{ asset('css/hrms.css') }}">

<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
  body{font-family:'Poppins',sans-serif}
  main{padding:24px 28px;background:#f8fafc;min-height:100vh}
  .page-header{margin-bottom:20px}
  .breadcrumb{font-size:12px;color:#94a3b8;margin-bottom:4px;letter-spacing:0.02em}
  .page-title{margin:0;font-size:1.5rem;font-weight:700;color:#0f172a;letter-spacing:-0.02em}
  .page-subtitle{margin:6px 0 0;color:#64748b;font-size:0.9rem;line-height:1.5;max-width:560px}

  .box,.card{background:#fff;border-radius:16px;padding:20px 24px;margin-bottom:20px;box-shadow:0 1px 3px rgba(15,23,42,0.06);border:1px solid #e2e8f0}
  .card .section-title{margin:0 0 4px;font-size:1.05rem;font-weight:600;color:#0f172a;display:flex;align-items:center;gap:8px}
  .card .section-title i{color:#6366f1;opacity:0.9}

  .summary-row{display:flex;flex-wrap:wrap;gap:12px}
  .summary-chip{padding:10px 16px;border-radius:12px;display:inline-flex;align-items:center;gap:8px;font-size:13px;color:#475569;background:#fff;border:1px solid #e2e8f0;box-shadow:0 1px 2px rgba(15,23,42,0.04)}
  .summary-chip .num{font-weight:700;font-size:1.15rem;color:#0f172a}

  .table-wrap{overflow-x:auto;border-radius:12px;border:1px solid #e2e8f0;margin-top:16px}
  .leave-table{width:100%;border-collapse:collapse;font-size:13px;background:#fff}
  .leave-table thead th{background:linear-gradient(180deg,#f1f5f9 0%,#e2e8f0 100%);color:#475569;font-weight:600;text-align:left;padding:14px 16px;font-size:12px;text-transform:uppercase;letter-spacing:0.04em;border-bottom:2px solid #e2e8f0}
  .leave-table thead th:first-child{border-radius:12px 0 0 0}
  .leave-table thead th:last-child{border-radius:0 12px 0 0}
  .leave-table tbody tr{transition:background 0.15s ease;border-bottom:1px solid #f1f5f9}
  .leave-table tbody tr:hover{background:#f8fafc}
  .leave-table tbody tr:last-child{border-bottom:none}
  .leave-table tbody td{padding:14px 16px;vertical-align:middle;color:#334155}
  .leave-table tbody td strong{display:block;font-weight:600;color:#0f172a;margin-bottom:2px}
  .employee-meta,.muted{font-size:11px;color:#94a3b8;font-weight:500}
  .reason-text{color:#64748b;max-width:280px;font-size:12px}

  .row{display:flex;gap:10px;flex-wrap:wrap}
  .row>*{flex:1 1 200px}
  input,select,button{padding:8px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-family:inherit}
  .btn{background:#38bdf8;color:#0f172a;border-color:#38bdf8;cursor:pointer}
  .btn-ghost{background:#fff}
  .muted{color:#6b7280;font-size:.9rem}

  .pill{padding:4px 8px;border-radius:999px;font-size:.8rem;white-space:nowrap}
  .s-pending{background:#fef9c3;color:#854d0e}
  .s-approved{background:#dcfce7;color:#166534}
  .s-rejected{background:#fee2e2;color:#991b1b}

  .actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
  .btn-sm{padding:8px 14px;font-size:12px;font-weight:600;border-radius:10px;border:none;cursor:pointer;margin:0 4px 0 0;display:inline-flex;align-items:center;gap:6px;transition:transform 0.1s ease,box-shadow 0.15s ease;font-family:inherit}
  .btn-sm:hover{transform:translateY(-1px)}
  .btn-approve{background:linear-gradient(180deg,#22c55e 0%,#16a34a 100%);color:#fff;box-shadow:0 2px 8px rgba(22,163,74,0.35)}
  .btn-approve:hover{box-shadow:0 4px 12px rgba(22,163,74,0.4)}
  .btn-reject{background:linear-gradient(180deg,#ef4444 0%,#dc2626 100%);color:#fff;box-shadow:0 2px 8px rgba(220,38,38,0.3)}
  .btn-reject:hover{box-shadow:0 4px 12px rgba(220,38,38,0.35)}
  .chip.pending{background:#fef9c3;border:1px solid #fde68a;color:#854d0e;padding:8px 14px;border-radius:999px;font-size:13px;cursor:pointer;font-family:inherit}

  .empty-state{text-align:center;padding:32px 24px;color:#94a3b8;font-size:13px;background:#f8fafc;border-radius:12px;margin-top:12px;border:1px dashed #e2e8f0}
  .empty-state i{font-size:28px;margin-bottom:8px;opacity:0.6;display:block}
  .status-badge{padding:5px 10px;border-radius:999px;font-size:11px;font-weight:600;display:inline-block}
  .status-badge.status-approved{background:#dcfce7;color:#166534}
  .status-badge.status-pending{background:#fef3c7;color:#92400e}
  .status-badge.status-rejected{background:#fee2e2;color:#991b1b}
  .employee-balance-trigger{background:none;border:none;padding:0;text-align:left;cursor:pointer;font-family:inherit;width:100%}
  .employee-balance-trigger:hover{text-decoration:underline;color:#6366f1}
  .employee-balance-trigger strong{font-weight:600}
  .balance-card-overlay{position:fixed;inset:0;background:rgba(15,23,42,0.5);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;z-index:1001}
  .balance-card-overlay.open{display:flex}
  .balance-card{background:#fff;border-radius:16px;padding:28px;width:94%;max-width:720px;box-shadow:0 24px 48px rgba(15,23,42,0.2);border:1px solid #e2e8f0;aspect-ratio:4/3;display:flex;flex-direction:column;overflow:hidden}
  .balance-card-header{flex-shrink:0;margin-bottom:12px}
  .balance-card h4{margin:0 0 4px;font-size:1.1rem;color:#0f172a}
  .balance-card .balance-code{font-size:12px;color:#64748b}
  .balance-card-body{flex:1;overflow-y:auto;min-height:0}
  .balance-card-loading{color:#64748b;padding:16px 0}
  .balance-card-error{color:#b91c1c;padding:16px 0;font-size:13px}
  .balance-type-cards{display:flex;flex-direction:column;gap:10px}
  .balance-type-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px 14px;font-size:13px}
  .balance-type-card .bal-type-name{font-weight:600;color:#0f172a;margin-bottom:8px}
  .balance-type-card .bal-row{display:flex;justify-content:space-between;margin-bottom:4px}
  .balance-type-card .bal-row:last-child{margin-bottom:0}
  .balance-type-card .bal-remaining{font-weight:600;color:#166534}
  .balance-card-close{flex-shrink:0;margin-top:12px;padding:8px 16px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;cursor:pointer;font-size:13px}
  .balance-card-close:hover{background:#f8fafc}
  .decision-toggle{position:relative;display:block;margin-bottom:16px;min-height:48px}
  .decision-toggle .btn-decision{position:absolute;top:50%;transform:translate(-50%,-50%);width:25%;box-sizing:border-box;padding:12px 16px;border-radius:12px;font-size:15px;font-weight:600;cursor:pointer;border:2px solid #cbd5e1;background:#e2e8f0;color:#475569;font-family:inherit;text-align:center}
  .decision-toggle .btn-decision[data-mode="quick"]{left:25%}
  .decision-toggle .btn-decision[data-mode="normal"]{left:75%}
  .decision-toggle .btn-decision:hover{background:#cbd5e1;border-color:#94a3b8;color:#334155}
  .decision-toggle .btn-decision.active{background:#6366f1;color:#fff;border-color:#6366f1}
  .leave-table tbody tr.tr-coming-7{background:#fef9c3}
  .leave-table tbody tr.tr-coming-7:hover{background:#fef3c7}
  .leave-table tbody tr.pending-row-hidden{display:none}
  .leave-table input[type="checkbox"].pending-row-cb,
  .leave-table thead input[type="checkbox"]#admin-select-all{transform:scale(1.6);cursor:pointer;accent-color:#6366f1}
  .badge-within-7{display:inline-block;margin-left:6px;padding:2px 8px;border-radius:999px;font-size:10px;font-weight:600;background:#f59e0b;color:#fff}
  .bulk-actions{display:flex;align-items:center;gap:12px;margin-bottom:12px;flex-wrap:wrap}
  .bulk-actions .btn-bulk{padding:8px 16px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;border:none;font-family:inherit}
  .bulk-actions .btn-bulk:disabled{opacity:0.5;cursor:not-allowed}
  .bulk-actions .btn-bulk-approve{background:#16a34a;color:#fff}
  .bulk-actions .btn-bulk-reject{background:#dc2626;color:#fff}
  .bulk-actions .selected-count{font-size:13px;color:#64748b}
</style>
</head>
<body>
<header><div class="title">Web-Based HRMS</div><div class="user-info">
    <a href="{{ route('admin.profile') }}" style="text-decoration: none; color: inherit;">
        <i class="fa-regular fa-bell"></i> &nbsp; HR Admin
    </a>
</div></header>

<div class="container">
  @include('admin.layout.sidebar')

  <main>
    <div class="page-header">
      <div class="breadcrumb">Home > Leave > Leave Request</div>
      <h2 class="page-title">Leave Request</h2>
      <p class="page-subtitle">Review and approve or reject leave requests. Pending your approval are requests sent by supervisors.</p>
    </div>

    <!-- Summary chips (same style as supervisor) -->
    <div class="card">
      <div class="summary-row" id="kpis">
        <span class="summary-chip"><span class="num" id="k-total">-</span><span>Total</span></span>
        <span class="summary-chip"><span class="num" id="k-pending-approval">-</span><span>Pending your approval</span></span>
        <span class="summary-chip"><span class="num" id="k-approved">-</span><span>Approved</span></span>
        <span class="summary-chip"><span class="num" id="k-rejected">-</span><span>Rejected</span></span>
      </div>
    </div>

    <!-- Pending your approval (admin: pending_admin only) -->
    <div class="card">
      <h3 class="section-title"><i class="fa-solid fa-inbox"></i> Pending your approval</h3>
      <p class="section-desc" style="margin:0 0 12px;color:#64748b;font-size:13px;">Leave requests sent to you for final approval. Approve or reject below.</p>
      <div class="decision-toggle" id="pending-decision-toggle" style="display:none;">
        <button type="button" class="btn-decision active" data-mode="quick" aria-pressed="true">Quick decision</button>
        <button type="button" class="btn-decision" data-mode="normal" aria-pressed="false">Normal decision</button>
      </div>
      <div class="bulk-actions" id="admin-bulk-actions" style="display:none;">
        <span class="selected-count" id="admin-selected-count">0 selected</span>
        <button type="button" class="btn-bulk btn-bulk-approve" id="admin-bulk-approve" disabled>Approve selected</button>
        <button type="button" class="btn-bulk btn-bulk-reject" id="admin-bulk-reject" disabled>Reject selected</button>
      </div>
      <div id="pending-admin-empty" class="empty-state" style="display:none;"><i class="fa-solid fa-inbox"></i> No leave requests pending your approval.</div>
      <div id="pending-admin-wrap" class="table-wrap" style="display:none;">
        <table class="leave-table">
          <thead>
            <tr>
              <th style="width:42px"><input type="checkbox" id="admin-select-all" title="Select all" aria-label="Select all"></th>
              <th>Employee</th><th>Department</th><th>Supervisor</th><th>Type</th><th>Period</th><th>Days</th><th>Reason</th><th>Actions</th>
            </tr>
          </thead>
          <tbody id="pending-tbody"></tbody>
        </table>
      </div>
    </div>

    <!-- Leave you have approved or rejected (empty until admin acts) -->
    <div class="card">
      <h3 class="section-title"><i class="fa-solid fa-clipboard-list"></i> Leave you have approved or rejected</h3>
      <p class="section-desc" style="margin:0 0 12px;color:#64748b;font-size:13px;">Leave requests you have already approved or rejected. This list is empty until you take action on requests above.</p>
      <div class="row" style="margin-bottom:12px;">
        <div><label>Search</label><input id="q" type="text" placeholder="EMP001 / name"></div>
        <div><label>Department</label><select id="dept"><option value="">All</option>@foreach($departments as $dept)<option value="{{ $dept->department_id }}">{{ $dept->department_name }}</option>@endforeach</select></div>
        <div><label>Type</label><select id="type"><option value="">All</option>@foreach($leaveTypes as $t)<option value="{{ $t->leave_type_id }}">{{ $t->leave_name }}</option>@endforeach</select></div>
        <div><label>Status</label><select id="status"><option value="">All</option><option value="approved">Approved</option><option value="rejected">Rejected</option></select></div>
        <div style="align-self:end"><button class="btn" id="apply">Filter</button><button class="btn-ghost" id="clear">Clear</button></div>
      </div>
      <div class="table-wrap">
        <table class="leave-table" id="tbl">
          <thead>
            <tr>
              <th>Employee</th>
              <th>Department</th>
              <th>Supervisor</th>
              <th>Type</th>
              <th>Period</th>
              <th>Days</th>
              <th>Your action</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>

    <footer>© {{ date('Y') }} Web-Based HRMS. All Rights Reserved.</footer>
  </main>
    </div>

<!-- Leave balance pop-out card (4:3 ratio, card form) -->
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

<!-- Approve confirmation modal -->
<div class="modal" id="approve-confirm-modal" style="display:none;position:fixed;inset:0;align-items:center;justify-content:center;background:rgba(0,0,0,.45);z-index:1000;">
  <div class="sheet" style="background:#fff;border-radius:12px;padding:24px;max-width:400px;width:92%;box-shadow:0 20px 45px rgba(15,23,42,.18);">
    <div style="text-align:center;margin-bottom:20px;">
      <div style="width:56px;height:56px;margin:0 auto 16px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;">
        <i class="fa-solid fa-circle-check" style="font-size:28px;color:#166534;"></i>
      </div>
      <h3 style="margin:0 0 8px;font-size:1.15rem;color:#0f172a;">Approve leave request?</h3>
      <p class="muted" style="margin:0;font-size:14px;">Are you sure you want to approve this leave request? This action will update the status.</p>
    </div>
    <div style="display:flex;gap:10px;justify-content:center;">
      <button type="button" id="approve-confirm-cancel" class="btn-ghost" style="padding:10px 20px;">Cancel</button>
      <button type="button" id="approve-confirm-ok" class="btn" style="background:#16a34a;color:#fff;border-color:#16a34a;padding:10px 24px;"><i class="fa-solid fa-check"></i> Confirm</button>
    </div>
  </div>
</div>

<!-- Reject modal -->
<div class="modal" id="reject-modal" style="display:none;position:fixed;inset:0;align-items:center;justify-content:center;background:rgba(0,0,0,.45);z-index:1000;">
  <div class="sheet" style="background:#fff;border-radius:12px;padding:16px;max-width:520px;width:92%;box-shadow:0 20px 45px rgba(15,23,42,.18);">
    <h3 style="margin:0 0 10px">Reject Leave</h3>
    <p class="muted" style="margin:0 0 12px">Provide a reason. This will be visible to the employee.</p>
    <div style="margin-bottom:8px;">
      <div style="font-size:0.9rem;font-weight:600;color:#0f172a;margin-bottom:6px;">Quick replies</div>
      <div id="reject-quick" style="display:flex;flex-wrap:wrap;gap:8px;"></div>
      <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap;">
        <button type="button" id="reject-clear" class="btn-ghost" style="padding:6px 10px;">Clear</button>
      </div>
    </div>
    <textarea id="reject-reason" style="width:100%;min-height:110px;padding:10px;border:1px solid #d1d5db;border-radius:10px;"></textarea>
    <div id="reject-error" class="muted" style="color:#b91c1c;margin-top:6px;min-height:18px;"></div>
    <div style="margin-top:12px;display:flex;justify-content:flex-end;gap:10px;">
      <button id="reject-cancel" class="btn-ghost">Cancel</button>
      <button id="reject-submit" class="btn" style="background:#ef4444;color:#fff;border-color:#ef4444;">Reject</button>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

  const ENDPOINT_LIST   = "{{ route('admin.leave.request.data') }}";
  const ENDPOINT_STATUS = (id) => "{{ route('admin.leave.request.status', ['leave' => '__ID__']) }}".replace('__ID__', id);
  const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  let ROWS = [];
  const QUICK_REPLIES = [
    'Missing supporting document.',
    'Apply earlier / notice period not met.',
    'Date range invalid / conflict with schedule.',
  ];

  /* ========= Sidebar: single active, single open, persistence ========= */
  const groups  = document.querySelectorAll('.sidebar-group');
  const toggles = document.querySelectorAll('.sidebar-toggle');
  const links   = document.querySelectorAll('.submenu a');
  const STORAGE_KEY = 'hrms_sidebar_open_group';

  // Normalize URL path for reliable comparison
  const normPath = (u) => {
    const url = new URL(u, location.origin);
    let p = url.pathname
      .replace(/\/index\.php$/i, '') // strip Laravel front controller if present at end
      .replace(/\/index\.php\//i, '/') // strip if in middle
      .replace(/\/+$/, '');            // strip trailing slash
    return p === '' ? '/' : p;
  };
  const here = normPath(location.href);

  // Clear any server-rendered states to avoid double-highlighting
  groups.forEach(g => {
    g.classList.remove('open');
    const t = g.querySelector('.sidebar-toggle');
    if (t) t.setAttribute('aria-expanded','false');
  });
  links.forEach(a => a.classList.remove('active'));

  // Pick exactly one active link (exact match; fallback to startsWith)
  let activeLink = null;
  for (const a of links) {
    if (normPath(a.href) === here) { activeLink = a; break; }
  }
  if (!activeLink) {
    for (const a of links) {
      const p = normPath(a.href);
      if (p !== '/' && here.startsWith(p)) { activeLink = a; break; }
    }
  }

  let openedByActive = false;
  if (activeLink) {
    activeLink.classList.add('active');
    const g = activeLink.closest('.sidebar-group');
    if (g) {
      g.classList.add('open');
      const t = g.querySelector('.sidebar-toggle');
      if (t) t.setAttribute('aria-expanded','true');
      openedByActive = true;
      const idx = Array.from(groups).indexOf(g);
      if (idx >= 0) localStorage.setItem(STORAGE_KEY, String(idx));
    }
  }

  // If nothing opened by active link, restore last open or default to first
  if (!openedByActive) {
    const idx = localStorage.getItem(STORAGE_KEY);
    if (idx !== null && groups[idx]) {
      groups[idx].classList.add('open');
      const t = groups[idx].querySelector('.sidebar-toggle');
      if (t) t.setAttribute('aria-expanded','true');
    } else if (groups[0]) {
      groups[0].classList.add('open');
      const t0 = groups[0].querySelector('.sidebar-toggle');
      if (t0) t0.setAttribute('aria-expanded','true');
    }
  }

  // Accordion behavior + persistence
  toggles.forEach((btn, i) => {
    btn.setAttribute('role','button');
    btn.setAttribute('tabindex','0');

    const doToggle = (e) => {
      e.preventDefault();
      const group = btn.closest('.sidebar-group');
      const isOpen = group.classList.contains('open');

      groups.forEach(g => {
        g.classList.remove('open');
        const t = g.querySelector('.sidebar-toggle');
        if (t) t.setAttribute('aria-expanded','false');
      });

      if (!isOpen) {
        group.classList.add('open');
        btn.setAttribute('aria-expanded','true');
        localStorage.setItem(STORAGE_KEY, String(i));
      } else {
        btn.setAttribute('aria-expanded','false');
        localStorage.removeItem(STORAGE_KEY);
      }
    };

    btn.addEventListener('click', doToggle);
    btn.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') doToggle(e);
    });
  });


  /* ===== Leave logic ===== */
  const $ = (s)=>document.querySelector(s);
  const tbody = $('#tbl tbody');
  const esc = (v = '') => String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

  const pills = {
    'pending (supervisor)': '<span class="pill s-pending">Pending (supervisor)</span>',
    'supervisor approved': '<span class="pill s-pending">Supervisor approved</span>',
    'pending admin': '<span class="pill s-pending">Pending admin</span>',
    'approved': '<span class="pill s-approved">Approved</span>',
    'rejected': '<span class="pill s-rejected">Rejected</span>',
    'cancelled': '<span class="pill s-rejected">Cancelled</span>',
  };

  const pendingTbody = document.getElementById('pending-tbody');
  const pendingAdminEmpty = document.getElementById('pending-admin-empty');
  const pendingAdminWrap = document.getElementById('pending-admin-wrap');

  // Show empty state for "Pending your approval" until data loads
  if (pendingAdminEmpty) pendingAdminEmpty.style.display = 'block';

  function updateKpis() {
    const total = ROWS.length;
    const pendingApproval = ROWS.filter(r=>(r.status_raw||r.status||'').toLowerCase()==='pending_admin').length;
    const approved = ROWS.filter(r=>(r.status_raw||r.status||'').toLowerCase()==='approved').length;
    const rejected = ROWS.filter(r=>(r.status_raw||r.status||'').toLowerCase()==='rejected').length;
    if ($('#k-total')) $('#k-total').textContent = total;
    if ($('#k-pending-approval')) $('#k-pending-approval').textContent = pendingApproval;
    if ($('#k-approved')) $('#k-approved').textContent = approved;
    if ($('#k-rejected')) $('#k-rejected').textContent = rejected;
  }

  function isLeaveComing7Days(startStr, endStr) {
    if (!startStr || !endStr) return false;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const endWindow = new Date(today);
    endWindow.setDate(endWindow.getDate() + 7);
    const start = new Date(startStr);
    const end = new Date(endStr);
    start.setHours(0, 0, 0, 0);
    end.setHours(0, 0, 0, 0);
    return start <= endWindow && end >= today;
  }

  function applyPendingDecisionMode() {
    const toggleWrap = document.getElementById('pending-decision-toggle');
    if (!toggleWrap || !pendingTbody) return;
    const rows = pendingTbody.querySelectorAll('tr.pending-row');
    const quickBtn = toggleWrap.querySelector('[data-mode="quick"]');
    const normalBtn = toggleWrap.querySelector('[data-mode="normal"]');
    const isQuick = quickBtn && quickBtn.classList.contains('active');
    rows.forEach(tr => {
      const coming7 = tr.getAttribute('data-coming-7-days') === '1';
      tr.classList.toggle('pending-row-hidden', isQuick && !coming7);
    });
  }

  function renderPendingApproval() {
    const pending = ROWS.filter(r=>(r.status_raw||'').toLowerCase()==='pending_admin');
    if (!pendingTbody) return;
    pendingTbody.innerHTML = '';
    const toggleWrap = document.getElementById('pending-decision-toggle');
    const bulkWrap = document.getElementById('admin-bulk-actions');
    if (toggleWrap) toggleWrap.style.display = pending.length ? 'flex' : 'none';
    if (bulkWrap) bulkWrap.style.display = pending.length ? 'flex' : 'none';
    if (pendingAdminEmpty) pendingAdminEmpty.style.display = pending.length ? 'none' : 'block';
    if (pendingAdminWrap) pendingAdminWrap.style.display = pending.length ? 'block' : 'none';
    if (!pending.length) return;
    pending.forEach(r => {
      const tr = document.createElement('tr');
      const empId = r.employee_id != null ? r.employee_id : r.id;
      const coming7 = isLeaveComing7Days(r.start, r.end);
      tr.className = 'pending-row' + (coming7 ? ' tr-coming-7' : '');
      tr.setAttribute('data-coming-7-days', coming7 ? '1' : '0');
      tr.setAttribute('data-leave-id', String(r.id));
      tr.innerHTML = `
        <td><input type="checkbox" class="pending-row-cb" value="${r.id}" aria-label="Select row"></td>
        <td>
          <button type="button" class="employee-balance-trigger" data-employee-id="${empId}" data-employee-name="${esc(r.employee)}" data-employee-code="${esc(r.code)}" title="View leave balance">
            <strong>${r.employee}</strong><br><span class="employee-meta">${r.code}</span>
          </button>
        </td>
        <td>${r.dept}</td>
        <td>${r.supervisor ? esc(r.supervisor) : '—'}</td>
        <td>${r.type}</td>
        <td>${r.start} to ${r.end}${coming7 ? ' <span class="badge-within-7" title="Within 7 days">Within 7 days</span>' : ''}</td>
        <td>${r.days}</td>
        <td class="reason-text" title="${esc(r.reason)}">${esc(r.reason) || '—'}</td>
        <td class="actions">
          <button type="button" class="btn-sm btn-approve" data-id="${r.id}" data-status="approved"><i class="fa-solid fa-check"></i> Approve</button>
          <button type="button" class="btn-sm btn-reject" data-id="${r.id}" data-status="rejected"><i class="fa-solid fa-times"></i> Reject</button>
        </td>
      `;
      pendingTbody.appendChild(tr);
    });
    applyPendingDecisionMode();
    bindActions();
    if (typeof updateAdminBulkState === 'function') updateAdminBulkState();
  }

  function render(rows) {
    tbody.innerHTML = '';
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="6">No leave approved or rejected by you yet.</td></tr>';
      updateKpis();
      renderPendingApproval();
      return;
    }

    rows.forEach(r => {
      const tr = document.createElement('tr');
      const s = (r.status_raw || r.status || '').toLowerCase();
      const actionBadge = s === 'approved'
        ? '<span class="status-badge status-approved">Approved by you</span>'
        : s === 'rejected'
          ? '<span class="status-badge status-rejected">Rejected by you</span>'
          : '<span class="status-badge status-pending">—</span>';
      tr.innerHTML = `
        <td><strong>${r.employee}</strong><br><span class="employee-meta">${r.code}</span></td>
        <td>${r.dept}</td>
        <td>${r.supervisor ? esc(r.supervisor) : '—'}</td>
        <td>${r.type}</td>
        <td>${r.start} to ${r.end}</td>
        <td>${r.days}</td>
        <td>${actionBadge}</td>
      `;
      tbody.appendChild(tr);
    });
    updateKpis();
    renderPendingApproval();
    bindActions();
  }

  /** Only leave requests admin has already approved or rejected (for "Leave you have approved or rejected" table) */
  function getActedRows() {
    return ROWS.filter(r => {
      const s = (r.status_raw || r.status || '').toLowerCase();
      return s === 'approved' || s === 'rejected';
    });
  }

  function applyFilters() {
    const q = $('#q').value.trim().toLowerCase();
    const dept = $('#dept').value;
    const type = $('#type').value;
    const statusVal = $('#status').value;
    const actedRows = getActedRows();
    const rows = actedRows.filter(r => {
      const qmatch = !q || (r.code||'').toLowerCase().includes(q) || (r.employee||'').toLowerCase().includes(q);
      const dmatch = !dept || String(r.dept_id) === String(dept);
      const tmatch = !type || String(r.type_id) === String(type);
      const smatch = !statusVal || (r.status_raw || r.status || '').toLowerCase() === statusVal.toLowerCase();
      return qmatch && dmatch && tmatch && smatch;
    });
    render(rows);
  }

  async function loadData() {
    tbody.innerHTML = '<tr><td colspan="6">Loading...</td></tr>';
    try {
      const resp = await fetch(ENDPOINT_LIST, { headers: { 'Accept': 'application/json' }});
      if (!resp.ok) throw new Error('Failed to load leave requests');
      const json = await resp.json();
      ROWS = Array.isArray(json.data) ? json.data.map(r => ({
        ...r,
        dept_id: r.dept_id ? String(r.dept_id) : '',
        type_id: r.type_id ? String(r.type_id) : '',
      })) : [];
      updateKpis();
      renderPendingApproval();
      applyFilters();
    } catch (err) {
      tbody.innerHTML = `<tr><td colspan="6">Error: ${err.message}</td></tr>`;
    }
  }

  async function updateStatus(id, status, reason = null) {
    const btns = document.querySelectorAll(`button[data-id="${id}"]`);
    btns.forEach(b => { b.disabled = true; b.textContent = '...'; });
    try {
      const payload = { status, expected: 'pending_admin' };
      if (status === 'rejected') {
        payload.reason = reason || '';
      }
      const resp = await fetch(ENDPOINT_STATUS(id), {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': CSRF_TOKEN,
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
      });
      if (!resp.ok) throw new Error(await resp.text() || 'Update failed');
      await loadData();
    } catch (err) {
      alert('Unable to update leave: ' + err.message);
    } finally {
      btns.forEach(b => {
        b.disabled = false;
        b.innerHTML = b.classList.contains('btn-approve') ? '<i class="fa-solid fa-check"></i> Approve' : '<i class="fa-solid fa-times"></i> Reject';
      });
    }
  }

  /* Approve confirm modal */
  const approveModal = document.getElementById('approve-confirm-modal');
  const approveConfirmOk = document.getElementById('approve-confirm-ok');
  const approveConfirmCancel = document.getElementById('approve-confirm-cancel');
  let pendingApproveId = null;

  function openApproveConfirm(id) {
    pendingApproveId = id;
    if (approveModal) approveModal.style.display = 'flex';
  }

  function closeApproveConfirm() {
    pendingApproveId = null;
    if (approveModal) approveModal.style.display = 'none';
  }

  approveConfirmOk && approveConfirmOk.addEventListener('click', () => {
    if (pendingApproveId != null) {
      updateStatus(pendingApproveId, 'approved');
      closeApproveConfirm();
    }
  });
  approveConfirmCancel && approveConfirmCancel.addEventListener('click', closeApproveConfirm);
  approveModal && approveModal.addEventListener('click', (e) => { if (e.target === approveModal) closeApproveConfirm(); });

  function bindActions() {
    document.querySelectorAll('.actions button').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-id');
        const status = btn.getAttribute('data-status');
        if (status === 'rejected') {
          openRejectDialog(id);
        } else if (status === 'approved') {
          openApproveConfirm(id);
        } else {
          updateStatus(id, status);
        }
      });
    });
  }

  $('#apply').addEventListener('click', loadData);
  $('#clear').addEventListener('click', () => {
    $('#q').value = '';
    $('#dept').value = '';
    $('#type').value = '';
    $('#status').value = '';
    loadData();
  });

  // initial load
  loadData();

  // Auto-update: poll so when supervisor approves, admin sees new pending items
  setInterval(loadData, 30000);

  // Quick vs Normal decision toggle (default: Quick = coming 7 days only)
  (function () {
    const toggleWrap = document.getElementById('pending-decision-toggle');
    if (!toggleWrap) return;
    toggleWrap.addEventListener('click', (e) => {
      const btn = e.target.closest('.btn-decision');
      if (!btn) return;
      const mode = btn.getAttribute('data-mode');
      toggleWrap.querySelectorAll('.btn-decision').forEach(b => {
        const isActive = b.getAttribute('data-mode') === mode;
        b.classList.toggle('active', isActive);
        b.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });
      applyPendingDecisionMode();
    });
  })();

  // Admin multi-select and bulk actions
  let adminBulkRejectIds = [];
  function getAdminVisibleCheckboxes() {
    if (!pendingTbody) return [];
    return Array.from(pendingTbody.querySelectorAll('.pending-row-cb')).filter(cb => {
      const tr = cb.closest('tr');
      return tr && !tr.classList.contains('pending-row-hidden');
    });
  }
  function updateAdminBulkState() {
    const visible = getAdminVisibleCheckboxes();
    const checked = visible.filter(cb => cb.checked);
    const n = checked.length;
    const countEl = document.getElementById('admin-selected-count');
    const bulkApprove = document.getElementById('admin-bulk-approve');
    const bulkReject = document.getElementById('admin-bulk-reject');
    const selectAll = document.getElementById('admin-select-all');
    if (countEl) countEl.textContent = n + ' selected';
    if (bulkApprove) bulkApprove.disabled = n === 0;
    if (bulkReject) bulkReject.disabled = n === 0;
    if (selectAll) {
      selectAll.checked = visible.length > 0 && checked.length === visible.length;
      selectAll.indeterminate = checked.length > 0 && checked.length < visible.length;
    }
  }
  document.getElementById('pending-admin-wrap') && document.getElementById('pending-admin-wrap').addEventListener('change', (e) => {
    if (e.target.classList.contains('pending-row-cb')) updateAdminBulkState();
  });
  document.getElementById('admin-select-all') && document.getElementById('admin-select-all').addEventListener('change', function() {
    getAdminVisibleCheckboxes().forEach(cb => { cb.checked = this.checked; });
    updateAdminBulkState();
  });
  document.getElementById('admin-bulk-approve') && document.getElementById('admin-bulk-approve').addEventListener('click', async () => {
    const ids = getAdminVisibleCheckboxes().filter(cb => cb.checked).map(cb => cb.value);
    if (!ids.length) return;
    const btn = document.getElementById('admin-bulk-approve');
    btn.disabled = true;
    for (const id of ids) {
      try {
        await updateStatus(id, 'approved');
      } catch (_) {}
    }
    btn.disabled = false;
    loadData();
  });
  document.getElementById('admin-bulk-reject') && document.getElementById('admin-bulk-reject').addEventListener('click', () => {
    const ids = getAdminVisibleCheckboxes().filter(cb => cb.checked).map(cb => cb.value);
    if (!ids.length) return;
    adminBulkRejectIds = ids.slice();
    rejectModal.dataset.id = '';
    rejectReason.value = '';
    document.getElementById('reject-error').textContent = '';
    rejectModal.style.display = 'flex';
    rejectReason.focus();
  });

  /* Leave balance pop-out card (event delegation for dynamically added rows) */
  const balanceOverlay = document.getElementById('balance-card-overlay');
  const balanceName = document.getElementById('balance-card-name');
  const balanceCode = document.getElementById('balance-card-code');
  const balanceLoading = document.getElementById('balance-card-loading');
  const balanceError = document.getElementById('balance-card-error');
  const balanceTypeCards = document.getElementById('balance-type-cards');
  const balanceClose = document.getElementById('balance-card-close');
  const BALANCE_URL = "{{ url('admin/leave/employee') }}";
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.employee-balance-trigger');
    if (!btn) return;
    e.preventDefault();
    const empId = btn.getAttribute('data-employee-id');
    const empName = btn.getAttribute('data-employee-name') || '—';
    const empCode = btn.getAttribute('data-employee-code') || '';
    balanceName.textContent = empName;
    balanceCode.textContent = empCode || '—';
    balanceLoading.style.display = 'block';
    balanceError.style.display = 'none';
    balanceError.textContent = '';
    balanceTypeCards.style.display = 'none';
    balanceTypeCards.innerHTML = '';
    balanceOverlay.classList.add('open');
    fetch(`${BALANCE_URL}/${empId}/balance`, { headers: { 'Accept': 'application/json' } })
      .then(r => r.ok ? r.json() : Promise.reject(new Error('Failed to load')))
      .then(data => {
        balanceLoading.style.display = 'none';
        if (data.balances && data.balances.length) {
          balanceTypeCards.innerHTML = data.balances.map(b =>
            '<div class="balance-type-card">' +
            '<div class="bal-type-name">' + (b.type || '—') + '</div>' +
            '<div class="bal-row"><span>Entitlement</span><span>' + (b.total ?? '—') + '</span></div>' +
            '<div class="bal-row"><span>Used</span><span>' + (b.used ?? '—') + '</span></div>' +
            '<div class="bal-row"><span>Pending</span><span>' + (b.pending ?? '—') + '</span></div>' +
            '<div class="bal-row"><span class="bal-remaining">Remaining</span><span class="bal-remaining">' + (b.remaining ?? '—') + '</span></div>' +
            '</div>'
          ).join('');
          balanceTypeCards.style.display = 'flex';
        } else {
          balanceError.textContent = 'No leave balance data.';
          balanceError.style.display = 'block';
        }
      })
      .catch(err => {
        balanceLoading.style.display = 'none';
        balanceError.textContent = err.message || 'Could not load leave balance.';
        balanceError.style.display = 'block';
      });
  });
  if (balanceClose) balanceClose.addEventListener('click', () => balanceOverlay.classList.remove('open'));
  if (balanceOverlay) balanceOverlay.addEventListener('click', (e) => { if (e.target === balanceOverlay) balanceOverlay.classList.remove('open'); });

  /* Reject modal logic */
  const rejectModal = document.getElementById('reject-modal');
  const rejectReason = document.getElementById('reject-reason');
  const rejectCancel = document.getElementById('reject-cancel');
  const rejectSubmit = document.getElementById('reject-submit');
  const rejectQuick = document.getElementById('reject-quick');
  const rejectClear = document.getElementById('reject-clear');
  const rejectError = document.getElementById('reject-error');
  const REJECT_LS_KEY = 'hrms_reject_reason_last';

  // Render quick replies
  rejectQuick.innerHTML = QUICK_REPLIES.map(text => `
    <button type="button" class="chip pending" data-reply="${text.replace(/"/g,'&quot;')}">${text}</button>
  `).join('');

  const setRejectReason = (text, append = false) => {
    if (append && rejectReason.value.trim().length) {
      rejectReason.value = `${rejectReason.value.trim()} ${text}`.trim();
    } else {
      rejectReason.value = text;
    }
    rejectReason.focus();
    rejectReason.selectionStart = rejectReason.selectionEnd = rejectReason.value.length;
    toggleRejectSubmit();
  };

  const toggleRejectSubmit = () => {
    rejectError.textContent = '';
  };

  rejectQuick.querySelectorAll('button').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const text = btn.dataset.reply || btn.textContent;
      const append = e.shiftKey;
      setRejectReason(text, append);
    });
  });

  rejectClear.addEventListener('click', () => {
    rejectReason.value = '';
    toggleRejectSubmit();
    rejectReason.focus();
  });

  rejectReason.addEventListener('input', toggleRejectSubmit);

  rejectCancel.addEventListener('click', () => { rejectModal.style.display = 'none'; });
  rejectSubmit.addEventListener('click', () => {
    const reason = rejectReason.value.trim() || null;
    if (reason) localStorage.setItem(REJECT_LS_KEY, reason);
    rejectModal.style.display = 'none';
    if (adminBulkRejectIds.length > 0) {
      const ids = adminBulkRejectIds.slice();
      adminBulkRejectIds = [];
      (async () => {
        for (const id of ids) {
          try { await updateStatus(id, 'rejected', reason); } catch (_) {}
        }
        loadData();
      })();
      return;
    }
    const id = rejectModal.dataset.id;
    updateStatus(id, 'rejected', reason);
  });

  const openRejectDialog = (id) => {
    adminBulkRejectIds = [];
    rejectModal.dataset.id = id;
    rejectError.textContent = '';
    rejectReason.value = '';
    toggleRejectSubmit();
    rejectModal.style.display = 'flex';
    rejectReason.focus();
  };
});
</script>

</body>
</html>
