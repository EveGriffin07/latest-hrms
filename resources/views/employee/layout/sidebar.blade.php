@php
  $role = strtolower(Auth::user()->role ?? 'employee');
  $isSupervisor = ($role === 'supervisor');

  $openSection = null;
  if (request()->routeIs('employee.dashboard')) $openSection = 'dashboard';
  elseif (request()->routeIs('employee.onboarding.index') || request()->routeIs('manager.onboarding*')) $openSection = 'onboarding';
  elseif (request()->is('employee/training*')) $openSection = 'training';
  elseif (request()->routeIs('employee.ot_claims.*') || request()->routeIs('employee.overtime_inbox.*')) $openSection = 'ot_claims';
  elseif (request()->is('employee/attendance*') || request()->routeIs('supervisor.penalty_removal.*')) $openSection = 'attendance';
  elseif (request()->is('employee/face*')) $openSection = 'face';
  elseif (request()->is('employee/leave*') || request()->routeIs('supervisor.leave.*')) $openSection = 'leave';
  elseif (request()->is('employee/payroll*')) $openSection = 'payroll';
  elseif (request()->routeIs('employee.kpis') || request()->routeIs('employee.kpis.self-eval') || request()->is('employee/appraisal*')) $openSection = 'performance';
  elseif (request()->routeIs('employee.profile') || request()->routeIs('supervisor.profile')) $openSection = 'profile';
@endphp
<aside class="sidebar">

  {{-- 1. DASHBOARD --}}
  <div class="sidebar-group {{ $openSection === 'dashboard' ? 'open' : '' }}">
    <a href="{{ route('employee.dashboard') }}" class="sidebar-toggle sidebar-quick-link">
      <div class="left">
        <i class="fa-solid fa-chart-pie"></i>
        <span>My Dashboard</span>
      </div>
    </a>
  </div>

  {{-- PROFILE (role-based link) --}}
  <div class="sidebar-group {{ $openSection === 'profile' ? 'open' : '' }}">
    <a href="{{ $isSupervisor ? route('supervisor.profile') : route('employee.profile') }}" class="sidebar-toggle sidebar-quick-link">
      <div class="left">
        <i class="fa-solid fa-user"></i>
        <span>Profile</span>
      </div>
    </a>
  </div>

  {{-- 2. ONBOARDING --}}
  <div class="sidebar-group {{ $openSection === 'onboarding' ? 'open' : '' }}">
    <a href="{{ route('employee.onboarding.index') }}" class="sidebar-toggle sidebar-quick-link">
      <div class="left">
        <i class="fa-solid fa-list-check"></i>
        <span>My Onboarding</span>
      </div>
    </a>
  </div>

  {{-- 4. TRAINING --}}
  <div class="sidebar-group {{ $openSection === 'training' ? 'open' : '' }}">
    <a href="#" class="sidebar-toggle">
      <div class="left">
        <i class="fa-solid fa-graduation-cap"></i>
        <span>My Training</span>
      </div>
      <i class="fa-solid fa-chevron-right arrow"></i>
    </a>
    <ul class="submenu">
      <li><a href="{{ route('employee.training.index') }}">Training Overview</a></li>
    </ul>
  </div>

  {{-- 5. ATTENDANCE --}}
  <div class="sidebar-group {{ $openSection === 'attendance' ? 'open' : '' }}">
    <a href="#" class="sidebar-toggle">
      <div class="left">
        <i class="fa-solid fa-user-clock"></i>
        <span>Attendance</span>
      </div>
      <i class="fa-solid fa-chevron-right arrow"></i>
    </a>
    <ul class="submenu">
      <li><a href="{{ route('employee.attendance.log') }}">Daily Log</a></li>
      <li><a href="{{ route('employee.penalties.index') }}">Penalty Removal</a></li>
      @if($isSupervisor)
        <li><a href="{{ route('supervisor.penalty_removal.index') }}">Penalty Removal Requests</a></li>
      @endif
    </ul>
  </div>

  {{-- 5b. FACE RECOGNITION --}}
  <div class="sidebar-group {{ $openSection === 'face' ? 'open' : '' }}">
    <a href="#" class="sidebar-toggle">
      <div class="left">
        <i class="fa-regular fa-face-smile"></i>
        <span>Face Recognition</span>
      </div>
      <i class="fa-solid fa-chevron-right arrow"></i>
    </a>
    <ul class="submenu">
      <li><a href="{{ route('employee.face.enroll') }}">Enroll My Face</a></li>
      <li><a href="{{ route('employee.face.verify.form') }}">Verify My Face</a></li>
    </ul>
  </div>

  {{-- 6. OT: single dropdown — My OT for all; Team OT Approvals for supervisors --}}
  <div class="sidebar-group {{ $openSection === 'ot_claims' ? 'open' : '' }}">
    <a href="#" class="sidebar-toggle">
      <div class="left">
        <i class="fa-solid fa-business-time"></i>
        <span>OT</span>
      </div>
      <i class="fa-solid fa-chevron-right arrow"></i>
    </a>
    <ul class="submenu">
      <li><a href="{{ route('employee.ot_claims.index') }}">My OT</a></li>
      @if($isSupervisor)
        <li><a href="{{ route('employee.overtime_inbox.index') }}">Team OT Approvals</a></li>
      @endif
    </ul>
  </div>

  {{-- 7. LEAVE: single link for employee; expandable (My Leave + Team Leave Approvals) for supervisor --}}
  @if($isSupervisor)
    <div class="sidebar-group {{ $openSection === 'leave' ? 'open' : '' }}">
      <a href="#" class="sidebar-toggle">
        <div class="left">
          <i class="fa-solid fa-plane-departure"></i>
          <span>Leave</span>
        </div>
        <i class="fa-solid fa-chevron-right arrow"></i>
      </a>
      <ul class="submenu">
        <li><a href="{{ route('employee.leave.apply') }}">My Leave</a></li>
        <li><a href="{{ route('supervisor.leave.inbox') }}">Team Leave Approvals</a></li>
      </ul>
    </div>
  @else
    <div class="sidebar-group {{ $openSection === 'leave' ? 'open' : '' }}">
      <a href="{{ route('employee.leave.apply') }}" class="sidebar-toggle sidebar-quick-link">
        <div class="left">
          <i class="fa-solid fa-plane-departure"></i>
          <span>Leave</span>
        </div>
      </a>
    </div>
  @endif

  {{-- 8. PAYROLL --}}
  <div class="sidebar-group {{ $openSection === 'payroll' ? 'open' : '' }}">
    <a href="{{ route('employee.payroll.payslips') }}" class="sidebar-toggle sidebar-quick-link">
      <div class="left">
        <i class="fa-solid fa-file-invoice-dollar"></i>
        <span>Payroll</span>
      </div>
    </a>
  </div>

  {{-- ==========================================
       PERFORMANCE APPRAISALS
       ========================================== --}}
       
  {{-- 1. Personal Appraisals (Self-Evaluations) --}}
  <div class="sidebar-group">
    <a href="{{ route('employee.kpis.self-eval') }}" class="sidebar-toggle sidebar-quick-link">
      <div class="left">
        <i class="fa-solid fa-star-half-stroke"></i>
        <span>My Appraisals</span>
      </div>
    </a>
  </div>

  {{-- 2. Supervisor Inbox (Grading Team Appraisals) --}}
  <div class="sidebar-group">
    <a href="{{ route('supervisor.appraisal.inbox') }}" class="sidebar-toggle sidebar-quick-link">
      <div class="left">
        <i class="fa-solid fa-clipboard-user"></i>
        <span>Team Appraisals</span>
      </div>
    </a>
  </div>

  <div class="sidebar-divider"></div>

  {{-- LOGOUT --}}
  <div class="sidebar-main-item">
    <a href="#" class="logout-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
      <div class="left">
        <i class="fa-solid fa-arrow-right-from-bracket"></i>
        <span>Logout</span>
      </div>
    </a>
  </div>
  <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
    @csrf
  </form>
