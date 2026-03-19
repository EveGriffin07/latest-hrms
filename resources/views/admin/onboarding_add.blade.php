<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Setup Provisioning - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
  <style>
      .form-section { background: white; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
      .section-title { font-size: 18px; font-weight: 600; color: #0f172a; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; display: flex; align-items: center; gap: 10px; }
      .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: #334155; }
      .form-group input[type="date"], .form-group textarea { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; margin-bottom: 20px; font-family: inherit; font-size: 14px; outline: none; transition: 0.2s; }
      .ts-control { padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-family: inherit; font-size: 14px; box-shadow: none; transition: 0.2s; margin-bottom: 20px; }
      .ts-control.focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
      .ts-dropdown { border-radius: 8px; border: 1px solid #cbd5e1; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); overflow: hidden; font-family: inherit; font-size: 14px; }
      .ts-dropdown .option { padding: 10px 12px; }
      .ts-dropdown .active { background-color: #eff6ff; color: #1e40af; }
      .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
      .employee-meta-box { background: #f8fafc; border: 1px dashed #cbd5e1; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 20px; align-items: center; flex-wrap: wrap; }
      .meta-item { display: flex; flex-direction: column; }
      .meta-label { font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600; }
      .meta-value { font-size: 15px; font-weight: 700; color: #0f172a; }
      .checkbox-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
      .check-card { background: #fff; border: 1px solid #cbd5e1; padding: 15px; border-radius: 8px; display: flex; align-items: center; gap: 12px; cursor: pointer; transition: 0.2s; }
      .check-card:hover { border-color: #2563eb; background: #eff6ff; }
      .check-card input[type="checkbox"] { transform: scale(1.3); accent-color: #2563eb; }
      .check-card strong { font-size: 14px; color: #1e293b; }
      .check-card i { font-size: 18px; color: #64748b; width: 24px; text-align: center; }
  </style>
</head>
<body>
<header>
  <div class="title">Web-Based HRMS</div>
  <div class="user-info">
    <a href="{{ route('admin.profile') }}" style="text-decoration: none; color: inherit;"><i class="fa-regular fa-user"></i> &nbsp; {{ Auth::user()->name ?? 'HR Admin' }}</a>
  </div>
</header>

<div class="container">
  @include('admin.layout.sidebar')

  <main>
    <div style="margin-bottom: 30px;">
        <div class="breadcrumb" style="color: #64748b; font-size: 14px; margin-bottom: 5px;">Home > Onboarding > <span style="color: #0f172a; font-weight: 500;">Setup</span></div>
        <h2 style="margin:0; font-size:28px; color:#0f172a;">Hierarchy & Provisioning Setup</h2>
        <p style="color: #64748b; margin-top: 5px; font-size: 15px;">Assign a supervisor, configure hardware, and define the welcome tasks for a new hire.</p>
    </div>

    <form action="{{ route('admin.onboarding.store') }}" method="POST">
        @csrf
        
        {{-- STEP 1: EMPLOYEE SELECTION --}}
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-user-check" style="color: #2563eb;"></i> Step 1: Target Employee</h3>
            <div class="form-group">
                <label>Search & Select New Hire <span style="color:#dc2626">*</span></label>
                <select name="employee_id" id="employeeSelect" required placeholder="-- Type to search by name or ID --">
                    <option value="">-- Type to search by name or ID --</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->employee_id }}" 
                                data-dept="{{ $emp->department->department_name ?? 'N/A' }}"
                                data-pos="{{ $emp->position->position_name ?? 'N/A' }}">
                            {{ $emp->user->name ?? 'Unknown' }} (ID: {{ $emp->employee_code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="employee-meta-box" id="metaBox" style="display: none;">
                <div class="meta-item"><span class="meta-label"><i class="fa-solid fa-building"></i> Department</span><span class="meta-value" id="metaDept">-</span></div>
                <div style="width: 2px; height: 30px; background: #cbd5e1;"></div>
                <div class="meta-item"><span class="meta-label"><i class="fa-solid fa-briefcase"></i> Role</span><span class="meta-value" id="metaPos">-</span></div>
            </div>

            <div class="form-row" style="margin-bottom: 0;">
                <div class="form-group"><label>First Day (Start Date) <span style="color:#dc2626">*</span></label><input type="date" name="startDate" required></div>
                <div class="form-group"><label>Onboarding Completion Deadline <span style="color:#dc2626">*</span></label><input type="date" name="deadline" required></div>
            </div>
        </div>

        {{-- STEP 2: SUPERVISOR ASSIGNMENT --}}
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-sitemap" style="color: #2563eb;"></i> Step 2: Reporting Structure</h3>
            <div class="form-group" style="margin-bottom: 0;">
                <label>Assign Supervisor / Manager <span style="color:#dc2626">*</span></label>
                <select name="supervisor_id" id="supervisorSelect" required placeholder="-- Search to assign a Supervisor --">
                    <option value="">-- Search to assign a Supervisor --</option>
                    @foreach($supervisors as $sup)
                        <option value="{{ $sup->employee_id }}">{{ $sup->user->name }} ({{ $sup->department->department_name ?? 'General' }})</option>
                    @endforeach
                </select>
                <small style="color:#64748b; margin-top: 5px; display: block;">This manager will be responsible for reviewing and verifying the checklist tasks.</small>
            </div>
        </div>

        {{-- STEP 3: ASSETS & CHECKLIST --}}
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-laptop-code" style="color: #2563eb;"></i> Step 3: IT Assets & System Access</h3>
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 10px; color: #334155;">Hardware Required</label>
            <div class="checkbox-grid">
                <label class="check-card"><input type="checkbox" name="assets[]" value="Laptop" checked> <i class="fa-solid fa-laptop"></i> <strong>MacBook / Laptop</strong></label>
                <label class="check-card"><input type="checkbox" name="assets[]" value="Monitor"> <i class="fa-solid fa-desktop"></i> <strong>Extra Monitor</strong></label>
                <label class="check-card"><input type="checkbox" name="assets[]" value="Mobile Phone"> <i class="fa-solid fa-mobile-screen"></i> <strong>Work Phone</strong></label>
            </div>
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 10px; color: #334155; margin-top: 20px;">System Accounts</label>
            <div class="checkbox-grid">
                <label class="check-card"><input type="checkbox" name="access[]" value="Corporate Email" checked> <i class="fa-regular fa-envelope"></i> <strong>Corp Email</strong></label>
                <label class="check-card"><input type="checkbox" name="access[]" value="Internal VPN"> <i class="fa-solid fa-network-wired"></i> <strong>VPN Access</strong></label>
                <label class="check-card"><input type="checkbox" name="access[]" value="GitHub / Jira"> <i class="fa-brands fa-github"></i> <strong>Dev Tools</strong></label>
            </div>
            
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 15px; color: #334155; margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 20px;">Standard HR Compliance Checklists:</label>
            <div class="checkbox-grid">
                <label class="check-card"><input type="checkbox" name="default_tasks[]" value="documents" checked> <i class="fa-solid fa-file-signature"></i> <strong>Collect HR Docs</strong></label>
                <label class="check-card"><input type="checkbox" name="default_tasks[]" value="orientation" checked> <i class="fa-solid fa-chalkboard-user"></i> <strong>Orientation</strong></label>
                <label class="check-card"><input type="checkbox" name="default_tasks[]" value="policies" checked> <i class="fa-solid fa-book"></i> <strong>Policy Sign-off</strong></label>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label>Custom Task (Optional)</label>
                <textarea name="customTask" rows="2" placeholder="e.g., Schedule lunch with the QA team on Day 3..."></textarea>
            </div>
        </div>

        <div style="text-align: right; margin-bottom: 50px;">
            <a href="{{ route('admin.onboarding') }}" style="background: #fff; color: #475569; border: 1px solid #cbd5e1; padding: 12px 24px; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-block; margin-right: 10px;">Cancel</a>
            <button type="submit" style="background: #2563eb; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 6px rgba(37,99,235,0.2);"><i class="fa-solid fa-bolt"></i> Generate Setup & Tasks</button>
        </div>

        @if ($errors->any())
    <div style="background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 25px;">
        <ul style="margin: 0; padding-left: 20px;">
            @foreach ($errors->all() as $error)
                <li style="font-size: 14px;">{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
    </form>
  </main>
</div>

<script>
    document.querySelector('form').addEventListener('submit', function(e) {
        const startDate = new Date(document.querySelector('input[name="startDate"]').value);
        const deadline = new Date(document.querySelector('input[name="deadline"]').value);
        const employeeId = document.querySelector('#employeeSelect').value;
        const supervisorId = document.querySelector('#supervisorSelect').value;

        let errors = [];

        // 1. Logic Check: Dates
        if (deadline < startDate) {
            errors.push("The onboarding deadline cannot be earlier than the start date.");
        }

        // 2. Logic Check: Hierarchy
        if (employeeId === supervisorId && employeeId !== "") {
            errors.push("An employee cannot be assigned as their own supervisor.");
        }

        if (errors.length > 0) {
            e.preventDefault();
            alert("Please fix the following errors:\n- " + errors.join("\n- "));
        }
    });

    // Dynamic Date Restriction: Deadline cannot be before Start Date
    const startInput = document.querySelector('input[name="startDate"]');
    const deadlineInput = document.querySelector('input[name="deadline"]');

    startInput.addEventListener('change', function() {
        deadlineInput.min = this.value;
    });

    
    document.addEventListener('DOMContentLoaded', function() {
        // Set the minimum selectable date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('startDateInput').setAttribute('min', today);
    });

</script>
</body>
</html>