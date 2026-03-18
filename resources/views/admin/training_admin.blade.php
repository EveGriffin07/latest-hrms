<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Training & Learning - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    /* Metric Cards */
    .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .metric-card { background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 20px; transition: transform 0.2s; }
    .metric-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
    .metric-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
    .metric-info h4 { margin: 0 0 5px 0; font-size: 13px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .metric-info p { margin: 0; font-size: 24px; font-weight: 700; color: #0f172a; }

    /* Table & Calendar Containers */
    .section-container { background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.02); margin-bottom: 30px; }
    .section-title { font-size: 18px; font-weight: 600; color: #0f172a; margin-bottom: 0; display: flex; align-items: center; gap: 10px; }

    /* SaaS Table */
    .hr-table { width: 100%; border-collapse: collapse; text-align: left; }
    .hr-table th { background: #f8fafc; padding: 14px 20px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb; }
    .hr-table td { padding: 16px 20px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #1e293b; }
    .hr-table tbody tr:hover { background: #f8fafc; }

    /* Badges & Buttons */
    .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
    .badge-completed { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .badge-active { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
    .badge-upcoming { background: #ffedd5; color: #9a3412; border: 1px solid #fed7aa; }

    .btn-icon { padding: 8px 12px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; text-decoration: none; font-family: inherit; }
    .btn-icon:hover { background: #f1f5f9; color: #0f172a; border-color: #cbd5e1; }
    .btn-icon.delete:hover { background: #fee2e2; color: #dc2626; border-color: #fecaca; }
    .btn-icon:disabled { opacity: 0.5; cursor: not-allowed; }

    /* MODAL STYLES */
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center; }
    .modal-overlay.active { display: flex; }
    .modal-card { background: #fff; width: 750px; max-width: 95%; border-radius: 16px; padding: 35px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); max-height: 90vh; overflow-y: auto; position: relative; animation: slideUp 0.3s ease-out; }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .close-btn { position:absolute; top:25px; right:25px; font-size:24px; cursor:pointer; color:#94a3b8; background:none; border:none; transition: 0.2s; }
    .close-btn:hover { color: #dc2626; }
    .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: #334155; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; margin-bottom: 20px; font-family: inherit; font-size: 14px; outline: none; transition: border-color 0.2s; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
  </style>
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info"><a href="{{ route('admin.profile') }}" style="text-decoration: none; color: inherit; font-weight: 500;"><i class="fa-regular fa-user"></i> &nbsp; {{ Auth::user()->name ?? 'HR Admin' }}</a></div>
  </header>

  <div class="container">
    @include('admin.layout.sidebar')

    <main>
      <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:30px;">
          <div>
              <div class="breadcrumb" style="color: #64748b; font-size: 14px; margin-bottom: 5px;">Home > <span style="color: #0f172a; font-weight: 500;">Training Management</span></div>
              <h2 style="margin:0; font-size:28px; color:#0f172a;">Training & Development</h2>
              <p style="color: #64748b; margin-top: 5px; font-size: 15px;">Manage employee skill development, schedules, and enrollments.</p>
          </div>
          <button onclick="openAddModal()" class="btn btn-primary" style="background: #2563eb; color: #fff; padding: 12px 24px; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 14px;">
            <i class="fa-solid fa-plus"></i> Create Program
          </button>
      </div>

      @if(session('success'))
        <div style="background-color: #dcfce7; color: #166534; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #bbf7d0; display:flex; align-items:center; gap:10px; font-weight:500;">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
      @endif

      @if($errors->any())
        <div style="background-color: #fee2e2; color: #991b1b; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #fecaca;">
            <div style="display:flex; align-items:center; gap:10px; font-weight:600; margin-bottom: 5px;">
                <i class="fa-solid fa-circle-exclamation"></i> Error Saving Training
            </div>
            <ul style="margin: 0; padding-left: 25px; font-size: 13px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
      @endif

      @php
          $totalPrograms = $programs->count();
          $activePrograms = $programs->where('tr_status', 'active')->count();
          $completedPrograms = $programs->where('tr_status', 'completed')->count();
          $thisMonth = $programs->filter(function($p) { 
              return \Carbon\Carbon::parse($p->start_date)->isCurrentMonth(); 
          })->count();
      @endphp

      <div class="metrics-grid">
          <div class="metric-card">
              <div class="metric-icon" style="background: #eff6ff; color: #3b82f6;"><i class="fa-solid fa-book-open"></i></div>
              <div class="metric-info">
                  <h4>Total Programs</h4>
                  <p>{{ $totalPrograms }}</p>
              </div>
          </div>
          <div class="metric-card">
              <div class="metric-icon" style="background: #f0fdf4; color: #22c55e;"><i class="fa-solid fa-spinner"></i></div>
              <div class="metric-info">
                  <h4>Active Now</h4>
                  <p>{{ $activePrograms }}</p>
              </div>
          </div>
          <div class="metric-card">
              <div class="metric-icon" style="background: #fffbeb; color: #f59e0b;"><i class="fa-regular fa-calendar-check"></i></div>
              <div class="metric-info">
                  <h4>This Month</h4>
                  <p>{{ $thisMonth }}</p>
              </div>
          </div>
          <div class="metric-card">
              <div class="metric-icon" style="background: #f8fafc; color: #64748b;"><i class="fa-solid fa-check-double"></i></div>
              <div class="metric-info">
                  <h4>Completed</h4>
                  <p>{{ $completedPrograms }}</p>
              </div>
          </div>
      </div>

      {{-- CALENDAR SECTION --}}
      <div class="section-container">
          <h3 class="section-title" style="margin-bottom: 20px;"><i class="fa-regular fa-calendar-days" style="color: #2563eb;"></i> Training Calendar</h3>
          <div id="calendar" style="padding-top: 10px;"></div>
      </div>
      
      <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
      <script>
        document.addEventListener('DOMContentLoaded', function() {
          var calendarEl = document.getElementById('calendar');
          var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth', 
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listWeek' },
            height: 'auto', contentHeight: 500, 
            events: "{{ route('admin.training.events') }}",
            eventClick: function(info) { if (info.event.url) { window.location.href = info.event.url; info.jsEvent.preventDefault(); } },
            eventDisplay: 'block', displayEventTime: false,
            buttonText: { today: 'Today', month: 'Month', week: 'Agenda' }
          });
          calendar.render();
        });
      </script>
      
      {{-- LIST TABLE WITH FILTERING & PAGINATION --}}
      <div class="section-container" style="padding: 0; overflow: hidden;">
        
        {{-- Controls Header --}}
        <div style="padding: 20px 25px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; background: #f8fafc; flex-wrap: wrap; gap: 15px;">
            <h3 class="section-title"><i class="fa-solid fa-list-ul" style="color: #2563eb;"></i> Program Directory</h3>
            
            <div style="display: flex; gap: 15px;">
                <input type="text" id="tableSearch" placeholder="Search title or trainer..." style="padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-size: 13px; width: 250px;">
                <select id="statusFilter" style="padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-size: 13px;">
                    <option value="">All Statuses</option>
                    <option value="Completed">Completed</option>
                    <option value="Ongoing">Ongoing</option>
                    <option value="Upcoming">Upcoming</option>
                </select>
            </div>
        </div>
        
        <table class="hr-table">
            <thead>
            <tr>
                <th>Title & Trainer</th>
                <th>Department</th>
                <th>Date & Time</th>
                <th>Mode</th>
                <th>Status</th>
                <th style="text-align:right">Action</th>
            </tr>
            </thead>
            <tbody id="programTableBody">
            @forelse($programs as $program)
            <tr class="program-row">
                <td>
                    <div style="font-weight:600; color:#0f172a; font-size: 15px;">{{ $program->training_name }}</div>
                    <div style="font-size: 12px; color:#64748b; margin-top: 3px;"><i class="fa-solid fa-user-tie" style="margin-right: 4px;"></i> {{ $program->provider }}</div>
                </td>
                <td style="font-weight: 500; color: #475569;">{{ $program->department->department_name ?? 'General (All)' }}</td>
                <td>
                    <span style="color: #1e293b; font-weight: 500;">
                        {{ \Carbon\Carbon::parse($program->start_date)->format('d M') }} 
                        @if($program->start_date != $program->end_date)
                            - {{ \Carbon\Carbon::parse($program->end_date)->format('d M Y') }}
                        @endif
                    </span><br>
                    <small style="color:#64748b;">
                        <i class="fa-regular fa-clock"></i> 
                        @if($program->start_time)
                            {{ \Carbon\Carbon::parse($program->start_time)->format('h:i A') }}
                        @else
                            TBD
                        @endif
                    </small>
                </td>
                <td>
                    @if($program->mode == 'Online')
                        <span style="color: #0284c7; font-size: 13px; font-weight: 600;"><i class="fa-solid fa-laptop"></i> Online</span>
                    @else
                        <span style="color: #059669; font-size: 13px; font-weight: 600;"><i class="fa-solid fa-building"></i> Onsite</span>
                    @endif
                </td>
                <td>
                    <span class="status-cell-text" style="display:none;">
                        {{ $program->tr_status == 'completed' ? 'Completed' : ($program->tr_status == 'active' ? 'Ongoing' : 'Upcoming') }}
                    </span>
                    @if($program->tr_status == 'completed')
                        <span class="badge badge-completed">Completed</span>
                    @elseif($program->tr_status == 'active')
                        <span class="badge badge-active">Ongoing</span>
                    @else
                        <span class="badge badge-upcoming">Upcoming</span>
                    @endif
                </td>
                <td style="text-align:right; white-space: nowrap;">
                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                        <a href="{{ route('admin.training.show', $program->training_id) }}" class="btn-icon" title="View & Enroll"><i class="fa-solid fa-eye" style="color: #2563eb;"></i></a>
                        <button onclick="openEditModal({{ json_encode($program) }})" class="btn-icon" title="Edit"><i class="fa-solid fa-pen"></i></button>
                        <form action="{{ route('admin.training.delete', $program->training_id) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Delete this training program?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-icon delete"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr id="emptyRow">
                <td colspan="6" style="text-align: center; padding: 50px 20px; color:#64748b;">
                    <i class="fa-solid fa-folder-open" style="font-size: 32px; color: #cbd5e1; margin-bottom: 15px;"></i><br>
                    <span style="font-size: 15px; font-weight: 500; color: #334155;">No training programs found.</span><br>
                    Click "Create Program" to schedule a new learning session.
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>

        {{-- Pagination Footer --}}
        @if($programs->count() > 0)
        <div style="padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border-top: 1px solid #e2e8f0;">
            <span id="pageInfo" style="font-size: 13px; color: #64748b; font-weight: 500;">Showing entries</span>
            <div style="display: flex; gap: 8px;" id="paginationControls">
                <button id="prevPage" class="btn-icon"><i class="fa-solid fa-chevron-left"></i> Prev</button>
                <button id="nextPage" class="btn-icon">Next <i class="fa-solid fa-chevron-right"></i></button>
            </div>
        </div>
        @endif
      </div>
      
      <footer style="margin-top:40px; text-align:center; color:#94a3b8; font-size:13px;">© 2026 Web-Based HRMS. All Rights Reserved.</footer>
    </main>
  </div>

  {{-- ADD/EDIT MODAL --}}
  <div id="trainingModal" class="modal-overlay">
    <div class="modal-card">
        <button class="close-btn" onclick="closeModal()">&times;</button>
        <div style="border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 25px;">
            <h3 id="modalTitle" style="margin:0; font-size:22px; color: #0f172a; font-weight: 700;">Add Training Program</h3>
        </div>
        
        <form id="trainingForm" action="{{ route('admin.training.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_method" id="methodField" value="POST">

            <div class="form-group">
                <label>Training Title <span style="color:#dc2626">*</span></label>
                <input type="text" id="trainingTitle" name="trainingTitle" maxlength="255" required placeholder="e.g., Leadership & Communication Workshop">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Trainer Name <span style="color:#dc2626">*</span></label>
                    <input type="text" id="trainerName" name="trainerName" maxlength="255" required placeholder="e.g., John Doe">
                </div>
                <div class="form-group">
                    <label>Department Target</label>
                    <select id="department" name="department">
                        <option value="" selected>General (All Departments)</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->department_name }}">{{ $dept->department_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Trainer Company <span style="color:#94a3b8; font-weight:normal;">(Optional)</span></label>
                    <input type="text" id="trainerCompany" name="trainerCompany" maxlength="255" placeholder="e.g., SkillPath Solutions">
                </div>
                <div class="form-group">
                    <label>Trainer Email <span style="color:#94a3b8; font-weight:normal;">(Optional)</span></label>
                    <input type="email" id="trainerEmail" name="trainerEmail" maxlength="255" placeholder="e.g., contact@skillpath.com">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Start Date <span style="color:#dc2626">*</span></label>
                    <input type="date" id="startDate" name="startDate" required>
                </div>
                <div class="form-group">
                    <label>End Date <span style="color:#dc2626">*</span></label>
                    <input type="date" id="endDate" name="endDate" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Start Time <span style="color:#dc2626">*</span></label>
                    <input type="time" id="startTime" name="startTime" required>
                </div>
                <div class="form-group">
                    <label>Mode <span style="color:#dc2626">*</span></label>
                    <select id="mode" name="mode" required>
                        <option value="Onsite">Onsite (In-Person)</option>
                        <option value="Online">Online (Virtual)</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Location / Meeting Link <span style="color:#dc2626">*</span></label>
                    <input type="text" id="location" name="location" maxlength="255" required placeholder="e.g., Room 202 or Zoom Link">
                </div>
                <div class="form-group" id="capacityGroup">
                    <label>Max Participants <span style="color:#dc2626">*</span></label>
                    <input type="number" id="maxParticipants" name="maxParticipants" min="1" step="1" placeholder="e.g., 50">
                </div>
            </div>

            <div class="form-group">
                <label>Program Description</label>
                <textarea id="description" name="description" rows="4" placeholder="Detail the objectives and agenda for this training..."></textarea>
            </div>

            <div style="text-align:right; margin-top:30px; border-top: 1px solid #e2e8f0; padding-top: 20px;">
                <button type="button" onclick="closeModal()" style="background: #fff; border: 1px solid #cbd5e1; color: #475569; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; margin-right: 10px;">Cancel</button>
                <button type="submit" id="submitBtn" style="background: #2563eb; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 6px rgba(37,99,235,0.2);">Save Training</button>
            </div>
        </form>
    </div>
  </div>

  <script>
    // ==========================================
    // VANILLA JS PAGINATION & FILTERING SYSTEM
    // ==========================================
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('tableSearch');
        const statusFilter = document.getElementById('statusFilter');
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');
        const pageInfo = document.getElementById('pageInfo');
        
        if(!searchInput || !prevBtn) return;

        const tableRows = Array.from(document.querySelectorAll('.program-row'));
        let currentPage = 1;
        const rowsPerPage = 5; 
        let filteredRows = [...tableRows];

        function filterAndPaginate() {
            const searchTerm = searchInput.value.toLowerCase();
            const statusTerm = statusFilter.value.toLowerCase();

            filteredRows = tableRows.filter(row => {
                const text = row.innerText.toLowerCase();
                const statusText = row.querySelector('.status-cell-text').innerText.toLowerCase().trim();

                const matchesSearch = text.includes(searchTerm);
                const matchesStatus = statusTerm === "" || statusText === statusTerm;

                return matchesSearch && matchesStatus;
            });

            currentPage = 1;
            renderTable();
        }

        function renderTable() {
            tableRows.forEach(row => row.style.display = 'none');

            const totalRows = filteredRows.length;
            const totalPages = Math.ceil(totalRows / rowsPerPage) || 1;

            if (currentPage < 1) currentPage = 1;
            if (currentPage > totalPages) currentPage = totalPages;

            const startIdx = (currentPage - 1) * rowsPerPage;
            const endIdx = startIdx + rowsPerPage;

            filteredRows.slice(startIdx, endIdx).forEach(row => {
                row.style.display = 'table-row';
            });

            const showingStart = totalRows === 0 ? 0 : startIdx + 1;
            const showingEnd = Math.min(endIdx, totalRows);
            pageInfo.innerText = `Showing ${showingStart} to ${showingEnd} of ${totalRows} entries`;

            prevBtn.disabled = currentPage === 1;
            nextBtn.disabled = currentPage === totalPages || totalRows === 0;
        }

        searchInput.addEventListener('input', filterAndPaginate);
        statusFilter.addEventListener('change', filterAndPaginate);

        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) { currentPage--; renderTable(); }
        });
        
        nextBtn.addEventListener('click', () => {
            const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
            if (currentPage < totalPages) { currentPage++; renderTable(); }
        });

        renderTable();
    });

    // ==========================================
    // MODAL LOGIC & FORM VALIDATION
    // ==========================================
    const modal = document.getElementById('trainingModal');
    const form = document.getElementById('trainingForm');
    const modalTitle = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');
    const methodField = document.getElementById('methodField');
    const storeRoute = "{{ route('admin.training.store') }}";
    const updateBaseUrl = "{{ url('/admin/training/update') }}"; 

    const modeSelect = document.getElementById('mode');
    const capacityGroup = document.getElementById('capacityGroup');
    const maxParticipantsInput = document.getElementById('maxParticipants');
    
    // UI Validation: Prevent numbers in Trainer Name
    const trainerNameInput = document.getElementById('trainerName');
    trainerNameInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^a-zA-Z\s\.\,\-]/g, '');
    });
    
    // UI Validation: Date logic constraints
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');

    startDateInput.addEventListener('change', function() {
        endDateInput.min = this.value;
        if(endDateInput.value && endDateInput.value < this.value) {
            endDateInput.value = '';
        }
    });

    modeSelect.addEventListener('change', function() {
        if(this.value === 'Online') {
            capacityGroup.style.display = 'none';
            maxParticipantsInput.required = false;
            maxParticipantsInput.value = ''; 
        } else {
            capacityGroup.style.display = 'block';
            maxParticipantsInput.required = true;
        }
    });

    function openAddModal() {
        form.reset();
        form.action = storeRoute;
        methodField.value = "POST";
        modalTitle.innerText = "Add Training Program";
        submitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Training';
        
        // NEW: Lock Start Date to Today or future for NEW programs
        const today = new Date().toISOString().split('T')[0];
        startDateInput.setAttribute('min', today);
        endDateInput.min = '';

        modeSelect.value = "Onsite";
        modeSelect.dispatchEvent(new Event('change'));
        
        modal.classList.add('active');
    }

    function openEditModal(data) {
        document.getElementById('trainingTitle').value = data.training_name;
        document.getElementById('trainerName').value = data.provider;
        document.getElementById('trainerCompany').value = data.trainer_company || "";
        document.getElementById('trainerEmail').value = data.trainer_email || "";

        if(data.department) {
            document.getElementById('department').value = data.department.department_name;
        } else {
            document.getElementById('department').value = "";
        }
        
        // NEW: Remove the min date restriction when editing historical records
        startDateInput.removeAttribute('min');
        
        // Fill dates and set constraints immediately
        startDateInput.value = data.start_date;
        endDateInput.value = data.end_date;
        endDateInput.min = data.start_date; // Lock End Date based on existing Start Date
        
        if(data.start_time) {
            document.getElementById('startTime').value = data.start_time.substring(0, 5); 
        } else {
            document.getElementById('startTime').value = "";
        }
        document.getElementById('maxParticipants').value = data.max_participants || "";
        
        modeSelect.value = data.mode;
        modeSelect.dispatchEvent(new Event('change'));

        document.getElementById('location').value = data.location;
        document.getElementById('description').value = data.tr_description;

        form.action = updateBaseUrl + "/" + data.training_id;
        methodField.value = "POST"; 
        
        modalTitle.innerText = "Edit Training Program";
        submitBtn.innerHTML = '<i class="fa-solid fa-check"></i> Update Changes';
        modal.classList.add('active');
    }

    function closeModal() {
        modal.classList.remove('active');
    }
    
    window.onclick = function(event) {
        if (event.target == modal) closeModal();
    }
  </script>
</body>
</html>