</aside>
<script>
(function () {
  if (window.__HRMS_EMPLOYEE_SIDEBAR_INIT__) return;
  window.__HRMS_EMPLOYEE_SIDEBAR_INIT__ = true;
  document.addEventListener('DOMContentLoaded', function () {
    var groups = document.querySelectorAll('.sidebar-group');
    var toggles = document.querySelectorAll('.sidebar-toggle');
    var STORAGE_KEY = 'hrms_sidebar_open_group';

    function closeAll() {
      groups.forEach(function (g) {
        g.classList.remove('open');
        var t = g.querySelector('.sidebar-toggle');
        if (t) t.setAttribute('aria-expanded', 'false');
      });
    }

    toggles.forEach(function (btn, i) {
      btn.setAttribute('role', 'button');
      btn.setAttribute('tabindex', '0');
      btn.addEventListener('click', function (e) {
        if (btn.classList.contains('sidebar-quick-link')) return;
        e.preventDefault();
        var group = btn.closest('.sidebar-group');
        var isOpen = group.classList.contains('open');
        closeAll();
        if (!isOpen) {
          group.classList.add('open');
          btn.setAttribute('aria-expanded', 'true');
          try { localStorage.setItem(STORAGE_KEY, String(i)); } catch (err) {}
        } else {
          try { localStorage.removeItem(STORAGE_KEY); } catch (err) {}
        }
      });
    });
  });
})();
</script>
