<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Audit Logs - HRMS</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
<link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
  .audit-page { display: flex; min-height: calc(100vh - 60px); }
  .audit-sidebar { width: 240px; flex-shrink: 0; background: #f8fafc; border-right: 1px solid #e2e8f0; padding: 16px; }
  .audit-sidebar h3 { font-size: 0.85rem; color: #64748b; text-transform: uppercase; margin: 0 0 12px; padding: 0 8px; }
  .audit-sidebar label { display: block; font-size: 0.8rem; color: #475569; margin-bottom: 4px; }
  .audit-sidebar select, .audit-sidebar input { width: 100%; padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 12px; font-size: 0.9rem; }
  .audit-sidebar .filter-section { margin-bottom: 20px; }
  .audit-sidebar .sidebar-item { padding: 8px 12px; border-radius: 8px; margin-bottom: 4px; cursor: pointer; font-size: 0.9rem; }
  .audit-sidebar .sidebar-item:hover { background: #e2e8f0; }
  .audit-sidebar .sidebar-item.active { background: #0f172a; color: #fff; }
  .audit-main { flex: 1; padding: 24px; overflow: auto; }
  .audit-topbar { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-bottom: 20px; }
  .audit-search { flex: 1; min-width: 200px; max-width: 400px; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; }
  .audit-btn { padding: 10px 18px; border-radius: 10px; font-size: 0.9rem; cursor: pointer; border: 1px solid #e2e8f0; background: #fff; }
  .audit-btn.primary { background: #0f172a; color: #fff; border-color: #0f172a; }
  .audit-btn.primary:hover { background: #1e293b; }
  .audit-table-wrap { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.08); overflow: hidden; }
  table.audit-table { width: 100%; border-collapse: collapse; }
  table.audit-table th, table.audit-table td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
  table.audit-table th { background: #f8fafc; font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600; }
  table.audit-table tbody tr:hover { background: #f8fafc; }
  table.audit-table tbody tr:hover .view-detail { opacity: 1; }
  .user-cell { display: flex; align-items: center; gap: 10px; }
  .user-avatar { width: 36px; height: 36px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #64748b; font-size: 0.9rem; overflow: hidden; }
  .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
  .user-info .name { font-weight: 500; color: #0f172a; }
  .user-info .role { font-size: 0.8rem; color: #64748b; }
  .entity-id { font-family: monospace; font-size: 0.85rem; cursor: pointer; max-width: 120px; overflow: hidden; text-overflow: ellipsis; }
  .action-tag { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 500; }
  .action-tag.create, .action-tag.approve, .action-tag.check_in, .action-tag.check_out, .action-tag.enroll_success { background: #dcfce7; color: #166534; }
  .action-tag.update { background: #dbeafe; color: #1e40af; }
  .action-tag.delete, .action-tag.reject { background: #fee2e2; color: #991b1b; }
  .action-tag.default { background: #f1f5f9; color: #475569; }
  .status-success { color: #166534; }
  .status-failed { color: #991b1b; }
  .timestamp-date { font-weight: 500; }
  .timestamp-time { font-size: 0.8rem; color: #64748b; }
  .view-detail { font-size: 0.85rem; color: #2563eb; cursor: pointer; opacity: 0; transition: opacity .2s; }
  .view-detail:hover { text-decoration: underline; }
  .pagination-wrap { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-top: 16px; padding: 12px 0; }
  .pagination-info { font-size: 0.9rem; color: #64748b; }
  .pagination-controls { display: flex; align-items: center; gap: 12px; }
  .pagination-controls button { padding: 8px 14px; border: 1px solid #e2e8f0; background: #fff; border-radius: 8px; cursor: pointer; font-size: 0.9rem; }
  .pagination-controls button:disabled { opacity: 0.5; cursor: not-allowed; }
  .pagination-controls .page-num { font-size: 0.9rem; color: #475569; }
  .page-size select { padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; }
  .skeleton { background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%); background-size: 200% 100%; animation: skeleton 1.2s ease-in-out infinite; border-radius: 6px; }
  @keyframes skeleton { to { background-position: 200% 0; } }
  .skeleton-row td { padding: 14px 16px; }
  .skeleton-row .skeleton { height: 20px; }
  .drawer-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 1000; display: none; }
  .drawer-overlay.visible { display: block; }
  .drawer { position: fixed; top: 0; right: 0; width: 100%; max-width: 480px; height: 100%; background: #fff; box-shadow: -4px 0 20px rgba(0,0,0,.15); z-index: 1001; overflow: auto; transform: translateX(100%); transition: transform .25s ease; }
  .drawer.visible { transform: translateX(0); }
  .drawer-content { padding: 24px; }
  .drawer h2 { margin: 0 0 20px; font-size: 1.25rem; }
  .drawer .detail-row { margin-bottom: 16px; }
  .drawer .detail-label { font-size: 0.75rem; text-transform: uppercase; color: #64748b; margin-bottom: 4px; }
  .drawer .detail-value { font-size: 0.95rem; }
  .drawer .meta-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
  .drawer .meta-table th, .drawer .meta-table td { padding: 8px 12px; border: 1px solid #e2e8f0; text-align: left; }
  .drawer .meta-table th { background: #f8fafc; width: 140px; }
  .drawer-actions { margin-top: 24px; display: flex; gap: 10px; }
  .drawer-actions button { padding: 10px 18px; border-radius: 8px; cursor: pointer; font-size: 0.9rem; }
  .drawer-actions .btn-close { background: #f1f5f9; border: 1px solid #e2e8f0; }
  .drawer-actions .btn-copy { background: #0f172a; color: #fff; border: none; }
</style>
</head>
<body>
<header><div class="title">Web-Based HRMS</div><div class="user-info">
    <a href="{{ route('admin.profile') }}" style="text-decoration: none; color: inherit;"><i class="fa-regular fa-bell"></i> &nbsp; HR Admin</a>
</div></header>

<div class="container">
  @include('admin.layout.sidebar')

  <main class="audit-main">
    <div class="breadcrumb">Home > Audit Log</div>
    <h2>Audit Logs</h2>
    <p class="subtitle">View system and user actions. Read-only.</p>

    <div class="audit-page">
      {{-- Left settings sidebar (filters) --}}
      <aside class="audit-sidebar">
        <h3>Filters</h3>
        <div class="filter-section">
          <label>Action</label>
          <select id="filter-action">
            <option value="">All</option>
            <option value="login_success">Login</option>
            <option value="login_failed">Login Failed</option>
            <option value="check_in_success">Check In</option>
            <option value="check_out_success">Check Out</option>
            <option value="face_enrollment_started">Enroll Start</option>
            <option value="face_enrollment_success">Enroll Success</option>
            <option value="face_enrollment_failed">Enroll Failed</option>
            <option value="leave_request_created">Leave Created</option>
            <option value="leave_request_cancelled">Leave Cancelled</option>
            <option value="leave_request_approved">Leave Approved</option>
            <option value="leave_request_rejected">Leave Rejected</option>
            <option value="attendance_failed">Attendance Failed</option>
          </select>
        </div>
        <div class="filter-section">
          <label>Type</label>
          <select id="filter-type">
            <option value="">All</option>
            <option value="AUTH">Auth</option>
            <option value="FACE">Face</option>
            <option value="ATTENDANCE">Attendance</option>
            <option value="LEAVE">Leave</option>
            <option value="PROFILE">Profile</option>
          </select>
        </div>
        <div class="filter-section">
          <label>Date range</label>
          <select id="filter-date-range">
            <option value="last7">Last 7 days</option>
            <option value="all">All</option>
            <option value="today">Today</option>
            <option value="last30">Last 30 days</option>
            <option value="custom">Custom</option>
          </select>
        </div>
        <div class="filter-section" id="custom-dates" style="display:none">
          <label>From</label>
          <input type="date" id="filter-date-from">
          <label>To</label>
          <input type="date" id="filter-date-to">
        </div>
        <div class="filter-section">
          <label>Status</label>
          <select id="filter-status">
            <option value="">All</option>
            <option value="SUCCESS">Success</option>
            <option value="FAILED">Failed</option>
          </select>
        </div>
      </aside>

      <div style="flex:1; min-width:0;">
        {{-- Top filter / search bar --}}
        <div class="audit-topbar">
          <input type="text" class="audit-search" id="keyword" placeholder="Search by user, employee, entity ID, message…">
          <button type="button" class="audit-btn primary" id="btn-filter">Filter</button>
          <button type="button" class="audit-btn" id="btn-clear">Clear</button>
        </div>

        {{-- Table --}}
        <div class="audit-table-wrap">
          <table class="audit-table">
            <thead>
              <tr>
                <th>User</th>
                <th>Entity ID</th>
                <th>Action</th>
                <th>Type</th>
                <th>Timestamp</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="table-body">
              <tr class="skeleton-row"><td colspan="6"><div class="skeleton" style="height:24px"></div></td></tr>
              <tr class="skeleton-row"><td colspan="6"><div class="skeleton" style="height:24px"></div></td></tr>
              <tr class="skeleton-row"><td colspan="6"><div class="skeleton" style="height:24px"></div></td></tr>
            </tbody>
          </table>
        </div>

        {{-- Pagination + page size --}}
        <div class="pagination-wrap">
          <span class="pagination-info" id="pagination-info">0 items</span>
          <div class="pagination-controls">
            <button type="button" id="btn-prev" disabled>Previous</button>
            <span class="page-num" id="page-num">Page 1 of 1</span>
            <button type="button" id="btn-next" disabled>Next</button>
          </div>
          <div class="page-size">
            <label>Show </label>
            <select id="page-size">
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <footer style="margin-top:24px">© 2025 Web-Based HRMS. All Rights Reserved.</footer>
  </main>
</div>

{{-- Detail drawer --}}
<div class="drawer-overlay" id="drawer-overlay"></div>
<div class="drawer" id="drawer">
  <div class="drawer-content">
    <h2>Audit log detail</h2>
    <div id="drawer-body"></div>
    <div class="drawer-actions">
      <button type="button" class="btn-close" id="drawer-close">Close</button>
    </div>
  </div>
</div>

<script>
(function() {
  const DATA_URL = "{{ route('admin.audit.log.data') }}";
  const SHOW_URL = (id) => "{{ url('admin/audit-log') }}/" + id;

  let state = { page: 1, per_page: 25, total: 0, last_page: 1, detailJson: null };
  let debounceTimer = null;

  const tbody = document.getElementById('table-body');
  const keyword = document.getElementById('keyword');
  const filterAction = document.getElementById('filter-action');
  const filterType = document.getElementById('filter-type');
  const filterDateRange = document.getElementById('filter-date-range');
  const filterDateFrom = document.getElementById('filter-date-from');
  const filterDateTo = document.getElementById('filter-date-to');
  const filterStatus = document.getElementById('filter-status');
  const customDates = document.getElementById('custom-dates');
  const btnFilter = document.getElementById('btn-filter');
  const btnClear = document.getElementById('btn-clear');
  const btnPrev = document.getElementById('btn-prev');
  const btnNext = document.getElementById('btn-next');
  const pageNum = document.getElementById('page-num');
  const pageSize = document.getElementById('page-size');
  const paginationInfo = document.getElementById('pagination-info');
  const drawerOverlay = document.getElementById('drawer-overlay');
  const drawer = document.getElementById('drawer');
  const drawerBody = document.getElementById('drawer-body');
  const drawerClose = document.getElementById('drawer-close');

  filterDateRange.addEventListener('change', function() {
    customDates.style.display = this.value === 'custom' ? 'block' : 'none';
  });

  function getParams() {
    const p = new URLSearchParams();
    p.set('page', state.page);
    p.set('per_page', state.per_page);
    if (keyword.value.trim()) p.set('keyword', keyword.value.trim());
    if (filterAction.value) p.set('action', filterAction.value);
    if (filterType.value) p.set('type', filterType.value);
    if (filterDateRange.value) p.set('date_range', filterDateRange.value);
    if (filterDateRange.value === 'custom') {
      if (filterDateFrom.value) p.set('date_from', filterDateFrom.value);
      if (filterDateTo.value) p.set('date_to', filterDateTo.value);
    }
    if (filterStatus.value) p.set('status', filterStatus.value);
    return p;
  }

  function actionTagClass(action) {
    const a = (action || '').toLowerCase();
    if (a.includes('create') || a.includes('approve') || a.includes('check_in') || a.includes('check_out') || a.includes('enroll_success')) return 'create';
    if (a.includes('update')) return 'update';
    if (a.includes('delete') || a.includes('reject') || a.includes('failed')) return 'delete';
    return 'default';
  }

  function escapeHtml(s) {
    if (s == null) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function renderSkeleton() {
    tbody.innerHTML = '';
    for (let i = 0; i < 5; i++) {
      const tr = document.createElement('tr');
      tr.className = 'skeleton-row';
      tr.innerHTML = '<td colspan="6"><div class="skeleton" style="height:20px"></div></td>';
      tbody.appendChild(tr);
    }
  }

  function renderTable(data) {
    if (!data || data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:#64748b">No logs found.</td></tr>';
      return;
    }
    tbody.innerHTML = data.map(row => {
      const user = row.user || {};
      const avatar = user.avatar_url
        ? '<img src="' + escapeHtml(user.avatar_url) + '" alt="">'
        : (user.name ? (user.name.charAt(0) || '?').toUpperCase() : '—');
      const tagClass = actionTagClass(row.action);
      const date = (row.timestamp || '').split(' ');
      const dateStr = date[0] || '';
      const timeStr = date[1] || '';
      return '<tr>' +
        '<td><div class="user-cell"><div class="user-avatar">' + avatar + '</div><div class="user-info"><div class="name">' + escapeHtml(user.name || '—') + '</div><div class="role">' + escapeHtml(user.role || '') + '</div></div></div></td>' +
        '<td class="entity-id" title="' + escapeHtml(row.entity_id) + '">' + escapeHtml(String(row.entity_id).length > 16 ? String(row.entity_id).slice(0, 16) + '…' : row.entity_id) + '</td>' +
        '<td><span class="action-tag ' + tagClass + '">' + escapeHtml(row.action || '—') + '</span></td>' +
        '<td>' + escapeHtml(row.type || '—') + '</td>' +
        '<td><div class="timestamp-date">' + escapeHtml(dateStr) + '</div><div class="timestamp-time">' + escapeHtml(timeStr) + '</div></td>' +
        '<td><span class="view-detail" data-id="' + row.id + '">View detail</span></td>' +
      '</tr>';
    }).join('');

    tbody.querySelectorAll('.view-detail').forEach(el => {
      el.addEventListener('click', () => openDetail(parseInt(el.getAttribute('data-id'), 10)));
    });
    tbody.querySelectorAll('.entity-id').forEach(el => {
      el.addEventListener('click', function() {
        const full = this.getAttribute('title') || this.textContent;
        if (full && navigator.clipboard) navigator.clipboard.writeText(full);
      });
    });
  }

  function load() {
    renderSkeleton();
    fetch(DATA_URL + '?' + getParams().toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json())
      .then(res => {
        state.total = res.total || 0;
        state.last_page = res.last_page || 1;
        state.page = res.current_page || 1;
        renderTable(res.data || []);
        paginationInfo.textContent = state.total + ' item' + (state.total !== 1 ? 's' : '');
        pageNum.textContent = 'Page ' + state.page + ' of ' + (state.last_page || 1);
        btnPrev.disabled = state.page <= 1;
        btnNext.disabled = state.page >= state.last_page;
      })
      .catch(() => {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:#991b1b">Failed to load.</td></tr>';
      });
  }

  function openDetail(id) {
    fetch(SHOW_URL(id), { headers: { 'Accept': 'application/json' } })
      .then(r => r.json())
      .then(log => {
        state.detailJson = log;
        let metaHtml = '';
        const meta = log.metadata || {};
        const beforeStatus = meta['before_status'] ?? null;
        const afterStatus = meta['after_status'] ?? null;
        const transitionSuffix = (beforeStatus || afterStatus)
          ? ' (' + escapeHtml(beforeStatus ?? '—') + ' -> ' + escapeHtml(afterStatus ?? '—') + ')'
          : '';
        const metaEntries = Object.entries(meta || {}).filter(([k]) => String(k).toLowerCase() !== 'ip');
        if (metaEntries.length) {
          metaHtml = '<div class="detail-row"><div class="detail-label">Metadata</div><table class="meta-table"><tbody>' +
            metaEntries.map(([k, v]) => '<tr><th>' + escapeHtml(k) + '</th><td>' + escapeHtml(typeof v === 'object' ? JSON.stringify(v) : String(v)) + '</td></tr>').join('') +
            '</tbody></table></div>';
        }
        drawerBody.innerHTML =
          '<div class="detail-row"><div class="detail-label">Actor</div><div class="detail-value">' + escapeHtml(log.actor_name || '—') + ' (' + escapeHtml(log.actor_role || '—') + ')</div></div>' +
          '<div class="detail-row"><div class="detail-label">Action / Status</div><div class="detail-value">' + escapeHtml(log.action || '—') + ' / ' + escapeHtml(log.status || '—') + transitionSuffix + '</div></div>' +
          '<div class="detail-row"><div class="detail-label">Entity</div><div class="detail-value">' + escapeHtml(log.entity_type || '—') + ' #' + escapeHtml(log.entity_id || '—') + '</div></div>' +
          '<div class="detail-row"><div class="detail-label">Type</div><div class="detail-value">' + escapeHtml(log.type || '—') + '</div></div>' +
          '<div class="detail-row"><div class="detail-label">Timestamp</div><div class="detail-value">' + escapeHtml(log.timestamp || '—') + '</div></div>' +
          '<div class="detail-row"><div class="detail-label">Message</div><div class="detail-value">' + escapeHtml(log.message || '—') + '</div></div>' +
          metaHtml;
        drawer.classList.add('visible');
        drawerOverlay.classList.add('visible');
      })
      .catch(() => {
        drawerBody.innerHTML = '<p style="color:#991b1b">Could not load detail.</p>';
        drawer.classList.add('visible');
        drawerOverlay.classList.add('visible');
      });
  }

  function closeDrawer() {
    drawer.classList.remove('visible');
    drawerOverlay.classList.remove('visible');
  }

  btnFilter.addEventListener('click', () => { state.page = 1; load(); });
  keyword.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => { state.page = 1; load(); }, 400);
  });
  btnClear.addEventListener('click', () => {
    keyword.value = '';
    filterAction.value = '';
    filterType.value = '';
    filterDateRange.value = 'last7';
    filterDateFrom.value = '';
    filterDateTo.value = '';
    filterStatus.value = '';
    customDates.style.display = 'none';
    state.page = 1;
    load();
  });
  btnPrev.addEventListener('click', () => { if (state.page > 1) { state.page--; load(); } });
  btnNext.addEventListener('click', () => { if (state.page < state.last_page) { state.page++; load(); } });
  pageSize.addEventListener('change', () => { state.per_page = parseInt(pageSize.value, 10); state.page = 1; load(); });
  drawerOverlay.addEventListener('click', closeDrawer);
  drawerClose.addEventListener('click', closeDrawer);

  load();
})();
</script>
</body>
</html>
