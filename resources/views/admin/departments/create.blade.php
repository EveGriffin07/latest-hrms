<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Department - Admin - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    main { padding:24px; }
    .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:16px; max-width:500px; }
    label { display:block; font-weight:600; margin-bottom:6px; }
    input, select { width:100%; padding:10px 12px; border:1px solid #e5e7eb; border-radius:8px; }
    .btn { padding:8px 14px; border-radius:8px; border:none; cursor:pointer; text-decoration:none; display:inline-block; font-size:14px; margin-right:8px; }
    .btn-primary { background:#2563eb; color:#fff; }
    .btn-secondary { background:#64748b; color:#fff; }
    .error { color:#dc2626; font-size:13px; margin-top:4px; }
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
      <div class="breadcrumb">Admin · Department Management · Create</div>
      <h2 style="margin:0 0 4px;">Create Department</h2>

      @if($errors->any())
        <div class="notice error" style="padding:10px; background:#fee2e2; border-radius:10px; margin-bottom:12px;">{{ $errors->first() }}</div>
      @endif

      <div class="card">
        <form method="POST" action="{{ route('admin.departments.store') }}">
          @csrf
          <div style="margin-bottom:14px;">
            <label for="department_name">Department name *</label>
            <input type="text" id="department_name" name="department_name" value="{{ old('department_name') }}" required>
            @error('department_name') <span class="error">{{ $message }}</span> @enderror
          </div>
          <div style="margin-bottom:14px;">
            <label for="manager_id">Supervisor</label>
            <select id="manager_id" name="manager_id">
              <option value="">— None —</option>
              @foreach($managers as $u)
                <option value="{{ $u->user_id }}" {{ old('manager_id') == $u->user_id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
              @endforeach
            </select>
            @error('manager_id') <span class="error">{{ $message }}</span> @enderror
          </div>
          <div>
            <button type="submit" class="btn btn-primary">Create Department</button>
            <a href="{{ route('admin.departments.index') }}" class="btn btn-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </main>
  </div>
</body>
</html>
