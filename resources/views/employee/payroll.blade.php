<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Payroll - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    main { padding:2rem; }
    .breadcrumb { font-size:.85rem; color:#94a3b8; margin-bottom:1rem; }
    h2 { color:#6366f1; margin:0 0 .4rem 0; }
    .subtitle { color:#64748b; margin-bottom:1.2rem; }
    .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px; }
    .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:14px; box-shadow:0 8px 20px rgba(15,23,42,.06); }
    .label { font-size:12px; color:#94a3b8; text-transform:uppercase; letter-spacing:.02em; }
    .value { font-size:22px; font-weight:700; color:#0f172a; }
    table { width:100%; border-collapse:collapse; }
    thead { background:#0f172a; color:#c4b5fd; }
    th, td { padding:12px 14px; border-bottom:1px solid #e5e7eb; text-align:left; }
    tbody tr:hover { background:#f8fafc; }
    .pill { display:inline-flex; align-items:center; gap:6px; background:#eef2ff; color:#4338ca; border-radius:999px; padding:6px 10px; font-size:.85rem; }
    .btn-view { padding:6px 12px; font-size:13px; border-radius:8px; background:#6366f1; color:#fff; border:none; cursor:pointer; text-decoration:none; display:inline-block; }
    .btn-view:hover { background:#4f46e5; color:#fff; }
    .payroll-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.4); display:none; align-items:center; justify-content:center; z-index:1000; padding:20px; }
    .payroll-overlay.show { display:flex; }
    .payroll-detail-card { background:#fff; border-radius:14px; box-shadow:0 20px 50px rgba(0,0,0,0.2); max-width:520px; width:100%; max-height:90vh; overflow:auto; }
    .payroll-detail-card .card-head { padding:16px 20px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center; }
    .payroll-detail-card .card-head h3 { margin:0; font-size:1.1rem; color:#0f172a; }
    .payroll-detail-card .card-close { background:none; border:none; font-size:1.4rem; color:#64748b; cursor:pointer; padding:0 4px; line-height:1; }
    .payroll-detail-card .card-body { padding:20px; }
    .payroll-detail-card .detail-row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f1f5f9; }
    .payroll-detail-card .detail-row:last-child { border-bottom:none; }
    .payroll-detail-card .detail-section { font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:#64748b; margin:14px 0 8px 0; }
    .payroll-detail-card .detail-section:first-child { margin-top:0; }
    .payroll-detail-card .total-row { font-weight:700; font-size:1.05rem; margin-top:10px; padding-top:10px; border-top:2px solid #e5e7eb; }
    .payroll-detail-card .line-items { font-size:13px; }
    .payroll-detail-card .line-items th, .payroll-detail-card .line-items td { padding:6px 8px; text-align:left; }
    .payroll-detail-card .line-items .num { text-align:right; }
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
      <div class="breadcrumb">Payroll · Payslips & Tax</div>
      <h2>My Payroll</h2>
      <p class="subtitle">Download payslips and review tax withholdings.</p>

      <div class="grid" style="margin-bottom:16px;">
        <div class="card">
          <div class="label">Last Net Pay</div>
          <div class="value">{{ $lastNetPay !== null ? 'RM ' . number_format($lastNetPay, 2) : '—' }}</div>
          @if($lastPayDate)
            <span class="pill"><i class="fa-solid fa-calendar"></i> {{ $lastPayDate }}</span>
          @else
            <span class="pill muted">No payslips yet</span>
          @endif
        </div>
        <div class="card">
          <div class="label">YTD Gross</div>
          <div class="value">{{ isset($ytdGross) ? 'RM ' . number_format($ytdGross, 2) : '—' }}</div>
        </div>
        <div class="card">
          <div class="label">YTD Tax Withheld</div>
          <div class="value">{{ isset($ytdTax) ? 'RM ' . number_format($ytdTax, 2) : '—' }}</div>
        </div>
      </div>

      <div class="card" style="margin-bottom:16px;">
        <div class="label" style="margin-bottom:8px;">Recent Payslips</div>
        <table>
          <thead>
            <tr><th>Period</th><th>Gross</th><th>Net</th><th>Status</th><th>Action</th></tr>
          </thead>
          <tbody>
            @forelse(($recentPayslips ?? []) as $row)
              <tr data-period="{{ $row['period_month'] ?? '' }}">
                <td>{{ $row['period_label'] }}</td>
                <td>{{ isset($row['gross']) && $row['gross'] !== null ? 'RM ' . number_format($row['gross'], 2) : '—' }}</td>
                <td>{{ isset($row['net']) && $row['net'] !== null ? 'RM ' . number_format($row['net'], 2) : '—' }}</td>
                <td>{{ $row['status'] }}</td>
                <td style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                  @if(isset($row['gross']) && $row['gross'] !== null)
                    <button type="button" class="btn-view js-payroll-view" data-period="{{ $row['period_month'] ?? '' }}">View</button>
                  @endif
                  @if(!empty($row['payslip_id']))
                    <a href="{{ route('employee.payroll.download', ['payslip' => $row['payslip_id']]) }}" class="btn btn-secondary btn-small">Download</a>
                  @endif
                  @if((!isset($row['gross']) || $row['gross'] === null) && empty($row['payslip_id']))
                    <span class="muted">—</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="muted">No payroll periods released yet. Released periods will appear here.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="card">
        <div class="label" style="margin-bottom:8px;">Tax Documents</div>
        <table>
          <thead>
            <tr><th>Year</th><th>Form</th><th>Status</th><th>Action</th></tr>
          </thead>
          <tbody>
            @forelse(($taxDocuments ?? []) as $doc)
              <tr>
                <td>{{ $doc['year'] }}</td>
                <td>{{ $doc['form'] }}</td>
                <td>{{ $doc['status'] }}</td>
                <td><a href="{{ route('employee.payroll.tax.download', $doc['year']) }}" class="btn btn-secondary btn-small">Download</a></td>
              </tr>
            @empty
              <tr><td colspan="4" class="muted">No tax documents yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </main>
  </div>

  <div class="payroll-overlay" id="payrollDetailOverlay" aria-hidden="true">
    <div class="payroll-detail-card">
      <div class="card-head">
        <h3 id="payrollDetailTitle">Payroll details</h3>
        <button type="button" class="card-close" id="payrollDetailClose" aria-label="Close">&times;</button>
      </div>
      <div class="card-body" id="payrollDetailBody">
        <div class="muted" style="text-align:center; padding:2rem;">Loading…</div>
      </div>
    </div>
  </div>

  <script>
  (function() {
    const overlay = document.getElementById('payrollDetailOverlay');
    const titleEl = document.getElementById('payrollDetailTitle');
    const bodyEl = document.getElementById('payrollDetailBody');
    const closeBtn = document.getElementById('payrollDetailClose');
    const detailUrl = '{{ route("employee.payroll.detail") }}';

    function formatMoney(n) {
      return n != null ? 'RM ' + Number(n).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';
    }

    function renderDetail(data) {
      const b = data.breakdown || {};
      let html = '<div class="detail-section">Summary</div>';
      html += '<div class="detail-row"><span>Gross pay</span><span><strong>' + formatMoney(data.gross) + '</strong></span></div>';
      html += '<div class="detail-row"><span>Total deductions</span><span>- ' + formatMoney(b.total_deductions) + '</span></div>';
      html += '<div class="detail-row total-row"><span>Net pay</span><span>' + formatMoney(data.net) + '</span></div>';

      if (data.line_items && data.line_items.length) {
        html += '<div class="detail-section">Line items</div>';
        html += '<table class="line-items" style="width:100%; border-collapse:collapse;"><thead><tr><th>Type</th><th>Description</th><th class="num">Amount</th></tr></thead><tbody>';
        data.line_items.forEach(function(item) {
          const amt = item.amount != null ? (item.item_type === 'DEDUCTION' ? '- ' + formatMoney(item.amount) : formatMoney(item.amount)) : '—';
          html += '<tr><td>' + (item.item_type || '') + '</td><td>' + (item.description || item.code || '') + '</td><td class="num">' + amt + '</td></tr>';
        });
        html += '</tbody></table>';
      }

      bodyEl.innerHTML = html;
    }

    document.querySelectorAll('.js-payroll-view').forEach(function(btn) {
      btn.addEventListener('click', function() {
        const period = btn.getAttribute('data-period');
        if (!period) return;
        overlay.classList.add('show');
        overlay.setAttribute('aria-hidden', 'false');
        titleEl.textContent = 'Payroll details — ' + (period ? new Date(period + '-01').toLocaleDateString('en-GB', { month: 'long', year: 'numeric' }) : '');
        bodyEl.innerHTML = '<div class="muted" style="text-align:center; padding:2rem;">Loading…</div>';

        fetch(detailUrl + '?period_month=' + encodeURIComponent(period), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
          .then(function(r) {
            return r.json().then(function(json) {
              if (!r.ok) {
                bodyEl.innerHTML = '<p class="muted">' + (json.message || 'No details for this period.') + '</p>';
                return;
              }
              if (json.message && (json.message.includes('not found') || json.message.includes('No payroll'))) {
                bodyEl.innerHTML = '<p class="muted">' + (json.message || 'No details for this period.') + '</p>';
                return;
              }
              renderDetail(json);
            });
          })
          .catch(function() {
            bodyEl.innerHTML = '<p class="muted">Could not load details. Please try again.</p>';
          });
      });
    });

    function closeOverlay() {
      overlay.classList.remove('show');
      overlay.setAttribute('aria-hidden', 'true');
    }
    closeBtn.addEventListener('click', closeOverlay);
    overlay.addEventListener('click', function(e) { if (e.target === overlay) closeOverlay(); });
  })();
  </script>
</body>
</html>
