<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OT Claims - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    main { padding:2rem; }
    .card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:16px; box-shadow:0 8px 18px rgba(15,23,42,0.08); margin-bottom:16px; }
    .breadcrumb { color:#94a3b8; margin-bottom:8px; }
    .subtitle { color:#64748b; margin-bottom:1rem; }
    .table-wrap { overflow-x:auto; }
    table { width:100%; border-collapse:collapse; margin-top:10px; }
    th, td { padding:10px 12px; border-bottom:1px solid #e5e7eb; text-align:left; }
    thead { background:#0f172a; color:#38bdf8; }
    .status { padding:4px 10px; border-radius:999px; font-size:0.85rem; font-weight:700; display:inline-block; }
    .status.approved { background:#dcfce7; color:#166534; }
    .status.rejected { background:#fee2e2; color:#991b1b; }
    .status.pending { background:#fef9c3; color:#854d0e; }
    .notice { padding:10px 14px; border-radius:10px; margin-bottom:12px; }
    .notice.success { background:#dcfce7; color:#166534; }
    .notice.error { background:#fee2e2; color:#991b1b; }
    .payout-cell { font-weight:600; color:#0f172a; }
    .btn-payroll { padding:6px 10px; font-size:12px; border-radius:8px; background:#0ea5e9; color:#fff; border:none; cursor:pointer; display:inline-flex; align-items:center; gap:4px; text-decoration:none; }
    .btn-payroll:hover { background:#0284c7; color:#fff; }
    .overlay { position:fixed; inset:0; background:rgba(15,23,42,0.5); display:none; align-items:center; justify-content:center; z-index:1000; }
    .overlay.open { display:flex; }
    .payroll-card { width:100%; max-width:380px; background:#fff; border-radius:14px; box-shadow:0 20px 40px rgba(0,0,0,0.15); padding:0; overflow:hidden; }
    .payroll-card-header { background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color:#fff; padding:18px 20px; }
    .payroll-card-header h3 { margin:0; font-size:1rem; font-weight:600; display:flex; align-items:center; gap:8px; }
    .payroll-card-body { padding:20px; font-size:14px; }
    .payroll-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #e5e7eb; }
    .payroll-row:last-child { border-bottom:none; }
    .payroll-row .label { color:#64748b; }
    .payroll-row .value { font-weight:600; color:#0f172a; }
    .payroll-total { margin-top:12px; padding-top:12px; border-top:2px solid #0ea5e9; font-size:1.1rem; font-weight:700; color:#0f172a; display:flex; justify-content:space-between; align-items:center; }
    .payroll-card-footer { padding:14px 20px; background:#f8fafc; border-top:1px solid #e5e7eb; text-align:right; }
    .payroll-card-footer .btn-sm { padding:8px 14px; border-radius:8px; border:none; cursor:pointer; background:#e5e7eb; color:#374151; font-size:13px; }
  </style>
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info"><span><i class="fa-regular fa-bell"></i> &nbsp; <a href="{{ route('employee.profile') }}" style="color:inherit; text-decoration:none;">{{ Auth::user()->name ?? 'Employee' }}</a></span></div>
  </header>
  <div class="container">
    @include('employee.layout.sidebar')
    <main>
      <div class="breadcrumb">Attendance · OT Claims</div>
      <h2 style="margin:0 0 .3rem 0; color:#0ea5e9;">OT Claims</h2>
      <p class="subtitle">Submit and track overtime claims (Supervisor → Admin approval).</p>

      @if(session('success'))
        <div class="notice success">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div class="notice error">{{ session('error') }}</div>
      @endif

      <div class="card" style="margin-bottom:16px;">
        <a href="{{ route('employee.ot_claims.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Claim OT</a>
      </div>

      <div class="card">
        <h3 style="margin:0 0 10px 0;">My OT Claims</h3>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Date</th>
                <th>Hours</th>
                <th>Status</th>
                <th>Payout</th>
                <th>Submitted</th>
                <th>Supervisor remark</th>
                <th>Admin remark</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($claims as $c)
                @php
                  $statusClass = 'pending';
                  if ($c->status === \App\Models\OvertimeClaim::STATUS_ADMIN_APPROVED || $c->status === \App\Models\OvertimeClaim::STATUS_SUPERVISOR_APPROVED || $c->status === \App\Models\OvertimeClaim::STATUS_ADMIN_PENDING) {
                    $statusClass = 'approved';
                  } elseif (in_array($c->status, [\App\Models\OvertimeClaim::STATUS_SUPERVISOR_REJECTED, \App\Models\OvertimeClaim::STATUS_ADMIN_REJECTED], true)) {
                    $statusClass = 'rejected';
                  }
                  $breakdown = $c->hasApprovedPayroll() ? $c->getPayoutBreakdown() : null;
                @endphp
                <tr class="js-claim-row"
                  @if($breakdown)
                    data-payout-date="{{ $breakdown['date'] }}"
                    data-payout-hours="{{ $breakdown['hours'] }}"
                    data-payout-hourly="{{ $breakdown['hourly_rate'] }}"
                    data-payout-multiplier="{{ $breakdown['multiplier'] }}"
                    data-payout-total="{{ $breakdown['payout'] }}"
                  @endif
                >
                  <td>{{ $loop->iteration }}</td>
                  <td>{{ $c->date?->format('Y-m-d') }}</td>
                  <td>{{ number_format($c->getEffectiveApprovedHours(), 2) }}</td>
                  <td><span class="status {{ $statusClass }}">{{ $c->status }}</span></td>
                  <td class="payout-cell">
                    @if($c->hasApprovedPayroll())
                      {{ number_format($c->getPayout(), 2) }}
                    @else
                      —
                    @endif
                  </td>
                  <td>{{ $c->submitted_at ? $c->submitted_at->format('M j, Y H:i') : '—' }}</td>
                  <td>{{ $c->supervisor_remark ? Str::limit($c->supervisor_remark, 30) : '—' }}</td>
                  <td>{{ $c->admin_remark ? Str::limit($c->admin_remark, 30) : '—' }}</td>
                  <td>
                    @if($c->hasApprovedPayroll())
                      <button type="button" class="btn-payroll js-view-payroll" title="View payroll card"><i class="fa-solid fa-wallet"></i> View payroll</button>
                    @elseif($c->isEditableByEmployee())
                      <a href="{{ route('employee.ot_claims.edit', $c) }}" class="btn btn-secondary btn-small">Edit</a>
                    @elseif($c->isCancellableByEmployee())
                      <form method="POST" action="{{ route('employee.ot_claims.cancel', $c) }}" style="display:inline;" onsubmit="return confirm('Cancel this claim?');">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-small">Cancel</button>
                      </form>
                    @else
                      —
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="9" style="text-align:center; color:#94a3b8;">No OT claims yet. <a href="{{ route('employee.ot_claims.create') }}">Claim OT</a></td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  {{-- Payroll card modal (for approved claims) --}}
  <div class="overlay" id="payrollCardOverlay" aria-hidden="true">
    <div class="payroll-card" role="dialog" aria-labelledby="payrollCardTitle">
      <div class="payroll-card-header">
        <h3 id="payrollCardTitle"><i class="fa-solid fa-wallet"></i> OT Payroll</h3>
      </div>
      <div class="payroll-card-body">
        <div class="payroll-row"><span class="label">Date</span><span class="value" id="pc-date">—</span></div>
        <div class="payroll-row"><span class="label">Approved hours</span><span class="value" id="pc-hours">—</span></div>
        <div class="payroll-row"><span class="label">Hourly rate</span><span class="value" id="pc-hourly">—</span></div>
        <div class="payroll-row"><span class="label">OT rate (multiplier)</span><span class="value" id="pc-multiplier">—</span></div>
        <div class="payroll-total"><span>Total payout</span><span id="pc-total">—</span></div>
      </div>
      <div class="payroll-card-footer">
        <button type="button" class="btn-sm" id="payrollCardClose">Close</button>
      </div>
    </div>
  </div>

  <script>
  (function() {
    var overlay = document.getElementById('payrollCardOverlay');
    if (!overlay) return;
    function openCard() {
      var row = this.closest('.js-claim-row');
      if (!row || !row.getAttribute('data-payout-total')) return;
      document.getElementById('pc-date').textContent = row.getAttribute('data-payout-date') || '—';
      document.getElementById('pc-hours').textContent = row.getAttribute('data-payout-hours') + ' h';
      document.getElementById('pc-hourly').textContent = row.getAttribute('data-payout-hourly');
      document.getElementById('pc-multiplier').textContent = row.getAttribute('data-payout-multiplier') + '×';
      document.getElementById('pc-total').textContent = row.getAttribute('data-payout-total');
      overlay.classList.add('open');
      overlay.setAttribute('aria-hidden', 'false');
    }
    function closeCard() {
      overlay.classList.remove('open');
      overlay.setAttribute('aria-hidden', 'true');
    }
    document.querySelectorAll('.js-view-payroll').forEach(function(btn) {
      btn.addEventListener('click', openCard);
    });
    document.getElementById('payrollCardClose').addEventListener('click', closeCard);
    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) closeCard();
    });
  })();
  </script>
</body>
</html>