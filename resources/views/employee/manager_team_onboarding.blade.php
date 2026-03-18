<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Team Onboarding - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
      .team-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
      .team-table th, .team-table td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
      .team-table th { background: #f8fafc; font-weight: 600; color: #475569; }
      .status-pill { padding: 4px 12px; border-radius: 99px; font-size: 12px; font-weight: 500; }
      .status-pending { background: #fef9c3; color: #a16207; }
      .status-progress { background: #dbeafe; color: #1d4ed8; }
      .status-completed { background: #dcfce7; color: #15803d; }
  </style>
</head>

<body>
<header>
  <div class="title">Web-Based HRMS</div>
  <div class="user-info">
    <span>Welcome, {{ Auth::user()->name }}</span>
  </div>
</header>

<div class="container">
  @include('employee.layout.sidebar')

  <main>
    <div class="breadcrumb">Home > Manager Self-Service > Team Onboarding</div>
    
    <h2>My Team's Onboarding</h2>
    <p class="subtitle">Track and approve onboarding tasks for your direct reports.</p>

    @if(session('error'))
        <div style="background: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fa-solid fa-triangle-exclamation"></i> {{ session('error') }}
        </div>
    @endif

    <table class="team-table">
        <thead>
            <tr>
                <th>Employee Name</th>
                <th>Job Title</th>
                <th>Start Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($subordinates as $sub)
                @if($sub->onboarding)
                <tr>
                    <td><strong>{{ $sub->user->name ?? 'N/A' }}</strong></td>
                    <td>{{ $sub->position->position_name ?? 'N/A' }}</td>
                    <td>{{ \Carbon\Carbon::parse($sub->onboarding->start_date)->format('d M Y') }}</td>
                    <td>
                        @if($sub->onboarding->status == 'completed')
                            <span class="status-pill status-completed">Completed</span>
                        @elseif($sub->onboarding->status == 'in_progress')
                            <span class="status-pill status-progress">In Progress</span>
                        @else
                            <span class="status-pill status-pending">Pending</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('manager.onboarding.show', $sub->onboarding->onboarding_id) }}" class="btn btn-primary" style="padding: 6px 14px; font-size: 13px;">
                            Review Tasks
                        </a>
                    </td>
                </tr>
                @endif
            @empty
                <tr>
                    <td colspan="5" style="text-align:center; padding: 30px; color: #64748b;">
                        No team members currently assigned to you.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <footer>© 2026 Web-Based HRMS. All Rights Reserved.</footer>
  </main>
</div>
</body>
</html>