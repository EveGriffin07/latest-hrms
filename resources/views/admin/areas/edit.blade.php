<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Area - Admin - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    main { padding:24px; }
    .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:16px; }
    .card.wide { max-width:700px; }
    label { display:block; font-weight:600; margin-bottom:6px; }
    input, select { width:100%; padding:10px 12px; border:1px solid #e5e7eb; border-radius:8px; }
    .btn { padding:8px 14px; border-radius:8px; border:none; cursor:pointer; text-decoration:none; display:inline-block; font-size:14px; margin-right:8px; }
    .btn-primary { background:#2563eb; color:#fff; }
    .btn-secondary { background:#64748b; color:#fff; }
    .btn-sm { padding:6px 10px; font-size:12px; }
    table { width:100%; border-collapse:collapse; }
    th, td { padding:8px 12px; border-bottom:1px solid #e5e7eb; text-align:left; }
    thead th { background:#0f172a; color:#e2e8f0; }
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
      <div class="breadcrumb">Admin · Area Management · Edit</div>
      <h2 style="margin:0 0 4px;">Edit Area: {{ $area->name }}</h2>

      @if(session('success'))
        <div class="notice success" style="padding:10px; background:#dcfce7; color:#166534; border-radius:10px; margin-bottom:12px;">{{ session('success') }}</div>
      @endif
      @if($errors->any())
        <div class="notice error" style="padding:10px; background:#fee2e2; border-radius:10px; margin-bottom:12px;">{{ $errors->first() }}</div>
      @endif

      <div class="card wide" style="margin-bottom:20px;">
        <h3 style="margin:0 0 12px;">Area details</h3>
        <form method="POST" action="{{ route('admin.areas.update', $area) }}">
          @csrf
          @method('PUT')
          <div style="margin-bottom:14px;">
            <label for="name">Area name *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $area->name) }}" required>
            @error('name') <span class="error">{{ $message }}</span> @enderror
          </div>
          <div style="margin-bottom:14px;">
            <label for="supervisor_id">Supervisor</label>
            <select id="supervisor_id" name="supervisor_id">
              <option value="">— None —</option>
              @foreach($supervisors as $u)
                <option value="{{ $u->user_id }}" {{ old('supervisor_id', $area->supervisor_id) == $u->user_id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
              @endforeach
            </select>
            @error('supervisor_id') <span class="error">{{ $message }}</span> @enderror
          </div>
          <div>
            <button type="submit" class="btn btn-primary">Update Area</button>
            <a href="{{ route('admin.areas.index') }}" class="btn btn-secondary">Back to list</a>
          </div>
        </form>
      </div>

      <div class="card wide" style="margin-bottom:20px;">
        <h3 style="margin:0 0 12px;">Add employee to this area</h3>
        <form method="POST" action="{{ route('admin.areas.move_employee') }}" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
          @csrf
          <input type="hidden" name="area_id" value="{{ $area->id }}">
          <div style="min-width:200px;">
            <label for="add_user_id">Employee (user)</label>
            <select id="add_user_id" name="user_id" required>
              <option value="">— Select —</option>
              @foreach($usersNotInArea as $u)
                <option value="{{ $u->user_id }}">{{ $u->name }} ({{ $u->email }})</option>
              @endforeach
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Add to this area</button>
        </form>
        @if(isset($usersNotInArea) && $usersNotInArea->isEmpty())
          <p style="margin:10px 0 0; color:#64748b;">All employees are already assigned to an area.</p>
        @endif
      </div>

      <div class="card wide">
        <h3 style="margin:0 0 12px;">Employees in this area</h3>
        <p style="margin:0 0 12px; color:#64748b;">Move an employee to another area using the form below.</p>
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Employee code</th>
              <th>Move to area</th>
            </tr>
          </thead>
          <tbody>
            @forelse($employeesInArea as $u)
              <tr>
                <td>{{ $u->name }}</td>
                <td>{{ $u->email }}</td>
                <td>{{ $u->employee->employee_code ?? '—' }}</td>
                <td>
                  <form method="POST" action="{{ route('admin.areas.move_employee') }}" style="display:inline;">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $u->user_id }}">
                    <select name="area_id" style="width:auto; padding:4px; margin-right:6px;">
                      @foreach($otherAreas as $oa)
                        <option value="{{ $oa->id }}">{{ $oa->name }}</option>
                      @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-secondary">Move</button>
                  </form>
                  @if($otherAreas->isEmpty())
                    <span style="color:#64748b;">No other areas</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="4">No employees assigned to this area.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </main>
  </div>
</body>
</html>
