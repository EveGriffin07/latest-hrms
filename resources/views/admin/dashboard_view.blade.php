<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Announcements - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    /* MODAL STYLES */
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center; }
    .modal-overlay.active { display: flex; }
    .modal-card { background: #fff; width: 600px; max-width: 90%; border-radius: 16px; padding: 32px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); max-height: 90vh; overflow-y: auto; position: relative; animation: slideUp 0.3s ease-out; }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .modal-title { font-size: 20px; font-weight: 700; color: #0f172a; }
    .close-btn { background: none; border: none; font-size: 24px; color: #64748b; cursor: pointer; }
    
    .badge { padding: 4px 10px; border-radius: 99px; font-size: 11px; font-weight: 700; }
    .badge-critical { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
    .badge-important { background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; }
    .badge-normal { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

    .btn-icon { padding: 6px 10px; border-radius: 6px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer; display: inline-block; transition: all 0.2s; }
    .btn-icon:hover { background: #f1f5f9; color: #0f172a; border-color: #cbd5e1; transform: translateY(-1px); }
    
    .view-label { font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; margin-bottom: 4px; }
    .view-value { font-size: 15px; color: #0f172a; margin-bottom: 16px; font-weight: 500; }
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

    <main>
      <div class="page-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
          <div>
              <div class="breadcrumb">Home > Dashboard > Announcements</div>
              <h2 style="margin:0; font-size:24px; color:#0f172a;">Manage Announcements</h2>
          </div>
          <button onclick="openAddModal()" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Post Announcement
          </button>
      </div>

      @if(session('success'))
        <div style="background:#dcfce7; color:#166534; padding:12px; border-radius:8px; margin-bottom:20px; border:1px solid #bbf7d0;">
            <i class="fa-solid fa-check-circle"></i> {{ session('success') }}
        </div>
      @endif

      <div class="table-container">
        <table class="hr-table">
          <thead>
            <tr>
              <th style="width: 100px;">ID</th> <th>Title</th>
              <th>Date</th>
              <th>Priority</th>
              <th>Audience</th>
              <th style="text-align:right;">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($announcements as $announce)
            <tr>
              <td>
                <span style="font-weight:700; color:#64748b; font-size:12px;">
                    {{ $announce->announcement_code }} </span>
              </td>
              <td>
                <a href="#" onclick="openViewModal({{ json_encode($announce) }})" class="link-title" style="font-weight:600; color:#2563eb; text-decoration:none;">
                  {{ $announce->title }}
                </a>
              </td>
              <td>{{ $announce->publish_at ? $announce->publish_at->format('Y-m-d') : $announce->created_at->format('Y-m-d') }}</td>
              <td>
                @if($announce->priority == 'Critical')
                    <span class="badge badge-critical">Critical</span>
                @elseif($announce->priority == 'Important')
                    <span class="badge badge-important">Important</span>
                @else
                    <span class="badge badge-normal">Normal</span>
                @endif
              </td>
              <td>{{ $announce->audience_type }}</td>
              
              <td style="text-align:right;">
                  <button type="button" class="btn-icon" title="View Details" onclick="openViewModal({{ json_encode($announce) }})"><i class="fa-solid fa-eye"></i></button>
                  <button type="button" class="btn-icon" title="Reuse / Duplicate" onclick="duplicateAnnouncement({{ json_encode($announce) }})"><i class="fa-solid fa-copy"></i></button>
                  <button type="button" class="btn-icon" title="Edit" onclick="openEditModal({{ json_encode($announce) }})"><i class="fa-solid fa-pen"></i></button>
                  <form action="{{ route('admin.announcements.destroy', $announce->announcement_id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this announcement?')">
                      @csrf @method('DELETE')
                      <button type="submit" class="btn-icon" style="color:#dc2626;" title="Delete"><i class="fa-solid fa-trash"></i></button>
                  </form>
              </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center; padding: 30px; color: #94a3b8;">No announcements found.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <footer>© 2025 Web-Based HRMS. All Rights Reserved.</footer>
    </main>
  </div>

  <div id="announcementModal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <span class="modal-title" id="modalTitle">Post New Announcement</span>
            <button class="close-btn" onclick="closeModal('announcementModal')">&times;</button>
        </div>
        <form id="announcementForm" action="{{ route('admin.announcements.store') }}" method="POST">
          @csrf <input type="hidden" name="_method" id="methodField" value="POST">
          <div class="form-group">
            <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px;">Title <span style="color:red">*</span></label>
            <input type="text" id="title" name="title" required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px;">
          </div>
          <div class="form-group" style="margin-top:16px;">
            <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px;">Message <span style="color:red">*</span></label>
            <textarea id="message" name="message" rows="4" required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px; font-family:inherit;"></textarea>
          </div>
          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-top:16px;">
              <div>
                <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px;">Priority <span style="color:red">*</span></label>
                <select id="priority" name="priority" required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px;">
                  <option value="Normal">Normal</option><option value="Important">Important</option><option value="Critical">Critical</option>
                </select>
              </div>
              <div>
                <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px;">Audience <span style="color:red">*</span></label>
                <select id="audience" name="audience" required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px;">
                  <option value="All Employees">All Employees</option><option value="Admins Only">Admins Only</option><option value="Specific Department">Specific Department</option>
                </select>
              </div>
          </div>
          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-top:16px;">
              <div>
                <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px;">Department</label>
                <select id="department" name="department" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px;">
                  <option value="" selected>All Departments</option>
                  <option value="IT">IT</option><option value="HR">HR</option><option value="Finance">Finance</option>
                </select>
              </div>
              <div>
                <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px;">Expiry</label>
                <input type="date" id="expires" name="expires" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px;">
              </div>
          </div>
          <div class="form-group" style="margin-top:16px;">
            <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px;">Notes</label>
            <textarea id="remarks" name="remarks" rows="2" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px; font-family:inherit;"></textarea>
          </div>
          <div class="form-actions" style="margin-top:24px; text-align:right; display:flex; gap:10px; justify-content:flex-end;">
            <button type="button" onclick="closeModal('announcementModal')" class="btn btn-secondary">Cancel</button>
            <button type="submit" id="submitBtn" class="btn btn-primary">Publish</button>
          </div>
        </form>
    </div>
  </div>

  <div id="viewModal" class="modal-overlay">
    <div class="modal-card" style="width: 500px;">
        <div class="modal-header">
            <span class="modal-title" style="font-size: 18px;">Announcement Details</span>
            <button class="close-btn" onclick="closeModal('viewModal')">&times;</button>
        </div>
        <div style="margin-bottom: 20px;">
            <h3 id="viewTitle" style="margin: 0 0 10px 0; color: #0f172a; font-size: 18px;"></h3>
            <div style="display:flex; gap: 10px; margin-bottom: 20px;">
                <span id="viewPriorityBadge"></span>
                <span id="viewDate" style="font-size:13px; color:#64748b; padding-top:2px;"></span>
            </div>
            <div class="view-label">Message</div>
            <p id="viewContent" style="color:#334155; line-height:1.6; white-space: pre-line; background:#f8fafc; padding:12px; border-radius:8px; border:1px solid #e2e8f0; font-size:14px;"></p>
        </div>
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
            <div><div class="view-label">Audience</div><div id="viewAudience" class="view-value"></div></div>
            <div><div class="view-label">Department</div><div id="viewDepartment" class="view-value"></div></div>
        </div>
        <div id="viewRemarksBox" style="background:#fffbeb; border:1px solid #fcd34d; padding:12px; border-radius:8px; display:none; margin-top:10px;">
            <strong style="font-size:12px; color:#92400e; display:block; margin-bottom:4px;">Internal Notes:</strong>
            <p id="viewRemarks" style="font-size:13px; color:#b45309; margin:0;"></p>
        </div>
        <div style="margin-top: 24px; text-align: right;">
            <button onclick="closeModal('viewModal')" class="btn btn-secondary">Close</button>
        </div>
    </div>
  </div>

  <script>
    const form = document.getElementById('announcementForm');
    const title = document.getElementById('modalTitle');
    const btn = document.getElementById('submitBtn');
    const methodField = document.getElementById('methodField');
    const storeRoute = "{{ route('admin.announcements.store') }}";
    const updateBaseUrl = "{{ url('/admin/dashboard/announcement/update') }}";

    function openAddModal() {
        form.reset(); form.action = storeRoute; methodField.value = "POST"; 
        title.innerText = "Post New Announcement"; btn.innerText = "Publish";
        document.getElementById('announcementModal').classList.add('active');
    }

    function openEditModal(data) {
        fillForm(data);
        form.action = updateBaseUrl + "/" + data.announcement_id;
        methodField.value = "PUT"; 
        title.innerText = "Edit Announcement"; btn.innerText = "Update Changes";
        document.getElementById('announcementModal').classList.add('active');
    }

    function duplicateAnnouncement(data) {
        fillForm(data);
        form.action = storeRoute; methodField.value = "POST"; 
        title.innerText = "Reuse Announcement"; btn.innerText = "Publish Copy";
        document.getElementById('expires').value = ""; 
        document.getElementById('announcementModal').classList.add('active');
    }

    function fillForm(data) {
        document.getElementById('title').value = data.title;
        document.getElementById('message').value = data.content; 
        document.getElementById('priority').value = data.priority;
        document.getElementById('audience').value = data.audience_type;
        document.getElementById('department').value = data.department;
        document.getElementById('expires').value = data.expires_at ? data.expires_at.split('T')[0] : '';
        document.getElementById('remarks').value = data.remarks || "";
    }

    function openViewModal(data) {
        document.getElementById('viewTitle').innerText = data.title;
        document.getElementById('viewContent').innerText = data.content;
        document.getElementById('viewDate').innerText = data.created_at.split('T')[0];
        document.getElementById('viewAudience').innerText = data.audience_type;
        document.getElementById('viewDepartment').innerText = data.department || "All Departments";
        const badge = document.getElementById('viewPriorityBadge');
        badge.innerText = data.priority;
        badge.className = 'badge'; 
        if(data.priority === 'Critical') badge.classList.add('badge-critical');
        else if(data.priority === 'Important') badge.classList.add('badge-important');
        else badge.classList.add('badge-normal');
        const remarksBox = document.getElementById('viewRemarksBox');
        if(data.remarks) { document.getElementById('viewRemarks').innerText = data.remarks; remarksBox.style.display = 'block'; } 
        else { remarksBox.style.display = 'none'; }
        document.getElementById('viewModal').classList.add('active');
    }

    function closeModal(modalId) { document.getElementById(modalId).classList.remove('active'); }
    window.onclick = function(event) { if (event.target.classList.contains('modal-overlay')) { event.target.classList.remove('active'); } }

    document.addEventListener('DOMContentLoaded', function () {
      const groups = document.querySelectorAll('.sidebar-group');
      groups.forEach(group => {
        const toggle = group.querySelector('.sidebar-toggle');
        if (!toggle) return;
        toggle.addEventListener('click', function (e) {
          if(e.target.closest('.submenu')) return;
          e.preventDefault();
          group.classList.toggle('open');
        });
      });
    });
  </script>

</body>
</html>