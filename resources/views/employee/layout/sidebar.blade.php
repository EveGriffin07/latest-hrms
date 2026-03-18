@php
  // 1. Check if the user is a Supervisor or Manager
  $role = strtolower(Auth::user()->role ?? 'employee');
  $isSupervisor = ($role === 'supervisor' || $role === 'manager');

  // 2. Smart routing to keep menus open and highlight the active blue pill
  $openSection = null;
  if (request()->routeIs('employee.dashboard')) $openSection = 'dashboard';
  elseif (request()->routeIs('employee.profile') || request()->routeIs('supervisor.profile')) $openSection = 'profile';
  elseif (request()->routeIs('employee.assistant') || request()->routeIs('supervisor.assistant')) $openSection = 'assistant';
  elseif (request()->is('employee/attendance*') || request()->is('employee/face*') || request()->is('employee/ot-claims*') || request()->is('employee/overtime-requests*') || request()->is('employee/penalties*')) $openSection = 'attendance';
  elseif (request()->is('employee/leave*') && !request()->is('supervisor/leave*')) $openSection = 'leave';
  elseif (request()->is('employee/payroll*')) $openSection = 'payroll';
  elseif (request()->is('employee/training*') || request()->is('employee/onboarding*') || request()->is('employee/appraisal*') || request()->routeIs('employee.kpis.*')) $openSection = 'performance';
  elseif ($isSupervisor && (request()->is('supervisor/*') || request()->is('employee/ot-claims-inbox*') || request()->is('employee/overtime_inbox*')) && !request()->routeIs('supervisor.profile')) $openSection = 'team';
@endphp

<aside class="sidebar">
  {{-- ==========================================
       MAIN NAVIGATION (Everyone sees this)
       ========================================== --}}
  <div class="sidebar-group {{ $openSection === 'dashboard' ? 'open' : '' }}">
    <a href="{{ route('employee.dashboard') }}" class="sidebar-toggle sidebar-quick-link {{ $openSection === 'dashboard' ? 'active' : '' }}">
      <div class="left"><i class="fa-solid fa-chart-pie"></i><span>Dashboard</span></div>
    </a>
  </div>

  <div class="sidebar-group {{ $openSection === 'profile' ? 'open' : '' }}">
    <a href="{{ route($isSupervisor ? 'supervisor.profile' : 'employee.profile') }}" class="sidebar-toggle sidebar-quick-link {{ $openSection === 'profile' ? 'active' : '' }}">
      <div class="left"><i class="fa-solid fa-user-circle"></i><span>My Profile</span></div>
    </a>
  </div>

  <div class="sidebar-group {{ $openSection === 'assistant' ? 'open' : '' }}">
    <a href="{{ route('employee.assistant') }}" class="sidebar-toggle sidebar-quick-link {{ $openSection === 'assistant' ? 'active' : '' }}">
      <div class="left"><i class="fa-solid fa-robot"></i><span>AI Assistant</span></div>
    </a>
  </div>

  {{-- ==========================================
       MY HR: SELF SERVICE (Everyone sees this)
       ========================================== --}}
  <div style="padding: 20px 20px 5px; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">MY HR</div>

  <div class="sidebar-group {{ $openSection === 'attendance' ? 'open' : '' }}">
    <a href="#" class="sidebar-toggle {{ $openSection === 'attendance' ? 'active' : '' }}">
      <div class="left"><i class="fa-solid fa-clock"></i><span>Attendance & OT</span></div>
      <i class="fa-solid fa-chevron-right arrow"></i>
    </a>
    <ul class="submenu">
      <li><a href="{{ route('employee.attendance.log') }}" class="{{ request()->routeIs('employee.attendance.log') ? 'active' : '' }}">Daily Log</a></li>
      <li><a href="{{ route('employee.face.verify.form') }}" class="{{ request()->routeIs('employee.face.verify.form') ? 'active' : '' }}">Face Recognition</a></li>
      <li><a href="{{ route('employee.overtime_requests.index') }}" class="{{ request()->routeIs('employee.overtime_requests.index') ? 'active' : '' }}">Request Overtime</a></li>
      <li><a href="{{ route('employee.ot_claims.index') }}" class="{{ request()->routeIs('employee.ot_claims.index') ? 'active' : '' }}">My OT Claims</a></li>
    </ul>


