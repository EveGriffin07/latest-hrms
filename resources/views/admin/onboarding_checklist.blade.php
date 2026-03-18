<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provisioning Checklist - HRMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
    <style>
        .hero-card { background: white; border-radius: 12px; padding: 25px 30px; margin-bottom: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .hero-title { font-size: 22px; font-weight: 700; color: #0f172a; margin: 0 0 8px 0; }
        .hero-meta { display: flex; gap: 20px; font-size: 13px; color: #475569; font-weight: 500; }
        
        .progress-container { width: 300px; }
        .progress-wrapper { display: flex; align-items: center; gap: 10px; width: 100%; margin-top: 8px; }
        .progress-bar { flex: 1; height: 10px; background: #e2e8f0; border-radius: 5px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 5px; background: #2563eb; transition: width 0.3s; }
        
        .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 25px; }
        .summary-box { background: white; padding: 15px 20px; border-radius: 10px; border: 1px solid #e2e8f0; border-left: 4px solid #cbd5e1; }
        .summary-box.done { border-left-color: #10b981; }
        .summary-box.pend { border-left-color: #f59e0b; }
        .summary-box.late { border-left-color: #ef4444; }
        
        .task-table { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }
        .task-table th { background: #f8fafc; padding: 14px 20px; text-align: left; font-size: 12px; text-transform: uppercase; color: #64748b; font-weight: 600; border-bottom: 1px solid #e2e8f0; }
        .task-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #1e293b; }
        
        .task-badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
        .bg-done { background: #dcfce7; color: #166534; }
        .bg-pend { background: #fef3c7; color: #b45309; }
        .bg-late { background: #fee2e2; color: #991b1b; }

        .cat-badge { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
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
        <div style="margin-bottom: 20px;">
            <a href="{{ route('admin.onboarding') }}" style="color: #64748b; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-arrow-left"></i> Back to Provisioning Overview
            </a>
        </div>

        <div class="hero-card">
            <div>
                <h1 class="hero-title">{{ $onboarding->employee->user->name }}</h1>
                <div class="hero-meta">
                    <span><i class="fa-solid fa-building" style="color:#94a3b8;"></i> {{ $onboarding->employee->department->department_name ?? 'N/A' }}</span>
                    <span><i class="fa-solid fa-briefcase" style="color:#94a3b8;"></i> {{ $onboarding->employee->position->position_name ?? 'N/A' }}</span>
                    <span><i class="fa-solid fa-flag-checkered" style="color:#dc2626;"></i> Deadline: {{ \Carbon\Carbon::parse($onboarding->end_date)->format('d M Y') }}</span>
                </div>
            </div>
            <div class="progress-container">
                <span style="font-size: 13px; font-weight: 600; color: #475569;">Overall Checklist Completion</span>
                <div class="progress-wrapper">
                    <div class="progress-bar"><div class="progress-fill" style="width: {{ $onboarding->progress }}%; background: {{ $onboarding->progress == 100 ? '#10b981' : '#2563eb' }};"></div></div>
                    <span style="font-size: 14px; font-weight: 700; color: #0f172a;">{{ $onboarding->progress }}%</span>
                </div>
            </div>
        </div>

        <div class="summary-grid">
            <div class="summary-box"><div style="font-size:12px; color:#64748b;">Total Checklist Items</div><div style="font-size:24px; font-weight:700;">{{ $totalTasks }}</div></div>
            <div class="summary-box done"><div style="font-size:12px; color:#64748b;">Completed</div><div style="font-size:24px; font-weight:700; color:#10b981;">{{ $completedTasks }}</div></div>
            <div class="summary-box pend"><div style="font-size:12px; color:#64748b;">Pending Items</div><div style="font-size:24px; font-weight:700; color:#f59e0b;">{{ $pendingTasks }}</div></div>
            <div class="summary-box late"><div style="font-size:12px; color:#64748b;">Overdue</div><div style="font-size:24px; font-weight:700; color:#ef4444;">{{ $overdueTasks }}</div></div>
        </div>

        <h3 style="font-size: 18px; font-weight: 600; color: #0f172a; margin-bottom: 15px;"><i class="fa-solid fa-list-check" style="color: #2563eb;"></i> Detailed Provisioning Breakdown</h3>
        
        <table class="task-table">
            <thead>
            <tr>
                <th>Provisioning Task</th>
                <th>Responsibility</th>
                <th>Target Date</th>
                <th>Current Status</th>
            </tr>
            </thead>
            <tbody>
            @forelse($onboarding->tasks as $task)
            <tr>
                <td style="font-weight: 500;">
                    {{ $task->task_name }}
                    @if($task->remarks)
                        <div style="font-size:12px; color:#64748b; font-style:italic; margin-top:4px;">"{{ $task->remarks }}"</div>
                    @endif
                </td>
                <td>
                    {{-- Dynamically color the category badges based on responsibility --}}
                    @php
                        $catStyle = match($task->category) {
                            'IT & Assets', 'IT & Security' => 'background:#e0f2fe; color:#0369a1;',
                            'HR & Compliance' => 'background:#fce7f3; color:#be185d;',
                            'Culture & Team', 'Manager Task' => 'background:#fef3c7; color:#b45309;',
                            default => 'background:#f1f5f9; color:#475569;'
                        };
                    @endphp
                    <span class="cat-badge" style="{{ $catStyle }}">{{ $task->category }}</span>
                </td>
                <td style="font-size: 13px; color: #475569;">{{ \Carbon\Carbon::parse($task->due_date)->format('d M Y') }}</td>
                <td>
                    @if($task->is_completed)
                        <span class="task-badge bg-done"><i class="fa-solid fa-check"></i> Provisioned</span>
                    @elseif($task->due_date && \Carbon\Carbon::parse($task->due_date)->isPast())
                        <span class="task-badge bg-late"><i class="fa-solid fa-triangle-exclamation"></i> Overdue</span>
                    @else
                        <span class="task-badge bg-pend"><i class="fa-regular fa-clock"></i> Awaiting Setup</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align:center; padding: 30px; color: #94a3b8;">No tasks generated for this employee.</td>
            </tr>
            @endforelse
            </tbody>
        </table>

        <footer style="text-align: center; margin-top: 40px; color: #94a3b8; font-size: 13px;">© 2026 Web-Based HRMS. All Rights Reserved.</footer>
    </main>
</div>
</body>
</html>