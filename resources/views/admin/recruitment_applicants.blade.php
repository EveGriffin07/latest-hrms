<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Applicants - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">

  <style>
      /* Premium Table Styling */
      .table-container { background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); overflow: hidden; border: 1px solid #e5e7eb; margin-bottom: 40px; }
      .table-actions { padding: 20px; border-bottom: 1px solid #e5e7eb; background: #fff; display: flex; justify-content: space-between; align-items: center; }
      
      .hr-table { width: 100%; border-collapse: collapse; text-align: left; }
      .hr-table th { background: #f8fafc; padding: 16px 20px; font-size: 13px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb; }
      .hr-table td { padding: 16px 20px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #1e293b; }
      .hr-table tbody tr:hover { background: #f8fafc; }

      /* User Profile Cell */
      .user-profile { display: flex; align-items: center; gap: 15px; }
      .avatar-circle { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid #e2e8f0; background: #f1f5f9; }
      .user-info { display: flex; flex-direction: column; justify-content: center; }
      .user-name { font-weight: 600; color: #0f172a; font-size: 14px; line-height: 1.2; margin-bottom: 2px; }
      .user-email { font-size: 12px; color: #64748b; }

      /* Badges */
      .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
      .badge-applied { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
      .badge-interview { background: #fae8ff; color: #a21caf; border: 1px solid #f5d0fe; }
      .badge-hired { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
      .badge-rejected { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }

      /* Action Buttons */
      .action-buttons { display: flex; gap: 8px; align-items: center; }
      .btn-icon { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; text-decoration: none; }
      .btn-icon:hover { background: #f1f5f9; color: #2563eb; border-color: #cbd5e1; }
      .btn-icon.delete:hover { background: #fee2e2; color: #dc2626; border-color: #fecaca; }
      
      .search-input { padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 8px; width: 300px; font-size: 14px; font-family: inherit; transition: 0.2s; outline: none; }
      .search-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
  </style>
</head>
<body>

  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info-header" style="display: flex; align-items: center; gap: 10px;">
      <a href="{{ route('admin.profile') }}" style="text-decoration: none; color: inherit; font-weight: 500;">
        <i class="fa-regular fa-circle-user"></i> &nbsp; {{ Auth::user()->name ?? 'HR Admin' }}
      </a>
    </div>
  </header>

  <div class="container">

    @include('admin.layout.sidebar')

    <main>
      <div class="breadcrumb" style="color: #64748b; font-size: 14px; margin-bottom: 10px;">Home > Recruitment > <span style="color: #0f172a; font-weight: 500;">Applicants</span></div>
      <h2 style="font-size: 28px; color: #0f172a; margin-bottom: 5px;">Applicant Management</h2>
      <p class="subtitle" style="color: #64748b; margin-bottom: 30px;">Review incoming candidates and manage their progression through the hiring pipeline.</p>

      <div class="table-container">
        
        <div class="table-actions">
           <div style="position: relative;">
               <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 15px; top: 12px; color: #94a3b8;"></i>
               <input type="text" class="search-input" placeholder="Search applicant names..." style="padding-left: 40px;">
           </div>
        </div>

        <table class="hr-table">
          <thead>
            <tr>
              <th>Applicant Details</th>
              <th>Applied For</th>
              <th>Applied Date</th>
              <th>Stage</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            
            @forelse($applications as $app)
            @php
                $name = $app->applicant->full_name ?? 'Unknown Applicant';
                $email = $app->applicant->user->email ?? 'No Email';
                $avatar = $app->applicant->avatar_path ?? null;
            @endphp
            <tr>
              <td>
                <div class="user-profile">
                  @if($avatar)
                      <img src="{{ asset('storage/' . $avatar) }}" alt="{{ $name }}" class="avatar-circle">
                  @else
                      <img src="https://ui-avatars.com/api/?name={{ urlencode($name) }}&background=f1f5f9&color=475569" alt="{{ $name }}" class="avatar-circle">
                  @endif
                  
                  <div class="user-info">
                    <span class="user-name">{{ $name }}</span>
                    <span class="user-email">{{ $email }}</span>
                  </div>
                </div>
              </td>
              <td>
                <div style="display: flex; flex-direction: column;">
                    <span style="font-weight: 600; color: #2563eb; font-size: 14px;">{{ $app->job->job_title ?? 'Job Deleted' }}</span>
                    <span style="font-size: 12px; color: #64748b; margin-top: 2px;">{{ $app->job->department ?? 'Unknown Dept' }}</span>
                </div>
              </td>
              <td style="font-weight: 500; color: #475569;">{{ $app->created_at->format('d M Y') }}</td>
              <td>
                @if($app->app_stage == 'Hired')
                    <span class="badge badge-hired">Hired</span>
                @elseif($app->app_stage == 'Interview')
                    <span class="badge badge-interview">Interview</span>
                @elseif($app->app_stage == 'Rejected')
                    <span class="badge badge-rejected">Rejected</span>
                @elseif($app->app_stage == 'Applied')
                    <span class="badge badge-applied">Applied</span>
                @else
                    <span class="badge badge-applied">{{ $app->app_stage }}</span>
                @endif
              </td>
              <td>
                <div class="action-buttons">
                    <a href="{{ route('admin.applicants.show', $app->application_id) }}" class="btn-icon" title="View Full Profile">
                        <i class="fa-solid fa-eye"></i>
                    </a>

                    <form action="{{ route('admin.applicants.updateStatus', $app->application_id) }}" method="POST" style="margin:0;">
                        @csrf
                        <input type="hidden" name="status" value="Rejected">
                        <button type="submit" class="btn-icon delete" title="Quick Reject" onclick="return confirm('Are you sure you want to reject {{ $name }}?')">
                            <i class="fa-solid fa-user-xmark"></i>
                        </button>
                    </form>
                </div>
              </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center; padding: 50px 20px; color: #64748b;">
                    <i class="fa-solid fa-users-slash" style="font-size: 32px; margin-bottom: 15px; color: #cbd5e1;"></i><br>
                    <span style="font-size: 15px; font-weight: 500; color: #334155;">No applicants found.</span><br>
                    When candidates apply for your open jobs, they will appear here.
                </td>
            </tr>
            @endforelse

          </tbody>
        </table>
      </div>

      <footer style="text-align: center; color: #94a3b8; font-size: 13px;">© 2026 Web-Based HRMS. All Rights Reserved.</footer>
    </main>
  </div>
</body>
</html>