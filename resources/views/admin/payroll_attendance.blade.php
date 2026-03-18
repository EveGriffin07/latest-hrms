<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Attendance Calculation - Payroll - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
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
    .pill{padding:4px 8px;border-radius:999px;font-size:.8rem;white-space:nowrap}
    .ok{background:#dcfce7;color:#166534}.warn{background:#fef9c3;color:#854d0e}.neg{background:#fee2e2;color:#991b1b}
    .right{text-align:right}
    .actions {display:flex;gap:8px;flex-wrap:wrap}
    .edit-on {outline: 2px dashed #38bdf8; outline-offset: 2px; border-radius:8px; padding:2px}

    /* === Sidebar enhancements === */
    .sidebar-toggle{display:flex;align-items:center;justify-content:space-between;padding:10px 12px;text-decoration:none;color:#0f172a;border-radius:10px}
    .sidebar-toggle .left{display:flex;gap:8px;align-items:center}
    .sidebar-toggle:hover{background:#f1f5f9}
    .sidebar-group .arrow{transition:transform .2s}
    .sidebar-group.open .arrow{transform:rotate(90deg)}
    .submenu{display:none;margin:0;padding-left:0;list-style:none}
    .sidebar-group.open .submenu{display:block}
    .submenu a{display:block;padding:8px 10px;border-radius:8px;margin:4px 8px;color:#0f172a;text-decoration:none}
    .submenu a.active{background:#e0f2fe;color:#0c4a6e;font-weight:600}
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
    <div class="breadcrumb">Home > Payroll > Attendance Calculation</div>
    <h2>Attendance Calculation</h2>
    <p class="subtitle">Select multiple employees, click <strong>Edit Selected</strong>, adjust values, then <strong>Save</strong> or <strong>Cancel</strong>.</p>

    <!-- How it works -->
    <div class="box">
      <div class="muted">
        Attendance % = <em>Payable Days ÷ Working Days × 100</em> &nbsp;•&nbsp;
        Health: <em>Good ≥ 95%</em>, <em>Watch 90–94.99%</em>, <em>Issue &lt; 90%</em>.
        Edits are front-end only (demo). Hook to your DB later to persist.
      </div>
    </div>

    <!-- Filters -->
    <div class="box">
      <div class="row">
        <div>
          <label>Search</label>
          <input id="q" type="text" placeholder="EMP001 or name">
        </div>
        <div>
          <label>Department</label>
          <select id="dept">
            <option value="">All</option><option>IT</option><option>HR</option><option>Finance</option><option>Marketing</option>
          </select>
        </div>
        <div>
          <label>From (last activity)</label>
          <input type="date" id="start">
        </div>
        <div>
          <label>To (last activity)</label>
          <input type="date" id="end">
        </div>
        <div style="align-self:end">
          <button class="btn" id="apply">Apply</button>
          <button class="btn-ghost" id="clear">Clear</button>
        </div>
      </div>
    </div>

    <!-- Batch actions & KPIs -->
    <div class="box">
      <div class="actions" style="margin-bottom:10px">
        <button class="btn" id="editSelected"><i class="fa-solid fa-pen-to-square"></i> Edit Selected</button>
        <button class="btn-ghost" id="saveEdits" disabled><i class="fa-solid fa-floppy-disk"></i> Save</button>
        <button class="btn-ghost" id="cancelEdits" disabled><i class="fa-solid fa-rotate-left"></i> Cancel</button>
      </div>
      <div class="row" style="align-items:stretch">
        <div><strong>Employees</strong><div id="k-emp" class="muted">-</div></div>
        <div><strong>Total Payable Days</strong><div id="k-days" class="muted">-</div></div>
        <div><strong>Total OT Hours</strong><div id="k-ot" class="muted">-</div></div>
        <div><strong>Total Penalty Pts</strong><div id="k-pen" class="muted">-</div></div>
        <div><strong>Average Attendance %</strong><div id="k-att" class="muted">-</div></div>
      </div>
    </div>

    <!-- Table -->
    <div class="box">
      <table id="tbl">
        <thead>
          <tr>
            <th style="width:36px;"><input type="checkbox" id="checkAll"></th>
            <th>Employee</th>
            <th>Department</th>
            <th class="right">Working Days</th>
            <th class="right">Payable Days</th>
            <th class="right">OT Hours</th>
            <th class="right">Penalty Pts</th>
            <th class="right">Attendance %</th>
            <th>Health</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
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
      <p class="muted" style="margin-top:8px">Tip: use the checkbox column to select multiple rows for editing.</p>
    </div>

    <footer>© 2025 Web-Based HRMS. All Rights Reserved.</footer>
  </main>
</div>

<script>
/* ==========================================
   SIDEBAR (accordion, active, persistence)
   ========================================== */
document.addEventListener('DOMContentLoaded', () => {
  const groups  = document.querySelectorAll('.sidebar-group');
  const toggles = document.querySelectorAll('.sidebar-toggle');
  const links   = document.querySelectorAll('.submenu a');
  const STORAGE_KEY = 'hrms_sidebar_open_group';

  // Close all first (avoid multiple groups staying open)
  groups.forEach(g => {
    g.classList.remove('open');
    const t = g.querySelector('.sidebar-toggle');
    if (t) t.setAttribute('aria-expanded', 'false');
  });

  // Highlight active link & open its group
  const here = new URL(location.href);
  let openedByActive = false;

  links.forEach(a => {
    const aUrl = new URL(a.href, location.origin);
    if (here.pathname === aUrl.pathname) {
      a.classList.add('active');
      const g = a.closest('.sidebar-group');
      if (g) {
        g.classList.add('open');
        const t = g.querySelector('.sidebar-toggle');
        if (t) t.setAttribute('aria-expanded', 'true');
        openedByActive = true;
      }
    }
  });

  // Restore last opened group if no active link matched; else default to first group
  if (!openedByActive) {
    const idx = localStorage.getItem(STORAGE_KEY);
    if (idx !== null && groups[idx]) {
      groups[idx].classList.add('open');
      const t = groups[idx].querySelector('.sidebar-toggle');
      if (t) t.setAttribute('aria-expanded', 'true');
    } else if (groups[0]) {
      groups[0].classList.add('open');
      const t0 = groups[0].querySelector('.sidebar-toggle');
      if (t0) t0.setAttribute('aria-expanded', 'true');
    }
  }

  // Accordion toggle: only one open at a time
  toggles.forEach((btn, i) => {
    btn.setAttribute('role', 'button');
    btn.setAttribute('tabindex', '0');

    const doToggle = (e) => {
      e.preventDefault();
      const group = btn.closest('.sidebar-group');
      const isOpen = group.classList.contains('open');

      groups.forEach(g => {
        g.classList.remove('open');
        const t = g.querySelector('.sidebar-toggle');
        if (t) t.setAttribute('aria-expanded', 'false');
      });

      if (!isOpen) {
        group.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
        localStorage.setItem(STORAGE_KEY, i.toString());
      } else {
        btn.setAttribute('aria-expanded', 'false');
        localStorage.removeItem(STORAGE_KEY);
      }
    };

    btn.addEventListener('click', doToggle);
    btn.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') doToggle(e);
    });
  });
});

/* =========================
   ATTENDANCE (your logic)
   ========================= */
const SEED = [
  {id:'EMP001', name:'John Tan',   dept:'IT',       work:22, days:21, ot:6, pen:1, last:'2025-11-03'},
  {id:'EMP002', name:'Alicia Wong',dept:'Finance',  work:22, days:22, ot:2, pen:0, last:'2025-11-04'},
  {id:'EMP003', name:'Marcus Lim', dept:'HR',       work:22, days:19, ot:0, pen:3, last:'2025-11-05'},
  {id:'EMP004', name:'Chen Wei',   dept:'Marketing',work:22, days:21, ot:5, pen:1, last:'2025-11-06'},
];
let DATA = JSON.parse(JSON.stringify(SEED));
const PRISTINE = JSON.parse(JSON.stringify(SEED));

let currentPage = 1;
let perPage = 25;
let pagination = { total: 0, last_page: 1, current_page: 1 };

let EDIT_MODE = false;
const SELECTED = new Set();
const EDIT_BUFFER = {};

const $ = s=>document.querySelector(s);
const tbody = $('#tbl tbody');

function pct(a,b){ if(!b) return 0; return (a/b)*100; }
function pillClass(p){ return p>=95?'ok':(p>=90?'warn':'neg'); }
function fmtPct(p){ return p.toFixed(2)+'%'; }
function inRange(d,s,e){ const x=new Date(d); if(s&&x<new Date(s))return false; if(e&&x>new Date(e))return false; return true; }

function kpis(rows){
  $('#k-emp').textContent = rows.length;
  $('#k-days').textContent = rows.reduce((a,b)=>a+b.days,0);
  $('#k-ot').textContent   = rows.reduce((a,b)=>a+b.ot,0);
  $('#k-pen').textContent  = rows.reduce((a,b)=>a+b.pen,0);
  const avg = rows.length ? rows.reduce((a,b)=>a+pct(b.days,b.work),0)/rows.length : 0;
  $('#k-att').textContent  = fmtPct(avg);
}

function filtered(){
  const q = $('#q').value.trim().toLowerCase();
  const d = $('#dept').value;
  const s = $('#start').value, e = $('#end').value;
  return DATA.filter(r =>
    (!q || r.id.toLowerCase().includes(q) || r.name.toLowerCase().includes(q)) &&
    (!d || r.dept===d) && inRange(r.last,s,e)
  );
}

function updatePaginationBar(){
  const total = pagination.total || 0;
  const cur = pagination.current_page || 1;
  const last = pagination.last_page || 1;
  if ($('#paginationInfo')) $('#paginationInfo').textContent = total + ' records';
  if ($('#pageNum')) $('#pageNum').textContent = 'Page ' + cur + ' of ' + last;
  if ($('#firstPage')) $('#firstPage').disabled = cur <= 1;
  if ($('#prevPage')) $('#prevPage').disabled = cur <= 1;
  if ($('#nextPage')) $('#nextPage').disabled = cur >= last;
  if ($('#lastPage')) $('#lastPage').disabled = cur >= last;
  if ($('#perPage') && $('#perPage').value !== String(perPage)) $('#perPage').value = String(perPage);
}

function refreshPaged(){
  const full = filtered();
  const total = full.length;
  const lastPage = Math.max(1, Math.ceil(total / perPage));
  currentPage = Math.min(Math.max(1, currentPage), lastPage);
  const start = (currentPage - 1) * perPage;
  const slice = full.slice(start, start + perPage);
  pagination = { total, current_page: currentPage, last_page: lastPage, per_page: perPage };
  render(slice);
  updatePaginationBar();
  kpis(full);
}

function render(rows){
  tbody.innerHTML = '';
  rows.forEach(r=>{
    const isSelected = SELECTED.has(r.id);
    const p = pct(r.days, r.work);
    const cls = pillClass(p);

    const src = (EDIT_MODE && isSelected && EDIT_BUFFER[r.id]) ? EDIT_BUFFER[r.id] : r;

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input type="checkbox" class="row-check" data-id="${r.id}" ${isSelected?'checked':''}></td>
      <td><strong>${r.name}</strong><br><span class="muted">${r.id}</span></td>
      <td>${r.dept}</td>

      <td class="right">
        <span class="val ${EDIT_MODE && isSelected ? 'edit-on' : ''}">${src.work}</span>
        <input type="number" min="0" class="edit-input" data-id="${r.id}" data-field="work" value="${src.work}" style="display:${EDIT_MODE && isSelected?'inline-block':'none'}">
      </td>
      <td class="right">
        <span class="val ${EDIT_MODE && isSelected ? 'edit-on' : ''}">${src.days}</span>
        <input type="number" min="0" class="edit-input" data-id="${r.id}" data-field="days" value="${src.days}" style="display:${EDIT_MODE && isSelected?'inline-block':'none'}">
      </td>
      <td class="right">
        <span class="val ${EDIT_MODE && isSelected ? 'edit-on' : ''}">${src.ot}</span>
        <input type="number" min="0" class="edit-input" data-id="${r.id}" data-field="ot" value="${src.ot}" style="display:${EDIT_MODE && isSelected?'inline-block':'none'}">
      </td>
      <td class="right">
        <span class="val ${EDIT_MODE && isSelected ? 'edit-on' : ''}">${src.pen}</span>
        <input type="number" min="0" class="edit-input" data-id="${r.id}" data-field="pen" value="${src.pen}" style="display:${EDIT_MODE && isSelected?'inline-block':'none'}">
      </td>

      <td class="right">${fmtPct(p)}</td>
      <td><span class="pill ${cls}">${cls==='ok'?'Good':cls==='warn'?'Watch':'Issue'}</span></td>
      <td><button class="btn-ghost view" data-id="${r.id}">View</button></td>
    `;
    tbody.appendChild(tr);
  });

  tbody.querySelectorAll('.row-check').forEach(cb=>{
    cb.addEventListener('change', ()=>{
      const id = cb.dataset.id;
      if (cb.checked) SELECTED.add(id); else SELECTED.delete(id);
      if (EDIT_MODE && !cb.checked) delete EDIT_BUFFER[id];
      updateActionButtons();
      refreshPaged();
    });
  });

  tbody.querySelectorAll('.edit-input').forEach(inp=>{
    inp.addEventListener('input', ()=>{
      const id = inp.dataset.id, field = inp.dataset.field;
      const v = Math.max(0, Number(inp.value || 0));
      if (!EDIT_BUFFER[id]) EDIT_BUFFER[id] = {...DATA.find(x=>x.id===id)};
      EDIT_BUFFER[id][field] = v;

      if (field === 'work' && EDIT_BUFFER[id].days > v) {
        EDIT_BUFFER[id].days = v;
        const pair = tbody.querySelector(`.edit-input[data-id="${id}"][data-field="days"]`);
        if (pair) pair.value = v;
      }
      if (field === 'days') {
        const baseWork = EDIT_BUFFER[id].work;
        if (v > baseWork) {
          EDIT_BUFFER[id].days = baseWork;
          inp.value = baseWork;
        }
      }
    });
  });

  kpis(rows);
}

function renderFiltered(){ currentPage = 1; refreshPaged(); }

document.getElementById('checkAll').addEventListener('change', (e)=>{
  SELECTED.clear();
  if (e.target.checked) filtered().forEach(r=>SELECTED.add(r.id));
  updateActionButtons();
  renderFiltered();
});

document.getElementById('apply').addEventListener('click', ()=>{ SELECTED.clear(); document.getElementById('checkAll').checked=false; renderFiltered(); });
document.getElementById('clear').addEventListener('click', ()=>{
  document.getElementById('q').value='';
  document.getElementById('dept').value='';
  document.getElementById('start').value='';
  document.getElementById('end').value='';
  SELECTED.clear(); document.getElementById('checkAll').checked=false;
  renderFiltered();
});

document.getElementById('editSelected').addEventListener('click', ()=>{
  if (SELECTED.size === 0) return alert('Select at least one employee to edit.');
  EDIT_MODE = true;
  EDIT_BUFFER_RESET_FROM_DATA();
  updateActionButtons();
  renderFiltered();
});

document.getElementById('saveEdits').addEventListener('click', ()=>{
  SELECTED.forEach(id=>{
    if (EDIT_BUFFER[id]) {
      const idx = DATA.findIndex(x=>x.id===id);
      DATA[idx] = {...DATA[idx], ...EDIT_BUFFER[id]};
    }
  });
  EDIT_MODE = false;
  for (const k in EDIT_BUFFER) delete EDIT_BUFFER[k];
  SELECTED.clear();
  document.getElementById('checkAll').checked = false;
  updateActionButtons();
  renderFiltered();
});

document.getElementById('cancelEdits').addEventListener('click', ()=>{
  for (const k in EDIT_BUFFER) delete EDIT_BUFFER[k];
  EDIT_MODE = false;
  updateActionButtons();
  renderFiltered();
});

function EDIT_BUFFER_RESET_FROM_DATA(){
  for (const id of SELECTED) {
    const row = DATA.find(x=>x.id===id);
    EDIT_BUFFER[id] = {work:row.work, days:row.days, ot:row.ot, pen:row.pen};
  }
}

function updateActionButtons(){
  const hasSel = SELECTED.size > 0;
  document.getElementById('editSelected').disabled = false;
  document.getElementById('saveEdits').disabled = !(hasSel && EDIT_MODE);
  document.getElementById('cancelEdits').disabled = !(hasSel && EDIT_MODE);
}

/* (Optional) Details modal guards — safe if modal elements aren’t present */
const modal = document.getElementById('modal'); 
const meta = document.getElementById('meta'); 
const breakdown = document.getElementById('breakdown');
const closeBtn = document.getElementById('close');
if (closeBtn && modal) {
  closeBtn.addEventListener('click', ()=> modal.style.display='none');
}

function openModal(e){
  const p = pct(e.days, e.work);
  if (!modal || !meta || !breakdown) return; // no modal present on this page
  meta.innerHTML = `<strong>${e.name}</strong> (${e.id}) — ${e.dept}<br>
    <span class="muted">Attendance % = Payable Days ÷ Working Days × 100</span>`;
  breakdown.innerHTML = `
    <tr><td style="padding:8px;">Working Days</td><td style="padding:8px;text-align:right">${e.work}</td></tr>
    <tr><td style="padding:8px;">Payable Days</td><td style="padding:8px;text-align:right">${e.days}</td></tr>
    <tr><td style="padding:8px;">Absent Days (derived)</td><td style="padding:8px;text-align:right">${Math.max(e.work - e.days,0)}</td></tr>
    <tr><td style="padding:8px;">OT Hours</td><td style="padding:8px;text-align:right">${e.ot}</td></tr>
    <tr><td style="padding:8px;">Penalty Points</td><td style="padding:8px;text-align:right">${e.pen}</td></tr>
    <tr><td style="padding:8px;"><strong>Attendance %</strong></td><td style="padding:8px;text-align:right"><strong>${fmtPct(p)}</strong></td></tr>
  `;
  modal.style.display = 'flex';
}

tbody.addEventListener('click', (e)=>{
  const btn = e.target.closest('.view');
  if (!btn) return;
  const row = DATA.find(x=>x.id===btn.dataset.id);
  openModal(row);
});

$('#firstPage').addEventListener('click', ()=>{ if (currentPage > 1) { currentPage = 1; refreshPaged(); } });
$('#prevPage').addEventListener('click', ()=>{ if (currentPage > 1) { currentPage--; refreshPaged(); } });
$('#nextPage').addEventListener('click', ()=>{ if (currentPage < (pagination.last_page || 1)) { currentPage++; refreshPaged(); } });
$('#lastPage').addEventListener('click', ()=>{ if (currentPage < (pagination.last_page || 1)) { currentPage = pagination.last_page; refreshPaged(); } });
$('#perPage').addEventListener('change', function(){ perPage = parseInt(this.value, 10); currentPage = 1; refreshPaged(); });

renderFiltered();
</script>

</body>
</html>
