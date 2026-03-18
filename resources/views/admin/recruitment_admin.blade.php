<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recruitment Management - HRMS</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
      /* Premium Table Styling */
      .table-container { background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); overflow: hidden; border: 1px solid #e5e7eb; margin-bottom: 40px; }
      .hr-table { width: 100%; border-collapse: collapse; text-align: left; }
      .hr-table th { background: #f8fafc; padding: 16px 20px; font-size: 13px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb; }
      .hr-table td { padding: 16px 20px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #1e293b; }
      .hr-table tbody tr:hover { background: #f8fafc; }

      /* Badges */
      .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
      .badge-open { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
      .badge-draft { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
      .badge-closed { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
      .badge-type { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

      /* Buttons */
      .btn-primary { background: #2563eb; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; font-size: 14px; }
      .btn-primary:hover { background: #1d4ed8; }
      
      .action-buttons { display: flex; gap: 8px; align-items: center; }
      .btn-icon { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; text-decoration: none; }
      .btn-icon:hover { background: #f1f5f9; color: #0f172a; }
      .btn-icon.delete:hover { background: #fee2e2; color: #dc2626; border-color: #fecaca; }

      .section-title { font-size: 20px; font-weight: 600; color: #0f172a; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }

      /* MODAL STYLES */
      .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center; }
      .modal-overlay.active { display: flex; }
      .modal-card { background: #fff; width: 750px; max-width: 95%; border-radius: 16px; padding: 35px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); max-height: 90vh; overflow-y: auto; position: relative; animation: slideUp 0.3s ease-out; }
      @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
      .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; }
      .modal-title { font-size: 22px; font-weight: 700; color: #0f172a; }
      .close-btn { background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 20px; color: #64748b; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; }
      .close-btn:hover { background: #fee2e2; color: #dc2626; }
      .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: #334155; }
      .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; margin-bottom: 20px; font-family: inherit; font-size: 14px; transition: border-color 0.2s; }
      .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
      .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
  </style>
</head>
<body>

  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info" style="display: flex; align-items: center; gap: 10px;">
      <a href="{{ route('admin.profile') }}" style="text-decoration: none; color: inherit; font-weight: 500;">
        <i class="fa-regular fa-circle-user"></i> &nbsp; {{ Auth::user()->name ?? 'HR Admin' }}
      </a>
    </div>
  </header>

  <div class="container">

    @include('admin.layout.sidebar')

    <main>
      <div class="breadcrumb" style="color: #64748b; font-size: 14px; margin-bottom: 10px;">Home > Dashboard > <span style="color: #0f172a; font-weight: 500;">Recruitment</span></div>
      <h2 style="font-size: 28px; color: #0f172a; margin-bottom: 5px;">Recruitment Management</h2>
      <p class="subtitle" style="color: #64748b; margin-bottom: 30px;">Manage job postings, approve hiring requests, and track applicant status.</p>

      @if(session('success'))
        <div style="background: #dcfce7; color: #166534; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #bbf7d0; display:flex; align-items:center; gap:10px; font-weight:500; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
          <i class="fa-solid fa-circle-check" style="font-size: 18px;"></i> {{ session('success') }}
        </div>
      @endif

      @if($errors->any())
        <div style="background-color: #fee2e2; color: #991b1b; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #f87171; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li style="font-size: 14px; margin-bottom: 4px;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
      @endif

      {{-- === PENDING HIRING REQUESTS === --}}
      @if(isset($requisitions) && $requisitions->count() > 0)
      <div style="margin-bottom: 50px;">
          <h3 class="section-title">
              <i class="fa-solid fa-clipboard-user" style="color: #f59e0b;"></i> Pending Hiring Requests
          </h3>
          <p style="font-size: 14px; color: #64748b; margin-top: -10px; margin-bottom: 20px;">Managers have requested the following positions. Approve to create a Draft Job Post.</p>
          
          <div class="table-container" style="border-top: 4px solid #f59e0b;">
              <table class="hr-table">
                  <thead style="background: #fffbeb;">
                      <tr>
                          <th>Requested By</th>
                          <th>Department</th>
                          <th>Job Title</th>
                          <th>Type / Qty</th>
                          <th style="width: 35%;">Justification</th>
                          <th style="text-align: right;">Actions</th>
                      </tr>
                  </thead>
                  <tbody>
                      @foreach($requisitions as $req)
                      <tr>
                          <td>
                              <div style="font-weight: 600; color: #0f172a;">{{ $req->requester->user->name ?? 'Unknown' }}</div>
                              <div style="font-size: 12px; color: #64748b;">Manager</div>
                          </td>
                          <td style="font-weight: 500;">{{ $req->department->department_name ?? 'General' }}</td>
                          <td style="font-weight: 600; color: #2563eb;">{{ $req->job_title }}</td>
                          <td>
                              <span class="badge badge-type">{{ $req->employment_type }}</span><br>
                              <span style="font-size: 12px; color: #475569; font-weight: 600; display: inline-block; margin-top: 6px;">Headcount: {{ $req->headcount }}</span>
                          </td>
                          <td style="font-size: 13px; color: #4b5563; line-height: 1.6; font-style: italic;">
                              "{{ $req->justification }}"
                          </td>
                          <td style="text-align: right;">
                              <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                  <form action="{{ route('admin.recruitment.approveRequisition', $req->requisition_id) }}" method="POST">
                                      @csrf
                                      <button type="submit" style="background: #10b981; color: #fff; border: none; padding: 8px 12px; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer; transition: 0.2s;" onclick="return confirm('Approve this request? A Draft job post will be created automatically.');">
                                          <i class="fa-solid fa-check"></i> Approve
                                      </button>
                                  </form>
                                  <form action="{{ route('admin.recruitment.rejectRequisition', $req->requisition_id) }}" method="POST">
                                      @csrf
                                      <button type="submit" style="background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; padding: 8px 12px; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer; transition: 0.2s;" onclick="return confirm('Are you sure you want to reject this hiring request?');">
                                          <i class="fa-solid fa-xmark"></i> Reject
                                      </button>
                                  </form>
                              </div>
                          </td>
                      </tr>
                      @endforeach
                  </tbody>
              </table>
          </div>
      </div>
      @endif

      {{-- === EXISTING JOB POSTS === --}}
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
          <h3 class="section-title" style="margin: 0;">
              <i class="fa-solid fa-briefcase" style="color: #2563eb;"></i> Active & Draft Job Postings
          </h3>
          <button onclick="openAddModal()" class="btn-primary">
            <i class="fa-solid fa-plus"></i> Post New Job Manually
          </button>
      </div>

      <div class="table-container">
        <table class="hr-table">
          <thead>
            <tr>
              <th style="width: 100px;">Job ID</th>
              <th>Job Title</th>
              <th>Department</th>
              <th>Type</th>
              <th>Closing Date</th>
              <th>Status</th>
              <th style="text-align: center;">Actions</th>
            </tr>
          </thead>
          <tbody>
            
            @forelse($jobPosts as $job)
            <tr>
              <td>
                <span style="background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-weight:700; color:#475569; font-size:12px; border: 1px solid #e2e8f0;">
                    #{{ $job->job_code }}
                </span>
              </td>

              <td>
                <div style="display: flex; flex-direction: column;">
                    <span style="font-weight: 600; color: #0f172a; font-size: 15px;">{{ $job->job_title }}</span>
                    <span style="font-size: 12px; color: #64748b; margin-top: 2px;"><i class="fa-solid fa-location-dot"></i> {{ $job->location }}</span>
                </div>
              </td>
              <td style="font-weight: 500;">{{ $job->department }}</td>
              <td>
                <span class="badge badge-type">{{ $job->job_type }}</span>
              </td>
              <td>
                <span style="font-weight: 500; color: {{ \Carbon\Carbon::parse($job->closing_date)->isPast() ? '#dc2626' : '#0f172a' }};">
                    {{ \Carbon\Carbon::parse($job->closing_date)->format('d M Y') }}
                </span>
              </td>
              <td>
                @if($job->job_status === 'Open')
                    <span class="badge badge-open">Open</span>
                @elseif($job->job_status === 'Draft')
                    <span class="badge badge-draft">Draft</span>
                @else
                    <span class="badge badge-closed">{{ $job->job_status }}</span>
                @endif
              </td>
              <td style="white-space: nowrap;">
                <div class="action-buttons" style="justify-content: center;">
                    <a href="{{ route('admin.applicants.index', ['job_id' => $job->job_id]) }}" class="btn-icon" title="View Applicants">
                        <i class="fa-solid fa-users" style="color: #2563eb;"></i>
                    </a>

                    <button type="button" class="btn-icon" title="Reuse / Duplicate" onclick="duplicateJob({{ json_encode($job) }})">
                        <i class="fa-regular fa-copy"></i>
                    </button>

                    <button type="button" class="btn-icon" title="Edit Job" onclick="openEditModal({{ json_encode($job) }})">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    
                    <form action="{{ route('admin.recruitment.destroy', $job->job_id) }}" method="POST" style="margin: 0;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-icon delete" title="Delete Job" onclick="return confirm('Are you sure you want to delete this job post?');">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </div>
              </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center; padding: 50px 20px; color: #64748b;">
                    <i class="fa-solid fa-folder-open" style="font-size: 32px; margin-bottom: 15px; color: #cbd5e1;"></i><br>
                    <span style="font-size: 15px; font-weight: 500; color: #334155;">No job posts found.</span><br>
                    Click "Post New Job Manually" to create your first posting.
                </td>
            </tr>
            @endforelse

          </tbody>
        </table>
      </div>

      <footer style="margin-top: 40px; text-align: center; color: #94a3b8; font-size: 13px;">© 2026 Web-Based HRMS. All Rights Reserved.</footer>
    </main>
  </div>

  {{-- === ADD/EDIT JOB MODAL === --}}
  <div id="jobModal" class="modal-overlay">
      <div class="modal-card">
          <div class="modal-header">
              <span class="modal-title" id="modalTitle">Post New Job Manually</span>
              <button type="button" class="close-btn" onclick="closeModal()">&times;</button>
          </div>
          
          <form id="jobForm" action="{{ route('admin.recruitment.store') }}" method="POST">
              @csrf
              
              <div class="form-group">
                  <label>Job Title <span style="color:#dc2626">*</span></label>
                  <input type="text" id="job_title" name="job_title" placeholder="e.g. Senior Software Engineer" required>
              </div>

              <div class="form-row">
                  <div class="form-group">
                      <label>Department <span style="color:#dc2626">*</span></label>
                      <select id="department" name="department" required>
                          <option value="" disabled selected>Select Department</option>
                          @foreach($departments as $dept)
                              <option value="{{ $dept->department_name }}">{{ $dept->department_name }}</option>
                          @endforeach
                      </select>
                  </div>
                  <div class="form-group">
                      <label>Employment Type <span style="color:#dc2626">*</span></label>
                      <select id="job_type" name="job_type" required>
                          <option value="Full-time">Full-time</option>
                          <option value="Part-time">Part-time</option>
                          <option value="Contract">Contract</option>
                          <option value="Internship">Internship</option>
                      </select>
                  </div>
              </div>

              <div class="form-row">
                  <div class="form-group">
                      <label>Location <span style="color:#dc2626">*</span></label>
                      <input type="text" id="location" name="location" placeholder="e.g. Kuala Lumpur, On-site" required>
                  </div>
                  <div class="form-group">
                      <label>Closing Date <span style="color:#dc2626">*</span></label>
                      <input type="date" id="closing_date" name="closing_date" required>
                  </div>
              </div>

              <div class="form-row">
                  <div class="form-group">
                      <label>Salary</label>
                      <input type="text" id="salary_range" name="salary_range" placeholder="e.g. 3000">
                  </div>
                  <div class="form-group" id="statusGroup" style="display:none;">
                      <label>Visibility Status <span style="color:#dc2626">*</span></label>
                      <select id="job_status" name="job_status">
                          <option value="Open">Open (Public)</option>
                          <option value="Draft">Draft (Hidden)</option>
                          <option value="Closed">Closed</option>
                      </select>
                  </div>
              </div>

              <div class="form-group">
                  <label>Job Description <span style="color:#dc2626">*</span></label>
                  <textarea id="job_description" name="job_description" rows="4" placeholder="Enter brief description of the role..." required></textarea>
              </div>

              <div class="form-group">
                  <label>Requirements <span style="color:#dc2626">*</span></label>
                  <textarea id="requirements" name="requirements" rows="4" placeholder="List the technical skills, experience required..." required></textarea>
              </div>

              <div class="form-group" style="background: #f8fafc; padding: 15px 20px; border-radius: 8px; border: 1px solid #e2e8f0; display: flex; align-items: flex-start; gap: 12px;">
                  <input type="checkbox" id="is_manager" name="is_manager" value="1" style="width: 18px; height: 18px; margin-top: 2px; cursor: pointer; accent-color: #2563eb;">
                  <label for="is_manager" style="margin: 0; cursor: pointer; color: #0f172a; font-size: 14px;">
                      <i class="fa-solid fa-user-tie" style="color: #2563eb; margin-right: 5px;"></i> 
                      <strong>Managerial Role</strong> <br>
                      <span style="color: #64748b; font-weight: 400; font-size: 13px; display: inline-block; margin-top: 4px;">Check this box if the person hired will be responsible for supervising other employees or conducting performance appraisals.</span>
                  </label>
              </div>

              <div style="text-align:right; margin-top:30px; padding-top: 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px;">
                  <button type="button" onclick="closeModal()" style="background: #fff; color: #475569; border: 1px solid #cbd5e1; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">Cancel</button>
                  <button type="submit" id="submitBtn" class="btn-primary">Post Job</button>
              </div>
          </form>
      </div>
  </div>

  <script>
    const modal = document.getElementById('jobModal');
    const form = document.getElementById('jobForm');
    const modalTitle = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');
    const statusGroup = document.getElementById('statusGroup');
    const storeRoute = "{{ route('admin.recruitment.store') }}";
    const updateBaseUrl = "{{ url('/admin/recruitment/update') }}"; 

    function openAddModal() {
        form.reset();
        form.action = storeRoute;
        modalTitle.innerText = "Post New Job Manually";
        submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Post Job';
        statusGroup.style.display = 'none'; 
        document.getElementById('is_manager').checked = false;
        modal.classList.add('active');
    }

    function openEditModal(data) {
        document.getElementById('job_title').value = data.job_title;
        document.getElementById('department').value = data.department;
        document.getElementById('job_type').value = data.job_type;
        document.getElementById('location').value = data.location;
        
        if(data.closing_date) {
            document.getElementById('closing_date').value = data.closing_date.split('T')[0];
        }
        
        document.getElementById('salary_range').value = data.salary_range || '';
        document.getElementById('job_description').value = data.job_description || '';
        document.getElementById('requirements').value = data.requirements || '';
        document.getElementById('job_status').value = data.job_status;
        
        document.getElementById('is_manager').checked = data.is_manager == 1;
        statusGroup.style.display = 'block'; 
        form.action = updateBaseUrl + "/" + data.job_id;
        
        modalTitle.innerText = "Edit Job Post";
        submitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Update Job';
        modal.classList.add('active');
    }

    function duplicateJob(data) {
        document.getElementById('job_title').value = data.job_title + " (Copy)";
        document.getElementById('department').value = data.department;
        document.getElementById('job_type').value = data.job_type;
        document.getElementById('location').value = data.location;
        document.getElementById('salary_range').value = data.salary_range || '';
        document.getElementById('job_description').value = data.job_description || '';
        document.getElementById('requirements').value = data.requirements || '';
        
        document.getElementById('closing_date').value = ''; 
        statusGroup.style.display = 'none'; 
        document.getElementById('is_manager').checked = false;
        
        form.action = storeRoute; 
        modalTitle.innerText = "Duplicate Job Post";
        submitBtn.innerHTML = '<i class="fa-solid fa-clone"></i> Create Duplicate';
        modal.classList.add('active');
    }

    function closeModal() {
        modal.classList.remove('active');
    }
    
    window.onclick = function(event) {
        if (event.target == modal) {
            closeModal();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
      const groups = document.querySelectorAll('.sidebar-group');
      groups.forEach(group => {
        const toggle = group.querySelector('.sidebar-toggle');
        if (!toggle) return;
        toggle.addEventListener('click', function (e) {
          e.preventDefault();
          groups.forEach(g => { if (g !== group) g.classList.remove('open'); });
          group.classList.toggle('open');
        });
      });
    });

    // 1. Instantly block invalid characters in text inputs
    document.getElementById('job_title').addEventListener('input', function() {
        // Allows alphanumeric, spaces, hyphens, dots, commas, &, +, and /
        this.value = this.value.replace(/[^a-zA-Z0-9\s\-\.,&+\/]/g, '');
    });

    document.getElementById('location').addEventListener('input', function() {
        this.value = this.value.replace(/[^a-zA-Z0-9\s\-\.,&]/g, '');
    });

    document.getElementById('salary_range').addEventListener('input', function() {
        // ONLY allows Numbers (0-9), spaces, hyphens, dots, and commas. 
        // Alphabets are completely blocked!
        this.value = this.value.replace(/[^0-9\s\-\.,]/g, '');
    });

    // 2. Block past dates in the HTML Calendar picker
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date().toISOString().split('T')[0];
        
        // Sets the minimum selectable date on the calendar to TODAY
        document.getElementById('closing_date').setAttribute('min', today);
    });
  </script>

</body>
</html>