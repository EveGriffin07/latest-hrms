<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register Employee - HRMS</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
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
    <div class="breadcrumb">Home > Employee Management > Register Employee</div>
    <h2>Register New Employee</h2>
    <p class="subtitle">Add new employee information into the system.</p>

    <div class="summary-cards" style="margin-bottom:16px;">
      <div class="card"><h4>Total Employees</h4><p>{{ $totalEmployees ?? 0 }}</p></div>
      <div class="card"><h4>Active Employees</h4><p>{{ $activeEmployees ?? 0 }}</p></div>
      <div class="card"><h4>Departments</h4><p>{{ $departmentsCount ?? 0 }}</p></div>
      <div class="card"><h4>On Leave Today</h4><p>{{ $onLeave ?? 0 }}</p></div>
      <div class="card"><h4>Total Applicants</h4><p>{{ $totalApplicants ?? 0 }}</p></div>
      <div class="card"><h4>Approved Applicants</h4><p>{{ $approvedApplicants ?? 0 }}</p></div>
    </div>

    <form class="filter-bar" method="GET" action="{{ route('admin.employee.list') }}" style="margin-bottom:20px;">
      <input type="hidden" name="tab" value="employees">
      <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name, email or code..." />
      <select name="department">
        <option value="">All Departments</option>
        @foreach($departments as $dept)
          <option value="{{ $dept->department_id }}" {{ request('department') == $dept->department_id ? 'selected' : '' }}>
            {{ $dept->department_name }}
          </option>
        @endforeach
      </select>
      <select name="status">
        <option value="">All Statuses</option>
        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        <option value="terminated" {{ request('status') === 'terminated' ? 'selected' : '' }}>Terminated</option>
      </select>
      <div class="actions">
        <button type="submit" class="btn-primary"><i class="fa-solid fa-filter"></i> Apply</button>
        @if(request('q') || request('department') || request('status'))
          <a class="btn-ghost" href="{{ route('admin.employee.list', ['tab' => 'employees']) }}"><i class="fa-solid fa-rotate-left"></i> Reset</a>
        @endif
      </div>
    </form>

    <div class="form-container">

      @if ($errors->any())
        <div style="background:#fef2f2; border:1px solid #fecdd3; color:#b91c1c; padding:12px 14px; border-radius:10px; margin-bottom:14px;">
          <strong>Fix the following:</strong>
          <ul style="margin:8px 0 0 18px; padding:0; list-style:disc;">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      @if (session('success'))
        <div style="background:#ecfdf3; border:1px solid #bbf7d0; color:#166534; padding:12px 14px; border-radius:10px; margin-bottom:14px;">
          {{ session('success') }}
        </div>
      @endif

      <form class="form-card" method="POST" action="{{ route('admin.employee.store') }}">
        @csrf
        <h3><i class="fa-solid fa-user-plus"></i> Employee Information</h3>

        <div class="form-group" id="applicantPicker">
          <label for="applicantSelect">Import from Applicant (optional)</label>
          <select id="applicantSelect" name="applicant_id">
            <option value="">-- Manual entry --</option>
            @foreach($applicants as $app)
              @php
                  // Extract the department ID from the job the applicant applied for
                  $appDeptId = '';
                  if ($app->latestApplication && $app->latestApplication->job) {
                      $jobDeptName = $app->latestApplication->job->department;
                      $matchingDept = $departments->where('department_name', $jobDeptName)->first();
                      if ($matchingDept) {
                          $appDeptId = $matchingDept->department_id;
                      }
                  }
              @endphp
              <option value="{{ $app->applicant_id }}"
                {{ old('applicant_id', request('applicant_id')) == $app->applicant_id ? 'selected' : '' }}
                data-name="{{ $app->full_name }}"
                data-email="{{ $app->email }}"
                data-phone="{{ $app->phone }}"
                data-address="{{ $app->location }}"
                data-dept="{{ $appDeptId }}">
                {{ $app->full_name }} ({{ $app->email }}) {{ $app->phone ? '· '.$app->phone : '' }}
              </option>
            @endforeach
          </select>
          @if($applicants->isEmpty())
            <small style="color:#b91c1c;">No applicants available yet.</small>
          @else
            <small style="color:#94a3b8;">Select any applicant to auto-fill fields; or choose “Manual entry” to type everything yourself.</small>
          @endif
        </div>

        <div class="form-group">
          <label for="employeeName">Full Name <span>*</span></label>
          <input type="text" id="employeeName" name="name" value="{{ old('name') }}" placeholder="e.g., John Doe" required>
        </div>

        <div class="form-group">
          <label for="email">Email Address <span>*</span></label>
          <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="e.g., john@example.com" required>
        </div>

        <div class="form-group">
          <label for="phone">Phone Number</label>
          <input type="text" id="phone" name="phone" value="{{ old('phone') }}" placeholder="e.g., +60123456789">
        </div>

        <div class="form-group">
          <label for="department">Department <span>*</span></label>
          <select id="department" name="department_id" required>
            <option value="" disabled {{ old('department_id') ? '' : 'selected' }}>Select Department</option>
            @foreach($departments as $dept)
              <option value="{{ $dept->department_id }}" {{ old('department_id') == $dept->department_id ? 'selected' : '' }}>
                {{ $dept->department_name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="form-group">
          <label for="designation">Position <span>*</span></label>
          <select id="designation" name="position_id" required>
            <option value="" disabled {{ old('position_id') ? '' : 'selected' }}>Select Position</option>
            @foreach($positions as $pos)
              <option value="{{ $pos->position_id }}" {{ old('position_id') == $pos->position_id ? 'selected' : '' }}>
                {{ $pos->position_name }}
              </option>
            @endforeach
          </select>
        </div>

        {{-- NEW FIELD: DIRECT SUPERVISOR --}}
        <div class="form-group">
          <label for="supervisor">Direct Supervisor</label>
          <select id="supervisor" name="supervisor_id">
            <option value="">-- No Supervisor (Top Level) --</option>
            @if(isset($supervisors))
                @foreach($supervisors as $supervisor)
                  <option value="{{ $supervisor->employee_id }}" data-dept="{{ $supervisor->department_id }}" {{ old('supervisor_id') == $supervisor->employee_id ? 'selected' : '' }}>
                    {{ $supervisor->user->name }} ({{ $supervisor->position->position_name }} - {{ $supervisor->department->department_name }})
                  </option>
                @endforeach
            @endif
          </select>
          <small style="color:#94a3b8; display:block; margin-top:5px;">Supervisors from the selected department are highlighted.</small>
        </div>

        <div class="form-row">
          <div class="form-group half">
            <label for="joinDate">Join Date <span>*</span></label>
            <input type="date" id="joinDate" name="hire_date" value="{{ old('hire_date') }}" required>
          </div>
          <div class="form-group half">
            <label for="status">Employment Status</label>
            <select id="status" name="employee_status">
              <option value="active" {{ old('employee_status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
              <option value="inactive" {{ old('employee_status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
              <option value="terminated" {{ old('employee_status') === 'terminated' ? 'selected' : '' }}>Terminated</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label for="baseSalary">Base Salary <span>*</span></label>
          <input type="number" step="0.01" min="0" id="baseSalary" name="base_salary" value="{{ old('base_salary', '0') }}" placeholder="e.g., 5000.00" required>
        </div>

        <div class="form-group">
          <label for="address">Address</label>
          <textarea id="address" name="address" rows="3" placeholder="Enter employee address">{{ old('address') }}</textarea>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Register Employee</button>
          <a href="{{ url('/admin/employee/list') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to Directory</a>
        </div>
      </form>
    </div>

    <footer>&copy; 2025 Web-Based HRMS. All Rights Reserved.</footer>
  </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  /* ===== Unified Sidebar Behavior ===== */
  const groups  = document.querySelectorAll('.sidebar-group');
  const toggles = document.querySelectorAll('.sidebar-toggle');
  const links   = document.querySelectorAll('.submenu a');
  const STORAGE_KEY = 'hrms_sidebar_open_group';

  const normPath = (u) => {
    const url = new URL(u, location.origin);
    let p = url.pathname.replace(/\/index\.php$/i, '').replace(/\/index\.php\//i, '/').replace(/\/+$/, '');
    return p === '' ? '/' : p;
  };
  const here = normPath(location.href);

  groups.forEach(g => {
    g.classList.remove('open');
    const t = g.querySelector('.sidebar-toggle');
    if (t) t.setAttribute('aria-expanded','false');
  });
  links.forEach(a => a.classList.remove('active'));

  let activeLink = null;
  for (const a of links) {
    if (normPath(a.href) === here) { activeLink = a; break; }
  }
  if (!activeLink) {
    let best = null;
    for (const a of links) {
      const p = normPath(a.href);
      if (p !== '/' && here.startsWith(p)) {
        if (!best || p.length > normPath(best.href).length) best = a;
      }
    }
    activeLink = best;
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

  /* ===== SMART SUPERVISOR DROPDOWN LOGIC ===== */
  const applicantSelect = document.getElementById('applicantSelect');
  const nameInput = document.getElementById('employeeName');
  const emailInput = document.getElementById('email');
  const phoneInput = document.getElementById('phone');
  const addressInput = document.getElementById('address');
  const departmentSelect = document.getElementById('department');
  const supervisorSelect = document.getElementById('supervisor');

  const updateSupervisors = () => {
      const selectedDeptId = departmentSelect.value;
      const supervisorOptions = Array.from(supervisorSelect.options).slice(1); // skip default empty option
      let firstMatch = null;

      supervisorOptions.forEach(opt => {
          // Reset text from any previous changes
          opt.text = opt.text.replace(/^⭐ /, '').replace(/^\[Other Dept\] /, '');

          if (opt.dataset.dept === selectedDeptId) {
              opt.text = "⭐ " + opt.text;
              opt.style.fontWeight = "bold";
              opt.style.color = "#2563eb"; // Blue highlight
              if (!firstMatch) firstMatch = opt;
          } else {
              opt.text = "[Other Dept] " + opt.text;
              opt.style.fontWeight = "normal";
              opt.style.color = "#94a3b8"; // Dimmed color
          }
      });

      // Auto-select the first supervisor in the department ONLY if HR hasn't manually picked one yet
      const currentSupOpt = supervisorSelect.options[supervisorSelect.selectedIndex];
      if (firstMatch && (!currentSupOpt || currentSupOpt.dataset.dept !== selectedDeptId)) {
          supervisorSelect.value = firstMatch.value;
      }
  };

  const fillFromApplicant = () => {
    const option = applicantSelect?.selectedOptions[0];
    if (!option || !option.dataset.name) return;
    
    nameInput.value = option.dataset.name || '';
    emailInput.value = option.dataset.email || '';
    phoneInput.value = option.dataset.phone || '';
    addressInput.value = option.dataset.address || '';
    
    // Auto-fill Department & trigger the Smart Supervisor Sort
    if (option.dataset.dept) {
        departmentSelect.value = option.dataset.dept;
        updateSupervisors(); 
    }
  };

  applicantSelect?.addEventListener('change', fillFromApplicant);
  departmentSelect?.addEventListener('change', updateSupervisors); // Trigger if HR changes dept manually
  
  // Initialize on load
  fillFromApplicant();
});
</script>
</body>
</html>