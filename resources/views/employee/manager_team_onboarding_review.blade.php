<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Review Onboarding Tasks - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
      .task-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
      .task-table th, .task-table td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
      .task-table th { background: #f8fafc; font-weight: 600; color: #475569; }
      .status-pill { padding: 4px 12px; border-radius: 99px; font-size: 12px; font-weight: 500; }
      .status-pending { background: #fef9c3; color: #a16207; }
      .status-completed { background: #dcfce7; color: #15803d; }
      .info-card { background: white; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 40px; }
      .info-block span { display: block; font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 600; margin-bottom: 4px; }
      .info-block strong { font-size: 16px; color: #0f172a; }
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
  {{-- Adjust sidebar include path if necessary --}}
  @if(Auth::user()->role === 'supervisor' || Auth::user()->role === 'manager')
      @include('supervisor.layout.sidebar')
  @else
      @include('employee.layout.sidebar')
  @endif

  <main>
    <div class="breadcrumb">Home > Manager Self-Service > Team Onboarding > Review Tasks</div>
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
        <div>
            <h2>Onboarding Checklist</h2>
            <p class="subtitle">Review the onboarding progress for your team member.</p>
        </div>
        <a href="{{ route('manager.onboarding.index') }}" class="btn btn-secondary" style="background: white; border: 1px solid #cbd5e1; color: #475569; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 500;">
            <i class="fa-solid fa-arrow-left"></i> Back to Team List
        </a>
    </div>

    <div class="info-card">
        <div class="info-block">
            <span>Employee Name</span>
            <strong>{{ $onboarding->employee->user->name ?? 'N/A' }}</strong>
        </div>
        <div class="info-block">
            <span>Start Date</span>
            <strong>{{ \Carbon\Carbon::parse($onboarding->start_date)->format('d M Y') }}</strong>
        </div>
        <div class="info-block">
            <span>Status</span>
            @if($onboarding->status == 'completed')
                <span class="status-pill status-completed" style="display: inline-block;">Completed</span>
            @elseif($onboarding->status == 'in_progress')
                <span class="status-pill status-progress" style="display: inline-block; background: #dbeafe; color: #1d4ed8;">In Progress</span>
            @else
                <span class="status-pill status-pending" style="display: inline-block;">Pending</span>
            @endif
        </div>
    </div>

    <table class="task-table">
        <thead>
            <tr>
                <th>Task Description</th>
                <th>Category</th>
                <th>Due Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($onboarding->tasks as $task)
            <tr>
                <td><strong>{{ $task->task_name }}</strong></td>
                <td>{{ $task->category }}</td>
                <td>{{ \Carbon\Carbon::parse($task->due_date)->format('d M Y') }}</td>
                <td>
                    @if($task->is_completed)
                        <span class="status-pill status-completed"><i class="fa-solid fa-check"></i> Done</span>
                    @else
                        <span class="status-pill status-pending"><i class="fa-regular fa-clock"></i> Pending</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align:center; padding: 30px; color: #64748b;">
                    No tasks assigned to this employee yet.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <footer style="margin-top: 40px; color: #94a3b8; font-size: 13px;">© 2026 Web-Based HRMS. All Rights Reserved.</footer>
  </main>
</div>
</body>
</html>