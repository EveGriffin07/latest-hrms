<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Onboarding - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    .hero-banner { background: linear-gradient(135deg, #1e293b, #0f172a); border-radius: 16px; padding: 40px; color: white; margin-bottom: 30px; position: relative; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
    .hero-banner::after { content: ''; position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.05); border-radius: 50%; }
    .hero-title { font-size: 28px; font-weight: 700; margin: 0 0 10px 0; }
    
    .progress-container { background: rgba(255,255,255,0.2); border-radius: 99px; height: 10px; width: 100%; overflow: hidden; margin: 15px 0; }
    .progress-fill { background: #10b981; height: 100%; transition: width 0.5s ease; border-radius: 99px; }
    
    .category-section { margin-bottom: 30px; background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .category-header { background: #f8fafc; padding: 15px 20px; border-bottom: 1px solid #e2e8f0; font-size: 16px; font-weight: 600; color: #0f172a; display: flex; align-items: center; gap: 10px; }
    
    .task-card { padding: 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; transition: 0.2s; }
    .task-card:last-child { border-bottom: none; }
    .task-card:hover { background: #f8fafc; }
    
    .task-info h4 { margin: 0 0 5px 0; font-size: 15px; color: #1e293b; font-weight: 600; }
    .task-meta { font-size: 13px; color: #64748b; display: flex; align-items: center; gap: 15px; }
    
    .btn-complete { background: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 6px rgba(37,99,235,0.2); }
    .btn-complete:hover { background: #1d4ed8; transform: translateY(-2px); }
    
    .badge-done { background: #dcfce7; color: #166534; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; border: 1px solid #bbf7d0; }
  </style>
</head>

<body>
<header>
  <div class="title">Web-Based HRMS</div>
  <div class="user-info">
      <i class="fa-regular fa-bell"></i> &nbsp; <a href="{{ route('employee.profile') }}" style="color:inherit; text-decoration:none;">{{ Auth::user()->name }}</a>
  </div>
</header>

<div class="container dashboard-shell">
  @include('employee.layout.sidebar')

  <main style="flex:1; padding:28px 32px; max-width:100%;">
    
    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; padding:15px 20px; border-radius:8px; margin-bottom:20px; border:1px solid #bbf7d0; font-weight: 500;">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="background:#fee2e2; color:#b91c1c; padding:15px 20px; border-radius:8px; margin-bottom:20px; border:1px solid #fecaca; font-weight: 500;">
            <i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}
        </div>
    @endif

    @if($onboarding)
        
        {{-- BEAUTIFUL HERO BANNER --}}
        <div class="hero-banner">
            <h1 class="hero-title">Welcome to the Team, {{ explode(' ', trim(Auth::user()->name))[0] }}! 🚀</h1>
            <p style="color: #cbd5e1; margin: 0 0 20px 0; font-size: 15px;">Your customized induction checklist is ready. Complete these tasks to get fully settled into your new role.</p>
            
            <div style="display:flex; justify-content:space-between; margin-bottom:5px; font-size: 14px; font-weight: 600;">
                <span>Overall Progress</span>
                <span style="color: #10b981;">{{ $onboarding->progress ?? 0 }}%</span>
            </div>
            <div class="progress-container">
                <div class="progress-fill" style="width: {{ $onboarding->progress ?? 0 }}%;"></div>
            </div>
            
            <div style="font-size:13px; color:#cbd5e1; margin-top:15px;">
                <i class="fa-regular fa-flag"></i> Target Completion: <strong>{{ \Carbon\Carbon::parse($onboarding->end_date)->format('d M Y') }}</strong>
            </div>
        </div>

        {{-- DYNAMICALLY GENERATED TASK CATEGORIES --}}
        @foreach($groupedTasks as $category => $tasks)
            <div class="category-section">
                <div class="category-header">
                    {{-- Dynamically choose an icon based on category name --}}
                    @if($category == 'IT & Assets' || $category == 'IT & Security')
                        <i class="fa-solid fa-laptop-code" style="color: #0284c7;"></i>
                    @elseif($category == 'HR & Compliance')
                        <i class="fa-solid fa-file-signature" style="color: #e11d48;"></i>
                    @elseif($category == 'Culture & Team')
                        <i class="fa-solid fa-people-group" style="color: #d97706;"></i>
                    @else
                        <i class="fa-solid fa-list-check" style="color: #2563eb;"></i>
                    @endif
                    {{ $category }}
                </div>
                
                <div>
                    @foreach($tasks as $task)
                        <div class="task-card">
                            <div class="task-info">
                                <h4>{{ $task->task_name }}</h4>
                                <div class="task-meta">
                                    @if($task->remarks)
                                        <span><i class="fa-solid fa-comment-dots" style="color:#94a3b8;"></i> "{{ $task->remarks }}"</span>
                                    @endif
                                </div>
                            </div>
                            
                            {{-- THE NEW FILE UPLOAD & ACTION LOGIC --}}
                            <div class="task-action">
                                @if($task->is_completed)
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        {{-- If they uploaded a file, let them view it --}}
                                        @if($task->file_path)
                                            <a href="{{ asset('storage/' . $task->file_path) }}" target="_blank" style="font-size: 13px; color: #2563eb; text-decoration: none; font-weight: 500;">
                                                <i class="fa-solid fa-file-pdf"></i> View Attachment
                                            </a>
                                        @endif
                                        <span class="badge-done"><i class="fa-solid fa-check"></i> Done</span>
                                    </div>
                                @else
                                    {{-- EMPLOYEE SELF-SERVICE BUTTON WITH ATTACHMENT --}}
                                    <form action="{{ route('employee.onboarding.complete', $task->task_id ?? $task->id) }}" method="POST" enctype="multipart/form-data" style="margin:0; display:flex; align-items:center; gap:10px;">
                                        @csrf
                                        
                                        {{-- Hidden File Input --}}
                                        <input type="file" name="document" id="file_{{ $task->task_id ?? $task->id }}" style="display: none;" onchange="document.getElementById('filename_{{ $task->task_id ?? $task->id }}').innerText = this.files[0].name">
                                        
                                        {{-- Custom Paperclip Button --}}
                                        <button type="button" onclick="document.getElementById('file_{{ $task->task_id ?? $task->id }}').click()" style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 10px 14px; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 500; color: #475569; transition: 0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
                                            <i class="fa-solid fa-paperclip"></i> Attach File
                                        </button>
                                        
                                        {{-- File Name Display (Shows up after picking a file) --}}
                                        <div id="filename_{{ $task->task_id ?? $task->id }}" style="font-size: 11px; color: #2563eb; max-width: 100px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; font-weight: 600;"></div>
                                        
                                        <button type="submit" class="btn-complete">
                                            Mark as Done
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

    @else
        {{-- Empty State --}}
        <div style="background: white; border-radius: 16px; border: 1px dashed #cbd5e1; text-align: center; padding: 60px 20px;">
            <div style="width: 80px; height: 80px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
                <i class="fa-solid fa-clipboard-check" style="font-size: 32px; color: #94a3b8;"></i>
            </div>
            <h3 style="margin: 0 0 10px 0; color: #0f172a;">You're all caught up!</h3>
            <p style="color:#64748b; margin: 0;">You have no active provisioning or onboarding tasks at the moment.</p>
        </div>
    @endif

    <footer style="text-align: center; margin-top: 40px; color: #94a3b8; font-size: 13px;">© 2026 Web-Based HRMS. All Rights Reserved.</footer>
  </main>
</div>

</body>
</html>