<a href="#" onclick="document.getElementById('requisitionModal').style.display='flex'; return false;" class="btn-sm" style="background: #f8fafc; color: #0f172a; border: 1px solid #cbd5e1; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s;">
            <i class="fa-solid fa-user-plus" style="color: #8b5cf6;"></i> Job Requisition
          </a>
  </div>

  <div class="sidebar-group {{ $openSection === 'leave' ? 'open' : '' }}">
    <a href="#" class="sidebar-toggle {{ $openSection === 'leave' ? 'active' : '' }}">
      <div class="left"><i class="fa-solid fa-plane-departure"></i><span>Leave</span></div>
      <i class="fa-solid fa-chevron-right arrow"></i>
    </a>
    <ul class="submenu">
      <li><a href="{{ route('employee.leave.apply') }}" class="{{ request()->routeIs('employee.leave.apply') ? 'active' : '' }}">Apply Leave</a></li>
      <li><a href="{{ route('employee.leave.balance') }}" class="{{ request()->routeIs('employee.leave.balance') ? 'active' : '' }}">Leave Balance</a></li>
    </ul>
  </div>

  <div class="sidebar-group {{ $openSection === 'payroll' ? 'open' : '' }}">
    <a href="#" class="sidebar-toggle {{ $openSection === 'payroll' ? 'active' : '' }}">
      <div class="left"><i class="fa-solid fa-wallet"></i><span>Payroll</span></div>
      <i class="fa-solid fa-chevron-right arrow"></i>
    </a>
    <ul class="submenu">
      <li><a href="{{ route('employee.payroll.payslips') }}" class="{{ request()->routeIs('employee.payroll.payslips') ? 'active' : '' }}">My Payslips</a></li>
      <li><a href="{{ route('employee.payroll.tax') }}" class="{{ request()->routeIs('employee.payroll.tax') ? 'active' : '' }}">Tax Documents</a></li>
    </ul>
  </div>

  <div class="sidebar-group {{ $openSection === 'performance' ? 'open' : '' }}">
    <a href="#" class="sidebar-toggle {{ $openSection === 'performance' ? 'active' : '' }}">
      <div class="left"><i class="fa-solid fa-star-half-stroke"></i><span>Performance & Growth</span></div>
      <i class="fa-solid fa-chevron-right arrow"></i>
    </a>
    <ul class="submenu">
      <li><a href="{{ route('employee.onboarding.index') }}" class="{{ request()->routeIs('employee.onboarding.index') ? 'active' : '' }}">My Onboarding</a></li>
      <li><a href="{{ route('employee.training.index') }}" class="{{ request()->routeIs('employee.training.index') ? 'active' : '' }}">My Training</a></li>
      <li><a href="{{ route('employee.kpis.self-eval') }}" class="{{ request()->routeIs('employee.kpis.self-eval') ? 'active' : '' }}">My Appraisals</a></li>
    </ul>
  </div>

  {{-- ==========================================
       TEAM MANAGEMENT (Only Supervisors see this)
       ========================================== --}}
  @if($isSupervisor)
  <div style="padding: 20px 20px 5px; font-size: 11px; font-weight: 700; color: #6366f1; text-transform: uppercase; letter-spacing: 0.5px;">TEAM MANAGEMENT</div>
  
  <div class="sidebar-group {{ $openSection === 'team' ? 'open' : '' }}">
    <a href="#" class="sidebar-toggle {{ $openSection === 'team' ? 'active' : '' }}">
      <div class="left"><i class="fa-solid fa-users-gear"></i><span>Manager Actions</span></div>
      <i class="fa-solid fa-chevron-right arrow"></i>
    </a>
    <ul class="submenu">
      {{-- Restored Missing Features --}}
      <li><a href="{{ Route::has('manager.onboarding.index') ? route('manager.onboarding.index') : '#' }}" class="{{ request()->routeIs('manager.onboarding*') ? 'active' : '' }}">Team Onboarding</a></li>
      <li><a href="#" class="">Job Requisitions</a> {{-- Replace '#' with your requisition route --}}</li>
      <li><a href="{{ route('supervisor.appraisal.inbox') }}" class="{{ request()->routeIs('supervisor.appraisal.inbox') ? 'active' : '' }}">Manage Team KPIs</a></li>
      
      {{-- Approvals --}}
      <li><a href="{{ route('employee.overtime_inbox.index') }}" class="{{ request()->routeIs('employee.overtime_inbox.index') ? 'active' : '' }}">Overtime Approvals</a></li>
      <li><a href="{{ route('supervisor.leave.inbox') }}" class="{{ request()->routeIs('supervisor.leave.inbox') ? 'active' : '' }}">Leave Approvals</a></li>
      <li><a href="{{ route('supervisor.penalty_removal.index') }}" class="{{ request()->routeIs('supervisor.penalty_removal.index') ? 'active' : '' }}">Penalty Removals</a></li>
    </ul>
  </div>
  @endif

  {{-- ==========================================
       LOGOUT
       ========================================== --}}
  <div style="margin-top: 40px;" class="sidebar-group">
    <a href="#" class="sidebar-toggle sidebar-quick-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
      <div class="left">
        <i class="fa-solid fa-arrow-right-from-bracket" style="color: #ef4444;"></i>
        <span style="color: #ef4444;">Logout</span>
      </div>
    </a>
  </div>
  <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
</aside>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    const toggles = document.querySelectorAll(".sidebar-toggle");
    toggles.forEach(toggle => {
      toggle.addEventListener("click", function (e) {
        // If it's a direct link (like Dashboard/Profile), don't trigger the accordion animation
        if(this.classList.contains('sidebar-quick-link')) return;
        e.preventDefault();
        
        // Close all other groups
        document.querySelectorAll(".sidebar-group").forEach(g => {
            if(g !== this.closest(".sidebar-group")) g.classList.remove("open");
        });
        
        // Toggle the clicked group
        const group = this.closest(".sidebar-group");
        group.classList.toggle("open");
      });
    });
  });
</script>