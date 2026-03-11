<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Area Management - Admin - HRMS</title>
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
    .btn-sm { padding:6px 10px; font-size:12px; }
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
      <div class="breadcrumb">Admin · Area Management</div>
      <h2 style="margin:0 0 4px;">Area Management</h2>
      <p style="margin:0; color:#64748b;">Create areas, assign supervisors, and manage employee assignment by area.</p>

      @if(session('success'))
        <div class="notice success" style="padding:10px; background:#dcfce7; color:#166534; border-radius:10px; margin-bottom:12px;">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div class="notice error" style="padding:10px; background:#fee2e2; color:#991b1b; border-radius:10px; margin-bottom:12px;">{{ session('error') }}</div>
      @endif

      <div class="card" style="margin-bottom:16px;">
        <a href="{{ route('admin.areas.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Create Area</a>
      </div>

      <div class="card">
        <table>
          <thead>
            <tr>
              <th>Area</th>
              <th>Supervisor</th>
              <th>Employees</th>
              <th>OT Claims</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($areas as $a)
              <tr>
                <td>{{ $a->name }}</td>
                <td>{{ $a->supervisor->name ?? '—' }}@if($a->supervisor)<br><small>{{ $a->supervisor->email }}</small>@endif</td>
                <td>{{ $a->users_count }}</td>
                <td>{{ $a->overtime_claims_count }}</td>
                <td>
                  <a href="{{ route('admin.areas.edit', $a) }}" class="btn btn-secondary btn-sm">Edit</a>
                </td>
              </tr>
            @empty
              <tr><td colspan="5">No areas yet. Create one to get started.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </main>
  </div>
</body>
</html>
