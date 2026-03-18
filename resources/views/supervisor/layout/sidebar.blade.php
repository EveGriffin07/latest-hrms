<aside class="sidebar">

  {{-- DASHBOARD --}}
  <div class="sidebar-group open">
    <a href="{{ route('employee.dashboard') }}" class="sidebar-toggle sidebar-quick-link">
      <div class="left">
        <i class="fa-solid fa-chart-pie"></i>
        <span>Dashboard</span>
      </div>
    </a>
  </div>

  {{-- PROFILE --}}
  <div class="sidebar-group">
    <a href="{{ route('supervisor.profile') }}" class="sidebar-toggle sidebar-quick-link">
      <div class="left">
        <i class="fa-solid fa-user"></i>
        <span>Profile</span>
      </div>
    </a>
  </div>

  {{-- ONBOARDING --}}
  <div class="sidebar-group">
    <a href="{{ route('employee.onboarding.index') }}" class="sidebar-toggle sidebar-quick-link">
      <div class="left">
        <i class="fa-solid fa-list-check"></i>
        <span>Onboarding</span>
      </div>
    </a>
  </div>

  {{-- TRAINING --}}
  <div class="sidebar-group">
    <a href="#" class="sidebar-toggle">
      <div class="left">
        <i class="fa-solid fa-graduation-cap"></i>
        <span>Training</span>
      </div>
      <i class="fa-solid fa-chevron-right arrow"></i>
    </a>
    <ul class="submenu">
      <li><a href="{{ route('employee.training.index') }}">Training Overview</a></li>
    </ul>
  </div>

  {{-- ATTENDANCE --}}
  <div class="sidebar-group">
    <a href="#" class="sidebar-toggle">
      <div class="left">
        <i class="fa-solid fa-user-clock"></i>
        <span>Attendance</span>
      </div>
      <i class="fa-solid fa-chevron-right arrow"></i>
    </a>
    <ul class="submenu">
      <li><a href="{{ route('employee.attendance.log') }}">Daily Log</a></li>
      <li><a href="{{ route('employee.face.enroll') }}">Face Enrollment</a></li>
      <li><a href="{{ route('employee.face.verify.form') }}">Verify My Face</a></li>
    </ul>
  </div>

  {{-- OT CLAIMS: driven by config/nav.php and role --}}
  @php
    $role = strtolower(Auth::user()->role ?? 'supervisor');
    $otItems = collect(config('nav.ot_claims.items', []))
      ->filter(fn($i) => in_array($role, $i['roles'] ?? [], true))
      ->values();
  @endphp
  @if($otItems->count())
    <div class="sidebar-group sidebar-group-ot open">
      <a href="#" class="sidebar-toggle">
        <div class="left">
          <i class="fa-solid fa-business-time"></i>
          <span>OT Claims</span>
        </div>
        <i class="fa-solid fa-chevron-down arrow"></i>
      </a>
      <ul class="submenu submenu-ot">
        @foreach($otItems as $item)
          <li>
            <a href="{{ route($item['route']) }}">
              {{ $item['title'] }}
            </a>
          </li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- LEAVE --}}
  <div class="sidebar-group">
    <a href="{{ route('employee.leave.apply') }}" class="sidebar-toggle sidebar-quick-link">
      <div class="left">
        <i class="fa-solid fa-plane-departure"></i>
        <span>Leave</span>
      </div>
    </a>
  </div>

  {{-- PAYROLL --}}
  <div class="sidebar-group">
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
        <i class="fa-solid fa-clipboard-user" style="color: #2563eb;"></i>
        <span style="font-weight: 600; color: #0f172a;">Team Appraisals</span>
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
  if (window.__HRMS_SUPERVISOR_SIDEBAR_INIT__) return;
  window.__HRMS_SUPERVISOR_SIDEBAR_INIT__ = true;
  document.addEventListener('DOMContentLoaded', function () {
    var groups = document.querySelectorAll('.sidebar-group');
    var toggles = document.querySelectorAll('.sidebar-toggle');
    var STORAGE_KEY = 'hrms_sidebar_open_group';

    function closeAll() {
      groups.forEach(function (g) {
        // Keep OT Claims group always open
        if (g.classList.contains('sidebar-group-ot')) return;
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
