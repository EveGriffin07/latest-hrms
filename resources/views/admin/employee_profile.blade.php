<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Profile - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    .profile-shell { display: grid; grid-template-columns: 260px 1fr; gap: 20px; align-items: start; }
    @media (max-width: 1024px) { .profile-shell { grid-template-columns: 1fr; } }
    .card { background: #fff; border-radius: 12px; padding: 18px; box-shadow: 0 8px 20px rgba(15,23,42,0.06); }
    .avatar-xl { width: 140px; height: 140px; border-radius: 50%; object-fit: cover; border: 4px solid #e2e8f0; background: #f8fafc; }
    .pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; font-weight: 600; font-size: 12px; text-transform: capitalize; }
    .pill-active { background: #ecfdf3; color: #15803d; }
    .pill-inactive { background: #fef9c3; color: #92400e; }
    .pill-terminated { background: #fee2e2; color: #b91c1c; }
    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; }
    .info-item { padding: 12px; border: 1px solid #e5e7eb; border-radius: 10px; background: #f8fafc; }
    .info-label { font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; }
    .info-value { color: #0f172a; font-weight: 600; margin-top: 4px; }
    .section-title { margin: 0 0 10px 0; display: flex; justify-content: space-between; align-items: center; }
  </style>
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
      <a href="{{ route('admin.profile') }}" style="text-decoration: none; color: inherit;">
        <i class="fa-regular fa-bell"></i> &nbsp; {{ Auth::user()->name ?? 'HR Admin' }}
      </a>
    </div>
  </header>

  <div class="container">
    @include('admin.layout.sidebar')

    <main class="employee-page">
      <div class="breadcrumb">Home > Employee Management > Employee Profile</div>
      <div style="display:flex; justify-content: space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:10px;">
        <div>
          <h2 style="margin:0;">Employee Profile</h2>
          <p class="subtitle" style="margin-top:4px;">Full snapshot of this employee's record.</p>
        </div>
        <a class="btn-primary" href="{{ route('admin.employee.list') }}" style="text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> Back to Overview</a>
      </div>

      @php
        $user = $employee->user;
        $userName = $user?->name ?? 'Employee';
        $userEmail = $user?->email ?? 'N/A';
        $avatar = $user?->avatar_path
          ? asset('storage/' . $user->avatar_path)
          : 'https://ui-avatars.com/api/?name=' . urlencode($userName) . '&background=E0F2FE&color=0F172A';
        $status = strtolower($employee->employee_status ?? 'inactive');
        $pillClass = match($status) {
          'active' => 'pill-active',
          'terminated' => 'pill-terminated',
          default => 'pill-inactive',
        };
      @endphp

      <div class="profile-shell">
        <div class="card" style="text-align:center;">
          <img src="{{ $avatar }}" alt="Avatar" class="avatar-xl">
          <h3 style="margin:14px 0 6px 0;">{{ $userName }}</h3>
          <div class="pill {{ $pillClass }}">{{ ucfirst($employee->employee_status ?? 'inactive') }}</div>
          <div style="margin-top:12px; color:#475569;">Employee Code: <strong>{{ $employee->employee_code }}</strong></div>
          <div style="margin-top:6px; color:#475569;">Role: <strong>{{ optional($employee->position)->position_name ?? 'Not set' }}</strong></div>
        </div>

        <div style="display:grid; gap:14px;">
          <div class="card">
            <div class="section-title">
              <h3 style="margin:0;">Job Information</h3>
            </div>
            <div class="info-grid">
              <div class="info-item">
                <div class="info-label">Department</div>
                <div class="info-value">{{ optional($employee->department)->department_name ?? 'Not set' }}</div>
              </div>
              <div class="info-item">
                <div class="info-label">Position</div>
                <div class="info-value">{{ optional($employee->position)->position_name ?? 'Not set' }}</div>
              </div>
              <div class="info-item">
                <div class="info-label">Hire Date</div>
                <div class="info-value">{{ $employee->hire_date ? \Carbon\Carbon::parse($employee->hire_date)->format('M d, Y') : '—' }}</div>
              </div>
              <div class="info-item">
                <div class="info-label">Base Salary</div>
                <div class="info-value">{{ isset($employee->base_salary) ? number_format($employee->base_salary, 2) : 'N/A' }}</div>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="section-title">
              <h3 style="margin:0;">Contact & Account</h3>
            </div>
            <div class="info-grid">
              <div class="info-item">
                <div class="info-label">Email</div>
                <div class="info-value">{{ $userEmail }}</div>
              </div>
              <div class="info-item">
                <div class="info-label">Phone</div>
                <div class="info-value">{{ $employee->phone ?? 'N/A' }}</div>
              </div>
              <div class="info-item">
                <div class="info-label">Address</div>
                <div class="info-value">{{ $employee->address ?? 'N/A' }}</div>
              </div>
            </div>
          </div>

          @if($employee->bank_code || $employee->bank_account_holder || $employee->bank_account_number)
          <div class="card">
            <div class="section-title">
              <h3 style="margin:0;">Bank Account <span style="font-weight:400; color:#94a3b8;">(for payroll)</span></h3>
            </div>
            <div class="info-grid">
              <div class="info-item">
                <div class="info-label">Bank</div>
                <div class="info-value">{{ $employee->getBankDisplayName() ?? '—' }}</div>
              </div>
              <div class="info-item">
                <div class="info-label">Account Type</div>
                <div class="info-value">{{ $employee->getAccountTypeLabel() ?? '—' }}</div>
              </div>
              <div class="info-item">
                <div class="info-label">Account Holder</div>
                <div class="info-value">{{ $employee->bank_account_holder ?? '—' }}</div>
              </div>
              <div class="info-item">
                <div class="info-label">Account Number</div>
                <div class="info-value">{{ $employee->getMaskedAccountNumber() ?? '—' }}</div>
              </div>
            </div>
          </div>
          @endif
        </div>
      </div>

      <footer>&copy; 2025 Web-Based HRMS. All Rights Reserved.</footer>
    </main>
  </div>
</body>
</html>
