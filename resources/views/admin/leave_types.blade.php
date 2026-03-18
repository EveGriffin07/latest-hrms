<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Leave Types - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    main { padding: 24px; }
    .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th, td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; text-align: left; }
    thead th { background: #0f172a; color: #e2e8f0; font-weight: 500; }
    .badge { padding: 4px 8px; border-radius: 999px; font-size: 12px; font-weight: 600; }
    .badge-none { background: #f1f5f9; color: #475569; }
    .badge-optional { background: #e0f2fe; color: #0369a1; }
    .badge-required { background: #fef3c7; color: #92400e; }
    input, select { padding: 8px 10px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 13px; }
    .btn-sm { padding: 6px 12px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; background: #0ea5e9; color: #fff; }
    .btn-sm:hover { background: #0284c7; }
    .notice { padding: 10px 14px; border-radius: 8px; margin-bottom: 12px; }
    .notice.success { background: #dcfce7; color: #166534; }
  </style>
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
      <a href="{{ route('admin.profile') }}" style="text-decoration:none;color:inherit;"><i class="fa-regular fa-bell"></i> &nbsp; HR Admin</a>
    </div>
  </header>
  <div class="container">
    @include('admin.layout.sidebar')
    <main>
      <div class="breadcrumb">Home &gt; Leave &gt; Leave Types</div>
      <h2 style="margin:0 0 4px;">Leave Types</h2>
      <p class="subtitle" style="margin:0 0 16px; color:#64748b;">Configure proof requirement and label per leave type. Employees see these rules on the leave application form.</p>

      @if(session('success'))
        <div class="notice success">{{ session('success') }}</div>
      @endif
      @if($errors->any())
        <div class="notice" style="background:#fee2e2; color:#991b1b;">{{ $errors->first() }}</div>
      @endif

      <div class="card">
        <table>
          <thead>
            <tr>
              <th>Leave Type</th>
              <th>Default days/year</th>
              <th>Proof requirement</th>
              <th>Proof label</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach($types as $type)
              <tr>
                <td><strong>{{ $type->leave_name }}</strong></td>
                <td>{{ $type->default_days_year }}</td>
                <td>
                  @php $pr = $type->proof_requirement ?? 'none'; @endphp
                  <span class="badge badge-{{ $pr }}">{{ ucfirst($pr) }}</span>
                </td>
                <td>{{ $type->proof_label ?? '—' }}</td>
                <td>
                  <button type="button" class="btn-sm" onclick="document.getElementById('edit-{{ $type->leave_type_id }}').style.display='block'">
                    <i class="fa-solid fa-pen"></i> Configure
                  </button>
                </td>
              </tr>
              <tr id="edit-{{ $type->leave_type_id }}" style="display:none; background:#f8fafc;">
                <td colspan="5" style="padding:14px;">
                  <form id="form-{{ $type->leave_type_id }}" method="POST" action="{{ route('admin.leave.types.update', $type) }}">
                    @csrf
                    <div style="display:flex; gap:16px; flex-wrap:wrap; align-items:flex-end;">
                      <div>
                        <label style="display:block; font-size:12px; margin-bottom:4px; color:#64748b;">Proof requirement</label>
                        <select name="proof_requirement" style="min-width:140px;">
                          <option value="none" {{ ($type->proof_requirement ?? '') === 'none' ? 'selected' : '' }}>None</option>
                          <option value="optional" {{ ($type->proof_requirement ?? '') === 'optional' ? 'selected' : '' }}>Optional</option>
                          <option value="required" {{ ($type->proof_requirement ?? '') === 'required' ? 'selected' : '' }}>Required</option>
                        </select>
                      </div>
                      <div style="flex:1; min-width:200px;">
                        <label style="display:block; font-size:12px; margin-bottom:4px; color:#64748b;">Proof label (shown on form)</label>
                        <input type="text" name="proof_label" value="{{ old('proof_label', $type->proof_label ?? '') }}" placeholder="e.g. Medical certificate" style="width:100%;">
                      </div>
                      <div>
                        <button type="submit" class="btn-sm"><i class="fa-solid fa-check"></i> Save</button>
                        <button type="button" class="btn-sm" style="background:#94a3b8;" onclick="document.getElementById('edit-{{ $type->leave_type_id }}').style.display='none'">Cancel</button>
                      </div>
                    </div>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </main>
  </div>
</body>
</html>
