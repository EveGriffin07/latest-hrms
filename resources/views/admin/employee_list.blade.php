<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <title>Employee Overview - HRMS</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">

  <style>
    /* Page-specific helpers */
    .employee-page .filter-bar { flex-wrap: wrap; gap: 10px; }
    .employee-page .filter-bar .actions { display: flex; gap: 8px; align-items: center; }
    .btn-ghost { background: #fff; border: 1px solid #d1d5db; color: #0f172a; border-radius: 8px; padding: 8px 12px; text-decoration: none; }
    .status-chip { display: inline-block; padding: 6px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
    .status-active { background: #ecfdf3; color: #15803d; }
    .status-inactive { background: #fef9c3; color: #92400e; }
    .status-terminated { background: #fee2e2; color: #b91c1c; }
    .muted { color: #94a3b8; font-size: 12px; }
    .table-meta { color: #64748b; font-size: 13px; margin-top: 4px; }
    .panel { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05); }
    .panel h3 { margin: 0 0 8px 0; display: flex; align-items: center; justify-content: space-between; gap: 8px; }
    .click-row { cursor: pointer; transition: background 0.15s ease; }
    .click-row:hover { background: #f8fafc; }
    .user-stack { display: flex; align-items: center; gap: 10px; }
    .avatar-sm { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; background: #f8fafc; }
    .label-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; text-transform: capitalize; }
    .pill-approved { background: #ecfdf3; color: #166534; }
    .pill-pending { background: #f1f5f9; color: #475569; }
    .pill-denied { background: #fee2e2; color: #b91c1c; }
    .pill-interview { background: #fef3c7; color: #92400e; }
    .tab-bar { display: inline-flex; gap: 6px; background: #e5e7eb; padding: 6px; border-radius: 12px; margin-bottom: 12px; }
    .tab-link { border: none; background: #e5e7eb; padding: 10px 14px; border-radius: 10px; font-weight: 600; color: #334155; cursor: pointer; transition: all .15s ease; }
    .tab-link.active { background: #fff; box-shadow: 0 4px 10px rgba(15,23,42,0.12); color: #0f172a; }
    .tab-panels { margin-top: 6px; }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }
    /* Modal */
    .modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.55); display: flex; align-items: center; justify-content: center; padding: 20px; z-index: 999; }
    .modal-overlay[hidden] { display: none; }
    .modal-card { background: #fff; border-radius: 14px; width: min(900px, 96vw); box-shadow: 0 24px 60px rgba(15,23,42,0.25); overflow: hidden; }
    .modal-head { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid #e2e8f0; }
    .modal-title { font-size: 18px; font-weight: 700; margin: 0; color: #0f172a; }
    .modal-close { border: none; background: #f1f5f9; border-radius: 10px; padding: 10px 14px; cursor: pointer; font-weight: 600; color: #0f172a; }
    .modal-body { padding: 24px; display: grid; grid-template-columns: 320px 1fr; gap: 20px; align-items: start; }
    @media (max-width: 1100px) { .modal-body { grid-template-columns: 1fr; } }
    .avatar-xl { width: 140px; height: 140px; border-radius: 50%; object-fit: cover; border: 4px solid #e2e8f0; background: #f8fafc; }
    .pill-modal { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; font-weight: 700; font-size: 12px; text-transform: capitalize; }
    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
    .info-item { padding: 12px; border: 1px solid #e5e7eb; border-radius: 10px; background: #f8fafc; }
    .info-label { font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; }
    .info-value { color: #0f172a; font-weight: 700; margin-top: 4px; }
    .modal-actions { display:flex; gap:10px; margin-top:12px; flex-wrap:wrap; }
    .btn-ghost { background: #fff; border: 1px solid #d1d5db; color: #0f172a; border-radius: 8px; padding: 8px 12px; text-decoration: none; }
    .service-chip { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; font-weight:700; font-size:12px; }
    .service-a { background:#e0f2fe; color:#0ea5e9; }
    .service-b { background:#fef3c7; color:#b45309; }
    .service-c { background:#ecfdf3; color:#15803d; }
    .service-inactive { background:#e2e8f0; color:#475569; }
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

      <div class="breadcrumb">Home > Employee Management > Employee Overview</div>
      <h2>Employee Overview</h2>
      <p class="subtitle">Live view of every employee record stored in the database.</p>

      <div class="summary-cards">
        <div class="card"><h4>Total Employees</h4><p>{{ $totalEmployees }}</p></div>
        <div class="card"><h4>Active Employees</h4><p>{{ $activeEmployees }}</p></div>
        <div class="card"><h4>Departments</h4><p>{{ $departmentsCount }}</p></div>
        <div class="card"><h4>On Leave Today</h4><p>{{ $onLeave }}</p></div>
        <div class="card"><h4>Total Applicants</h4><p>{{ $totalApplicants }}</p></div>
        <div class="card"><h4>Approved Applicants</h4><p>{{ $approvedApplicants }}</p></div>
      </div>

      @if (session('success'))
        <div style="background:#ecfdf3; border:1px solid #bbf7d0; color:#166534; padding:12px 14px; border-radius:10px; margin-bottom:14px;">
          {{ session('success') }}
        </div>
      @endif

      @php $activeTab = request('tab', $tab ?? 'employees'); @endphp
      <form class="filter-bar" method="GET" action="{{ route('admin.employee.list') }}">
        <input type="hidden" name="tab" id="tabField" value="{{ $activeTab }}">
        <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
        <input type="text" name="q" value="{{ $search }}" placeholder="Search name, email or code..." />

        <select name="department">
          <option value="">All Departments</option>
          @foreach($departments as $dept)
            <option value="{{ $dept->department_id }}" {{ $departmentId == $dept->department_id ? 'selected' : '' }}>
              {{ $dept->department_name }}
            </option>
          @endforeach
        </select>

        <select name="status">
          <option value="">All Statuses</option>
          <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
          <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
          <option value="terminated" {{ $status === 'terminated' ? 'selected' : '' }}>Terminated</option>
        </select>

        <div class="actions">
          <button type="submit" class="btn-primary"><i class="fa-solid fa-filter"></i> Apply</button>
          @if($search || $departmentId || $status)
            <a class="btn-ghost" href="{{ route('admin.employee.list', ['tab' => $activeTab]) }}"><i class="fa-solid fa-rotate-left"></i> Reset</a>
          @endif
          <a class="btn-primary" href="{{ route('admin.employee.add') }}" style="text-decoration:none;"><i class="fa-solid fa-user-plus"></i> Add Employee</a>
        </div>
      </form>

      <div class="content-section">
        <div class="tab-bar">
          <button class="tab-link {{ $activeTab === 'employees' ? 'active' : '' }}" data-tab="employees">Employees</button>
          <button class="tab-link {{ $activeTab === 'applicants' ? 'active' : '' }}" data-tab="applicants">Applicants</button>
        </div>

        <div class="tab-panels">
          <div class="panel tab-panel {{ $activeTab === 'employees' ? 'active' : '' }}" id="tab-employees">
            <h3>
              Employees
              <span class="table-meta">Click a row to open profile</span>
            </h3>
            <table>
              <thead>
                <tr>
                  <th>Employee</th>
                  <th>Department</th>
                  <th>Position</th>
                  <th>Status</th>
                  <th>Service</th>
                  <th>Email</th>
                  <th>Hire Date</th>
                </tr>
              </thead>
              <tbody>
                @forelse($employees as $employee)
                  @php
                    $user = $employee->user;
                    $avatar = $user?->avatar_path
                      ? asset('storage/' . $user->avatar_path)
                      : 'https://ui-avatars.com/api/?name=' . urlencode($user->name ?? ($employee->employee_code ?? 'Employee')) . '&background=E0F2FE&color=0F172A';
                  @endphp
                  <tr class="click-row"
                      data-kind="employee"
                      data-href="{{ route('admin.employee.profile', $employee->employee_id) }}"
                      data-name="{{ optional($employee->user)->name ?? 'N/A' }}"
                      data-code="{{ $employee->employee_code }}"
                      data-status="{{ $employee->employee_status }}"
                      data-department="{{ optional($employee->department)->department_name ?? 'Not set' }}"
                      data-position="{{ optional($employee->position)->position_name ?? 'Not set' }}"
                      data-hire="{{ \Carbon\Carbon::parse($employee->hire_date)->format('M d, Y') }}"
                      data-salary="{{ isset($employee->base_salary) ? number_format($employee->base_salary, 2) : 'N/A' }}"
                      data-avatar="{{ $avatar }}"
                      data-email="{{ optional($employee->user)->email ?? 'N/A' }}"
                      data-phone="{{ $employee->phone ?? 'N/A' }}"
                      data-address="{{ $employee->address ?? 'N/A' }}">
                    <td>
                      <div class="user-stack">
                        <img src="{{ $avatar }}" alt="Avatar of {{ optional($employee->user)->name ?? 'employee' }}" class="avatar-sm">
                        <div>
                          <div style="font-weight:600; color:#0f172a;">{{ optional($employee->user)->name ?? 'N/A' }}</div>
                          <div class="muted">{{ $employee->employee_code }}</div>
                        </div>
                      </div>
                    </td>
                    <td>{{ optional($employee->department)->department_name ?? 'N/A' }}</td>
                    <td>{{ optional($employee->position)->position_name ?? 'N/A' }}</td>
                    <td>
                      @php
                        $statusClass = match($employee->employee_status) {
                          'active' => 'status-active',
                          'inactive' => 'status-inactive',
                          default => 'status-terminated'
                        };
                      @endphp
                      <span class="status-chip {{ $statusClass }}">{{ ucfirst($employee->employee_status) }}</span>
                    </td>
                    <td>
                      @php
                        $svc = $employee->service_snapshot ?? ['band'=>'BAND_A','label'=>'New Staff (<2 years)','inactive'=>false,'status_label'=>'New Staff (<2 years)','years'=>0,'months'=>0];
                        $svcClass = $svc['inactive']
                          ? 'service-inactive'
                          : ( $svc['band'] === 'BAND_A' ? 'service-a' : ($svc['band'] === 'BAND_B' ? 'service-b' : 'service-c') );
                        $yearsLabel = $svc['years'] . ' yrs';
                        if ($svc['months'] > 0) { $yearsLabel .= ' ' . $svc['months'] . ' mos'; }
                      @endphp
                      <span class="service-chip {{ $svcClass }}" title="Calculated from Hire Date">
                        {{ $svc['inactive'] ? $svc['status_label'] : $svc['label'] }}
                      </span>
                      <div class="muted">Working Years: {{ $yearsLabel }}</div>
                    </td>
                    <td>{{ optional($employee->user)->email ?? 'N/A' }}</td>
                    <td>{{ \Carbon\Carbon::parse($employee->hire_date)->format('M d, Y') }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="7" style="text-align:center; padding:20px; color:#94a3b8;">No employees found for the selected filters.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>

            @php
              $empQuery = ['tab' => 'employees', 'q' => $search, 'department' => $departmentId, 'status' => $status, 'per_page' => $employees->perPage()];
              $currentPage = $employees->currentPage();
              $lastPage = $employees->lastPage();
            @endphp
            <div class="pagination-bar" style="display:flex; justify-content: space-between; align-items:center; margin-top:16px; flex-wrap: wrap; gap: 12px;">
              <span class="muted" style="font-size:13px;">{{ $employees->total() }} records</span>
              <div style="display:flex; align-items:center; gap: 10px;">
                <a href="{{ route('admin.employee.list', array_merge($empQuery, ['page' => 1])) }}" class="btn-ghost" style="padding:6px 12px; text-decoration:none; {{ $currentPage <= 1 ? 'pointer-events:none; opacity:0.5;' : '' }}"><i class="fa-solid fa-angles-left"></i> First</a>
                <a href="{{ route('admin.employee.list', array_merge($empQuery, ['page' => max(1, $currentPage - 1)])) }}" class="btn-ghost" style="padding:6px 12px; text-decoration:none; {{ $currentPage <= 1 ? 'pointer-events:none; opacity:0.5;' : '' }}"><i class="fa-solid fa-chevron-left"></i> Prev</a>
                <span style="font-size:13px; color:#475569;">Page {{ $currentPage }} of {{ $lastPage ?: 1 }}</span>
                <a href="{{ route('admin.employee.list', array_merge($empQuery, ['page' => min($lastPage, $currentPage + 1)])) }}" class="btn-ghost" style="padding:6px 12px; text-decoration:none; {{ $currentPage >= $lastPage ? 'pointer-events:none; opacity:0.5;' : '' }}">Next <i class="fa-solid fa-chevron-right"></i></a>
                <a href="{{ route('admin.employee.list', array_merge($empQuery, ['page' => $lastPage ?: 1])) }}" class="btn-ghost" style="padding:6px 12px; text-decoration:none; {{ $currentPage >= $lastPage ? 'pointer-events:none; opacity:0.5;' : '' }}">Last <i class="fa-solid fa-angles-right"></i></a>
              </div>
              <form method="GET" action="{{ route('admin.employee.list') }}" style="display:flex; align-items:center; gap:6px;">
                @foreach(array_filter(['tab' => 'employees', 'q' => $search, 'department' => $departmentId, 'status' => $status]) as $k => $v)
                  <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <input type="hidden" name="page" value="1">
                <label style="font-size:13px; color:#64748b;">Show</label>
                <select name="per_page" onchange="this.form.submit()" style="padding:6px 10px; border:1px solid #d1d5db; border-radius:8px; font-size:13px;">
                  <option value="10" {{ $employees->perPage() == 10 ? 'selected' : '' }}>10</option>
                  <option value="25" {{ $employees->perPage() == 25 ? 'selected' : '' }}>25</option>
                  <option value="50" {{ $employees->perPage() == 50 ? 'selected' : '' }}>50</option>
                  <option value="100" {{ $employees->perPage() == 100 ? 'selected' : '' }}>100</option>
                </select>
              </form>
            </div>
          </div>

          <div class="panel tab-panel {{ $activeTab === 'applicants' ? 'active' : '' }}" id="tab-applicants">
            <h3>
              Applicants
              <span class="table-meta">Click a row to open profile</span>
            </h3>
            <table>
              <thead>
                <tr>
                  <th>Applicant</th>
                  <th>Role</th>
                  <th>Stage</th>
                  <th>Email</th>
                  <th>Updated</th>
                  <th style="width:120px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($applicants as $applicant)
                  @php
                    $stage = optional($applicant->latestApplication)->app_stage ?? 'Profile';
                    $pillClass = match(strtolower($stage)) {
                      'approved' => 'pill-approved',
                      'denied', 'rejected' => 'pill-denied',
                      'interview' => 'pill-interview',
                      default => 'pill-pending',
                    };
                    $lastUpdated = optional($applicant->latestApplication)->updated_at
                        ?? optional($applicant->latestApplication)->created_at
                        ?? $applicant->created_at;
                    $appAvatar = $applicant->avatar_path
                        ? asset('storage/' . $applicant->avatar_path)
                        : 'https://ui-avatars.com/api/?name=' . urlencode($applicant->full_name ?? 'Applicant') . '&background=E0F2FE&color=0F172A';
                    $jobTitle = optional(optional($applicant->latestApplication)->job)->job_title ?? 'Not specified';
                    $deptName = optional(optional($applicant->latestApplication)->job)->department ?? 'N/A';
                  @endphp
                  <tr class="click-row"
                      data-kind="applicant"
                      data-href="{{ route('admin.applicants.profile', $applicant->applicant_id) }}"
                      data-name="{{ $applicant->full_name ?? 'N/A' }}"
                      data-stage="{{ $stage }}"
                      data-applicantid="{{ $applicant->applicant_id }}"
                      data-role="{{ $jobTitle }}"
                      data-department="{{ $deptName }}"
                      data-email="{{ optional($applicant->user)->email ?? ($applicant->email ?? 'N/A') }}"
                      data-phone="{{ $applicant->phone ?? 'N/A' }}"
                      data-location="{{ $applicant->location ?? 'N/A' }}"
                      data-avatar="{{ $appAvatar }}">
                    <td>
                      <div class="user-stack">
                        <img src="{{ $appAvatar }}" alt="Applicant Avatar" class="avatar-sm">
                        <div>
                          <div style="font-weight:600; color:#0f172a;">{{ $applicant->full_name ?? 'N/A' }}</div>
                          <div class="muted">Applicant #{{ $applicant->applicant_id }}</div>
                        </div>
                      </div>
                    </td>
                    <td>{{ optional(optional($applicant->latestApplication)->job)->job_title ?? 'Not specified' }}</td>
                    <td><span class="label-pill {{ $pillClass }}">{{ $stage }}</span></td>
                    <td>{{ optional($applicant->user)->email ?? ($applicant->email ?? 'N/A') }}</td>
                    <td>{{ optional($lastUpdated)->format('M d, Y') ?? '—' }}</td>
                    <td>
                      @if($applicant->latestApplication)
                        @php
                          $eval = $applicant->latestApplication;
                          $hasEvaluation = !is_null($eval->overall_score) || (!is_null($eval->test_score) && !is_null($eval->interview_score));
                        @endphp
                        @if($hasEvaluation)
                          <div style="display:flex; flex-direction:column; gap:6px; align-items:flex-start;">
                            <div class="muted" style="font-size:11px;">
                              Eval: 
                              <strong>{{ $eval->overall_score ?? '-' }}</strong>
                              @if(!is_null($eval->test_score) || !is_null($eval->interview_score))
                                <span style="color:#9ca3af;">(Test {{ $eval->test_score ?? '-' }}, Interview {{ $eval->interview_score ?? '-' }})</span>
                              @endif
                            </div>
                            <form method="POST" action="{{ route('admin.applicants.updateStatus', $eval->application_id) }}" style="display:inline;">
                              @csrf
                              <input type="hidden" name="status" value="Approved">
                              <input type="hidden" name="redirect_to_add" value="1">
                              <button type="submit" class="btn-primary row-action" style="padding:6px 10px; font-size:12px;">Approve</button>
                            </form>
                          </div>
                        @else
                          <a href="{{ route('admin.applicants.show', $applicant->latestApplication->application_id) }}" class="btn-secondary row-action" style="padding:6px 10px; font-size:12px;">Evaluate</a>
                        @endif
                      @else
                        <span class="muted">No application</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" style="text-align:center; padding:20px; color:#94a3b8;">No applicants yet.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
            <div style="display:flex; justify-content: space-between; align-items:center; margin-top:16px; flex-wrap: wrap; gap: 10px;">
              <div class="muted" style="font-size:13px;">
                Showing {{ $applicantsPage->firstItem() ?? 0 }}-{{ $applicantsPage->lastItem() ?? 0 }} of {{ $applicantsPage->total() }} applicants
              </div>
              <div>
                {{ $applicantsPage->appends(['q' => $search, 'status' => $status, 'department' => $departmentId, 'tab' => 'applicants'])->links() }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <footer>&copy; 2025 Web-Based HRMS. All Rights Reserved.</footer>
    </main>
  </div>

  {{-- Employee quick-view modal --}}
  <div class="modal-overlay" id="employeeModal" hidden>
    <div class="modal-card">
      <div class="modal-head">
        <h3 class="modal-title">Employee Profile</h3>
        <button class="modal-close" id="employeeModalClose">Close</button>
      </div>
      <div class="modal-body">
        <div style="text-align:left; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px;">
          <div style="display:flex; justify-content:center; margin-bottom:10px;">
            <img src="https://ui-avatars.com/api/?name=Employee&background=E0F2FE&color=0F172A" alt="Employee photo" id="modalAvatar" class="avatar-xl" style="width:110px; height:110px; border-width:3px;">
          </div>
          <h3 style="margin:0 0 6px 0;" id="modalName">Name</h3>
          <div class="pill pill-modal" id="modalStatus">Status</div>
          <div style="margin-top:10px; color:#475569;">Employee Code: <strong id="modalCode"></strong></div>
          <div style="margin-top:4px; color:#475569;">Role: <strong id="modalPosition"></strong></div>
        </div>
        <div style="display:grid; gap:14px; margin-top:12px;">
          <div class="info-grid">
            <div class="info-item">
              <div class="info-label">Department</div>
              <div class="info-value" id="modalDept"></div>
            </div>
            <div class="info-item">
              <div class="info-label">Hire Date</div>
              <div class="info-value" id="modalHire"></div>
            </div>
            <div class="info-item">
              <div class="info-label">Base Salary</div>
              <div class="info-value" id="modalSalary"></div>
            </div>
          </div>
          <div class="info-grid">
            <div class="info-item">
              <div class="info-label">Email</div>
              <div class="info-value" id="modalEmail"></div>
            </div>
            <div class="info-item">
              <div class="info-label">Phone</div>
              <div class="info-value" id="modalPhone"></div>
            </div>
            <div class="info-item" style="grid-column: span 2;">
              <div class="info-label">Address</div>
              <div class="info-value" id="modalAddress"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Applicant quick-view modal --}}
  <div class="modal-overlay" id="applicantModal" hidden>
    <div class="modal-card">
      <div class="modal-head">
        <h3 class="modal-title">Applicant Profile</h3>
        <button class="modal-close" id="applicantModalClose">Close</button>
      </div>
      <div class="modal-body">
        <div style="text-align:left; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px; display:flex; gap:14px; align-items:center;">
          <img src="" alt="Applicant photo" id="appModalAvatar" class="avatar-sm" style="width:60px; height:60px; border-radius:50%; object-fit:cover; border:3px solid #e2e8f0;">
          <div>
            <h3 style="margin:0 0 6px 0;" id="appModalName">Name</h3>
            <div class="pill pill-modal" id="appModalStage">Stage</div>
            <div style="margin-top:6px; color:#475569;">Applicant ID: <strong id="appModalId"></strong></div>
            <div style="margin-top:4px; color:#475569;">Role: <strong id="appModalRole"></strong></div>
          </div>
        </div>
        <div style="display:grid; gap:14px; margin-top:12px;">
          <div class="info-grid">
            <div class="info-item">
              <div class="info-label">Department</div>
              <div class="info-value" id="appModalDept"></div>
            </div>
            <div class="info-item">
              <div class="info-label">Email</div>
              <div class="info-value" id="appModalEmail"></div>
            </div>
            <div class="info-item">
              <div class="info-label">Phone</div>
              <div class="info-value" id="appModalPhone"></div>
            </div>
            <div class="info-item" style="grid-column: span 2;">
              <div class="info-label">Location</div>
              <div class="info-value" id="appModalLocation"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', () => {
    /* ===== Unified Sidebar Behavior: single active, single open, persisted ===== */
    const groups  = document.querySelectorAll('.sidebar-group');
    const toggles = document.querySelectorAll('.sidebar-toggle');
    const links   = document.querySelectorAll('.submenu a');
    const STORAGE_KEY = 'hrms_sidebar_open_group';

    // Normalize paths so /x and /x/ match; ignore index.php variants
    const normPath = (u) => {
      const url = new URL(u, location.origin);
      let p = url.pathname
        .replace(/\/index\.php$/i, '')
        .replace(/\/index\.php\//i, '/')
        .replace(/\/+$/, '');
      return p === '' ? '/' : p;
    };
    const here = normPath(location.href);

    // Clear any server-injected open/active to avoid double highlight
    groups.forEach(g => {
      g.classList.remove('open');
      const t = g.querySelector('.sidebar-toggle');
      if (t) t.setAttribute('aria-expanded','false');
    });
    links.forEach(a => a.classList.remove('active'));

    // Choose exactly one active link (exact match, else best prefix)
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

    // Restore previously open group if none opened from active
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

    // Accordion behavior + persistence
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

    const employeeModal = document.getElementById('employeeModal');
    const applicantModal = document.getElementById('applicantModal');
    const modalClose = document.getElementById('employeeModalClose');
    const appModalClose = document.getElementById('applicantModalClose');
    const modalFields = {
      name: document.getElementById('modalName'),
      code: document.getElementById('modalCode'),
      status: document.getElementById('modalStatus'),
      position: document.getElementById('modalPosition'),
      dept: document.getElementById('modalDept'),
      hire: document.getElementById('modalHire'),
      salary: document.getElementById('modalSalary'),
      email: document.getElementById('modalEmail'),
      phone: document.getElementById('modalPhone'),
      address: document.getElementById('modalAddress'),
      avatar: document.getElementById('modalAvatar'),
    };

    const openModal = (row) => {
      modalFields.name.textContent = row.dataset.name || 'N/A';
      modalFields.code.textContent = row.dataset.code || '—';
      modalFields.position.textContent = row.dataset.position || 'Not set';
      modalFields.dept.textContent = row.dataset.department || 'Not set';
      modalFields.hire.textContent = row.dataset.hire || '—';
      modalFields.salary.textContent = row.dataset.salary || 'N/A';
      modalFields.email.textContent = row.dataset.email || 'N/A';
      modalFields.phone.textContent = row.dataset.phone || 'N/A';
      modalFields.address.textContent = row.dataset.address || 'N/A';
      const fallbackAvatar = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(row.dataset.name || 'Employee') + '&background=E0F2FE&color=0F172A';
      if (modalFields.avatar) {
        modalFields.avatar.src = row.dataset.avatar || fallbackAvatar;
        modalFields.avatar.alt = (row.dataset.name || 'Employee') + ' photo';
      }

      const status = (row.dataset.status || 'inactive').toLowerCase();
      modalFields.status.textContent = status.charAt(0).toUpperCase() + status.slice(1);
      modalFields.status.className = 'pill pill-modal ' + (status === 'active' ? 'pill-active' : status === 'terminated' ? 'pill-terminated' : 'pill-inactive');

      employeeModal.hidden = false;
      document.body.style.overflow = 'hidden';
    };

    const appModalFields = {
      name: document.getElementById('appModalName'),
      stage: document.getElementById('appModalStage'),
      id: document.getElementById('appModalId'),
      role: document.getElementById('appModalRole'),
      dept: document.getElementById('appModalDept'),
      email: document.getElementById('appModalEmail'),
      phone: document.getElementById('appModalPhone'),
      location: document.getElementById('appModalLocation'),
      avatar: document.getElementById('appModalAvatar'),
    };

    const openApplicantModal = (row) => {
      appModalFields.name.textContent = row.dataset.name || 'N/A';
      const stage = (row.dataset.stage || 'Profile');
      appModalFields.stage.textContent = stage;
      appModalFields.stage.className = 'pill pill-modal ' + (stage.toLowerCase() === 'approved' ? 'pill-approved' : stage.toLowerCase() === 'denied' ? 'pill-denied' : stage.toLowerCase() === 'interview' ? 'pill-interview' : 'pill-pending');
      appModalFields.id.textContent = row.dataset.applicantid || '—';
      appModalFields.role.textContent = row.dataset.role || 'Not specified';
      appModalFields.dept.textContent = row.dataset.department || 'N/A';
      appModalFields.email.textContent = row.dataset.email || 'N/A';
      appModalFields.phone.textContent = row.dataset.phone || 'N/A';
      appModalFields.location.textContent = row.dataset.location || 'N/A';
      appModalFields.avatar.src = row.dataset.avatar || '';

      applicantModal.hidden = false;
      document.body.style.overflow = 'hidden';
    };

    const closeModal = () => {
      employeeModal.hidden = true;
      document.body.style.overflow = '';
    };

    const closeApplicantModal = () => {
      applicantModal.hidden = true;
      document.body.style.overflow = '';
    };

    // Clickable rows for profiles / modal
    document.querySelectorAll('.click-row').forEach(row => {
      row.addEventListener('click', (e) => {
        const kind = row.dataset.kind;
        const url = row.dataset.href;
        if (kind === 'employee') {
          e.preventDefault();
          openModal(row);
        } else if (kind === 'applicant') {
          e.preventDefault();
          openApplicantModal(row);
        } else if (url) {
          window.location.href = url;
        }
      });
    });

    modalClose?.addEventListener('click', closeModal);
    appModalClose?.addEventListener('click', closeApplicantModal);
    employeeModal?.addEventListener('click', (e) => {
      if (e.target === employeeModal) closeModal();
    });
    applicantModal?.addEventListener('click', (e) => {
      if (e.target === applicantModal) closeApplicantModal();
    });
    document.querySelectorAll('.row-action').forEach(btn => {
      ['click','mousedown','mouseup'].forEach(ev => btn.addEventListener(ev, e => e.stopPropagation()));
    });

    // Tabs: Employees / Applicants
    const tabs = document.querySelectorAll('.tab-link');
    const panels = {
      employees: document.getElementById('tab-employees'),
      applicants: document.getElementById('tab-applicants')
    };
    const tabField = document.getElementById('tabField');

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        const target = tab.dataset.tab;
        tabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        Object.entries(panels).forEach(([key, el]) => {
          if (!el) return;
          el.classList.toggle('active', key === target);
        });
        if (tabField) tabField.value = target;
      });
    });
  });
  </script>
</body>
</html>
