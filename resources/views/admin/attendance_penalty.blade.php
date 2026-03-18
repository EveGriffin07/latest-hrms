<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Penalty Removal & Tracking - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    /* Page-specific: keep white cards/tables for readability */
    main { padding: 2rem; }
    .breadcrumb { font-size:.85rem; color:#94a3b8; margin-bottom:1rem; }
    h2 { color:#38bdf8; margin:0 0 .25rem 0; }
    .subtitle { color:#94a3b8; margin-bottom:1.5rem; }

    .summary, .filters, .table-wrap { background:#fff; color:#111827; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,.08); }
    .summary { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px; padding:16px; margin-bottom:16px; }
    .summary .card { border:1px solid #edf2f7; border-radius:10px; text-align:center; padding:16px; }
    .summary .card h3 { font-size:.95rem; color:#6b7280; margin:0 0 6px; }
    .summary .card p { font-size:1.4rem; font-weight:600; color:#111827; margin:0; }

    .filters { padding:16px; margin-bottom:16px; }
    .filters .row { display:flex; gap:12px; flex-wrap:wrap; }
    .filters .split { flex:1 1 240px; }
    .filters label { display:block; font-size:.85rem; color:#6b7280; margin-bottom:6px; }
    .filters input, .filters select, .filters button {
      border:1px solid #d1d5db; background:#fff; color:#111827;
      border-radius:8px; padding:8px 10px; font-size:.92rem;
    }
    .filters .btn { cursor:pointer; }
    .filters .btn-primary { background:#38bdf8; border-color:#38bdf8; color:#0f172a; }
    .filters .btn-ghost { background:#fff; color:#111827; }

    .table-wrap { overflow:hidden; border:1px solid #e5e7eb; }
    table { width:100%; border-collapse:collapse; }
    thead { background:#0f172a; color:#38bdf8; }
    th, td { padding:12px 14px; border-bottom:1px solid #e5e7eb; text-align:left; }
    tbody tr:hover { background:#f8fafc; }

    .status { padding:4px 8px; border-radius:999px; font-size:.8rem; white-space:nowrap; }
    .pending  { background:#fef3c7; color:#92400e; }
    .approved { background:#dcfce7; color:#166534; }
    .rejected { background:#fee2e2; color:#991b1b; }

    .points { font-weight:600; color:#0f172a; }

    .btn-xs { padding:6px 10px; font-size:.85rem; border-radius:8px; border:1px solid #d1d5db; background:#fff; cursor:pointer; }
    .btn-approve { background:#22c55e; border-color:#22c55e; color:#fff; }
    .btn-reject  { background:#ef4444; border-color:#ef4444; color:#fff; }
    .btn-disabled { opacity:.5; cursor:not-allowed; }

    footer { text-align:center; color:#64748b; font-size:.8rem; padding:22px 0 0; }

    /* Mini modal confirm */
    .backdrop { position:fixed; inset:0; background:rgba(15,23,42,.55); display:none; align-items:center; justify-content:center; z-index:50; }
    .dialog { width:min(480px, 92vw); background:#fff; color:#111827; border-radius:14px; box-shadow:0 10px 30px rgba(0,0,0,.35); overflow:hidden; }
    .dialog header { padding:12px 16px; background:#0f172a; color:#e2e8f0; font-weight:600; }
    .dialog .body { padding:16px; }
    .dialog .actions { display:flex; gap:8px; justify-content:flex-end; padding:12px 16px; border-top:1px solid #e5e7eb; }
  </style>
</head>

<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
    <a href="{{ route('admin.profile') }}" style="text-decoration: none; color: inherit;">
        <i class="fa-regular fa-bell"></i> &nbsp; HR Admin
    </a>
</div>
  </header>

  <div class="container">
    @include('admin.layout.sidebar')

    <main>
      <div class="breadcrumb">Home > Attendance > Penalty Tracking</div>
      <h2>Penalty Removal & Tracking</h2>
      <p class="subtitle">Approve or reject attendance-related penalties and filter the records by employee, department, reason, status, or date.</p>

      @if(isset($todayAttendancePenalties) && $todayAttendancePenalties->count())
        <section class="card" style="margin-bottom:16px;">
          <h3 style="margin:0 0 8px;">Today’s Auto Penalties (Late / Absent)</h3>
          <p class="muted" style="margin:0 0 10px; font-size:13px;">Employees who were late or absent today based on attendance records. Use this as a guide when reviewing penalties.</p>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Employee</th>
                  <th>Department</th>
                  <th>Status</th>
                  <th>Late minutes</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                @foreach($todayAttendancePenalties as $row)
                  @php
                    $emp = $row->employee;
                    $user = $emp?->user;
                    $dept = $emp?->department;
                  @endphp
                  <tr>
                    <td>
                      <strong>{{ $user->name ?? 'Unknown' }}</strong><br>
                      <span class="muted">{{ $emp->employee_code ?? ('EMP-'.$row->employee_id) }}</span>
                    </td>
                    <td>{{ $dept->department_name ?? 'N/A' }}</td>
                    <td>{{ ucfirst($row->at_status) }}</td>
                    <td>{{ $row->late_minutes ?? 0 }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($row->date)->format('Y-m-d') }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </section>
      @endif

      <!-- DIFFERENT STATS from Attendance Tracking -->
      <section class="summary" id="summary">
        <div class="card"><h3>Total Penalties</h3><p id="s-total">0</p></div>
        <div class="card"><h3>Pending</h3><p id="s-pending">0</p></div>
        <div class="card"><h3>Approved</h3><p id="s-approved">0</p></div>
        <div class="card"><h3>Rejected</h3><p id="s-rejected">0</p></div>
      </section>

      <!-- Filters -->
      <section class="filters">
        <div class="row">
          <div class="split">
            <label for="q">Search (Name/ID)</label>
            <input id="q" type="text" placeholder="e.g., EMP007 or Sarah Lee">
          </div>
          <div class="split">
            <label for="dept">Department</label>
            <select id="dept">
              <option value="">All</option>
              @foreach($departments as $dept)
                <option value="{{ $dept->department_id }}">{{ $dept->department_name }}</option>
              @endforeach
            </select>
          </div>
          <div class="split">
            <label for="reason">Reason</label>
            <select id="reason">
              <option value="">Any</option>
              <option value="Late">Late</option>
              <option value="Absent">Absent</option>
              <option value="Early Checkout">Early Checkout</option>
              <option value="No Clock-in">No Clock-in</option>
            </select>
          </div>
          <div class="split">
            <label for="status">Status</label>
            <select id="status">
              <option value="">Any</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
          <div class="split">
            <label for="start">Start Date</label>
            <input type="date" id="start" value="{{ now()->toDateString() }}">
          </div>
          <div class="split">
            <label for="end">End Date</label>
            <input type="date" id="end" value="{{ now()->toDateString() }}">
          </div>
          <div class="split" style="align-self:end;">
            <button class="btn btn-primary" id="apply"><i class="fa-solid fa-filter"></i> Apply</button>
            <button class="btn btn-ghost" id="clear">Clear</button>
          </div>
        </div>
      </section>

      <!-- Table -->
      <section class="table-wrap">
        <table id="penaltyTable">
          <thead>
            <tr>
              <th>Penalty ID</th>
              <th>Employee</th>
              <th>Department</th>
              <th>Reason</th>
              <th>Points</th>
              <th>Date</th>
              <th>Status</th>
              <th style="width:180px;">Action</th>
            </tr>
          </thead>
          <tbody><!-- JS fills --></tbody>
        </table>
      </section>

      <section class="pagination-wrap" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-top:12px;">
        <span id="paginationInfo">0 records</span>
        <div style="display:flex; align-items:center; gap:10px;">
          <button type="button" class="btn btn-ghost" id="firstPage" disabled><i class="fa-solid fa-angles-left"></i> First</button>
          <button type="button" class="btn btn-ghost" id="prevPage" disabled>Prev</button>
          <span id="pageNum">Page 1 of 1</span>
          <button type="button" class="btn btn-ghost" id="nextPage" disabled>Next</button>
          <button type="button" class="btn btn-ghost" id="lastPage" disabled>Last <i class="fa-solid fa-angles-right"></i></button>
        </div>
        <div>
          <label>Show </label>
          <select id="perPage">
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
      </section>

      <footer>© 2025 Web-Based HRMS. All Rights Reserved.</footer>
    </main>
  </div>

  <!-- Confirm dialog (Approve) -->
  <div class="backdrop" id="confirm">
    <div class="dialog">
      <header id="confirmTitle">Confirm Action</header>
      <div class="body" id="confirmBody">Are you sure?</div>
      <div class="actions">
        <button class="btn btn-ghost" id="cancelAction">Cancel</button>
        <button class="btn btn-primary" id="proceedAction">Proceed</button>
      </div>
    </div>
  </div>

  <!-- Reject dialog (requires reason) -->
  <div class="backdrop" id="rejectBackdrop">
    <div class="dialog">
      <header>Reject Penalty Removal</header>
      <div class="body">
        <p id="rejectContext" style="margin:0 0 12px; color:#374151;"></p>
        <label for="rejectionRemark" style="display:block; margin-bottom:6px; font-weight:600;">Rejection reason / remark <span style="color:#dc2626;">*</span></label>
        <textarea id="rejectionRemark" rows="3" placeholder="Required. Enter reason for rejection..." style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:8px; font-size:.95rem;"></textarea>
        <p id="rejectError" style="margin:8px 0 0; color:#dc2626; font-size:.9rem;"></p>
      </div>
      <div class="actions">
        <button class="btn btn-ghost" id="rejectCancel">Cancel</button>
        <button class="btn btn-reject" id="rejectSubmit"><i class="fa-solid fa-xmark"></i> Reject</button>
      </div>
    </div>
  </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  /* ========= Sidebar: single active, single open, persistence ========= */
  const groups  = document.querySelectorAll('.sidebar-group');
  const toggles = document.querySelectorAll('.sidebar-toggle');
  const links   = document.querySelectorAll('.submenu a');
  const STORAGE_KEY = 'hrms_sidebar_open_group';

  const normPath = (u) => {
    const url = new URL(u, location.origin);
    let p = url.pathname
      .replace(/\/index\.php$/i, '')
      .replace(/\/index\.php\//i, '/')
      .replace(/\/+$/, '');
    return p === '' ? '/' : p;
  };
  const here = normPath(location.href);

  // Let JS own the active/open state to avoid double-highlights
  groups.forEach(g => {
    g.classList.remove('open');
    const t = g.querySelector('.sidebar-toggle');
    if (t) t.setAttribute('aria-expanded','false');
  });
  links.forEach(a => a.classList.remove('active'));

  // Choose exactly one active link
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

  /* ================== Penalty Removal & Tracking logic (API-backed) ================== */
  const ENDPOINT_LIST   = "{{ route('admin.attendance.penalty.data') }}";
  const ENDPOINT_STATUS = (id) => "{{ route('admin.attendance.penalty.status', ['penalty' => '__ID__']) }}".replace('__ID__', id);
  const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  let PENALTIES = [];
  let SUMMARY   = { total:0, pending:0, approved:0, rejected:0 };
  let PAGINATION = { current_page: 1, last_page: 1, per_page: 25, total: 0 };
  let currentPage = 1;
  let perPage = 25;

  const $ = s => document.querySelector(s);
  const tbody = document.querySelector('#penaltyTable tbody');

  function updateStats() {
    $('#s-total').textContent    = SUMMARY.total;
    $('#s-pending').textContent  = SUMMARY.pending;
    $('#s-approved').textContent = SUMMARY.approved;
    $('#s-rejected').textContent = SUMMARY.rejected;
  }

  function updatePagination() {
    const p = PAGINATION;
    if ($('#paginationInfo')) $('#paginationInfo').textContent = (p.total || 0) + ' records';
    if ($('#pageNum')) $('#pageNum').textContent = 'Page ' + (p.current_page || 1) + ' of ' + (p.last_page || 1);
    if ($('#prevPage')) $('#prevPage').disabled = (p.current_page || 1) <= 1;
    if ($('#nextPage')) $('#nextPage').disabled = (p.current_page || 1) >= (p.last_page || 1);
  }

  function wireActions() {
    document.querySelectorAll('.btn-approve').forEach(btn => {
      btn.addEventListener('click', () => {
        const pid = btn.getAttribute('data-pid');
        const row = PENALTIES.find(p => String(p.penalty_id) === String(btn.getAttribute('data-id')));
        if (!row || (row.status_raw || row.status || '').toLowerCase() !== 'pending') return;
        openConfirm(pid, btn.getAttribute('data-id'), 'approve', row);
      });
    });

    document.querySelectorAll('.btn-reject').forEach(btn => {
      btn.addEventListener('click', () => {
        const pid = btn.getAttribute('data-pid');
        const row = PENALTIES.find(p => String(p.penalty_id) === String(btn.getAttribute('data-id')));
        if (!row || (row.status_raw || row.status || '').toLowerCase() !== 'pending') return;
        openRejectModal(pid, btn.getAttribute('data-id'), row);
      });
    });
  }

  function render(rows) {
    tbody.innerHTML = '';
    if (!rows.length) {
      const tr = document.createElement('tr');
      const td = document.createElement('td');
      td.colSpan = 8; td.textContent = 'No penalties match the current filters.';
      tr.appendChild(td); tbody.appendChild(tr);
      updateStats();
      return;
    }

    rows.forEach(r => {
      const tr = document.createElement('tr');
      const statusRaw = (r.status_raw || r.status || '').toLowerCase();
      const isPending = statusRaw === 'pending';
      const actionCell = isPending
        ? `
          <button class="btn-xs btn-approve" data-pid="${r.pid}" data-id="${r.penalty_id}">
            <i class="fa-solid fa-check"></i> Approve
          </button>
          <button class="btn-xs btn-reject" data-pid="${r.pid}" data-id="${r.penalty_id}">
            <i class="fa-solid fa-xmark"></i> Reject
          </button>
        `
        : '<span style="color:#9ca3af;">—</span>';

      tr.innerHTML = `
        <td>${r.pid}</td>
        <td><strong>${r.name}</strong><br><span style="color:#6b7280;">${r.id}</span></td>
        <td>${r.dept}</td>
        <td>${r.reason}</td>
        <td class="points">${r.points}</td>
        <td>${r.date}</td>
        <td><span class="status ${(r.status_raw || r.status || '').toLowerCase()}">${r.status}</span></td>
        <td>${actionCell}</td>
      `;
      tbody.appendChild(tr);
    });

    updateStats();
    wireActions();
  }

  async function applyFilters() {
    const btn = document.getElementById('apply');
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Loading';

    const params = new URLSearchParams({
      q: $('#q').value.trim(),
      department: $('#dept').value,
      reason: $('#reason').value,
      status: $('#status').value,
      start: $('#start').value,
      end: $('#end').value,
      page: String(currentPage),
      per_page: String(perPage),
    });

    try {
      const resp = await fetch(`${ENDPOINT_LIST}?${params.toString()}`, { headers: { 'Accept': 'application/json' }});
      if (!resp.ok) throw new Error('Failed to load penalties');
      const json = await resp.json();
      PENALTIES = Array.isArray(json.data) ? json.data : [];
      SUMMARY = json.summary || SUMMARY;
      PAGINATION = json.pagination || PAGINATION;
      currentPage = PAGINATION.current_page || 1;
      perPage = PAGINATION.per_page || 25;
      if ($('#perPage')) $('#perPage').value = String(perPage);
      render(PENALTIES);
      updatePagination();
    } catch (err) {
      tbody.innerHTML = `<tr><td colspan="8">Could not load penalties. ${err.message}</td></tr>`;
    } finally {
      btn.disabled = false;
      btn.innerHTML = original;
    }
  }

  $('#apply').addEventListener('click', () => { currentPage = 1; applyFilters(); });
  $('#clear').addEventListener('click', () => {
    $('#q').value = '';
    $('#dept').value = '';
    $('#reason').value = '';
    $('#status').value = '';
    $('#start').value = '';
    $('#end').value = '';
    currentPage = 1;
    applyFilters();
  });
  $('#firstPage').addEventListener('click', () => { if (currentPage > 1) { currentPage = 1; applyFilters(); } });
  $('#prevPage').addEventListener('click', () => { if (currentPage > 1) { currentPage--; applyFilters(); } });
  $('#nextPage').addEventListener('click', () => { if (currentPage < (PAGINATION.last_page || 1)) { currentPage++; applyFilters(); } });
  $('#lastPage').addEventListener('click', () => { if (currentPage < (PAGINATION.last_page || 1)) { currentPage = PAGINATION.last_page; applyFilters(); } });
  $('#perPage').addEventListener('change', function() { perPage = parseInt(this.value, 10); currentPage = 1; applyFilters(); });

  // Initial load: show today's penalties by default (start/end prefilled with today)
  applyFilters();

  /* ----- Approve / Reject with confirm dialog ----- */
  const confirmBack = document.getElementById('confirm');
  const confirmTitle = document.getElementById('confirmTitle');
  const confirmBody  = document.getElementById('confirmBody');
  const cancelAction = document.getElementById('cancelAction');
  const proceedAction = document.getElementById('proceedAction');

  let pendingAction = null; // {pid, id, type} for approve
  let pendingReject = null;  // {id, row} for reject

  function openConfirm(pid, id, type, row) {
    confirmTitle.textContent = 'Approve Penalty Removal';
    confirmBody.innerHTML = `
      <p>Are you sure you want to <strong>approve</strong> penalty <strong>${row.pid}</strong> for <strong>${row.name} (${row.id})</strong>?</p>
      <p style="margin-top:8px; color:#6b7280;">Reason: ${row.reason} - Points: ${row.points} - Date: ${row.date}</p>
    `;
    pendingAction = { pid, id, type: 'approve' };
    confirmBack.style.display = 'flex';
  }

  function openRejectModal(pid, id, row) {
    pendingReject = { id, row };
    document.getElementById('rejectContext').textContent = `Penalty ${row.pid} for ${row.name} (${row.id}). Reason: ${row.reason} - Points: ${row.points} - Date: ${row.date}.`;
    document.getElementById('rejectionRemark').value = '';
    document.getElementById('rejectError').textContent = '';
    document.getElementById('rejectBackdrop').style.display = 'flex';
  }

  function closeRejectModal() {
    document.getElementById('rejectBackdrop').style.display = 'none';
    pendingReject = null;
  }

  function closeConfirm() {
    confirmBack.style.display = 'none';
    pendingAction = null;
  }

  cancelAction.addEventListener('click', closeConfirm);
  confirmBack.addEventListener('click', e => { if (e.target === confirmBack) closeConfirm(); });

  document.getElementById('rejectCancel').addEventListener('click', closeRejectModal);
  document.getElementById('rejectBackdrop').addEventListener('click', e => { if (e.target === document.getElementById('rejectBackdrop')) closeRejectModal(); });

  function submitStatus(id, body) {
    return fetch(ENDPOINT_STATUS(id), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': CSRF_TOKEN,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(body),
    });
  }

  proceedAction.addEventListener('click', () => {
    if (!pendingAction) return;
    const { id, type } = pendingAction;

    proceedAction.disabled = true;
    proceedAction.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Working';

    submitStatus(id, { action: type, expected_status: 'pending' })
      .then(async resp => {
        const data = await resp.json().catch(() => ({}));
        if (!resp.ok) {
          throw new Error(data.message || (resp.status === 422 ? 'Already processed.' : 'Failed to update penalty'));
        }
        return data;
      })
      .then(() => {
        closeConfirm();
        applyFilters();
      })
      .catch(err => {
        alert(err.message || 'Unable to update penalty.');
        applyFilters();
      })
      .finally(() => {
        proceedAction.disabled = false;
        proceedAction.innerHTML = 'Proceed';
      });
  });

  document.getElementById('rejectSubmit').addEventListener('click', () => {
    if (!pendingReject) return;
    const remark = document.getElementById('rejectionRemark').value.trim();
    const errEl = document.getElementById('rejectError');
    if (!remark) {
      errEl.textContent = 'Rejection reason is required.';
      return;
    }
    errEl.textContent = '';
    const btn = document.getElementById('rejectSubmit');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Rejecting...';

    submitStatus(pendingReject.id, { action: 'reject', expected_status: 'pending', rejection_remark: remark })
      .then(async resp => {
        const data = await resp.json().catch(() => ({}));
        if (!resp.ok) {
          throw new Error(data.message || (resp.status === 422 ? 'Already processed.' : 'Failed to reject penalty'));
        }
        return data;
      })
      .then(() => {
        closeRejectModal();
        applyFilters();
      })
      .catch(err => {
        errEl.textContent = err.message || 'Failed to reject.';
        applyFilters();
      })
      .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-xmark"></i> Reject';
      });
  });

  /* ----- Init ----- */
  applyFilters();
});
</script>
</body>
</html>
