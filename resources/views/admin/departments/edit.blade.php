<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Department - Admin - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    main { padding:24px; max-width:100%; }
    .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:16px; width:100%; }
    .card.wide { max-width:100%; }
    .card.wide.form-card { max-width:500px; }
    label { display:block; font-weight:600; margin-bottom:6px; }
    input, select { width:100%; padding:10px 12px; border:1px solid #e5e7eb; border-radius:8px; }
    input[type="search"] { max-width:100%; min-width:200px; }
    .btn { padding:8px 14px; border-radius:8px; border:none; cursor:pointer; text-decoration:none; display:inline-block; font-size:14px; margin-right:8px; }
    .btn-primary { background:#2563eb; color:#fff; }
    .btn-secondary { background:#64748b; color:#fff; }
    .btn-sm { padding:6px 10px; font-size:12px; }
    .error { color:#dc2626; font-size:13px; margin-top:4px; }
    .employee-list-wrap { max-height:400px; overflow:auto; border:1px solid #e5e7eb; border-radius:8px; margin-top:10px; }
    .employee-table { width:100%; border-collapse:collapse; table-layout:fixed; }
    .employee-table thead th { background:#0f172a; color:#e2e8f0; padding:10px 12px; text-align:left; font-size:13px; position:sticky; top:0; z-index:1; }
    .employee-table thead th.col-name { width:35%; min-width:180px; }
    .employee-table thead th.col-job { width:25%; min-width:120px; }
    .employee-table thead th.col-dept { width:25%; min-width:120px; }
    .employee-table thead th.col-check { width:15%; min-width:60px; text-align:right; }
    .employee-table td { padding:10px 12px; border-bottom:1px solid #e5e7eb; vertical-align:middle; }
    .employee-table td.col-name { }
    .employee-table td.col-name .name { font-weight:500; }
    .employee-table td.col-name .sub { color:#64748b; font-size:13px; }
    .employee-table td.col-job { color:#475569; font-size:13px; }
    .employee-table td.col-dept { color:#475569; font-size:13px; }
    .employee-table td.col-check { text-align:right; }
    .employee-table td.col-check input[type="checkbox"] { margin:0; }
    .employee-table tbody tr.in-dept { background:#f0fdf4; }
    .employee-table tbody tr.hidden { display:none; }
  </style>
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info"><a href="{{ route('admin.profile') }}" style="text-decoration:none;color:inherit;">HR Admin</a></div>
  </header>
  <div class="container">
    @include('admin.layout.sidebar')
    <main>
      <div class="breadcrumb">Admin · Department Management · Edit</div>
      <h2 style="margin:0 0 4px;">Edit Department: {{ $department->department_name }}</h2>

      @if(session('success'))
        <div class="notice success" style="padding:10px; background:#dcfce7; color:#166534; border-radius:10px; margin-bottom:12px;">{{ session('success') }}</div>
      @endif
      @if($errors->any())
        <div class="notice error" style="padding:10px; background:#fee2e2; border-radius:10px; margin-bottom:12px;">{{ $errors->first() }}</div>
      @endif

      <div class="card wide form-card" style="margin-bottom:20px;">
        <h3 style="margin:0 0 12px;">Department details</h3>
        <form method="POST" action="{{ route('admin.departments.update', $department) }}">
          @csrf
          @method('PUT')
          <div style="margin-bottom:14px;">
            <label for="department_name">Department name *</label>
            <input type="text" id="department_name" name="department_name" value="{{ old('department_name', $department->department_name) }}" required>
            @error('department_name') <span class="error">{{ $message }}</span> @enderror
          </div>
          <div style="margin-bottom:14px;">
            <label for="manager_id">Supervisor</label>
            <select id="manager_id" name="manager_id">
              <option value="">— None —</option>
              @foreach($managers as $u)
                <option value="{{ $u->user_id }}" {{ old('manager_id', $department->manager_id) == $u->user_id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
              @endforeach
            </select>
            <small id="manager-auto-tick-note" style="color:#64748b; display:block; margin-top:6px;">Selecting a supervisor auto-ticks employees assigned to that supervisor.</small>
            @error('manager_id') <span class="error">{{ $message }}</span> @enderror
          </div>
          <div>
            <button type="submit" class="btn btn-primary">Update Department</button>
            <a href="{{ route('admin.departments.index') }}" class="btn btn-secondary">Back to list</a>
          </div>
        </form>
      </div>

      <div class="card wide">
        <h3 style="margin:0 0 8px;">Assign employees to this department</h3>
        <p style="margin:0 0 12px; color:#64748b;">Employees already in this department are listed first and pre-checked. Use the filter to search by name, email, or code.</p>
        <form method="POST" action="{{ route('admin.departments.assign_employees', $department) }}" id="assign-form">
          @csrf
          <div style="margin-bottom:10px; display:flex; flex-wrap:wrap; align-items:flex-end; gap:12px;">
            <div>
              <label for="employee-filter">Filter employees</label>
              <input type="search" id="employee-filter" placeholder="Search by name, email, or employee code..." autocomplete="off">
            </div>
            <div style="display:flex; gap:8px; align-items:center;">
              <button type="button" id="tick-dept" class="btn btn-secondary btn-sm">Tick all in this department</button>
              <button type="button" id="tick-all" class="btn btn-secondary btn-sm">Tick all</button>
            </div>
          </div>
          <div class="employee-list-wrap" id="employee-list">
            <table class="employee-table">
              <thead>
                <tr>
                  <th class="col-name">Name / Email / Code</th>
                  <th class="col-job">Job</th>
                  <th class="col-dept">Department</th>
                  <th class="col-check">In this dept</th>
                </tr>
              </thead>
              <tbody>
                @foreach($allEmployees as $emp)
                  @php $inDept = $employeesInDept->contains('employee_id', $emp->employee_id); @endphp
                  <tr class="{{ $inDept ? 'in-dept' : '' }}" data-name="{{ strtolower(optional($emp->user)->name ?? '') }}" data-email="{{ strtolower(optional($emp->user)->email ?? '') }}" data-code="{{ strtolower($emp->employee_code ?? '') }}">
                    <td class="col-name">
                      <div class="name">{{ optional($emp->user)->name ?? '—' }}</div>
                      <div class="sub">{{ optional($emp->user)->email ?? '' }}</div>
                      <div class="sub">{{ $emp->employee_code ?? '—' }}</div>
                    </td>
                    <td class="col-job">{{ optional($emp->position)->position_name ?? '—' }}</td>
                    <td class="col-dept">{{ optional($emp->department)->department_name ?? '—' }}</td>
                    <td class="col-check">
                      <label style="display:inline-block; cursor:pointer; margin:0;">
                        <input type="checkbox" name="employee_ids[]" value="{{ $emp->employee_id }}" {{ $inDept ? 'checked' : '' }}>
                      </label>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          @if($allEmployees->isEmpty())
            <p style="margin:12px 0 0; color:#64748b;">No employees in the system yet.</p>
          @else
            <div style="margin-top:12px;">
              <button type="submit" class="btn btn-primary">Save employee assignment</button>
            </div>
          @endif
        </form>
      </div>
    </main>
  </div>
  <script>
    (function() {
      var managerToSubordinateIds = @json($managerToSubordinateIds ?? []);
      var managerSelect = document.getElementById('manager_id');
      var filterEl = document.getElementById('employee-filter');
      var listEl = document.getElementById('employee-list');
      if (!listEl) return;

      if (filterEl) {
        filterEl.addEventListener('input', function() {
          var q = (this.value || '').toLowerCase().trim();
          var rows = listEl.querySelectorAll('tbody tr');
          rows.forEach(function(row) {
            var name = (row.getAttribute('data-name') || '');
            var email = (row.getAttribute('data-email') || '');
            var code = (row.getAttribute('data-code') || '');
            var show = !q || name.indexOf(q) !== -1 || email.indexOf(q) !== -1 || code.indexOf(q) !== -1;
            row.classList.toggle('hidden', !show);
          });
        });
      }

      var tickDeptBtn = document.getElementById('tick-dept');
      var tickAllBtn = document.getElementById('tick-all');
      var table = listEl.querySelector('.employee-table');
      if (tickDeptBtn && table) {
        tickDeptBtn.addEventListener('click', function() {
          var inDeptRows = table.querySelectorAll('tbody tr.in-dept');
          var otherRows = table.querySelectorAll('tbody tr:not(.in-dept)');
          var inDeptCbs = [];
          var otherCbs = [];
          inDeptRows.forEach(function(row) {
            var cb = row.querySelector('input[type="checkbox"]');
            if (cb) inDeptCbs.push(cb);
          });
          otherRows.forEach(function(row) {
            var cb = row.querySelector('input[type="checkbox"]');
            if (cb) otherCbs.push(cb);
          });
          var anyOtherChecked = otherCbs.some(function(cb) { return cb.checked; });
          if (anyOtherChecked) {
            otherCbs.forEach(function(cb) { cb.checked = false; });
          } else {
            var allInDeptChecked = inDeptCbs.length > 0 && inDeptCbs.every(function(cb) { return cb.checked; });
            inDeptCbs.forEach(function(cb) { cb.checked = !allInDeptChecked; });
          }
        });
      }
      if (tickAllBtn && table) {
        tickAllBtn.addEventListener('click', function() {
          var nodeList = table.querySelectorAll('tbody input[type="checkbox"]');
          var cbs = Array.prototype.slice.call(nodeList);
          var allChecked = cbs.length > 0 && cbs.every(function(cb) { return cb.checked; });
          cbs.forEach(function(cb) { cb.checked = !allChecked; });
        });
      }

      var autoTickForManager = function(managerUserId) {
        if (!table || !managerUserId) return;
        var targetIds = managerToSubordinateIds[String(managerUserId)] || [];
        if (!targetIds.length) return;

        var targetSet = new Set(targetIds.map(function(id) { return String(id); }));
        var rows = table.querySelectorAll('tbody tr');
        rows.forEach(function(row) {
          var cb = row.querySelector('input[type="checkbox"]');
          if (!cb) return;
          if (targetSet.has(String(cb.value))) {
            cb.checked = true;
          }
        });
      };

      if (managerSelect) {
        managerSelect.addEventListener('change', function() {
          autoTickForManager(this.value);
        });
        autoTickForManager(managerSelect.value);
      }
    })();
  </script>
</body>
</html>