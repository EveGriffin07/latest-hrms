<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Department Management - Admin - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    main { padding:24px; }
    .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:16px; }
    table { width:100%; border-collapse:collapse; }
    th, td { padding:10px 12px; border-bottom:1px solid #e5e7eb; text-align:left; }
    thead th { background:#0f172a; color:#e2e8f0; }
    .btn { padding:8px 14px; border-radius:8px; border:none; cursor:pointer; text-decoration:none; display:inline-block; font-size:14px; }
    .btn-primary { background:#2563eb; color:#fff; }
    .btn-secondary { background:#64748b; color:#fff; }
    .btn-outline { background:#fff; color:#2563eb; border:1px solid #2563eb; }
    .btn-outline:hover { background:#eff6ff; }
    .btn-danger { background:#dc2626; color:#fff; }
    .btn-danger:hover { background:#b91c1c; }
    .btn-sm { padding:6px 10px; font-size:12px; }
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1000; align-items:center; justify-content:center; }
    .modal-overlay.show { display:flex; }
    .modal-box { background:#fff; border-radius:12px; padding:24px; max-width:420px; width:92%; box-shadow:0 20px 45px rgba(15,23,42,.18); }
    .modal-box h3 { margin:0 0 12px; font-size:1.1rem; color:#0f172a; }
    .modal-box p { margin:0 0 20px; font-size:14px; color:#475569; }
    .modal-actions { display:flex; gap:10px; justify-content:flex-end; }
    tr.dept-group-header td { font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; background:#f8fafc; padding:8px 12px; border-bottom:1px solid #e2e8f0; }
    tbody tr:not(.dept-group-header) td { border-bottom:1px solid #e5e7eb; }
  </style>
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info"><a href="{{ route('admin.profile') }}" style="text-decoration:none;color:inherit;"><i class="fa-regular fa-bell"></i> &nbsp; HR Admin</a></div>
  </header>
  <div class="container">
    @include('admin.layout.sidebar')
    <main>
      <div class="breadcrumb">Admin · Department Management</div>
      <h2 style="margin:0 0 4px;">Department Management</h2>
      <p style="margin:0; color:#64748b;">Create departments, assign managers, and manage employee assignment by department.</p>

      @if(session('success'))
        <div class="notice success" style="padding:10px; background:#dcfce7; color:#166534; border-radius:10px; margin-bottom:12px;">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div class="notice error" style="padding:10px; background:#fee2e2; color:#991b1b; border-radius:10px; margin-bottom:12px;">{{ session('error') }}</div>
      @endif

      <div class="card" style="margin-bottom:16px;">
        <a href="{{ route('admin.departments.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Create Department</a>
      </div>

      <div class="card">
        <table>
          <thead>
            <tr>
              <th>Department</th>
              <th>Supervisor</th>
              <th>Employees</th>
              <th>Action</th>
              <th>Delete</th>
            </tr>
          </thead>
          <tbody>
            @forelse($grouped as $letter => $group)
              <tr class="dept-group-header">
                <td colspan="5">{{ $letter }}</td>
              </tr>
              @foreach($group as $index => $d)
                @php $isLastInGroup = $index === $group->count() - 1; @endphp
                <tr>
                  <td>{{ $d->department_name }}</td>
                  <td>{{ $d->manager->name ?? '—' }}@if($d->manager)<br><small style="color:#64748b;">{{ $d->manager->email }}</small>@endif</td>
                  <td>{{ $d->employees_count }}</td>
                  <td>
                    <a href="{{ route('admin.departments.edit', $d) }}" class="btn btn-secondary btn-sm">Edit</a>
                    @if($isLastInGroup)
                      <a href="{{ route('admin.departments.duplicate', $d) }}" class="btn btn-outline btn-sm" title="Create same department with a different supervisor"><i class="fa-solid fa-copy"></i> Create same (new supervisor)</a>
                    @endif
                  </td>
                  <td>
                    <button type="button" class="btn btn-danger btn-sm btn-delete-dept" data-id="{{ $d->department_id }}" data-name="{{ e($d->department_name) }}" data-employees="{{ $d->employees_count }}" title="Delete department"><i class="fa-solid fa-trash-can"></i> Delete</button>
                  </td>
                </tr>
              @endforeach
            @empty
              <tr><td colspan="5">No departments yet. Create one to get started.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <!-- Delete department confirmation modal -->
      <div class="modal-overlay" id="delete-dept-modal" role="dialog" aria-labelledby="delete-dept-title" aria-modal="true">
        <div class="modal-box">
          <h3 id="delete-dept-title">Delete department?</h3>
          <p id="delete-dept-message">Are you sure you want to delete this department?</p>
          <form id="delete-dept-form" method="POST" action="">
            @csrf
            @method('DELETE')
            <div class="modal-actions">
              <button type="button" id="delete-dept-cancel" class="btn btn-secondary btn-sm">Cancel</button>
              <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash-can"></i> Delete</button>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
  <script>
    (function() {
      var modal = document.getElementById('delete-dept-modal');
      var form = document.getElementById('delete-dept-form');
      var messageEl = document.getElementById('delete-dept-message');
      var cancelBtn = document.getElementById('delete-dept-cancel');
      if (!modal || !form) return;
      document.querySelectorAll('.btn-delete-dept').forEach(function(btn) {
        btn.addEventListener('click', function() {
          var id = btn.getAttribute('data-id');
          var name = btn.getAttribute('data-name');
          var employees = parseInt(btn.getAttribute('data-employees') || '0', 10);
          form.action = '{{ route("admin.departments.destroy", ["department" => 0]) }}'.replace(/\/0$/, '/' + id);
          if (employees > 0) {
            messageEl.textContent = 'This department has ' + employees + ' employee(s). They will be unassigned from this department. Delete "' + name + '" anyway?';
          } else {
            messageEl.textContent = 'Are you sure you want to delete "' + name + '"?';
          }
          modal.classList.add('show');
        });
      });
      cancelBtn && cancelBtn.addEventListener('click', function() { modal.classList.remove('show'); });
      modal.addEventListener('click', function(e) { if (e.target === modal) modal.classList.remove('show'); });
    })();
  </script>
</body>
</html>
