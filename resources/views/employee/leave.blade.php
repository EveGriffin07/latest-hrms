<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Leave - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    body { background:#f6f8fb; }
    main { padding:28px; }
    .breadcrumb { font-size:.9rem; color:#94a3b8; margin-bottom:.4rem; letter-spacing:.01em; }
    h2 { color:#16a34a; margin:0 0 .2rem 0; }
    .subtitle { color:#64748b; margin:0 0 1.2rem 0; }
    .kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px; margin-bottom:18px; }
    .kpi { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:14px 16px; box-shadow:0 10px 25px rgba(15,23,42,.06); }
    .kpi-label { font-size:12px; letter-spacing:.02em; text-transform:uppercase; color:#94a3b8; }
    .kpi-value { font-size:28px; font-weight:700; color:#0f172a; }
    .kpi-sub { color:#94a3b8; font-size:.9rem; }
    .card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:16px; box-shadow:0 10px 25px rgba(15,23,42,.06); margin-bottom:16px; }
    .card header { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
    .card-title { font-weight:700; color:#0f172a; letter-spacing:.01em; }
    label { font-weight:600; color:#0f172a; font-size:0.95rem; display:block; margin-bottom:6px; }
    input, select, textarea { width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:10px; font-size:0.95rem; background:#fff; }
    textarea { min-height:120px; resize:vertical; }
    .form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:12px; }
    .muted { color:#64748b; font-size:0.9rem; }
    .error { color:#b91c1c; font-size:0.9rem; margin-top:6px; min-height:16px; }
    .btn { display:inline-flex; gap:8px; align-items:center; padding:10px 14px; border-radius:10px; border:1px solid transparent; cursor:pointer; font-weight:600; }
    .btn-primary { background:#2563eb; color:#fff; border-color:#2563eb; box-shadow:0 10px 20px rgba(37,99,235,0.18); }
    .btn-primary:disabled { opacity:.6; cursor:not-allowed; }
    .status { padding:4px 10px; border-radius:999px; font-size:0.85rem; font-weight:700; display:inline-block; }
    .pending, .supervisor_approved, .pending_admin { background:#fef9c3; color:#854d0e; }
    .approved { background:#dcfce7; color:#166534; }
    .rejected, .cancelled { background:#fee2e2; color:#991b1b; }
    .table-wrap { overflow-x:auto; }
    table { width:100%; border-collapse:collapse; }
    th, td { padding:12px 14px; border-bottom:1px solid #e5e7eb; text-align:left; }
    thead { background:#0f172a; color:#22c55e; }
    .pill { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; background:#ecfdf3; color:#15803d; font-weight:600; font-size:0.9rem; }
    .pending-strip { margin-bottom:16px; }
    .pending-list { margin:0; padding-left:20px; color:#475569; font-size:0.9rem; }
    .pending-list li { margin-bottom:4px; }
  </style>
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
      <span><i class="fa-regular fa-bell"></i> &nbsp; <a href="{{ route('employee.profile') }}" style="color:inherit; text-decoration:none;">{{ Auth::user()->name ?? 'Employee' }}</a></span>
    </div>
  </header>

  <div class="container">
    @include('employee.layout.sidebar')

    <main>
      <div class="breadcrumb">Leave - Apply / Balance / History</div>
      <h2>My Leave</h2>
      <p class="subtitle">Submit a new request, review balances, and track approvals.</p>

      @if(session('success'))
        <div class="notice success">{{ session('success') }}</div>
      @endif
      @if($errors->any())
        <div class="notice error">{{ $errors->first() }}</div>
      @endif

      <div class="kpi-grid">
        <div class="kpi">
          <div class="kpi-label">Leave Requests</div>
          <div class="kpi-value">{{ $summary['total'] ?? 0 }}</div>
          <div class="kpi-sub">Submit a request below.</div>
        </div>
        <div class="kpi">
          <div class="kpi-label">Pending Requests</div>
          <div class="kpi-value">{{ $summary['pending'] ?? 0 }}</div>
          @if(($summary['pending'] ?? 0) > 0)
            <div class="kpi-sub">Awaiting approval</div>
          @endif
        </div>
        <div class="kpi">
          <div class="kpi-label">Approved</div>
          <div class="kpi-value">{{ $summary['approved'] ?? 0 }}</div>
        </div>
        @foreach($balances as $bal)
          <div class="kpi">
            <div class="kpi-label">{{ $bal['name'] }} — Remaining</div>
            <div class="kpi-value">{{ $bal['remaining'] }} <span style="font-size:0.6em; font-weight:500; color:#64748b;">days</span></div>
            <div class="kpi-sub">Used: {{ $bal['used'] }} · Pending: {{ $bal['pending'] }} · Entitlement: {{ $bal['total'] }}</div>
          </div>
        @endforeach
      </div>

      @if(isset($pendingRequests) && $pendingRequests->isNotEmpty())
      <div class="card pending-strip">
        <div class="card-title" style="margin-bottom:8px;"><i class="fa-solid fa-clock"></i> Pending requests</div>
        <ul class="pending-list">
          @foreach($pendingRequests as $req)
            <li>{{ $req->leaveType->leave_name ?? 'Leave' }}: {{ $req->start_date?->format('M j') }} – {{ $req->end_date?->format('M j, Y') }} ({{ $req->total_days }} day{{ $req->total_days === 1 ? '' : 's' }})</li>
          @endforeach
        </ul>
      </div>
      @endif

      <div class="card">
        <header>
          <div class="card-title">Apply for Leave</div>
          <div class="pill"><i class="fa-solid fa-circle-info"></i> Inclusive of start & end dates</div>
        </header>
        <form id="leave-form" class="form" method="POST" action="{{ route('employee.leave.store') }}" enctype="multipart/form-data" novalidate>
          @csrf
          <div class="form-grid">
            <div>
              <label for="leave_type_id">Type</label>
              <select id="leave_type_id" name="leave_type_id" required>
                <option value="" disabled {{ old('leave_type_id') ? '' : 'selected' }}>Select type</option>
                @foreach($leaveTypes as $type)
                  <option value="{{ $type->leave_type_id }}"
                    data-proof-requirement="{{ $type->proof_requirement ?? 'none' }}"
                    data-proof-label="{{ $type->getProofLabel() }}"
                    {{ old('leave_type_id') == $type->leave_type_id ? 'selected' : '' }}>
                    {{ $type->leave_name }}
                  </option>
                @endforeach
              </select>
              <div class="error" data-err="leave_type_id"></div>
            </div>
            <div>
              <label for="start_date">Start Date</label>
              <input type="date" id="start_date" name="start_date" value="{{ old('start_date', now()->format('Y-m-d')) }}" required>
              <div class="error" data-err="start_date"></div>
            </div>
            <div>
              <label for="end_date">End Date</label>
              <input type="date" id="end_date" name="end_date" value="{{ old('end_date', now()->format('Y-m-d')) }}" required>
              <div class="error" data-err="end_date"></div>
            </div>
            <div>
              <label for="total_days_display">Total Days (auto)</label>
              <input type="text" id="total_days_display" value="-" readonly>
            </div>
          </div>
          <div style="margin-top:12px;">
            <label for="reason">Reason / Notes (optional)</label>
            <textarea id="reason" name="reason" placeholder="Reason / Notes" maxlength="500">{{ old('reason') }}</textarea>
            <div class="error" data-err="reason"></div>
          </div>
          <div id="proof-field-wrap" style="margin-top:12px; display:none;">
            <label for="proof" id="proof-label">Supporting document</label>
            <input type="file" id="proof" name="proof" accept=".pdf,.jpg,.jpeg,.png" style="padding:8px 0;">
            <div class="error" data-err="proof"></div>
          </div>
          <div style="margin-top:14px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary" id="submit-btn"><i class="fa-solid fa-paper-plane"></i> Submit Request</button>
            <span class="muted">Status starts as Pending. Total days is inclusive of start and end dates.</span>
          </div>
        </form>
      </div>

      <div class="card">
        <div class="card-title" style="margin-bottom:10px;">Recent Requests</div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Date Range</th>
                <th>Type</th>
                <th>Days</th>
                <th>Status</th>
                <th>Reason</th>
                <th>Submitted</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($requests as $request)
                <tr>
                  <td>{{ $request->start_date?->format('Y-m-d') }} → {{ $request->end_date?->format('Y-m-d') }}</td>
                  <td>{{ $request->leaveType->leave_name ?? 'N/A' }}</td>
                  <td>{{ $request->total_days }} {{ $request->total_days == 1 ? 'day' : 'days' }}</td>
                  <td>
                    <span class="status {{ $request->leave_status }}">{{ $request->getStatusLabel() }}</span>
                    @if($request->leave_status === 'rejected' && $request->reject_reason)
                      <div class="muted">Reason: {{ $request->reject_reason }}</div>
                    @endif
                  </td>
                  <td>{{ $request->reason ?? 'N/A' }}</td>
                  <td>{{ $request->created_at?->format('Y-m-d H:i') ?? 'N/A' }}</td>
                  <td>
                    @if($request->leave_status === \App\Models\LeaveRequest::STATUS_PENDING)
                      <form method="POST" action="{{ route('employee.leave.cancel', $request) }}" onsubmit="return confirm('Cancel this pending request?');">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-small" style="background:#ef4444;border-color:#ef4444;">Cancel</button>
                      </form>
                    @else
                      <span class="muted">—</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" style="text-align:center; color:#94a3b8;">No leave requests yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const form = document.getElementById('leave-form');
      const start = document.getElementById('start_date');
      const end   = document.getElementById('end_date');
      const total = document.getElementById('total_days_display');
      const type  = document.getElementById('leave_type_id');
      const proofWrap = document.getElementById('proof-field-wrap');
      const proofInput = document.getElementById('proof');
      const proofLabel = document.getElementById('proof-label');
      const errEls = {
        leave_type_id: document.querySelector('[data-err="leave_type_id"]'),
        start_date: document.querySelector('[data-err="start_date"]'),
        end_date: document.querySelector('[data-err="end_date"]'),
        reason: document.querySelector('[data-err="reason"]'),
        proof: document.querySelector('[data-err="proof"]'),
      };

      const setError = (field, msg) => {
        if (errEls[field]) errEls[field].textContent = msg || '';
      };

      function updateProofField() {
        const opt = type.options[type.selectedIndex];
        if (!opt || !opt.value) {
          proofWrap.style.display = 'none';
          proofInput.removeAttribute('required');
          proofInput.value = '';
          setError('proof', '');
          return;
        }
        const req = (opt.dataset.proofRequirement || 'none').toLowerCase();
        const label = opt.dataset.proofLabel || 'Supporting document';
        proofLabel.textContent = label;
        if (req === 'none') {
          proofWrap.style.display = 'none';
          proofInput.removeAttribute('required');
          proofInput.value = '';
          setError('proof', '');
        } else {
          proofWrap.style.display = 'block';
          if (req === 'required') {
            proofInput.setAttribute('required', 'required');
          } else {
            proofInput.removeAttribute('required');
          }
        }
      }
      type.addEventListener('change', updateProofField);
      updateProofField();

      const calc = () => {
        setError('start_date', '');
        setError('end_date', '');
        if (!start.value || !end.value) { total.value = '-'; return; }
        const s = new Date(start.value);
        const e = new Date(end.value);
        if (isNaN(s) || isNaN(e)) { total.value = '-'; return; }
        if (e < s) {
          total.value = '-';
          setError('end_date', 'End date cannot be before start date.');
          return;
        }
        const diff = Math.round((e - s) / 86400000) + 1; // inclusive days
        total.value = `${diff} day${diff === 1 ? '' : 's'}`;
      };

      start.addEventListener('change', () => { end.min = start.value; calc(); });
      end.addEventListener('change', calc);
      type.addEventListener('change', () => setError('leave_type_id', ''));

      form.addEventListener('submit', (e) => {
        let ok = true;
        setError('leave_type_id',''); setError('start_date',''); setError('end_date',''); setError('proof','');

        if (!type.value) { setError('leave_type_id', 'Please select a leave type.'); ok = false; }
        if (!start.value) { setError('start_date', 'Start date is required.'); ok = false; }
        if (!end.value)   { setError('end_date', 'End date is required.'); ok = false; }
        if (start.value && end.value) {
          const s = new Date(start.value); const e2 = new Date(end.value);
          if (e2 < s) { setError('end_date', 'End date cannot be before start date.'); ok = false; }
        }
        const opt = type.options[type.selectedIndex];
        if (opt && opt.value && (opt.dataset.proofRequirement || '').toLowerCase() === 'required' && !proofInput.files.length) {
          setError('proof', 'Proof document is required for this leave type.');
          ok = false;
        }
        if (!ok) { e.preventDefault(); return; }
      });

      calc();
    });
  </script>
</body>
</html>
