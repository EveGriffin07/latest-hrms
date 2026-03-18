<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Claim Overtime - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">

  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    body { background:#f5f7fb; }
    main { padding:28px 32px; }
    .page-box {
      background:#fff;
      border-radius:12px;
      padding:18px;
      box-shadow:0 12px 30px rgba(15,23,42,0.08);
      border:1px solid #e5e7eb;
    }
    .page-header { margin-bottom:14px; }
    .page-header h2 { margin:0; color:#0f172a; }
    .page-header p { margin:4px 0 0; color:#6b7280; font-size:14px; }

    .toolbar {
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      justify-content:flex-end;
      margin-bottom:12px;
    }
    .toolbar input,
    .toolbar select {
      min-width:160px;
      padding:10px 12px;
      border:1px solid #d1d5db;
      border-radius:10px;
      background:#fff;
      font-size:14px;
    }

    table {
      width:100%;
      border-collapse:collapse;
      background:#fff;
    }
    th, td { padding:12px 14px; text-align:left; border-bottom:1px solid #e5e7eb; }
    thead th { color:#0f172a; font-weight:600; background:#f8fafc; }

    .status {
      display:inline-flex;
      align-items:center;
      gap:6px;
      padding:6px 10px;
      border-radius:999px;
      font-weight:600;
      font-size:13px;
    }
    .pending { background:#fff7ed; color:#c05621; border:1px solid #fed7aa; }
    .approved { background:#ecfdf3; color:#15803d; border:1px solid #bbf7d0; }
    .hold { background:#eef2ff; color:#4338ca; border:1px solid #c7d2fe; }
    .issue-badge { background:#fef3c7; color:#92400e; border:1px solid #fcd34d; font-size:12px; padding:4px 8px; border-radius:6px; }
    .no-issue { color:#94a3b8; font-size:12px; }

    .btn {
      border:none;
      border-radius:999px;
      padding:8px 14px;
      font-weight:700;
      cursor:pointer;
      display:inline-flex;
      align-items:center;
      gap:8px;
      box-shadow:0 10px 20px rgba(16,185,129,0.25);
    }
    .btn-approve { background:#22c55e; color:#fff; }
    .btn-reject { background:#e5e7eb; color:#1f2937; box-shadow:none; }
    .btn-view { background:#e0e7ff; color:#312e81; box-shadow:none; }
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
      <div class="breadcrumb">Home > Payroll > Claim Overtime</div>
      <div class="page-header">
        <h2>Claim Overtime</h2>
        <p>Review and act on employee overtime requests currently pending approval.</p>
      </div>

      @if(isset($pendingAdmin) || isset($flaggedPending))
      <div class="grid-cards" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:12px; margin-bottom:20px;">
        <div class="mini-card" style="background:#dbeafe; border-radius:10px; padding:12px; text-align:center;">
          <div class="num" style="font-size:24px; font-weight:700; color:#1e40af;">{{ $pendingAdmin ?? 0 }}</div>
          <div class="label" style="font-size:12px; color:#1e40af;">Pending Admin</div>
        </div>
        <div class="mini-card" style="background:#fef3c7; border-radius:10px; padding:12px; text-align:center;">
          <div class="num" style="font-size:24px; font-weight:700; color:#b45309;">{{ $flaggedPending ?? 0 }}</div>
          <div class="label" style="font-size:12px; color:#b45309;">Flagged Pending</div>
        </div>
        <div class="mini-card" style="background:#dcfce7; border-radius:10px; padding:12px; text-align:center;">
          <div class="num" style="font-size:24px; font-weight:700; color:#15803d;">{{ $approvedAdmin ?? 0 }}</div>
          <div class="label" style="font-size:12px; color:#15803d;">Approved</div>
        </div>
        <div class="mini-card" style="background:#fee2e2; border-radius:10px; padding:12px; text-align:center;">
          <div class="num" style="font-size:24px; font-weight:700; color:#991b1b;">{{ ($rejectedSupervisor ?? 0) + ($rejectedAdmin ?? 0) }}</div>
          <div class="label" style="font-size:12px; color:#991b1b;">Rejected (Sup + Admin)</div>
        </div>
      </div>
      @endif

      <div class="page-box">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px; flex-wrap:wrap;">
          <div>
            <h3 style="margin:0 0 4px;">Pending Admin (OT Requests)</h3>
            <p style="margin:0; color:#6b7280;">Review overtime requests approved by supervisors. Toggle &quot;Flagged&quot; to see those marked for careful review.</p>
          </div>
          <div class="toolbar">
            <input type="text" id="search" placeholder="Search employee...">

            <select id="reviewFilter">
              <option value="all">All</option>
              <option value="flagged">Flagged only</option>
            </select>
            <select id="dept">
              <option value="">All Departments</option>
              @foreach($departments as $dept)
                <option value="{{ $dept->department_id }}">{{ $dept->department_name }}</option>
              @endforeach
            </select>
            <select id="range">
              <option value="">Any Date</option>
              <option value="this-month">This Month</option>
              <option value="last-month">Last Month</option>
            </select>
          </div>
        </div>

        <table id="ot-table">
          <thead>
            <tr>
              <th>Employee</th>
              <th>Dept</th>
              <th>Supervisor</th>
              <th>Date</th>
              <th>Hours</th>
              <th>Reason</th>
              <th>Issue</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

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
            <option value="10">10</option>
            <option value="25" selected>25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
      </section>

      <footer style="margin-top:16px; text-align:center; color:#94a3b8; font-size:12px;">Ac 2025 Web-Based HRMS. All Rights Reserved.</footer>
    </main>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', () => {

    const tbody   = document.querySelector('#ot-table tbody');
    const search  = document.getElementById('search');
    const dept    = document.getElementById('dept');
    const range   = document.getElementById('range');
    function getCSRF() {
      const meta = document.querySelector('meta[name="csrf-token"]');
      return (meta && meta.getAttribute('content')) || '';
    }
    const ENDPOINT_LIST   = "{{ route('admin.payroll.overtime.data') }}";
    const ENDPOINT_STATUS = (id) => "{{ route('admin.payroll.overtime.status', ['overtime' => '__ID__']) }}".replace('__ID__', id);
    let currentPage = 1;
    let perPage = 25;
    let pagination = { total: 0, last_page: 1, current_page: 1 };

    function updatePagination() {
      const el = document.getElementById('paginationInfo');
      const num = document.getElementById('pageNum');
      if (el) el.textContent = (pagination.total || 0) + ' records';
      if (num) num.textContent = 'Page ' + (pagination.current_page || 1) + ' of ' + (pagination.last_page || 1);
      const prevBtn = document.getElementById('prevPage');
      const nextBtn = document.getElementById('nextPage');
      const firstBtn = document.getElementById('firstPage');
      const lastBtn = document.getElementById('lastPage');
      if (prevBtn) prevBtn.disabled = (pagination.current_page || 1) <= 1;
      if (nextBtn) nextBtn.disabled = (pagination.current_page || 1) >= (pagination.last_page || 1);
      if (firstBtn) firstBtn.disabled = (pagination.current_page || 1) <= 1;
      if (lastBtn) lastBtn.disabled = (pagination.current_page || 1) >= (pagination.last_page || 1);
    }

    function rangeToDates(key) {
      const now = new Date();
      const firstOfThis = new Date(now.getFullYear(), now.getMonth(), 1);
      if (key === 'this-month') {
        return { start: firstOfThis.toISOString().slice(0,10), end: new Date(now.getFullYear(), now.getMonth()+1, 0).toISOString().slice(0,10) };
      }
      if (key === 'last-month') {
        const start = new Date(now.getFullYear(), now.getMonth()-1, 1);
        const end   = new Date(now.getFullYear(), now.getMonth(), 0);
        return { start: start.toISOString().slice(0,10), end: end.toISOString().slice(0,10) };
      }
      return { start:'', end:'' };
    }

    const reviewFilter = document.getElementById('reviewFilter');

    async function loadData() {
      tbody.innerHTML = '<tr><td colspan="8">Loading...</td></tr>';

      const { start, end } = rangeToDates(range.value);
      const params = new URLSearchParams({
        q: search.value.trim(),
        department: dept.value,
        review_filter: reviewFilter ? reviewFilter.value : 'all',
        start,
        end,
        page: String(currentPage),
        per_page: String(perPage),
      });

      try {
        const resp = await fetch(`${ENDPOINT_LIST}?${params.toString()}`, { headers: { 'Accept': 'application/json' }});
        if (!resp.ok) throw new Error('Unable to load overtime records');
        const json = await resp.json();
        const rows = Array.isArray(json.data) ? json.data : [];
        pagination = json.pagination || { total: 0, last_page: 1, current_page: 1, per_page: perPage };
        currentPage = pagination.current_page || 1;
        if (pagination.per_page) perPage = pagination.per_page;
        const perPageEl = document.getElementById('perPage');
        if (perPageEl && perPageEl.value !== String(perPage)) perPageEl.value = String(perPage);
        renderTable(rows);
        updatePagination();
      } catch (err) {
        tbody.innerHTML = `<tr><td colspan="8">Error: ${err.message}</td></tr>`;
      }
    }

    function renderTable(rows) {
      tbody.innerHTML = '';
      if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="8">No pending admin requests. All listed here have final_status = PENDING_ADMIN.</td></tr>';
        return;
      }

      rows.forEach(item => {
        let issueCell = '<span class="no-issue">—</span>';
        if (item.has_issue) {
          const remark = (item.issue_remark || '').replace(/"/g, '&quot;');
          const shortRemark = (item.issue_remark || '').substring(0, 40) + ((item.issue_remark || '').length > 40 ? '…' : '');
          issueCell = '<span class="issue-badge" title="' + (remark || 'Flagged for review') + '">Flagged</span>';
          if (item.issue_remark) issueCell += '<br><small style="color:#78716c;">' + shortRemark + '</small>';
        }
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td><strong>${item.employee}</strong><br><span style="color:#6b7280;">${item.code}</span></td>
          <td>${item.dept}</td>
          <td>${item.supervisor || '—'}</td>
          <td>${item.date}</td>
          <td>${item.hours}</td>
          <td>${item.reason ?? '-'}</td>
          <td>${issueCell}</td>
          <td>
            <button class="btn btn-approve" data-id="${item.ot_id}" data-action="approve"><i class="fa-solid fa-check"></i> Approve</button>
            <button class="btn btn-reject" data-id="${item.ot_id}" data-action="reject"><i class="fa-solid fa-xmark"></i> Reject</button>
          </td>
        `;
        tbody.appendChild(tr);
      });


      bindActions();
    }

    function bindActions() {
      document.querySelectorAll('[data-action]').forEach(btn => {
        btn.addEventListener('click', async () => {
          const action = btn.dataset.action;
          if (action === 'view') return;

          const id = btn.dataset.id;
          const label = btn.innerHTML;
          btn.disabled = true;
          btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

          try {
            const token = getCSRF();
            if (!token) {
              alert('Session expired or invalid. Please refresh the page and try again.');
              return;
            }
            let comment = '';
            if (action === 'reject') {
              comment = prompt('Reason for rejection (optional):') || '';
            }
            const resp = await fetch(ENDPOINT_STATUS(id), {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({ action, comment, _token: token }),
              credentials: 'same-origin',
            });
            if (!resp.ok) throw new Error(await resp.text() || 'Update failed');
            await loadData();
          } catch (err) {
            alert('Unable to update overtime: ' + err.message);
          } finally {
            btn.disabled = false;
            btn.innerHTML = label;
          }
        });
      });
    }


    search.addEventListener('input', () => { currentPage = 1; loadData(); });
    if (reviewFilter) reviewFilter.addEventListener('change', () => { currentPage = 1; loadData(); });
    dept.addEventListener('change', () => { currentPage = 1; loadData(); });
    range.addEventListener('change', () => { currentPage = 1; loadData(); });
    document.getElementById('firstPage').addEventListener('click', () => { if (currentPage > 1) { currentPage = 1; loadData(); } });
    document.getElementById('prevPage').addEventListener('click', () => { if (currentPage > 1) { currentPage--; loadData(); } });
    document.getElementById('nextPage').addEventListener('click', () => { if (currentPage < (pagination.last_page || 1)) { currentPage++; loadData(); } });
    document.getElementById('lastPage').addEventListener('click', () => { if (currentPage < (pagination.last_page || 1)) { currentPage = pagination.last_page; loadData(); } });
    document.getElementById('perPage').addEventListener('change', function() { perPage = parseInt(this.value, 10); currentPage = 1; loadData(); });

    loadData();
  });
  </script>
</body>
</html>

