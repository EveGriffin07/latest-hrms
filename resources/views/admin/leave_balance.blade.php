<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Leave Balance Tracking - HRMS</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
<link rel="stylesheet" href="{{ asset('css/hrms.css') }}">

<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
  .box{background:#fff;border-radius:10px;padding:16px;margin-bottom:16px}
  table{width:100%;border-collapse:collapse;background:#fff;border-radius:10px;overflow:hidden}
  th,td{padding:10px;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:middle}
  thead{background:#0f172a;color:#38bdf8}
  .row{display:flex;gap:10px;flex-wrap:wrap}
  .row>*{flex:1 1 200px}
  input,select,button{padding:8px;border:1px solid #d1d5db;border-radius:8px;background:#fff}
  input[type=number]{width:100px}
  .btn{background:#38bdf8;color:#0f172a;border-color:#38bdf8;cursor:pointer}
  .btn-ghost{background:#fff}
  .muted{color:#6b7280;font-size:.9rem}
  .actions{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
  .actions input[disabled]{background:#f1f5f9;color:#64748b;cursor:not-allowed}
  .btn-edit{background:#6366f1;color:#fff;border-color:#6366f1;cursor:pointer;padding:10px 20px;font-size:15px;border-radius:8px}
  .btn-save{background:#16a34a;color:#fff;border-color:#16a34a;cursor:pointer;padding:10px 20px;font-size:15px;border-radius:8px}
  .btn-cancel-edit{background:#64748b;color:#fff;border-color:#64748b;cursor:pointer;padding:10px 20px;font-size:15px;border-radius:8px}
  .table-toolbar{justify-content:flex-end}
  /* modal */
  .modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,.5)}
  .sheet{background:#fff;border-radius:10px;padding:16px;max-width:720px;width:92%}
</style>
</head>
<body>
<header><div class="title">Web-Based HRMS</div>
<div class="user-info">
    <a href="{{ route('admin.profile') }}" style="text-decoration: none; color: inherit;">
        <i class="fa-regular fa-bell"></i> &nbsp; HR Admin
    </a>
</div>
</header>

<div class="container">
  @include('admin.layout.sidebar')


  <main>
    <div class="breadcrumb">Home > Leave > Leave Balance Tracking</div>
    <h2>Leave Balance Tracking</h2>
    <p class="subtitle">View, edit (via <strong>Edit</strong>), and see detailed balance history and validity.</p>

    <!-- Filters -->
    <div class="box">
      <div class="row">
        <div>
          <label>Search</label>
          <input id="q" type="text" placeholder="EMP001 / name">
        </div>
        <div>
          <label>Department</label>
          <select id="dept">

            <option value="">All</option>
            @foreach($departments as $dept)
              <option value="{{ $dept->department_id }}">{{ $dept->department_name }}</option>
            @endforeach
          </select>
        </div>
        <div style="align-self:end">
          <button class="btn" id="apply">Filter</button>
          <button class="btn-ghost" id="clear">Clear</button>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="box">
      <div class="table-toolbar" style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
        <button type="button" class="btn-edit" id="table-edit-btn">Edit</button>
        <span id="table-save-cancel" style="display:none;">
          <button type="button" class="btn-save" id="table-save-btn">Save</button>
          <button type="button" class="btn-cancel-edit" id="table-cancel-btn">Cancel</button>
        </span>
      </div>
      <table id="tbl">
        <thead>
          <tr>
            <th>Employee</th><th>Department</th>
            <th>Annual (Remain)</th><th>Sick (Remain)</th>
            <th>Details</th><th>Actions</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>

    </div>

    <footer>© 2025 Web-Based HRMS. All Rights Reserved.</footer>
  </main>
</div>

<!-- Details modal -->
<div class="modal" id="modal">
  <div class="sheet">
    <h3 style="margin:0 0 6px">Leave Balance Details</h3>
    <div id="meta" class="muted" style="margin-bottom:10px"></div>
    <table style="width:100%;border:1px solid #e5e7eb;border-collapse:collapse">
      <thead>
        <tr style="background:#f8fafc">
          <th style="padding:8px;text-align:left">Type</th>
          <th style="padding:8px;text-align:right">Total</th>
          <th style="padding:8px;text-align:right">Used</th>
          <th style="padding:8px;text-align:right">Remaining</th>
        </tr>
      </thead>
      <tbody id="breakdown"></tbody>
    </table>
    <div class="muted" id="validity" style="margin-top:8px"></div>
    <div style="margin-top:12px;text-align:right">
      <button id="close">Close</button>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  /* ==========================================
     SIDEBAR — single active, single open
     ========================================== */

  const ENDPOINT = "{{ route('admin.leave.balance.data') }}";
  const groups  = document.querySelectorAll('.sidebar-group');
  const toggles = document.querySelectorAll('.sidebar-toggle');
  const links   = document.querySelectorAll('.submenu a');
  const STORAGE_KEY = 'hrms_sidebar_open_group';

  // Normalize a URL path for reliable comparison
  const normPath = (u) => {
    const url = new URL(u, location.origin);
    let p = url.pathname
      .replace(/\/index\.php/i,'')  // strip Laravel front controller if present
      .replace(/\/+$/,'');          // strip trailing slash
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

  // Find exactly one active link (exact match; fallback: startsWith)
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

  // Restore last-opened group or default to the first group
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

  // Accordion toggling (persist which group is open)
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

/* ================== Leave balance logic ================== */
  const $ = (s)=>document.querySelector(s);
  const tbody = $('#tbl tbody');
  let currentRows = [];

  function render(rows) {
    currentRows = rows || [];
    tbody.innerHTML = '';
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="6">No records.</td></tr>';
      return;
    }
    rows.forEach(r => {
      const tr = document.createElement('tr');
      tr.dataset.rowId = r.id;
      tr.innerHTML = `
        <td><strong>${r.name}</strong><br><span class="muted">${r.id}</span></td>
        <td>${r.dept}</td>
        <td>${r.annual}</td>
        <td>${r.sick}</td>
        <td><button type="button" class="btn-ghost" data-id="${r.id}">View</button></td>
        <td class="actions">
          <input type="number" min="0" data-field="annual" data-id="${r.id}" value="${r.annual}" disabled />
          <input type="number" min="0" data-field="sick" data-id="${r.id}" value="${r.sick}" disabled />
        </td>
      `;
      tbody.appendChild(tr);
    });
    bindActions(rows);
  }

  function bindActions(rows) {
    document.querySelectorAll('.btn-ghost').forEach(btn => {
      btn.addEventListener('click', () => {
        const row = rows.find(x => x.id === btn.dataset.id);
        if (!row) return;
        openModal(row);
      });
    });
  }

  const tableEditBtn = document.getElementById('table-edit-btn');
  const tableSaveCancel = document.getElementById('table-save-cancel');
  const tableSaveBtn = document.getElementById('table-save-btn');
  const tableCancelBtn = document.getElementById('table-cancel-btn');

  if (tableEditBtn) {
    tableEditBtn.addEventListener('click', () => {
      tbody.querySelectorAll('input[data-field]').forEach(inp => inp.disabled = false);
      tableEditBtn.style.display = 'none';
      tableSaveCancel.style.display = 'inline';
    });
  }
  if (tableCancelBtn) {
    tableCancelBtn.addEventListener('click', () => {
      currentRows.forEach(row => {
        const tr = tbody.querySelector(`tr[data-row-id="${row.id}"]`);
        if (!tr) return;
        tr.querySelector('input[data-field="annual"]').value = row.annual;
        tr.querySelector('input[data-field="annual"]').disabled = true;
        tr.querySelector('input[data-field="sick"]').value = row.sick;
        tr.querySelector('input[data-field="sick"]').disabled = true;
      });
      tableEditBtn.style.display = 'inline';
      tableSaveCancel.style.display = 'none';
    });
  }
  if (tableSaveBtn) {
    tableSaveBtn.addEventListener('click', () => {
      tbody.querySelectorAll('tr[data-row-id]').forEach(tr => {
        const id = tr.dataset.rowId;
        const annualInp = tr.querySelector('input[data-field="annual"]');
        const sickInp = tr.querySelector('input[data-field="sick"]');
        if (!annualInp || !sickInp) return;
        annualInp.disabled = true;
        sickInp.disabled = true;
        const annual = annualInp.value;
        const sick = sickInp.value;
        const row = currentRows.find(x => x.id === id);
        if (row) { row.annual = annual; row.sick = sick; }
        if (tr.cells[2]) tr.cells[2].textContent = annual;
        if (tr.cells[3]) tr.cells[3].textContent = sick;
      });
      tableEditBtn.style.display = 'inline';
      tableSaveCancel.style.display = 'none';
    });
  }

  async function loadData() {
    tbody.innerHTML = '<tr><td colspan="6">Loading...</td></tr>';
    const params = new URLSearchParams({
      department: $('#dept').value,
      q: $('#q').value.trim(),
    });
    try {
      const resp = await fetch(`${ENDPOINT}?${params.toString()}`, { headers: { 'Accept': 'application/json' }});
      if (!resp.ok) throw new Error('Failed to load balances');
      const json = await resp.json();
      render(Array.isArray(json.data) ? json.data : []);
    } catch (err) {
      tbody.innerHTML = `<tr><td colspan="6">Error: ${err.message}</td></tr>`;
    }
  }

  $('#apply').addEventListener('click', loadData);
  $('#clear').addEventListener('click', () => {
    $('#q').value = '';
    $('#dept').value = '';
    loadData();
  });

  /* Modal */
  const modal = document.getElementById('modal');
  const meta = document.getElementById('meta');
  const breakdown = document.getElementById('breakdown');
  const validity = document.getElementById('validity');
  document.getElementById('close').addEventListener('click', () => modal.style.display='none');

  function openModal(row) {
    meta.textContent = `${row.name} (${row.id}) — ${row.dept}`;
    breakdown.innerHTML = row.detail.map(d => `
      <tr>
        <td style="padding:8px;">${d.type}</td>
        <td style="padding:8px;text-align:right">${d.total}</td>
        <td style="padding:8px;text-align:right">${d.used}</td>
        <td style="padding:8px;text-align:right">${d.total - d.used}</td>
      </tr>
    `).join('');
    validity.textContent = 'Validity: current plan year (demo data)';
    modal.style.display = 'flex';
  }

  // initial load
  loadData();
});
</script>

</body>
</html>
