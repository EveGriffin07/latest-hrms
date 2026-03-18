<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Details - HRMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
    <style>
        /* Hero Section Card */
        .detail-card { background: #fff; padding: 35px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
        .header-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 30px; border-bottom: 2px solid #f1f5f9; padding-bottom: 25px; margin-bottom: 25px; }
        
        .grid-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
        .info-box { background: #f8fafc; padding: 20px; border-radius: 10px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; justify-content: flex-start; transition: 0.2s; }
        .info-box:hover { background: #f1f5f9; border-color: #cbd5e1; }
        .info-box span { display: flex; align-items: center; gap: 8px; font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 600; margin-bottom: 10px; letter-spacing: 0.5px; }
        .info-box span i { font-size: 16px; color: #2563eb; }
        .info-box strong { color: #0f172a; font-size: 15px; display: block; line-height: 1.4; }
        .info-box small { color: #475569; font-size: 13px; display: block; margin-top: 6px; display: flex; align-items: center; gap: 6px; }
        
        /* Table Styles */
        .hr-table { width: 100%; border-collapse: collapse; text-align: left; }
        .hr-table th { background: #f8fafc; padding: 14px 20px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb; }
        .hr-table td { padding: 16px 20px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #1e293b; }
        .hr-table tbody tr:hover { background: #f8fafc; }

        /* Badges */
        .status-badge { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; border: 1px solid transparent; }
        .status-badge.completed { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
        .status-badge.ongoing { background: #dbeafe; color: #1e40af; border-color: #bfdbfe; }
        .status-badge.upcoming { background: #ffedd5; color: #9a3412; border-color: #fed7aa; }
        .status-badge.failed { background: #fee2e2; color: #991b1b; border-color: #fecaca; }

        .btn-icon { padding: 8px 12px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; text-decoration: none; }
        .btn-icon:hover { background: #f1f5f9; color: #2563eb; border-color: #cbd5e1; }
        
        /* Modal Styles */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center; }
        .modal-overlay.active { display: flex; }
        .modal-card { background: #fff; width: 750px; max-width: 95%; border-radius: 16px; padding: 0; overflow: hidden; display: flex; flex-direction: column; max-height: 90vh; animation: slideUp 0.3s ease-out; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { padding: 25px 30px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
        .modal-body { padding: 30px; overflow-y: auto; }
        .modal-footer { padding: 20px 30px; border-top: 1px solid #e2e8f0; background: #f8fafc; text-align: right; }

        /* Employee Checkbox List inside Modal */
        .filter-bar { display: flex; gap: 15px; margin-bottom: 20px; }
        .search-input, .dept-select { flex: 1; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 14px; outline: none; transition: 0.2s; }
        .search-input:focus, .dept-select:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        
        .employee-list { border: 1px solid #e2e8f0; border-radius: 8px; max-height: 350px; overflow-y: auto; background: #fff; }
        .employee-item { display: flex; align-items: center; padding: 15px 20px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.2s; margin: 0; }
        .employee-item:last-child { border-bottom: none; }
        .employee-item:hover { background: #f8fafc; }
        .employee-item input[type="checkbox"] { transform: scale(1.3); margin-right: 20px; cursor: pointer; accent-color: #2563eb; }
        .emp-details { display: flex; flex-direction: column; }
        .emp-name { font-weight: 600; color: #0f172a; font-size: 14px; }
        .emp-sub { font-size: 12px; color: #64748b; margin-top: 2px; }
    </style>
</head>
<body>

<div class="container">
    @include('admin.layout.sidebar')

    <main>
        <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:30px;">
            <div>
                <div class="breadcrumb" style="color: #64748b; font-size: 14px; margin-bottom: 5px;">
                    <a href="{{ route('admin.training') }}" style="color: #64748b; text-decoration: none;">Training</a> > 
                    <span style="color: #0f172a; font-weight: 500;">Details</span>
                </div>
                <h2 style="margin:0; font-size:28px; color:#0f172a;">{{ $program->training_name }}</h2>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="{{ route('admin.training') }}" style="background: #fff; color: #475569; border: 1px solid #cbd5e1; padding: 10px 20px; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;"><i class="fa-solid fa-arrow-left"></i> Back</a>
                
                @php
                    $isFull = $program->mode == 'Onsite' && $program->max_participants && $program->enrollments->count() >= $program->max_participants;
                @endphp
                
                <button onclick="openEnrollModal()" class="btn btn-primary" {{ $isFull ? 'disabled' : '' }} style="background: {{ $isFull ? '#94a3b8' : '#2563eb' }}; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: {{ $isFull ? 'not-allowed' : 'pointer' }}; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-user-plus"></i> {{ $isFull ? 'Capacity Reached' : 'Enroll Employees' }}
                </button>
            </div>
        </div>

        @if(session('success'))
            <div style="background:#dcfce7; color:#166534; padding:15px 20px; border-radius:8px; margin-bottom:25px; border: 1px solid #bbf7d0; display:flex; align-items:center; gap:10px; font-weight: 500;">
                <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div style="background:#fee2e2; color:#b91c1c; padding:15px 20px; border-radius:8px; margin-bottom:25px; border: 1px solid #fecaca; display:flex; align-items:center; gap:10px; font-weight: 500;">
                <i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}
            </div>
        @endif

        {{-- INFO CARD --}}
        <div class="detail-card">
            <div class="header-row">
                <div style="flex: 1;">
                    <h3 style="font-size: 16px; font-weight: 600; margin: 0 0 10px 0; color: #0f172a;">Program Description</h3>
                    <p style="color:#475569; margin:0; line-height:1.6; font-size: 14px;">{{ $program->tr_description ?? 'No description provided.' }}</p>
                </div>
                <div>
                    @if($program->tr_status == 'completed')
                        <span class="status-badge completed"><i class="fa-solid fa-check-double"></i> Completed</span>
                    @elseif($program->tr_status == 'active')
                        <span class="status-badge ongoing"><i class="fa-solid fa-spinner"></i> Ongoing</span>
                    @else
                        <span class="status-badge upcoming"><i class="fa-regular fa-calendar"></i> Upcoming</span>
                    @endif
                </div>
            </div>

            <div class="grid-info">
                {{-- Trainer Box --}}
                <div class="info-box">
                    <span><i class="fa-solid fa-chalkboard-user"></i> Trainer Details</span>
                    <strong>{{ $program->provider }}</strong>
                    @if($program->trainer_company)
                        <small><i class="fa-regular fa-building"></i> {{ $program->trainer_company }}</small>
                    @endif
                    @if($program->trainer_email)
                        <small><i class="fa-regular fa-envelope"></i> <a href="mailto:{{ $program->trainer_email }}" style="color:#2563eb; text-decoration:none;">{{ $program->trainer_email }}</a></small>
                    @endif
                </div>

                {{-- Dates & Time Box --}}
                <div class="info-box">
                    <span><i class="fa-regular fa-clock"></i> Schedule</span>
                    <strong>
                        {{ \Carbon\Carbon::parse($program->start_date)->format('d M Y') }} 
                        @if($program->start_date != $program->end_date)
                            - <br>{{ \Carbon\Carbon::parse($program->end_date)->format('d M Y') }}
                        @endif
                    </strong>
                    <small style="margin-top: 10px;">
                        @if($program->start_time)
                            <i class="fa-solid fa-play" style="color:#cbd5e1; font-size: 10px;"></i> Starts at {{ \Carbon\Carbon::parse($program->start_time)->format('h:i A') }}
                        @else
                            Time TBD
                        @endif
                    </small>
                </div>

                {{-- Mode & Capacity Box --}}
                <div class="info-box">
                    <span><i class="fa-solid fa-layer-group"></i> Mode & Capacity</span>
                    <strong>
                        @if($program->mode == 'Online')
                            <i class="fa-solid fa-laptop" style="color:#0ea5e9;"></i> Online
                        @else
                            <i class="fa-solid fa-building" style="color:#10b981;"></i> Onsite
                        @endif
                    </strong>
                    <small style="margin-top: 10px;">
                        <i class="fa-solid fa-users" style="color:#cbd5e1;"></i>
                        @if($program->mode == 'Onsite' && $program->max_participants)
                            {{ $program->enrollments->count() }} / {{ $program->max_participants }} Enrolled
                        @else
                            Unlimited Capacity
                        @endif
                    </small>
                </div>

                <div class="info-box">
                    <span><i class="fa-solid fa-location-dot"></i> Location / Link</span>
                    <strong style="word-break: break-all; margin-bottom: auto;">
                        @if(Str::startsWith($program->location, ['http://', 'https://']))
                            <a href="{{ $program->location }}" target="_blank" style="color:#2563eb; text-decoration:none;">Join Virtual Meeting <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:12px; margin-left: 5px;"></i></a>
                        @else
                            {{ $program->location }}
                        @endif
                    </strong>
                </div>

                {{-- QR Code Box --}}
                <div class="info-box" style="align-items: center; text-align: center;">
                    <span style="justify-content: center;"><i class="fa-solid fa-qrcode"></i> Attendance QR</span>
                    
                    @if($program->qr_token)
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ $program->qr_token }}" alt="QR Code" style="margin-top: 5px; border-radius: 8px; border: 1px solid #e2e8f0; padding: 5px; background: #fff;">
                    @else
                        <strong style="color: #94a3b8; margin-top: 20px;">No QR generated</strong>
                    @endif
                </div>
            </div>
        </div>

        {{-- PARTICIPANTS LIST --}}
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="margin:0; font-size: 18px; color: #0f172a;"><i class="fa-solid fa-users-viewfinder" style="color: #2563eb; margin-right: 8px;"></i> Enrolled Participants ({{ $program->enrollments->count() }})</h3>
        </div>
        
        <div style="background:#fff; border-radius:12px; border:1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
            <table class="hr-table">
                <thead>
                    <tr>
                        <th>Employee Info</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($program->enrollments as $enrollment)
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: #0f172a; font-size: 14px;">{{ $enrollment->employee->user->name ?? 'N/A' }}</div>
                            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">{{ $enrollment->employee->employee_code ?? '-' }}</div>
                        </td>
                        <td style="font-weight: 500; color: #475569;">{{ $enrollment->employee->department->department_name ?? '-' }}</td>
                        <td>
                            @if($enrollment->completion_status == 'completed')
                                <span class="status-badge completed">Completed</span>
                            @elseif($enrollment->completion_status == 'failed')
                                <span class="status-badge failed">Failed / Absent</span>
                            @else
                                <span class="status-badge ongoing" style="background: #f1f5f9; color: #475569; border-color: #e2e8f0;">Enrolled</span>
                            @endif
                        </td>
                        <td style="color:#64748b; font-size:13px; font-style: {{ $enrollment->remarks ? 'normal' : 'italic' }};">
                            {{ $enrollment->remarks ?? 'No remarks' }}
                        </td>
                        <td style="text-align:right;">
                            <button onclick="openUpdateModal({{ $enrollment->enrollment_id }}, '{{ $enrollment->completion_status }}', '{{ addslashes($enrollment->remarks) }}')" class="btn-icon" title="Update Status"><i class="fa-solid fa-pen"></i> Update</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align:center; padding:50px 20px; color:#94a3b8;">
                            <i class="fa-solid fa-users-slash" style="font-size:32px; margin-bottom:15px; display:block; color: #cbd5e1;"></i>
                            <span style="font-size: 15px; font-weight: 500; color: #334155; display: block; margin-bottom: 5px;">No participants enrolled.</span>
                            Click the "Enroll Employees" button to add attendees.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <footer style="margin-top:40px; text-align:center; color:#94a3b8; font-size:13px;">© 2026 Web-Based HRMS. All Rights Reserved.</footer>
    </main>
</div>

{{-- === BULK ENROLLMENT MODAL WITH SEARCH === --}}
<div id="enrollModal" class="modal-overlay">
    <form action="{{ route('admin.training.enroll', $program->training_id) }}" method="POST" class="modal-card">
        @csrf
        <div class="modal-header">
            <h3 style="margin:0; font-size: 20px; color: #0f172a;">Enroll Employees</h3>
            <button type="button" onclick="closeModal('enrollModal')" style="border:none; background:none; font-size:24px; cursor:pointer; color:#94a3b8; transition: 0.2s;">&times;</button>
        </div>
        
        <div class="modal-body">
            @if($program->mode == 'Onsite' && $program->max_participants)
                <div style="background:#eff6ff; color:#1e40af; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:13px; font-weight:600; border: 1px solid #bfdbfe; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-circle-info"></i> Capacity Alert: {{ $program->max_participants - $program->enrollments->count() }} slots remaining.
                </div>
            @endif
            
            <p style="margin-top:0; color:#64748b; font-size:14px; margin-bottom:20px;">Select employees to enroll in this training. Only active employees are shown below.</p>
            
            {{-- SEARCH & FILTER BAR --}}
            <div class="filter-bar">
                <input type="text" id="searchInput" class="search-input" placeholder="Search employee name..." onkeyup="filterList()">
                <select id="deptFilter" class="dept-select" onchange="filterList()">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->department_name }}">{{ $dept->department_name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- SELECT ALL CHECKBOX --}}
            <div style="margin-bottom:15px; padding-left:20px; background: #f8fafc; padding: 10px 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <label style="font-weight:600; font-size:14px; cursor:pointer; color:#0f172a; display:flex; align-items:center; gap:12px; margin: 0;">
                    <input type="checkbox" id="selectAllBtn" onchange="toggleSelectAll(this)" style="transform: scale(1.3); accent-color: #2563eb; cursor: pointer;"> Select All Visible
                </label>
            </div>

            {{-- SCROLLABLE EMPLOYEE LIST --}}
            <div class="employee-list" id="employeeList">
                @if($potentialTrainees->count() > 0)
                    @foreach($potentialTrainees as $emp)
                        <label class="employee-item" data-name="{{ strtolower($emp->user->name) }}" data-dept="{{ $emp->department->department_name ?? '' }}">
                            <input type="checkbox" name="employee_ids[]" value="{{ $emp->employee_id }}" class="emp-checkbox" onchange="updateSelectedCount()">
                            <div class="emp-details">
                                <span class="emp-name">{{ $emp->user->name }}</span>
                                <span class="emp-sub">{{ $emp->department->department_name ?? 'No Dept' }} • {{ $emp->employee_code }}</span>
                            </div>
                        </label>
                    @endforeach
                @else
                    <div style="padding:40px 20px; text-align:center; color:#94a3b8; font-size:14px;">
                        <i class="fa-solid fa-users-slash" style="font-size: 24px; margin-bottom: 10px; color: #cbd5e1; display: block;"></i>
                        All active employees are already enrolled.
                    </div>
                @endif
            </div>
            
            <div style="margin-top:15px; font-size:14px; color:#475569; font-weight:500;">
                Selected: <span id="selectedCount" style="color: #2563eb; font-weight: 700; font-size: 16px;">0</span> employees
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" onclick="closeModal('enrollModal')" style="background: #fff; border: 1px solid #cbd5e1; color: #475569; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; margin-right: 10px;">Cancel</button>
            <button type="submit" id="submitEnrollBtn" {{ $potentialTrainees->count() == 0 ? 'disabled' : '' }} style="background: #2563eb; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">Enroll Selected</button>
        </div>
    </form>
</div>

{{-- UPDATE STATUS MODAL --}}
<div id="updateModal" class="modal-overlay">
    <form id="updateForm" action="" method="POST" class="modal-card" style="width:500px;">
        @csrf
        <div class="modal-header">
            <h3 style="margin:0; font-size: 20px; color: #0f172a;">Update Training Progress</h3>
            <button type="button" onclick="closeModal('updateModal')" style="border:none; background:none; font-size:24px; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <div class="modal-body">
            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom:8px; font-size:13px; font-weight:600; color:#334155;">Completion Status</label>
                <select name="completion_status" id="modalStatus" style="width:100%; padding:12px; border-radius:8px; border:1px solid #cbd5e1; font-family: inherit; font-size: 14px; outline:none; transition: 0.2s;">
                    <option value="enrolled">Enrolled (Ongoing)</option>
                    <option value="completed">Completed (Passed)</option>
                    <option value="failed">Failed / Did Not Attend</option>
                </select>
            </div>
            <div>
                <label style="display:block; margin-bottom:8px; font-size:13px; font-weight:600; color:#334155;">Remarks (Optional)</label>
                <textarea name="remarks" id="modalRemarks" rows="4" placeholder="e.g., Scored 90% on the final test." style="width:100%; padding:12px; border-radius:8px; border:1px solid #cbd5e1; font-family: inherit; font-size: 14px; outline:none; resize:vertical; transition: 0.2s;"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeModal('updateModal')" style="background: #fff; border: 1px solid #cbd5e1; color: #475569; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; margin-right: 10px;">Cancel</button>
            <button type="submit" style="background: #0f172a; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">Save Changes</button>
        </div>
    </form>
</div>

<script>
    // --- SEARCH FILTER LOGIC ---
    function filterList() {
        let search = document.getElementById('searchInput').value.toLowerCase();
        let dept = document.getElementById('deptFilter').value;
        let items = document.querySelectorAll('.employee-item');

        items.forEach(item => {
            let name = item.getAttribute('data-name');
            let itemDept = item.getAttribute('data-dept');
            
            let matchSearch = name.includes(search);
            let matchDept = dept === "" || itemDept === dept;

            if (matchSearch && matchDept) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
        
        // Uncheck 'Select All' if user is filtering
        document.getElementById('selectAllBtn').checked = false;
    }

    function toggleSelectAll(source) {
        let items = document.querySelectorAll('.employee-item');
        items.forEach(item => {
            if (item.style.display !== 'none') {
                let checkbox = item.querySelector('input[type="checkbox"]');
                checkbox.checked = source.checked;
            }
        });
        updateSelectedCount();
    }
    
    function updateSelectedCount() {
        let checkboxes = document.querySelectorAll('.emp-checkbox:checked');
        let countDisplay = document.getElementById('selectedCount');
        let submitBtn = document.getElementById('submitEnrollBtn');
        
        let count = checkboxes.length;
        countDisplay.innerText = count;
        
        // Disable submit if nothing is selected
        if(count === 0) {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.5';
            submitBtn.style.cursor = 'not-allowed';
        } else {
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
        }
        
        // --- CAPACITY ENFORCEMENT (FRONTEND) ---
        @if($program->mode == 'Onsite' && $program->max_participants)
            let maxCapacity = {{ $program->max_participants }};
            let currentlyEnrolled = {{ $program->enrollments->count() }};
            let remainingSlots = maxCapacity - currentlyEnrolled;
            
            if(count > remainingSlots) {
                alert("You cannot select more than " + remainingSlots + " employees because the training capacity will be exceeded.");
                // Uncheck the last selected checkbox
                event.target.checked = false;
                updateSelectedCount(); // Recalculate
            }
        @endif
    }
    
    // Run once on load to disable the submit button initially
    document.addEventListener('DOMContentLoaded', function() {
        updateSelectedCount();
        
        // Add focus effect to dropdown
        const modalStatus = document.getElementById('modalStatus');
        const modalRemarks = document.getElementById('modalRemarks');
        
        [modalStatus, modalRemarks].forEach(el => {
            el.addEventListener('focus', function() { this.style.borderColor = '#2563eb'; this.style.boxShadow = '0 0 0 3px rgba(37,99,235,0.1)'; });
            el.addEventListener('blur', function() { this.style.borderColor = '#cbd5e1'; this.style.boxShadow = 'none'; });
        });
    });

    // --- MODAL CONTROLS ---
    function openEnrollModal() { document.getElementById('enrollModal').classList.add('active'); }
    
    function openUpdateModal(id, status, remarks) {
        let form = document.getElementById('updateForm');
        let url = "{{ route('admin.training.updateStatus', ':id') }}";
        form.action = url.replace(':id', id);
        
        document.getElementById('modalStatus').value = status;
        document.getElementById('modalRemarks').value = (remarks === 'null' || remarks === '') ? '' : remarks;
        
        document.getElementById('updateModal').classList.add('active');
    }

    function closeModal(id) { document.getElementById(id).classList.remove('active'); }
</script>

</body>
</html>