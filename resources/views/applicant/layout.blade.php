<!DOCTYPE html>
<html>
<head>
    <title>Applicant Portal – HRMS</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">

    @stack('styles')

    <style>
        /* === HEADER & NOTIFICATION BELL STYLES === */
        header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 5px 45px; 
            position: relative;
        }

        .header-actions {
            position: relative;
            display: flex;
            align-items: center;
        }

        .notif-btn {
            background: transparent;
            border: none;
            color: #fff; /* Assuming your header is blue */
            font-size: 22px;
            cursor: pointer;
            position: relative;
            transition: 0.2s;
            padding: 5px;
        }
        
        .notif-btn:hover { color: #cbd5e1; }

        .notif-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: #ef4444;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            border: 2px solid #1e3a8a; /* Matches header bg to look cut out */
        }

        /* === DROPDOWN PANEL === */
        .notif-dropdown {
            display: none;
            position: absolute;
            top: 50px;
            right: 0;
            width: 350px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
            z-index: 1000;
            overflow: hidden;
            animation: dropFade 0.2s ease-out;
        }
        
        .notif-dropdown.active { display: block; }

        @keyframes dropFade {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .notif-header {
            background: #f8fafc;
            padding: 15px 20px;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            color: #0f172a;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notif-body { min-height: 100px; }

        .notif-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            gap: 12px;
            align-items: flex-start;
            text-decoration: none;
            transition: 0.2s;
        }
        .notif-item:hover { background: #f8fafc; }
        .notif-item:last-child { border-bottom: none; }

        .notif-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        .icon-interview { background: #dbeafe; color: #2563eb; }
        .icon-update { background: #fef3c7; color: #d97706; }

        .notif-text h5 { margin: 0 0 3px 0; font-size: 13px; color: #0f172a; }
        .notif-text p { margin: 0 0 5px 0; font-size: 12px; color: #64748b; line-height: 1.4; }
        .notif-text span { font-size: 11px; color: #94a3b8; font-weight: 500; }

        .notif-footer {
            background: #f8fafc;
            padding: 10px 20px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pagination-btn {
            background: transparent;
            border: none;
            color: #2563eb;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            padding: 5px;
        }
        .pagination-btn:disabled { color: #cbd5e1; cursor: not-allowed; }
        .page-indicator { font-size: 12px; color: #64748b; font-weight: 500; }
    </style>
</head>

<body>

{{-- =======================================================
     FETCH NOTIFICATIONS LOGIC
======================================================== --}}
@php
    $notifications = collect();
    
    if (Auth::check()) {
        $profile = \App\Models\ApplicantProfile::where('user_id', Auth::user()->user_id)->first();
        
        if ($profile) {
            // 1. Get Upcoming Interviews
            $interviews = \App\Models\Application::where('applicant_id', $profile->applicant_id)
                ->where('app_stage', 'Interview')
                ->whereNotNull('interview_datetime')
                ->where('interview_datetime', '>=', \Carbon\Carbon::now())
                ->with('job')
                ->get()
                ->map(function($app) {
                    return [
                        'type' => 'interview',
                        'title' => 'Interview Scheduled',
                        'message' => 'Your interview for ' . $app->job->job_title . ' is set for ' . \Carbon\Carbon::parse($app->interview_datetime)->format('d M Y, h:i A') . '.',
                        'time' => $app->updated_at->diffForHumans(),
                        'timestamp' => $app->updated_at,
                        'link' => route('applicant.applications')
                    ];
                });

            // 2. Get Recent Status Updates (Last 7 days, excluding 'Applied')
            $updates = \App\Models\Application::where('applicant_id', $profile->applicant_id)
                ->whereIn('app_stage', ['Reviewing', 'Hired', 'Rejected'])
                ->where('updated_at', '>=', \Carbon\Carbon::now()->subDays(7))
                ->with('job')
                ->get()
                ->map(function($app) {
                    $action = $app->app_stage === 'Reviewing' ? 'is now Under Review' : 'status changed to ' . $app->app_stage;
                    return [
                        'type' => 'update',
                        'title' => 'Application Update',
                        'message' => 'Your application for ' . $app->job->job_title . ' ' . $action . '.',
                        'time' => $app->updated_at->diffForHumans(),
                        'timestamp' => $app->updated_at,
                        'link' => route('applicant.applications')
                    ];
                });

            // Combine and sort them by most recent first
            $notifications = $interviews->concat($updates)->sortByDesc('timestamp')->values();
        }
    }
@endphp

{{-- =======================================================
     HEADER HTML
======================================================== --}}
<header>
    <div class="title">Applicant Portal</div>
    
    <div class="header-actions">
        {{-- Bell Button --}}
        <button id="notifBtn" class="notif-btn">
            <i class="fa-solid fa-bell"></i>
            @if($notifications->count() > 0)
                <span class="notif-badge">{{ $notifications->count() }}</span>
            @endif
        </button>

        {{-- Dropdown Panel --}}
        <div id="notifDropdown" class="notif-dropdown">
            <div class="notif-header">
                Notifications
                <span style="background: #e2e8f0; padding: 2px 8px; border-radius: 10px; font-size: 11px;">{{ $notifications->count() }} New</span>
            </div>
            
            <div class="notif-body" id="notifList">
                {{-- JS will inject items here --}}
            </div>

            <div class="notif-footer" id="notifFooter">
                <button id="prevBtn" class="pagination-btn"><i class="fa-solid fa-chevron-left"></i> Prev</button>
                <span id="pageIndicator" class="page-indicator">1 / 1</span>
                <button id="nextBtn" class="pagination-btn">Next <i class="fa-solid fa-chevron-right"></i></button>
            </div>
        </div>
    </div>
</header>

<div class="container">

    <aside class="sidebar">

        <ul>
            <li>
                <a href="{{ route('applicant.jobs') }}" class="{{ request()->is('applicant/jobs') ? 'active' : '' }}">
                    <i class="fa-solid fa-briefcase"></i>
                    Job Listings
                </a>
            </li>

            <li>
                <a href="{{ route('applicant.applications') }}" class="{{ request()->is('applicant/applications') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-lines"></i>
                    My Applications
                </a>
            </li>
        </ul>

        <hr class="sidebar-divider">

        <li class="{{ request()->routeIs('applicant.profile') ? 'active' : '' }}">
            <a href="{{ route('applicant.profile') }}">
                <i class="fa-solid fa-user"></i>
                <span>My Profile</span>
            </a>
        </li>

        <div class="sidebar-group">
            <a href="#" class="sidebar-toggle logout-link"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <div class="left"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></div>
            </a>
        </div>

        <form id="logout-form" method="POST" action="{{ route('logout') }}" style="display:none;">
            @csrf
        </form>

    </aside>

    <main class="content">
        @yield('content')
    </main>

</div>

{{-- =======================================================
     NOTIFICATION DROPDOWN JAVASCRIPT
======================================================== --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const notifBtn = document.getElementById('notifBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    const notifList = document.getElementById('notifList');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const pageIndicator = document.getElementById('pageIndicator');
    const notifFooter = document.getElementById('notifFooter');

    // Load PHP data into Javascript securely
    const notifications = @json($notifications);
    
    let currentPage = 1;
    const itemsPerPage = 4; // User requested 4 items per page!

    // Toggle Dropdown Visibility
    notifBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        notifDropdown.classList.toggle('active');
        if(notifDropdown.classList.contains('active')) {
            renderPage(1);
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
            notifDropdown.classList.remove('active');
        }
    });

    // Render a specific page of 4 notifications
    function renderPage(page) {
        currentPage = page;
        notifList.innerHTML = '';

        if (notifications.length === 0) {
            notifList.innerHTML = '<div style="padding: 30px 20px; text-align: center; color: #94a3b8; font-size: 13px;"><i class="fa-regular fa-bell-slash" style="font-size: 24px; margin-bottom: 10px;"></i><br>You have no new notifications.</div>';
            notifFooter.style.display = 'none';
            return;
        }

        const totalPages = Math.ceil(notifications.length / itemsPerPage);
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const currentItems = notifications.slice(startIndex, endIndex);

        currentItems.forEach(notif => {
            const iconClass = notif.type === 'interview' ? 'fa-calendar-day' : 'fa-clipboard-check';
            const bgClass = notif.type === 'interview' ? 'icon-interview' : 'icon-update';

            const html = `
                <a href="${notif.link}" class="notif-item">
                    <div class="notif-icon ${bgClass}">
                        <i class="fa-solid ${iconClass}"></i>
                    </div>
                    <div class="notif-text">
                        <h5>${notif.title}</h5>
                        <p>${notif.message}</p>
                        <span>${notif.time}</span>
                    </div>
                </a>
            `;
            notifList.insertAdjacentHTML('beforeend', html);
        });

        // Update Pagination Controls
        if (totalPages <= 1) {
            notifFooter.style.display = 'none';
        } else {
            notifFooter.style.display = 'flex';
            pageIndicator.innerText = `${currentPage} / ${totalPages}`;
            prevBtn.disabled = currentPage === 1;
            nextBtn.disabled = currentPage === totalPages;
        }
    }

    // Next/Prev Click Events
    prevBtn.addEventListener('click', () => { if (currentPage > 1) renderPage(currentPage - 1); });
    nextBtn.addEventListener('click', () => { 
        if (currentPage < Math.ceil(notifications.length / itemsPerPage)) renderPage(currentPage + 1); 
    });
});
</script>

</body>
</html>