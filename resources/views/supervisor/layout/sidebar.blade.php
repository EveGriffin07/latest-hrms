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
      {{-- NEW: Face Enrollment Menu Item Added Here --}}
      <li><a href="{{ route('employee.face.enroll') }}" class="{{ request()->routeIs('employee.face.enroll') ? 'active' : '' }}">Face Enrollment</a></li>
      <li><a href="{{ route('employee.overtime_requests.index') }}" class="{{ request()->routeIs('employee.overtime_requests.index') ? 'active' : '' }}">Request Overtime</a></li>
      {{-- NEW: Penalty Removal Menu Item --}}
      <li><a href="{{ route('employee.penalties.index') }}" class="{{ request()->routeIs('employee.penalties.index') ? 'active' : '' }}">Penalty Removal</a></li>
    </ul>
  </div>

  <div class="sidebar-group {{ $openSection === 'leave' ? 'open' : '' }}">
    <a href="#" class="sidebar-toggle {{ $openSection === 'leave' ? 'active' : '' }}">
      <div class="left"><i class="fa-solid fa-plane-departure"></i><span>Leave</span></div>
      <i class="fa-solid fa-chevron-right arrow"></i>
    </a>
    <ul class="submenu">
      <li><a href="{{ route('employee.leave.apply') }}" class="{{ request()->routeIs('employee.leave.apply') ? 'active' : '' }}">Apply Leave</a></li>
    </ul>
  </div>

  <div class="sidebar-group {{ $openSection === 'payroll' ? 'open' : '' }}">
    <a href="#" class="sidebar-toggle {{ $openSection === 'payroll' ? 'active' : '' }}">
      <div class="left"><i class="fa-solid fa-wallet"></i><span>Payroll</span></div>
      <i class="fa-solid fa-chevron-right arrow"></i>
    </a>
    <ul class="submenu">
      <li><a href="{{ route('employee.payroll.payslips') }}" class="{{ request()->routeIs('employee.payroll.payslips') ? 'active' : '' }}">My Payslips</a></li>
      <li><a href="{{ route('employee.ot_claims.index') }}" class="{{ request()->routeIs('employee.ot_claims.index') ? 'active' : '' }}">My OT Claims</a></li>
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
      <li><a href="{{ Route::has('manager.onboarding.index') ? route('manager.onboarding.index') : '#' }}" class="{{ request()->routeIs('manager.onboarding*') ? 'active' : '' }}">Team Onboarding</a></li>
      <li><a href="#" onclick="document.getElementById('requisitionModal').style.display='flex'; return false;">Job Requisitions</a></li>
      <li><a href="{{ route('supervisor.appraisal.inbox') }}" class="{{ request()->routeIs('supervisor.appraisal.inbox') ? 'active' : '' }}">Manage Team KPIs</a></li>
      
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

{{-- ========================================================= --}}
{{-- GLOBAL JOB REQUISITION MODAL (Hidden by default)          --}}
{{-- ========================================================= --}}
@if($isSupervisor)
<div id="requisitionModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: white; width: 100%; max-width: 500px; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); overflow: hidden;">
        
        {{-- Modal Header --}}
        <div style="background: #f8fafc; padding: 20px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; color: #0f172a; font-size: 18px;"><i class="fa-solid fa-user-plus" style="color: #8b5cf6;"></i> Request New Hire</h3>
            <button type="button" onclick="document.getElementById('requisitionModal').style.display='none'" style="background: none; border: none; font-size: 24px; color: #64748b; cursor: pointer; line-height: 1;">&times;</button>
        </div>
        
        {{-- Modal Body / Form --}}
        <div style="padding: 24px;">
            <form action="{{ route('employee.requisition.store') }}" method="POST">
                @csrf
                <div style="margin-bottom: 16px;">
                    <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #475569; font-size: 13px;">Job Title <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="job_title" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Poppins', sans-serif;" placeholder="e.g. Senior Software Engineer">
                </div>
                
                <div style="display: flex; gap: 16px; margin-bottom: 16px;">
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #475569; font-size: 13px;">Employment Type <span style="color: #ef4444;">*</span></label>
                        <select name="employment_type" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Poppins', sans-serif;">
                            <option value="Full-Time">Full-Time</option>
                            <option value="Part-Time">Part-Time</option>
                            <option value="Contract">Contract</option>
                            <option value="Internship">Internship</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #475569; font-size: 13px;">Headcount <span style="color: #ef4444;">*</span></label>
                        <input type="number" name="headcount" required min="1" value="1" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Poppins', sans-serif;">
                    </div>
                </div>

                <div style="margin-bottom: 24px;">
                    <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #475569; font-size: 13px;">Justification / Reason <span style="color: #ef4444;">*</span></label>
                    <textarea name="justification" required rows="4" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Poppins', sans-serif; resize: vertical;" placeholder="Why do you need to hire for this position? e.g. Replacing staff, expanding team..."></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="button" onclick="document.getElementById('requisitionModal').style.display='none'" style="padding: 10px 16px; background: white; border: 1px solid #cbd5e1; color: #475569; border-radius: 8px; font-weight: 600; cursor: pointer;">Cancel</button>
                    <button type="submit" style="padding: 10px 16px; background: #2563eb; border: none; color: white; border-radius: 8px; font-weight: 600; cursor: pointer;"><i class="fa-solid fa-paper-plane"></i> Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

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