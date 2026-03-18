<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Salary Calculation - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">

  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    body { background:#f5f7fb; }
    main { padding:28px 32px; }
    .card {
      background:#fff;
      border:1px solid #e5e7eb;
      border-radius:12px;
      padding:16px;
      margin-bottom:16px;
      box-shadow:0 12px 28px rgba(15,23,42,0.08);
    }
    h2 { margin-bottom:4px; }
    .subtitle { color:#6b7280; }
    .grid { display:flex; gap:12px; flex-wrap:wrap; }
    .grid > * { flex:1 1 220px; }
    label { display:block; margin-bottom:6px; font-weight:600; color:#0f172a; }
    input, select, button {
      width:100%;
      padding:10px 12px;
      border:1px solid #d1d5db;
      border-radius:10px;
      background:#fff;
      font-size:14px;
    }
    button { cursor:pointer; font-weight:700; }
    .btn-primary { background:#1f78f0; color:#fff; border-color:#1f78f0; box-shadow:0 10px 20px rgba(31,120,240,0.25); }
    .btn-ghost { background:#fff; color:#1f2937; }
    table { width:100%; border-collapse:collapse; background:#fff; }
    th, td { padding:12px 14px; border-bottom:1px solid #e5e7eb; text-align:left; }
    thead th { background:#f8fafc; color:#0f172a; }
    .num { text-align:right; }
    .muted { color:#6b7280; font-size:13px; }
    .chip { display:inline-block; padding:6px 10px; background:#e0f2fe; color:#0c4a6e; border-radius:999px; font-weight:600; }
    .modal { position:fixed; inset:0; background:rgba(0,0,0,0.45); display:none; align-items:center; justify-content:center; }
    .sheet { background:#fff; border-radius:12px; padding:18px; width:min(760px,92vw); box-shadow:0 16px 40px rgba(0,0,0,0.25); }
    .status-card { display:flex; flex-direction:column; gap:10px; }
    .status-badge { display:inline-flex; align-items:center; gap:10px; padding:10px 14px; border-radius:14px; font-weight:800; letter-spacing:0.04em; text-transform:uppercase; font-size:13px; }
    .badge-open { background:#ecfdf3; color:#166534; }
    .badge-draft { background:#e0f2fe; color:#0c4a6e; }
    .badge-locked { background:#fff7ed; color:#9a3412; }
    .badge-paid { background:#eef2ff; color:#3730a3; }
    .badge-published { background:#f3e8ff; color:#6b21a8; }
    .status-helper { color:#475569; font-size:13px; line-height:1.35; }
    .meta-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:10px; }
    .meta-item { background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px; }
    .meta-label { font-size:12px; text-transform:uppercase; color:#94a3b8; letter-spacing:0.05em; margin-bottom:4px; }
    .meta-value { font-weight:700; color:#0f172a; }
    .meta-sub { margin-top:2px; }
    .section-disabled { opacity:0.55; pointer-events:none; }
    #adjustments-card.section-disabled .adj-toggle-btn { pointer-events:auto; opacity:1; }
    .insights { display:grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap:10px; margin-bottom:10px; }
    .insight-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:12px; box-shadow:0 6px 16px rgba(15,23,42,0.05); cursor:pointer; transition:all .15s ease; }
    .insight-card:hover { box-shadow:0 10px 24px rgba(15,23,42,0.08); transform:translateY(-1px); }
    .insight-count { font-size:22px; font-weight:800; color:#0f172a; }
    .insight-label { color:#475569; font-weight:600; }
    .insight-card.active { border-color:#2563eb; box-shadow:0 12px 28px rgba(37,99,235,0.18); }
    .release-window-msg { margin:0; font-size:13px; font-weight:600; color:#b91c1c; }
    .confirm-box { background:#fff; border-radius:12px; padding:18px; width:min(520px, 92vw); box-shadow:0 16px 40px rgba(0,0,0,0.22); }
    .confirm-actions { display:flex; gap:10px; justify-content:flex-end; margin-top:14px; flex-wrap:wrap; }
    .detail-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px; }
    .detail-tab { border:none; border-radius:10px; padding:10px 14px; background:#f1f5f9; color:#475569; font-weight:700; display:flex; align-items:center; gap:8px; cursor:pointer; transition:all .15s ease; flex:1 1 140px; justify-content:center; }
    .detail-tab.active { background:#1f78f0; color:#fff; box-shadow:0 10px 22px rgba(31,120,240,0.25); }
    .detail-tab:hover { transform:translateY(-1px); }
    .tab-panel { display:none; }
    .tab-panel.active { display:block; }
    .salary-detail-header { margin-bottom:12px; padding-bottom:12px; border-bottom:1px solid #e2e8f0; }
    .salary-detail-header .emp-name { font-size:1.1rem; font-weight:700; color:#0f172a; }
    .salary-detail-header .emp-meta { font-size:0.9rem; color:#64748b; margin-top:2px; }
    .salary-detail-formula { background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px 14px; margin-bottom:14px; font-size:0.85rem; color:#475569; line-height:1.5; }
    .salary-detail-formula strong { color:#0f172a; }
    .salary-detail-formula .scope { margin-top:8px; font-size:0.8rem; color:#94a3b8; }
    .salary-detail-section { margin-bottom:18px; }
    .salary-detail-section:last-child { margin-bottom:0; }
    .salary-detail-section-title { font-size:0.8rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:8px; padding-bottom:4px; }
    .info-grid .info-row { display:grid; grid-template-columns:1fr 1.2fr; gap:10px 16px; align-items:baseline; padding:8px 0; border-bottom:1px solid #f1f5f9; font-size:0.9rem; }
    .info-grid .info-row:last-child { border-bottom:none; }
    .info-grid .info-row .info-label { color:#475569; font-weight:600; }
    .info-grid .info-row .info-value { color:#0f172a; word-break:break-word; }
    .info-grid .info-row.highlight .info-value { font-weight:700; color:#0f172a; }
    .info-grid .info-row.formula-row { background:#fefce8; border-radius:8px; padding:10px 12px; margin-top:6px; border:1px solid #fef08a; }
    .info-grid .info-row.formula-row .info-value { font-size:0.85rem; }
    .sheet { max-width:560px; }
    @media (max-width: 640px) {
      .detail-tabs { flex-direction:column; }
      .detail-tab { justify-content:space-between; }
      .tab-panel { display:none; }
      .tab-panel.active { display:block; }
      .info-grid .info-row { grid-template-columns:1fr; }
    }
  </style>
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
    <a href="{{ route('admin.profile') }}" style="text-decoration: none; color: inherit;">
        <i class="fa-regular fa-bell"></i> &nbsp; HR Admin
    </a>
</div>
  </header>

  <div class="container">
    @include('admin.layout.sidebar')

    <main>
      <div class="breadcrumb">Home > Payroll > Salary Calculation</div>
      <h2>Salary Calculation</h2>
      <p class="subtitle">Gross -> Deductions -> Net, with clear rates, details, and quick adjustments.</p>

      @php
        $status = strtoupper($payrollStatus ?? 'OPEN');
        $statusClass = [
          'OPEN' => 'badge-open',
          'DRAFT' => 'badge-draft',
          'LOCKED' => 'badge-locked',
          'PAID' => 'badge-paid',
          'PUBLISHED' => 'badge-published',
        ][$status] ?? 'badge-open';
      @endphp

      <div class="card status-card">
        <div>
          <div class="muted" style="font-weight:700; letter-spacing:0.04em; text-transform:uppercase; margin-bottom:6px;">Payroll Status</div>
          <span id="payrollStatusBadge" class="status-badge {{ $statusClass }}">{{ $status }}</span>
        </div>
        <div class="status-helper">Each month has its own status. Only the selected month is affected by Generate, Release, or Adjustments.</div>
        @if(in_array($status, ['LOCKED', 'PAID', 'PUBLISHED']))
          <div class="status-helper" style="color:#b91c1c; font-weight:600;">Payroll for this month is locked. Corrections must be applied in a later month.</div>
        @else
          <div class="status-helper">Only DRAFT payroll can be recalculated or adjusted. Release (DRAFT → LOCKED) freezes this month only; other months are unchanged.</div>
        @endif

        @php
          $showLocked    = $status === 'LOCKED' || (!empty($payrollMeta['locked_by']) || !empty($payrollMeta['locked_at']));
          $showPaid      = $status === 'PAID' || !empty($payrollMeta['paid_at']);
          $showPublished = $status === 'PUBLISHED' || !empty($payrollMeta['published_at']);
        @endphp

        <div class="meta-grid">
          <div class="meta-item">
            <div class="meta-label">Created by</div>
            <div class="meta-value">{{ $payrollMeta['created_by'] ?? '--' }}</div>
            <div id="generatedAtText" class="meta-sub muted">Generated at {{ $payrollMeta['generated_at'] ?? '--' }}</div>
          </div>
          @if($showLocked)
            <div class="meta-item">
              <div class="meta-label">Locked by</div>
              <div class="meta-value">{{ $payrollMeta['locked_by'] ?? '--' }}</div>
              <div class="meta-sub muted">Locked at {{ $payrollMeta['locked_at'] ?? '--' }}</div>
            </div>
          @endif
          @if($showPaid)
            <div class="meta-item">
              <div class="meta-label">Paid at</div>
              <div class="meta-value">{{ $payrollMeta['paid_at'] ?? '--' }}</div>
            </div>
          @endif
          @if($showPublished)
            <div class="meta-item">
              <div class="meta-label">Published at</div>
              <div class="meta-value">{{ $payrollMeta['published_at'] ?? '--' }}</div>
            </div>
          @endif
        </div>
      </div>

      <details class="card" style="margin-bottom:1rem;">
        <summary style="cursor:pointer; font-weight:700;">Payroll by month (each month has its own status)</summary>
        <p class="muted" style="margin:8px 0;">Release affects only the selected month. Other months remain unchanged.</p>
        <div style="overflow-x:auto;">
          <table class="table" style="margin-top:8px;">
            <thead>
              <tr>
                <th>Month</th>
                <th>Status</th>
                <th>Generated</th>
                <th>Released at</th>
                <th>Released by</th>
              </tr>
            </thead>
            <tbody>
              @foreach($payrollHistory ?? [] as $row)
                <tr>
                  <td>{{ $row['period_month'] }}</td>
                  <td><span class="status-badge {{ ($row['status'] === 'LOCKED' ? 'badge-locked' : ($row['status'] === 'DRAFT' ? 'badge-draft' : ($row['status'] === 'PAID' ? 'badge-paid' : ($row['status'] === 'PUBLISHED' ? 'badge-published' : 'badge-open')))) }}">{{ strtoupper($row['status']) }}</span></td>
                  <td>{{ $row['generated_at'] }}</td>
                  <td>{{ $row['released_at'] }}</td>
                  <td>{{ $row['released_by'] }}</td>
                </tr>
              @endforeach
              @if(empty($payrollHistory))
                <tr><td colspan="5" class="muted">No payroll periods yet.</td></tr>
              @endif
            </tbody>
          </table>
        </div>
      </details>

      <div class="card">
        <div class="grid" style="align-items:end;">
          <div>
            <label>Payroll Period (Month)</label>
            <select id="period">
              @foreach($periodOptions as $period)
                <option value="{{ $period }}" {{ $period === $currentPeriod ? 'selected' : '' }}>{{ $period }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label>Department</label>
            <select id="dept">
              <option value="">All</option>
              @foreach($departments as $dept)
                <option value="{{ $dept->department_id }}" {{ (string)$dept->department_id === (string)($currentDept ?? '') ? 'selected' : '' }}>{{ $dept->department_name }}</option>
              @endforeach
            </select>
          </div>
          <div id="actionButtonsWrap" style="display:flex; gap:10px; justify-content:flex-end; flex:1 1 auto; flex-wrap:wrap; align-items:center;">
            @if(in_array($status, ['OPEN','DRAFT']) && $status === 'DRAFT')
              <p id="release-window-msg" class="release-window-msg" style="{{ ($releaseWindowClosed ?? false) ? '' : 'display:none;' }}">Payroll release window for this previous month has passed.</p>
            @endif
            <button class="btn-ghost" id="action-lock" title="Release (lock) payroll" {{ ($releaseWindowClosed ?? false) ? 'disabled' : '' }} style="{{ in_array($status, ['OPEN','DRAFT']) ? ($status === 'DRAFT' ? '' : 'display:none;') : 'display:none;' }}">Release Payroll</button>
            <button class="btn-primary" id="action-generate" style="{{ $status === 'OPEN' ? '' : ($status === 'DRAFT' ? '' : 'display:none;') }}">{{ $status === 'OPEN' ? 'Generate Payroll' : ($status === 'DRAFT' ? 'Recalculate Payroll' : '') }}</button>
            @if(in_array($status, ['LOCKED', 'PAID']))
              <button class="btn-primary" id="action-publish">Publish Payslips</button>
            @else
              <div class="muted" style="align-self:center;">Read-only mode.</div>
            @endif
          </div>
        </div>
      </div>

      <div class="insights" id="insightsRow">
        <div class="insight-card" data-filter="absent">
          <div class="insight-count" id="insight-absent">0</div>
          <div class="insight-label">Employees with Absent</div>
        </div>
        <div class="insight-card" data-filter="late">
          <div class="insight-count" id="insight-late">0</div>
          <div class="insight-label">Employees with Late</div>
        </div>
        <div class="insight-card" data-filter="unpaid">
          <div class="insight-count" id="insight-unpaid">0</div>
          <div class="insight-label">Employees with Unpaid Leave</div>
        </div>
        <div class="insight-card" data-filter="incomplete">
          <div class="insight-count" id="insight-incomplete">0</div>
          <div class="insight-label">Employees with Incomplete Punch</div>
        </div>
      </div>

      <div class="card" id="adjustments-card">
        <div class="adj-tool-header">
          <div class="adj-tool-header-row">
            <div>
              <h3 style="margin:0 0 4px;">Payroll corrections</h3>
              <p class="adj-period-tie" id="adj-period-tie">For month: <strong id="adj-period-label">{{ \Carbon\Carbon::createFromFormat('Y-m', $currentPeriod ?? now()->format('Y-m'))->format('F Y') }}</strong></p>
            </div>
            <button type="button" class="adj-toggle-btn" id="adj-toggle-btn" aria-expanded="true" title="Hide adjustments section">Hide adjustments</button>
          </div>
        </div>

        <div class="adj-card-body" id="adj-card-body">
        <div class="adj-form-section">
          <div class="grid">
            <div>
              <label for="adj-emp">Employee</label>
              <select id="adj-emp"><option value="">Select employee</option></select>
            </div>
          </div>

          <div id="adj-summary-box" class="adj-summary-box" style="display:none;">
            <div class="adj-summary-title">Current payroll summary (this month)</div>
            <div class="adj-summary-grid" id="adj-summary-grid"></div>
          </div>

          <div class="adj-form-fields grid" id="adj-form-fields" style="display:none;">
            <div>
              <label>Category</label>
              <select id="adj-type">
                <option value="earning">Earning</option>
                <option value="deduction">Deduction</option>
              </select>
            </div>
            <div>
              <label>Sub-type</label>
              <select id="adj-subtype">
                <option value="bonus">Bonus</option>
                <option value="allowance">Allowance</option>
                <option value="other_earning">Other (Earning)</option>
              </select>
            </div>
            <div>
              <label>Amount (RM)</label>
              <input type="number" id="adj-amount" step="0.01" min="0" placeholder="e.g. 150.00">
            </div>
            <div class="adj-reason-wrap">
              <label>Reason <span class="required">*</span></label>
              <textarea id="adj-reason" rows="2" placeholder="Describe the reason for this adjustment (min. 10 characters)" maxlength="500"></textarea>
            </div>
            <div class="adj-preview-wrap" id="adj-preview-wrap" style="display:none;">
              <div class="adj-preview-title">Impact preview</div>
              <div class="adj-preview-content" id="adj-preview-content"></div>
            </div>
            <div class="adj-buttons">
              <button type="button" class="btn-primary" id="adj-apply">Save adjustment</button>
              <button type="button" class="btn-ghost" id="adj-reset">Clear form</button>
            </div>
          </div>
        </div>

        <div class="muted" id="adj-note" style="margin-top:8px;">@if(in_array($status, ['LOCKED', 'PAID', 'PUBLISHED'])) Payroll for this month is locked. Corrections must be applied in a later month. @else Payroll is in DRAFT — adjustments are editable until release. @endif</div>

        <div class="adj-history-section" id="adj-history-section" style="display:none;">
          <div class="adj-history-title">Recent adjustments (this employee, this month)</div>
          <div class="adj-history-editable" id="adj-history-editable"></div>
          <table class="adj-history-table" id="adj-history-table">
            <thead><tr><th>Category</th><th>Sub-type</th><th class="num">Amount</th><th>Reason</th><th>Date</th></tr></thead>
            <tbody id="adj-history-tbody"></tbody>
          </table>
          <p class="muted adj-history-empty" id="adj-history-empty">No adjustments yet for this employee this month.</p>
        </div>

        <div class="adj-basic-salary-section" id="adj-basic-salary-section">
          <div class="adj-basic-salary-title">Basic salary (current &amp; future months)</div>
          <div class="adj-basic-salary-fields grid" id="adj-basic-salary-fields">
            <div>
              <label>Effective from month</label>
              <input type="text" id="adj-effective-month" readonly class="readonly" placeholder="Same as selected period">
            </div>
            <div>
              <label>Current basic salary (RM)</label>
              <input type="text" id="adj-current-base" readonly class="readonly" placeholder="Select employee">
            </div>
            <div>
              <label for="adj-new-base">New basic salary (RM) <span class="required">*</span></label>
              <input type="number" id="adj-new-base" step="0.01" min="0.01" placeholder="e.g. 3500.00">
            </div>
            <div class="adj-basic-reason-wrap">
              <label for="adj-basic-reason">Reason (optional)</label>
              <textarea id="adj-basic-reason" rows="2" placeholder="e.g. Annual increment" maxlength="500"></textarea>
            </div>
            <div class="adj-basic-buttons">
              <button type="button" class="btn-primary" id="adj-update-basic-btn">Update basic salary</button>
            </div>
          </div>
        </div>
        </div>
      </div>

      <style>
        .adj-tool-header { margin-bottom:12px; }
        .adj-tool-header-row { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; }
        .adj-toggle-btn { flex-shrink:0; padding:8px 14px; font-size:13px; font-weight:600; color:#475569; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:8px; cursor:pointer; white-space:nowrap; }
        .adj-toggle-btn:hover { background:#e2e8f0; color:#0f172a; }
        #adjustments-card.adj-collapsed .adj-card-body { display:none; }
        #adjustments-card.adj-collapsed .adj-toggle-btn { background:#e0f2fe; color:#0c4a6e; }
        .adj-period-tie { margin:0; font-size:13px; color:#64748b; }
        .adj-summary-box { background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px; margin:12px 0; }
        .adj-summary-title { font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:#64748b; margin-bottom:10px; font-weight:700; }
        .adj-summary-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(100px, 1fr)); gap:10px; font-size:14px; }
        .adj-summary-grid .item { display:flex; flex-direction:column; }
        .adj-summary-grid .item .k { color:#64748b; font-size:12px; }
        .adj-summary-grid .item .v { font-weight:700; color:#0f172a; }
        .adj-form-fields { margin-top:14px; }
        .adj-reason-wrap { grid-column:1 / -1; }
        .adj-reason-wrap textarea { width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:10px; font-size:14px; resize:vertical; min-height:60px; }
        .adj-preview-wrap { grid-column:1 / -1; background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; padding:12px; margin-top:8px; }
        .adj-preview-title { font-size:12px; font-weight:700; color:#1e40af; margin-bottom:6px; }
        .adj-preview-content { font-size:14px; color:#1e3a8a; }
        .adj-buttons { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
        .adj-history-section { margin-top:20px; padding-top:16px; border-top:1px solid #e5e7eb; }
        .adj-history-title { font-size:13px; font-weight:700; color:#0f172a; margin-bottom:10px; }
        .adj-history-editable { font-size:12px; color:#059669; margin-bottom:8px; }
        .adj-history-table { width:100%; border-collapse:collapse; font-size:13px; }
        .adj-history-table th, .adj-history-table td { padding:8px 10px; border-bottom:1px solid #e5e7eb; text-align:left; }
        .adj-history-table th.num, .adj-history-table td.num { text-align:right; }
        .adj-history-empty { margin:10px 0 0; font-size:13px; }
        .required { color:#b91c1c; }
        .adj-basic-salary-section { margin-top:24px; padding-top:20px; border-top:1px solid #e5e7eb; }
        .adj-basic-salary-title { font-size:14px; font-weight:700; color:#0f172a; margin-bottom:6px; }
        .adj-basic-salary-desc { font-size:13px; color:#64748b; margin:0 0 14px; line-height:1.4; }
        .adj-basic-salary-fields { align-items:flex-end; }
        .adj-basic-reason-wrap { grid-column:1 / -1; }
        .adj-basic-reason-wrap textarea { width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:10px; font-size:14px; resize:vertical; min-height:56px; }
        .adj-basic-buttons { display:flex; gap:10px; align-items:center; }
        input.readonly { background:#f1f5f9; color:#475569; cursor:default; }
      </style>

      <div class="card">
        <table id="tbl">
          <thead>
            <tr>
              <th>Employee</th>
              <th>Department</th>
              <th class="num sortable" data-sort="base">Basic Salary</th>
              <th class="num">Allowance</th>
              <th class="num">EPF</th>
              <th class="num">Tax</th>
              <th class="num">Adjustments</th>
              <th class="num sortable" data-sort="net">Net</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <section class="pagination-wrap" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-top:12px;">
        <span class="pagination-info" id="paginationInfo">0 records</span>
        <div style="display:flex; align-items:center; gap:10px;">
          <button type="button" class="btn btn-ghost btn-icon" id="firstPage" disabled><i class="fa-solid fa-angles-left"></i> First</button>
          <button type="button" class="btn btn-ghost btn-icon" id="prevPage" disabled>Prev</button>
          <span id="pageNum">Page 1 of 1</span>
          <button type="button" class="btn btn-ghost btn-icon" id="nextPage" disabled>Next</button>
          <button type="button" class="btn btn-ghost btn-icon" id="lastPage" disabled>Last <i class="fa-solid fa-angles-right"></i></button>
        </div>
        <div>
          <label>Show </label>
          <select id="perPage">
            <option value="10">10</option>
            <option value="25" selected>25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
      </section>

      <footer style="text-align:center; color:#94a3b8; font-size:12px;">Ac 2025 Web-Based HRMS. All Rights Reserved.</footer>
    </main>
  </div>

  <div class="modal" id="modal">
    <div class="sheet">
      <h3 style="margin:0 0 4px;">Salary Details</h3>
      <div id="meta" class="salary-detail-header"></div>
      <div id="formulaBox" class="salary-detail-formula"></div>

      <div class="detail-tabs">
        <button class="detail-tab active" data-tab="breakdown"><i class="fa-solid fa-calculator"></i> Payroll Breakdown</button>
        <button class="detail-tab" data-tab="attendance"><i class="fa-solid fa-calendar"></i> Attendance Source</button>
        <button class="detail-tab" data-tab="bank"><i class="fa-solid fa-building-columns"></i> Bank Account</button>
      </div>

      <div class="tab-panels">
        <div class="tab-panel active" id="tab-breakdown">
          <table style="width:100%; border-collapse:collapse; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">
            <tbody id="breakdown"></tbody>
          </table>
        </div>

        <div class="tab-panel" id="tab-attendance">
          <div class="info-grid" id="attendanceLists"></div>
        </div>

        <div class="tab-panel" id="tab-bank">
          <div class="info-grid" id="bankDetails"></div>
        </div>
      </div>

      <div style="margin-top:14px; text-align:right;">
        <button class="btn-ghost" id="close">Close</button>
      </div>
    </div>
  </div>

  <!-- Confirmation modals -->
  <div class="modal" id="confirmLock" style="display:none; align-items:center; justify-content:center;">
    <div class="confirm-box">
      <h3 style="margin:0 0 8px;">Release Payroll</h3>
      <p style="margin:0 0 8px;" id="releasePeriodMessage">This will lock the payroll for the selected month. You cannot edit after release. Corrections must be done as next-month adjustments.</p>
      <p style="margin:0 0 10px; font-weight:700; color:#0f172a; font-size:15px;" id="releasePeriodLabel"></p>
      <div style="margin-bottom:10px;">
        <label style="display:block; font-weight:600; margin-bottom:4px;">Release note (recommended)</label>
        <textarea id="releaseNote" rows="3" style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:8px;" placeholder="Optional note for audit trail"></textarea>
      </div>
      <div style="margin-bottom:12px;">
        <label style="display:block; font-weight:600; margin-bottom:4px; color:#0f172a;">Re-enter your password <span style="color:#b91c1c;">*</span></label>
        <input type="password" id="releasePassword" autocomplete="current-password" placeholder="Enter your admin password" style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:15px;">
        <small style="display:block; margin-top:4px; color:#64748b;">You must confirm your password to release payroll.</small>
      </div>
      <label style="display:flex; gap:8px; align-items:center; font-size:13px; color:#475569;">
        <input type="checkbox" id="lockConfirmCheck"> I understand this action is irreversible for this period.
      </label>
      <div class="confirm-actions">
        <button class="btn-ghost" id="lockCancel">Cancel</button>
        <button class="btn-primary" id="lockConfirmBtn" disabled>Release Payroll</button>
      </div>
    </div>
  </div>

  <div class="modal" id="confirmPublish" style="display:none; align-items:center; justify-content:center;">
    <div class="confirm-box">
      <h3 style="margin:0 0 8px;">Publish Payslips</h3>
      <p style="margin:0;">Employees will be able to view their payslips for this period.</p>
      <div class="confirm-actions">
        <button class="btn-ghost" id="publishCancel">Cancel</button>
        <button class="btn-primary" id="publishConfirmBtn">Publish Payslips</button>
      </div>
    </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', () => {

    const ENDPOINT = "{{ route('admin.payroll.salary.data') }}";
    const ROUTES = {
      generate: "{{ route('admin.payroll.salary.generate') }}",
      adjustment: "{{ route('admin.payroll.salary.adjustment') }}",
      adjustment_summary: "{{ route('admin.payroll.salary.adjustment_summary') }}",
      update_basic_salary: "{{ route('admin.payroll.salary.update_basic_salary') }}",
      lock: "{{ route('admin.payroll.salary.lock') }}",
      pay: "{{ route('admin.payroll.salary.pay') }}",
      publish: "{{ route('admin.payroll.salary.publish') }}",
    };
    const ADJ_SUBTYPES = {
      earning: [
        { value: 'bonus', label: 'Bonus' },
        { value: 'allowance', label: 'Allowance' },
        { value: 'other_earning', label: 'Other (Earning)' },
      ],
      deduction: [
        { value: 'deduction', label: 'Deduction' },
        { value: 'late_penalty', label: 'Late Penalty' },
        { value: 'absence', label: 'Absence' },
        { value: 'other_deduction', label: 'Other (Deduction)' },
      ],
    };
    function getCSRF() {
      const meta = document.querySelector('meta[name="csrf-token"]');
      return (meta && meta.getAttribute('content')) || '';
    }
    const CSRF = getCSRF();
    const toast = document.createElement('div');
    toast.setAttribute('role', 'alert');
    toast.style.cssText = 'position:fixed; top:24px; right:24px; padding:20px 28px; border-radius:14px; box-shadow:0 20px 40px rgba(0,0,0,0.25), 0 0 0 1px rgba(0,0,0,0.05); display:none; z-index:99999; font-size:17px; font-weight:700; max-width:420px; min-width:280px; align-items:center; gap:14px; line-height:1.35;';
    toast.style.display = 'none';
    const toastIcon = document.createElement('span');
    toastIcon.setAttribute('aria-hidden', 'true');
    toast.appendChild(toastIcon);
    const toastText = document.createElement('span');
    toast.appendChild(toastText);
    document.body.appendChild(toast);
    const TOAST_STYLES = {
      success: {
        bg: '#ecfdf5',
        border: '#10b981',
        text: '#065f46',
        icon: '<i class="fa-solid fa-circle-check" style="font-size:1.5em;"></i>',
      },
      error: {
        bg: '#fef2f2',
        border: '#dc2626',
        text: '#991b1b',
        icon: '<i class="fa-solid fa-circle-xmark" style="font-size:1.5em;"></i>',
      },
      info: {
        bg: '#eff6ff',
        border: '#3b82f6',
        text: '#1e40af',
        icon: '<i class="fa-solid fa-circle-info" style="font-size:1.5em;"></i>',
      },
    };
    function showToast(msg, type = 'info') {
      const style = TOAST_STYLES[type] || TOAST_STYLES.info;
      toast.style.background = style.bg;
      toast.style.border = '3px solid ' + style.border;
      toast.style.color = style.text;
      toast.style.borderLeftWidth = '8px';
      toast.style.borderLeftColor = style.border;
      toastIcon.innerHTML = style.icon;
      toastIcon.style.color = style.border;
      toastText.textContent = msg;
      toast.style.display = 'flex';
      clearTimeout(toast._tid);
      toast._tid = setTimeout(() => { toast.style.display = 'none'; }, 6000);
    }
    const PAYROLL_STATUS = "{{ $payrollStatus }}";
    const RELEASE_WINDOW_CLOSED = {{ ($releaseWindowClosed ?? false) ? 'true' : 'false' }};
    let currentPayrollStatus = PAYROLL_STATUS;
    const CAN_ADJUST = () => currentPayrollStatus === 'DRAFT';
    let DATA = [];
    let FILTER = null;
    let currentPage = 1;
    let perPage = 25;
    let pagination = { total: 0, last_page: 1, current_page: 1 };
    const ADJ = {};

    const $ = (s) => document.querySelector(s);
    const tbody = $('#tbl tbody');

    const money = (n) => Number(n ?? 0).toLocaleString('en-MY', { minimumFractionDigits:2, maximumFractionDigits:2 });

    function calc(e) {
      // Formula: Gross = Basic Salary + Allowance + Adjustments; Deductions = Late + Absent + Unpaid Leave + Penalties + EPF + Tax; Net = Gross - Deductions
      const base = Number(e.base || 0);
      const allow = Number(e.allow || 0);
      const adjTotal = Number(e.adjustment_total ?? 0);
      const gross = base + allow + adjTotal;
      const lateDed = Number(e.late_ded ?? 0);
      const absentDed = Number(e.absent_ded ?? 0);
      const unpaidDed = Number(e.unpaid_ded ?? 0);
      const penaltyDed = Number(e.penalty ?? 0);
      const employeeEpf = Number(e.epfTax || 0);
      const tax = Number(e.tax_total || 0);
      const totalDeductions = lateDed + absentDed + unpaidDed + penaltyDed + employeeEpf + tax;
      const net = gross - totalDeductions;

      const adj = (adjTotal !== 0) ? { amount: Math.abs(adjTotal), type: adjTotal >= 0 ? 'earning' : 'deduction' } : (ADJ[e.id] || null);
      const adjAmount = adj ? (adj.type === 'deduction' ? -Number(adj.amount) : Number(adj.amount)) : 0;
      const isDeduction = adj && adj.type === 'deduction';

      return {
        gross,
        deductions: totalDeductions,
        net,
        adj,
        adjAmount,
        isDeduction,
      };
    }

    function applyFilter(rows) {
      if (!FILTER) return rows;
      switch (FILTER) {
        case 'absent': return rows.filter(r => (r.absent_days || 0) > 0);
        case 'late': return rows.filter(r => (r.late_minutes || 0) > 0);
        case 'unpaid': return rows.filter(r => (r.unpaid_leave_days || 0) > 0);
        case 'incomplete': return rows.filter(r => (r.incomplete_punches || 0) > 0);
        default: return rows;
      }
    }

    function render(rows) {
      const filtered = applyFilter(rows);
      tbody.innerHTML = '';
      if (!filtered.length) {
        tbody.innerHTML = '<tr><td colspan="9">No records.</td></tr>';
        return;
      }
      filtered.forEach(e => {
        const c = calc(e);
        const displayNet = Math.max(0, (e.net != null && e.net !== undefined && e.net !== '') ? Number(e.net) : c.net);
        const badges = [];
        if ((e.absent_days || 0) > 0) badges.push('<span class="chip" style="background:#fee2e2;color:#991b1b;">Absent</span>');
        if ((e.late_minutes || 0) > 0) badges.push('<span class="chip" style="background:#fef9c3;color:#92400e;">Late</span>');
        if ((e.unpaid_leave_days || 0) > 0) badges.push('<span class="chip" style="background:#e0f2fe;color:#0c4a6e;">Unpaid leave</span>');
        if ((e.incomplete_punches || 0) > 0) badges.push('<span class="chip" style="background:#ede9fe;color:#5b21b6;">Incomplete</span>');

        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td><strong>${e.name}</strong><br><span class="muted">${e.id}</span><div style="margin-top:4px; display:flex; gap:4px; flex-wrap:wrap;">${badges.join('')}</div></td>
          <td>${e.dept}</td>
          <td class="num"><strong>${money(e.base)}</strong></td>
          <td class="num" title="${e.allowItems.map(i => i[0] + ': RM ' + i[1]).join(', ')}">${money(e.allow)}</td>
          <td class="num">-${money(e.epfTax || 0)}</td>
          <td class="num">-${money(e.tax_total || 0)}</td>
          <td class="num">${c.adj ? `<span class="chip" style="display:inline-flex;align-items:center;gap:6px;padding:6px 10px;font-size:12px;">Adj ${c.isDeduction ? '-' : '+'}RM ${money(c.adjAmount)}</span>` : '—'}</td>
          <td class="num"><strong>${money(displayNet)}</strong></td>
          <td><button class="btn-ghost" data-id="${e.id}">Details</button></td>
        `;
        tbody.appendChild(tr);
      });
      document.querySelectorAll('[data-id]').forEach(btn => {
        btn.addEventListener('click', () => {
          const row = rows.find(x => x.id === btn.dataset.id);
          if (row) openModal(row);
        });
      });
    }

    function updateInsights(rows) {
      const counts = {
        absent: rows.filter(r => (r.absent_days || 0) > 0).length,
        late: rows.filter(r => (r.late_minutes || 0) > 0).length,
        unpaid: rows.filter(r => (r.unpaid_leave_days || 0) > 0).length,
        incomplete: rows.filter(r => (r.incomplete_punches || 0) > 0).length,
      };
      document.getElementById('insight-absent').textContent = counts.absent;
      document.getElementById('insight-late').textContent = counts.late;
      document.getElementById('insight-unpaid').textContent = counts.unpaid;
      document.getElementById('insight-incomplete').textContent = counts.incomplete;
    }

    // Sorting
    const headers = document.querySelectorAll('th.sortable');
    headers.forEach(h => {
      h.style.cursor = 'pointer';
      h.addEventListener('click', () => {
        const key = h.dataset.sort;
        const dir = h.dataset.dir === 'desc' ? 'asc' : 'desc';
        h.dataset.dir = dir;
        DATA = [...DATA].sort((a, b) => {
          const av = key === 'net' ? ((a.net != null && a.net !== undefined && a.net !== '') ? Number(a.net) : calc(a).net) : Number(a.base || 0);
          const bv = key === 'net' ? ((b.net != null && b.net !== undefined && b.net !== '') ? Number(b.net) : calc(b).net) : Number(b.base || 0);
          return dir === 'asc' ? av - bv : bv - av;
        });
        render(DATA);
      });
    });


    function updatePagination() {
      const el = document.getElementById('paginationInfo');
      const num = document.getElementById('pageNum');
      const prevBtn = document.getElementById('prevPage');
      const nextBtn = document.getElementById('nextPage');
      const firstBtn = document.getElementById('firstPage');
      const lastBtn = document.getElementById('lastPage');
      if (el) el.textContent = (pagination.total || 0) + ' records';
      if (num) num.textContent = 'Page ' + (pagination.current_page || 1) + ' of ' + (pagination.last_page || 1);
      if (prevBtn) prevBtn.disabled = (pagination.current_page || 1) <= 1;
      if (nextBtn) nextBtn.disabled = (pagination.current_page || 1) >= (pagination.last_page || 1);
      if (firstBtn) firstBtn.disabled = (pagination.current_page || 1) <= 1;
      if (lastBtn) lastBtn.disabled = (pagination.current_page || 1) >= (pagination.last_page || 1);
    }

    async function loadData() {
      tbody.innerHTML = '<tr><td colspan="9">Loading...</td></tr>';
      const params = new URLSearchParams({
        department: $('#dept').value,
        period: $('#period').value,
        page: String(currentPage),
        per_page: String(perPage),
      });
      try {
        const resp = await fetch(`${ENDPOINT}?${params.toString()}`, { headers: { 'Accept': 'application/json' }});
        if (!resp.ok) throw new Error('Failed to load salaries');
        const json = await resp.json();
        DATA = Array.isArray(json.data) ? json.data : [];
        pagination = json.pagination || { total: 0, last_page: 1, current_page: 1, per_page: perPage };
        currentPage = pagination.current_page || 1;
        if (pagination.per_page) perPage = pagination.per_page;
        const perPageEl = document.getElementById('perPage');
        if (perPageEl && perPageEl.value !== String(perPage)) perPageEl.value = String(perPage);
        if (json.insights) {
          document.getElementById('insight-absent').textContent = json.insights.absent ?? 0;
          document.getElementById('insight-late').textContent = json.insights.late ?? 0;
          document.getElementById('insight-unpaid').textContent = json.insights.unpaid ?? 0;
          document.getElementById('insight-incomplete').textContent = json.insights.incomplete ?? 0;
        } else {
          updateInsights(DATA);
        }
        render(DATA);
        updatePagination();
        if (json.employees && json.employees.length) {
          const sel = document.getElementById('adj-emp');
          if (sel) {
            sel.innerHTML = '<option value="">Select employee</option>' + json.employees.map(e => {
              const vid = e.employee_id ?? e.id;
              return `<option value="${vid}">${e.name} (${e.id})</option>`;
            }).join('');
          }
        } else {
          populateAdjEmp(DATA);
        }
        const lockBtn = document.getElementById('action-lock');
        if (lockBtn) lockBtn.disabled = RELEASE_WINDOW_CLOSED;
      } catch (err) {
        tbody.innerHTML = `<tr><td colspan="9">Error: ${err.message}</td></tr>`;
      }
    }

    function populateAdjEmp(rows) {
      const sel = document.getElementById('adj-emp');
      if (!sel) return;
      sel.innerHTML = '<option value="">Select employee</option>' + (rows || []).map(e => {
        const vid = e.employee_id ?? e.id;
        return `<option value="${vid}">${e.name} (${e.id})</option>`;
      }).join('');
    }

    $('#period').addEventListener('change', () => {
      const period = $('#period').value;
      const dept = $('#dept').value;
      const params = new URLSearchParams({ period });
      if (dept) params.set('dept', dept);
      window.location.href = '{{ route("admin.payroll.salary") }}?' + params.toString();
    });
    $('#dept').addEventListener('change', () => { currentPage = 1; loadData(); });
    document.getElementById('firstPage').addEventListener('click', () => { if (currentPage > 1) { currentPage = 1; loadData(); } });
    document.getElementById('prevPage').addEventListener('click', () => { if (currentPage > 1) { currentPage--; loadData(); } });
    document.getElementById('nextPage').addEventListener('click', () => { if (currentPage < (pagination.last_page || 1)) { currentPage++; loadData(); } });
    document.getElementById('lastPage').addEventListener('click', () => { if (currentPage < (pagination.last_page || 1)) { currentPage = pagination.last_page; loadData(); } });
    document.getElementById('perPage').addEventListener('change', function() { perPage = parseInt(this.value, 10); currentPage = 1; loadData(); });

    const btnGenerate = document.getElementById('action-generate');
    btnGenerate?.addEventListener('click', async (e) => {
      e.preventDefault();
      if (!btnGenerate) return;
      const originalText = btnGenerate.textContent;
      btnGenerate.disabled = true;
      btnGenerate.textContent = 'Processing...';
      try {
        const resp = await postAction(ROUTES.generate, {
          period_month: $('#period').value,
          department_id: $('#dept').value,
        });
        showToast(resp.message || 'Payroll generated successfully.', 'success');
        currentPayrollStatus = 'DRAFT';
        // Update UI to DRAFT without refresh
        const badge = document.getElementById('payrollStatusBadge');
        if (badge) {
          badge.textContent = 'DRAFT';
          badge.className = 'status-badge badge-draft';
        }
        const genAt = document.getElementById('generatedAtText');
        if (genAt) genAt.textContent = 'Generated at ' + new Date().toLocaleString('en-MY', { dateStyle: 'medium', timeStyle: 'short' });
        const btnGen = document.getElementById('action-generate');
        if (btnGen) { btnGen.textContent = 'Recalculate Payroll'; btnGen.style.display = ''; }
        const btnLock = document.getElementById('action-lock');
        if (btnLock) btnLock.style.display = 'inline-flex';
        await loadData();
      } catch (err) {
        showToast(err.message, 'error');
      } finally {
        btnGenerate.disabled = false;
        btnGenerate.textContent = currentPayrollStatus === 'DRAFT' ? 'Recalculate Payroll' : originalText;
      }
    });

    // Confirmation modal helpers
    const confirmLock = document.getElementById('confirmLock');
    const confirmPublish = document.getElementById('confirmPublish');
    const lockCheck = document.getElementById('lockConfirmCheck');
    const lockConfirmBtn = document.getElementById('lockConfirmBtn');

    const hideModal = (el) => { if (el) el.style.display = 'none'; };
    const showModal = (el) => { if (el) el.style.display = 'flex'; };

    const releasePasswordEl = document.getElementById('releasePassword');
    function updateLockConfirmBtnState() {
      const pwd = releasePasswordEl?.value?.trim() || '';
      lockConfirmBtn.disabled = !lockCheck?.checked || !pwd;
    }
    lockCheck?.addEventListener('change', updateLockConfirmBtnState);
    releasePasswordEl?.addEventListener('input', updateLockConfirmBtnState);

    async function postAction(url, payload) {
      const token = getCSRF();
      if (!token) {
        showToast('Session expired or invalid. Please refresh the page and try again.', 'error');
        return Promise.reject(new Error('CSRF token missing'));
      }
      const form = new FormData();
      form.append('_token', token);
      Object.entries(payload).forEach(([k,v]) => form.append(k, v));
      const resp = await fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body: form,
        credentials: 'same-origin',
      });
      if (!resp.ok) {
        const j = await resp.json().catch(() => ({}));
        throw new Error(j.message || 'Action failed');
      }
      return resp.json();
    }

    let releaseTargetPeriod = ''; // month selected when opening Release modal (YYYY-MM)
    function formatPeriodLabel(ym) {
      if (!ym) return '';
      const [y, m] = ym.split('-');
      const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
      return (months[parseInt(m, 10) - 1] || m) + ' ' + y;
    }
    document.getElementById('action-lock')?.addEventListener('click', (e) => {
      e.preventDefault();
      releaseTargetPeriod = $('#period').value;
      const labelEl = document.getElementById('releasePeriodLabel');
      if (labelEl) labelEl.textContent = 'Month to release: ' + formatPeriodLabel(releaseTargetPeriod);
      lockCheck.checked = false;
      if (releasePasswordEl) releasePasswordEl.value = '';
      lockConfirmBtn.disabled = true;
      const releaseNote = document.getElementById('releaseNote');
      if (releaseNote) releaseNote.value = '';
      showModal(confirmLock);
    });
    document.getElementById('lockCancel')?.addEventListener('click', () => hideModal(confirmLock));
    lockConfirmBtn?.addEventListener('click', async () => {
      try {
        if (!releaseTargetPeriod) {
          showToast('Please close and open Release again to select the month.', 'error');
          return;
        }
        const password = releasePasswordEl?.value?.trim() || '';
        if (!password) {
          showToast('Please re-enter your password to confirm.', 'error');
          return;
        }
        const payload = { period_month: releaseTargetPeriod, password: password };
        const dept = $('#dept').value;
        if (dept) payload.department_id = dept;
        const note = document.getElementById('releaseNote')?.value?.trim();
        if (note) payload.release_note = note;
        await postAction(ROUTES.lock, payload);
        showToast('Payroll released successfully for ' + formatPeriodLabel(releaseTargetPeriod) + '.', 'success');
        const q = new URLSearchParams({ period: releaseTargetPeriod }); if ($('#dept').value) q.set('dept', $('#dept').value); window.location.href = '{{ route("admin.payroll.salary") }}?' + q.toString();
      } catch (err) {
        showToast(err.message || 'Release failed.', 'error');
      }
    });

    document.getElementById('action-publish')?.addEventListener('click', (e) => {
      e.preventDefault(); showModal(confirmPublish);
    });
    document.getElementById('publishCancel')?.addEventListener('click', () => hideModal(confirmPublish));
    document.getElementById('publishConfirmBtn')?.addEventListener('click', async () => {
      try {
        await postAction(ROUTES.publish, { period_month: $('#period').value });
        const period = $('#period').value;
        const q = new URLSearchParams({ period }); if ($('#dept').value) q.set('dept', $('#dept').value); window.location.href = '{{ route("admin.payroll.salary") }}?' + q.toString();
      } catch (err) { showToast(err.message, 'error'); }
    });

    document.getElementById('action-export')?.addEventListener('click', (e) => {
      e.preventDefault();
      showToast('Export options pending implementation.', 'info');
    });

    // Insight card filtering
    document.querySelectorAll('.insight-card').forEach(card => {
      card.addEventListener('click', () => {
        const f = card.dataset.filter;
        if (FILTER === f) {
          FILTER = null;
          document.querySelectorAll('.insight-card').forEach(c => c.classList.remove('active'));
        } else {
          FILTER = f;
          document.querySelectorAll('.insight-card').forEach(c => c.classList.remove('active'));
          card.classList.add('active');
        }
        render(DATA);
      });
    });

    // Adjustment controls: period label, summary, sub-types, preview, history
    const adjNote = document.getElementById('adj-note');
    const adjCard = document.getElementById('adjustments-card');
    const adjCardBody = document.getElementById('adj-card-body');
    const adjToggleBtn = document.getElementById('adj-toggle-btn');
    (function initAdjToggle() {
      const key = 'payroll_adj_section_hidden';
      const hidden = sessionStorage.getItem(key) === '1';
      if (hidden && adjCard) {
        adjCard.classList.add('adj-collapsed');
        if (adjToggleBtn) { adjToggleBtn.textContent = 'Show adjustments'; adjToggleBtn.setAttribute('aria-expanded', 'false'); }
      }
      adjToggleBtn?.addEventListener('click', function() {
        adjCard?.classList.toggle('adj-collapsed');
        const collapsed = adjCard?.classList.contains('adj-collapsed');
        if (adjToggleBtn) {
          adjToggleBtn.textContent = collapsed ? 'Show adjustments' : 'Hide adjustments';
          adjToggleBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
          adjToggleBtn.title = collapsed ? 'Show adjustments section' : 'Hide adjustments section';
        }
        try { sessionStorage.setItem(key, collapsed ? '1' : '0'); } catch (e) {}
      });
      if (adjToggleBtn && !adjCard?.classList.contains('adj-collapsed')) {
        adjToggleBtn.textContent = 'Hide adjustments';
      }
    })();
    const adjPeriodLabel = document.getElementById('adj-period-label');
    const adjSummaryBox = document.getElementById('adj-summary-box');
    const adjSummaryGrid = document.getElementById('adj-summary-grid');
    const adjFormFields = document.getElementById('adj-form-fields');
    const adjPreviewWrap = document.getElementById('adj-preview-wrap');
    const adjPreviewContent = document.getElementById('adj-preview-content');
    const adjHistorySection = document.getElementById('adj-history-section');
    const adjHistoryTbody = document.getElementById('adj-history-tbody');
    const adjHistoryEmpty = document.getElementById('adj-history-empty');
    const adjHistoryEditable = document.getElementById('adj-history-editable');

    function formatAdjPeriod(ym) {
      if (!ym) return '';
      const [y, m] = ym.split('-');
      const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
      return (months[parseInt(m, 10) - 1] || m) + ' ' + y;
    }
    const adjEffectiveMonth = document.getElementById('adj-effective-month');
    const adjCurrentBase = document.getElementById('adj-current-base');
    const adjNewBase = document.getElementById('adj-new-base');
    const adjBasicReason = document.getElementById('adj-basic-reason');
    function updateAdjPeriodLabel() {
      const periodYm = $('#period').value;
      const label = formatAdjPeriod(periodYm);
      if (adjPeriodLabel) adjPeriodLabel.textContent = label;
      if (adjEffectiveMonth) adjEffectiveMonth.value = label;
    }
    $('#period')?.addEventListener('change', updateAdjPeriodLabel);
    updateAdjPeriodLabel();

    function renderSubtypes(category) {
      const sel = document.getElementById('adj-subtype');
      if (!sel) return;
      const opts = ADJ_SUBTYPES[category] || ADJ_SUBTYPES.earning;
      sel.innerHTML = opts.map(o => `<option value="${o.value}">${o.label}</option>`).join('');
    }
    document.getElementById('adj-type')?.addEventListener('change', function() {
      renderSubtypes(this.value);
      updateAdjPreview();
    });

    let adjSummaryData = null;
    async function fetchAdjSummary() {
      const empId = $('#adj-emp').value;
      const period = $('#period').value;
      if (!empId || !period) {
        adjSummaryBox.style.display = 'none';
        adjFormFields.style.display = 'none';
        adjHistorySection.style.display = 'none';
        adjSummaryData = null;
        if (adjCurrentBase) adjCurrentBase.value = '';
        return;
      }
      try {
        const r = await fetch(ROUTES.adjustment_summary + '?period_month=' + encodeURIComponent(period) + '&employee_id=' + encodeURIComponent(empId), { headers: { 'Accept': 'application/json' } });
        const j = await r.json();
        adjSummaryData = j;
        if (j.run) {
          adjSummaryBox.style.display = 'block';
          adjSummaryGrid.innerHTML = `
            <div class="item"><span class="k">Basic Salary</span><span class="v">RM ${Number(j.run.base).toLocaleString('en-MY', { minimumFractionDigits: 2 })}</span></div>
            <div class="item"><span class="k">Allowance</span><span class="v">RM ${Number(j.run.allowance).toLocaleString('en-MY', { minimumFractionDigits: 2 })}</span></div>
            <div class="item"><span class="k">Adjustments</span><span class="v">RM ${Number(j.run.adjustment_total).toLocaleString('en-MY', { minimumFractionDigits: 2 })}</span></div>
            <div class="item"><span class="k">Gross</span><span class="v">RM ${Number(j.run.gross).toLocaleString('en-MY', { minimumFractionDigits: 2 })}</span></div>
            <div class="item"><span class="k">EPF (11%)</span><span class="v">- RM ${Number(j.run.epf).toLocaleString('en-MY', { minimumFractionDigits: 2 })}</span></div>
            <div class="item"><span class="k">Tax (3%)</span><span class="v">- RM ${Number(j.run.tax).toLocaleString('en-MY', { minimumFractionDigits: 2 })}</span></div>
            <div class="item"><span class="k">Net</span><span class="v">RM ${Number(j.run.net).toLocaleString('en-MY', { minimumFractionDigits: 2 })}</span></div>
          `;
        } else {
          adjSummaryBox.style.display = 'block';
          adjSummaryGrid.innerHTML = '<div class="item"><span class="k">No payroll run</span><span class="v">Generate payroll for this month first.</span></div>';
        }
        adjFormFields.style.display = (CAN_ADJUST() && j.is_editable && j.run) ? 'block' : 'none';
        adjHistorySection.style.display = 'block';
        adjHistoryEditable.textContent = j.is_editable ? 'Payroll is in Draft — adjustments are editable until release.' : 'Payroll is locked for this month.';
        if (adjEffectiveMonth) adjEffectiveMonth.value = j.period_label || formatAdjPeriod($('#period').value);
        if (adjCurrentBase) adjCurrentBase.value = (j.employee_base_salary != null && j.employee_base_salary !== '') ? Number(j.employee_base_salary).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';
        const list = j.adjustments || [];
        if (list.length) {
          adjHistoryTbody.innerHTML = list.map(a => `<tr><td>${a.category}</td><td>${a.sub_type}</td><td class="num">${a.category === 'Deduction' ? '-' : ''} RM ${Number(a.amount).toLocaleString('en-MY', { minimumFractionDigits: 2 })}</td><td>${(a.reason || '').slice(0, 80)}${(a.reason && a.reason.length > 80) ? '…' : ''}</td><td>${a.date || '—'}</td></tr>`).join('');
          adjHistoryEmpty.style.display = 'none';
        } else {
          adjHistoryTbody.innerHTML = '';
          adjHistoryEmpty.style.display = 'block';
        }
        updateAdjPreview();
      } catch (e) {
        adjSummaryData = null;
        adjSummaryBox.style.display = 'none';
        adjFormFields.style.display = 'none';
      }
    }

    function updateAdjPreview() {
      if (!adjSummaryData || !adjSummaryData.run) { adjPreviewWrap.style.display = 'none'; return; }
      const amount = Number($('#adj-amount').value || 0);
      const type = $('#adj-type').value;
      const currentGross = adjSummaryData.run.gross;
      const currentNet = adjSummaryData.run.net;
      if (amount <= 0) { adjPreviewWrap.style.display = 'none'; return; }
      const delta = type === 'earning' ? amount : -amount;
      const newAdjTotal = adjSummaryData.run.adjustment_total + delta;
      const base = adjSummaryData.run.base;
      const allowance = adjSummaryData.run.allowance;
      const newGross = base + allowance + newAdjTotal;
      const newEpf = Math.round(newGross * 0.11 * 100) / 100;
      const newTax = Math.round(newGross * 0.03 * 100) / 100;
      const newNet = Math.round((newGross - newEpf - newTax) * 100) / 100;
      adjPreviewWrap.style.display = 'block';
      adjPreviewContent.innerHTML = `After this adjustment: <strong>Gross</strong> = RM ${newGross.toLocaleString('en-MY', { minimumFractionDigits: 2 })}, <strong>Net</strong> = RM ${newNet.toLocaleString('en-MY', { minimumFractionDigits: 2 })}`;
    }
    document.getElementById('adj-amount')?.addEventListener('input', updateAdjPreview);
    document.getElementById('adj-amount')?.addEventListener('change', updateAdjPreview);
    document.getElementById('adj-reason')?.addEventListener('input', updateAdjPreview);

    $('#adj-emp')?.addEventListener('change', fetchAdjSummary);

    if (!CAN_ADJUST()) {
      ['adj-emp','adj-type','adj-subtype','adj-amount','adj-reason','adj-apply','adj-reset'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.disabled = true;
      });
      adjCard?.classList.add('section-disabled');
      if (adjNote) adjNote.textContent = 'Adjustments locked: payroll is not in DRAFT.';
    } else {
      ['adj-emp','adj-type','adj-subtype','adj-amount','adj-reason'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.disabled = false;
      });
      adjCard?.classList.remove('section-disabled');
    }

    $('#adj-apply')?.addEventListener('click', async () => {
      if (!CAN_ADJUST()) return;
      const employeeId = $('#adj-emp').value;
      const type = $('#adj-type').value;
      const subType = $('#adj-subtype').value;
      const amount = Number($('#adj-amount').value || 0);
      const reason = $('#adj-reason').value.trim();
      if (!employeeId) return showToast('Select an employee', 'error');
      if (reason.length < 10) return showToast('Reason must be at least 10 characters', 'error');
      if (amount <= 0) return showToast('Amount must be greater than 0', 'error');
      const btn = $('#adj-apply');
      const orig = btn.textContent;
      btn.disabled = true;
      btn.textContent = 'Saving...';
      try {
        const token = getCSRF();
        if (!token) { showToast('Session expired. Please refresh.', 'error'); return; }
        const form = new FormData();
        form.append('_token', token);
        form.append('period_month', $('#period').value);
        form.append('employee_id', employeeId);
        form.append('adjustment_type', type);
        form.append('adjustment_sub_type', subType);
        form.append('amount', amount);
        form.append('reason', reason);
        const resp = await fetch(ROUTES.adjustment, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
          body: form,
          credentials: 'same-origin',
        });
        if (!resp.ok) {
          const j = await resp.json().catch(() => ({}));
          throw new Error(j.message || 'Save failed');
        }
        showToast('Adjustment saved.', 'success');
        $('#adj-amount').value = '';
        $('#adj-reason').value = '';
        fetchAdjSummary();
        loadData();
      } catch (err) {
        showToast(err.message, 'error');
      } finally {
        btn.disabled = false;
        btn.textContent = orig;
      }
    });

    $('#adj-reset')?.addEventListener('click', () => {
      if (!CAN_ADJUST()) return;
      $('#adj-amount').value = '';
      $('#adj-reason').value = '';
      $('#adj-type').value = 'earning';
      renderSubtypes('earning');
      updateAdjPreview();
    });

    document.getElementById('adj-update-basic-btn')?.addEventListener('click', async () => {
      const periodMonth = $('#period').value;
      const employeeId = $('#adj-emp').value;
      const newBaseVal = $('#adj-new-base').value.trim();
      const reason = (document.getElementById('adj-basic-reason')?.value || '').trim();
      if (!employeeId) {
        showToast('Select an employee first.', 'error');
        return;
      }
      const newBase = parseFloat(newBaseVal);
      if (isNaN(newBase) || newBase < 0.01) {
        showToast('Enter a valid new base salary (min 0.01).', 'error');
        return;
      }
      const btn = document.getElementById('adj-update-basic-btn');
      const origText = btn.textContent;
      btn.disabled = true;
      btn.textContent = 'Updating...';
      try {
        const token = getCSRF();
        if (!token) { showToast('Session expired. Please refresh.', 'error'); return; }
        const form = new FormData();
        form.append('_token', token);
        form.append('period_month', periodMonth);
        form.append('employee_id', employeeId);
        form.append('new_base_salary', newBase);
        form.append('reason', reason);
        const resp = await fetch(ROUTES.update_basic_salary, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
          body: form,
          credentials: 'same-origin',
        });
        const data = await resp.json().catch(() => ({}));
        if (!resp.ok) {
          throw new Error(data.message || 'Update failed');
        }
        showToast(data.message || 'Basic salary updated.', 'success');
        $('#adj-new-base').value = '';
        if (adjBasicReason) adjBasicReason.value = '';
        if (adjCurrentBase) adjCurrentBase.value = Number(data.new_salary).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        fetchAdjSummary();
        loadData();
      } catch (err) {
        showToast(err.message || 'Failed to update basic salary', 'error');
      } finally {
        btn.disabled = false;
        btn.textContent = origText;
      }
    });

    const modal = document.getElementById('modal');
    const meta = document.getElementById('meta');
    const breakdown = document.getElementById('breakdown');
    const attendanceLists = document.getElementById('attendanceLists');
    const bankDetails = document.getElementById('bankDetails');
    const tabLinks = modal.querySelectorAll('.detail-tab');
    const tabPanels = {
      breakdown: document.getElementById('tab-breakdown'),
      attendance: document.getElementById('tab-attendance'),
      bank: document.getElementById('tab-bank'),
    };
    document.getElementById('close').addEventListener('click', () => modal.style.display = 'none');

    tabLinks.forEach(btn => {
      btn.addEventListener('click', () => {
        const target = btn.dataset.tab;
        tabLinks.forEach(b => b.classList.remove('active'));
        Object.values(tabPanels).forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        tabPanels[target]?.classList.add('active');
      });
    });

    async function openModal(e) {
      const c = calc(e);
      const rawNet = (e.net != null && e.net !== undefined && e.net !== '') ? Number(e.net) : c.net;
      const detailNet = Math.max(0, rawNet);
      const detailGross = (e.gross != null && e.gross !== undefined && e.gross !== '') ? Number(e.gross) : c.gross;
      const formulaBoxEl = document.getElementById('formulaBox');
      meta.innerHTML = `<div class="emp-name">${e.name}</div><div class="emp-meta">${e.id} · ${e.dept}</div>`;
      if (formulaBoxEl) formulaBoxEl.innerHTML = `
        <strong>Gross</strong> = Basic + Allowance + Adjustments.<br>
        Attendance deductions (Late + Absent + Unpaid Leave + Penalties) are capped at Basic Salary.<br>
        EPF and Tax apply only to <strong>Chargeable Salary</strong> (after cap).<br>
        <strong>Net Pay</strong> = max(0, Chargeable − EPF − Tax).
        <div class="scope">All figures for selected payroll month only.</div>`;

      breakdown.innerHTML = `
        <tr><td colspan="2" style="padding:8px;background:#f1f5f9;font-weight:700;">Earnings</td></tr>
        <tr><td style="padding:8px;">Basic Salary</td><td style="padding:8px;text-align:right">RM ${money(e.base || 0)}</td></tr>
        <tr><td style="padding:8px;">Allowance</td><td style="padding:8px;text-align:right">RM ${money(e.allow || 0)}</td></tr>
        <tr><td style="padding:8px;">Adjustments</td><td style="padding:8px;text-align:right">${c.adj ? (c.isDeduction ? '-' : '+') + ' RM ' + money(c.adjAmount) : 'RM 0.00'}</td></tr>
        <tr><td style="padding:8px;background:#e0f2fe;"><strong>Gross Pay</strong></td><td style="padding:8px;text-align:right;background:#e0f2fe;"><strong>RM ${money(detailGross)}</strong></td></tr>
        <tr><td colspan="2" style="padding:8px;background:#f1f5f9;font-weight:700;">Deductions</td></tr>
        <tr><td style="padding:8px;">Late</td><td style="padding:8px;text-align:right">- RM ${money(e.late_ded || 0)}</td></tr>
        <tr><td style="padding:8px;">Absent</td><td style="padding:8px;text-align:right">- RM ${money(e.absent_ded || 0)}</td></tr>
        <tr><td style="padding:8px;">Unpaid Leave</td><td style="padding:8px;text-align:right">- RM ${money(e.unpaid_ded || 0)}</td></tr>
        <tr><td style="padding:8px;">Penalties</td><td style="padding:8px;text-align:right">- RM ${money(e.penalty || 0)}</td></tr>
        <tr><td style="padding:8px;">EPF (11%)</td><td style="padding:8px;text-align:right">- RM ${money(e.epfTax || 0)}</td></tr>
        <tr><td style="padding:8px;">Tax (3%)</td><td style="padding:8px;text-align:right">- RM ${money(e.tax_total || 0)}</td></tr>
        <tr><td style="padding:8px;background:#fee2e2;"><strong>Total Deductions</strong></td><td style="padding:8px;text-align:right;background:#fee2e2;"><strong>- RM ${money(c.deductions)}</strong></td></tr>
        <tr><td style="padding:8px;background:#f8fafc"><strong>Net Pay</strong></td><td style="padding:8px;text-align:right;background:#f8fafc"><strong>RM ${money(detailNet)}</strong></td></tr>
      `;

      tabLinks.forEach(b => b.classList.remove('active'));
      Object.values(tabPanels).forEach(p => p.classList.remove('active'));
      tabLinks[0].classList.add('active');
      tabPanels.breakdown.classList.add('active');

      try {
        const url = `{{ route('admin.payroll.salary.detail') }}?period_month=${encodeURIComponent($('#period').value)}&employee_id=${encodeURIComponent(e.employee_id || '')}`;
        const resp = await fetch(url, { headers: { 'Accept': 'application/json' }});
        if (!resp.ok) throw new Error('Failed to load details');
        const detail = await resp.json();

        // Bank account (employee)
        const bank = (detail.employee && detail.employee.bank) ? detail.employee.bank : {};
        const bankName = bank.bank_name || bank.bank_code || '—';
        const accType = bank.account_type_label || bank.account_type || '—';
        const accNo = bank.account_number_masked || bank.account_number || '—';
        const branch = bank.branch || '—';
        const swift = bank.swift || '—';
        if (bankDetails) {
          bankDetails.innerHTML = `
            <div class="salary-detail-section">
              <div class="salary-detail-section-title">Bank account</div>
              <div class="info-row"><span class="info-label">Bank</span><span class="info-value">${bankName}</span></div>
              <div class="info-row"><span class="info-label">Account number</span><span class="info-value">${accNo}</span></div>
              <div class="info-row"><span class="info-label">Account type</span><span class="info-value">${accType}</span></div>
              <div class="info-row"><span class="info-label">Branch</span><span class="info-value">${branch}</span></div>
              <div class="info-row"><span class="info-label">SWIFT</span><span class="info-value">${swift}</span></div>
            </div>
          `;
        }

        const att = detail.attendance || {};
        const present = Array.isArray(att.present_days) ? att.present_days : [];
        const absentMarked = Array.isArray(att.absent_days) ? att.absent_days : [];
        const absentPayroll = Number(att.absent_days_payroll ?? 0);
        const workedDays = Number(att.worked_days ?? 0);
        const approvedLeaveDays = Number(att.approved_leave_days ?? 0);
        const workingDaysInMonth = Number(att.working_days_in_month ?? 26);
        const late = Array.isArray(att.late) ? att.late : [];
        const incomplete = Array.isArray(att.incomplete) ? att.incomplete : [];
        const leaveDays = Array.isArray(att.leave_days) ? att.leave_days : [];
        attendanceLists.innerHTML = `
          <div class="salary-detail-section">
            <div class="salary-detail-section-title">Working days</div>
            <div class="info-row"><span class="info-label">Present days</span><span class="info-value">${present.length} day${present.length !== 1 ? 's' : ''}</span></div>
            <div class="info-row"><span class="info-label">Late</span><span class="info-value">${late.length} record${late.length !== 1 ? 's' : ''}</span></div>
            <div class="info-row highlight"><span class="info-label">Worked days (present + late)</span><span class="info-value">${workedDays} of ${workingDaysInMonth} days</span></div>
          </div>
          <div class="salary-detail-section">
            <div class="salary-detail-section-title">Leave</div>
            <div class="info-row"><span class="info-label">Leave days (in attendance)</span><span class="info-value">${leaveDays.length} day${leaveDays.length !== 1 ? 's' : ''}</span></div>
            <div class="info-row"><span class="info-label">Approved leave (this period)</span><span class="info-value">${approvedLeaveDays} day${approvedLeaveDays !== 1 ? 's' : ''}</span></div>
          </div>
          <div class="salary-detail-section">
            <div class="salary-detail-section-title">Absent (payroll deduction)</div>
            <div class="info-row formula-row"><span class="info-label">Absent days used in payroll</span><span class="info-value">${absentPayroll} day${absentPayroll !== 1 ? 's' : ''} (${workingDaysInMonth} − ${workedDays} − ${approvedLeaveDays})</span></div>
          </div>
          <div class="salary-detail-section">
            <div class="salary-detail-section-title">Other</div>
            <div class="info-row"><span class="info-label">Incomplete punch</span><span class="info-value">${incomplete.length} day${incomplete.length !== 1 ? 's' : ''}</span></div>
          </div>
        `;

        const b = detail.breakdown || {};
        const bGross = b.gross != null ? Number(b.gross) : detailGross;
        const bNet = Math.max(0, (b.net != null ? Number(b.net) : detailNet));
        const bChargeable = Number(b.chargeable_salary ?? 0);
        const bOriginalAtt = Number(b.original_attendance_deduction ?? 0);
        const bCappedAtt = Number(b.capped_attendance_deduction ?? 0);
        const bDed = b.total_deductions != null ? Number(b.total_deductions) : c.deductions;
        const showEpfTax = bChargeable > 0;
        const epfRow = showEpfTax ? `<tr><td style="padding:8px;">EPF (11% on chargeable)</td><td style="padding:8px;text-align:right">- RM ${money(b.epf ?? e.epfTax ?? 0)}</td></tr>` : '';
        const taxRow = showEpfTax ? `<tr><td style="padding:8px;">Tax (3% on chargeable)</td><td style="padding:8px;text-align:right">- RM ${money(b.tax ?? e.tax_total ?? 0)}</td></tr>` : '';
        breakdown.innerHTML = `
          <tr><td colspan="2" style="padding:8px;background:#f1f5f9;font-weight:700;">Earnings</td></tr>
          <tr><td style="padding:8px;">Basic Salary</td><td style="padding:8px;text-align:right">RM ${money(b.base ?? e.base ?? 0)}</td></tr>
          <tr><td style="padding:8px;">Allowance</td><td style="padding:8px;text-align:right">RM ${money(b.allowance ?? e.allow ?? 0)}</td></tr>
          <tr><td style="padding:8px;">Adjustments</td><td style="padding:8px;text-align:right">RM ${money(b.adjustment ?? e.adjustment_total ?? 0)}</td></tr>
          <tr><td style="padding:8px;background:#e0f2fe;"><strong>Gross Pay</strong></td><td style="padding:8px;text-align:right;background:#e0f2fe;"><strong>RM ${money(bGross)}</strong></td></tr>
          <tr><td colspan="2" style="padding:8px;background:#f1f5f9;font-weight:700;">Attendance-related deductions</td></tr>
          <tr><td style="padding:8px;">Late</td><td style="padding:8px;text-align:right">- RM ${money(b.late_ded ?? e.late_ded ?? 0)}</td></tr>
          <tr><td style="padding:8px;">Absent</td><td style="padding:8px;text-align:right">- RM ${money(b.absent_ded ?? e.absent_ded ?? 0)}</td></tr>
          <tr><td style="padding:8px;">Unpaid Leave</td><td style="padding:8px;text-align:right">- RM ${money(b.unpaid_ded ?? e.unpaid_ded ?? 0)}</td></tr>
          <tr><td style="padding:8px;">Penalties</td><td style="padding:8px;text-align:right">- RM ${money(b.penalty ?? e.penalty ?? 0)}</td></tr>
          <tr><td style="padding:8px;background:#fef3c7;"><strong>Original deduction total</strong></td><td style="padding:8px;text-align:right;background:#fef3c7;"><strong>- RM ${money(bOriginalAtt)}</strong></td></tr>
          <tr><td style="padding:8px;background:#fef3c7;">Capped at Basic Salary</td><td style="padding:8px;text-align:right;background:#fef3c7;"><strong>- RM ${money(bCappedAtt)}</strong></td></tr>
          <tr><td style="padding:8px;background:#d1fae5;"><strong>Chargeable salary (after cap)</strong></td><td style="padding:8px;text-align:right;background:#d1fae5;"><strong>RM ${money(bChargeable)}</strong></td></tr>
          ${epfRow}
          ${taxRow}
          <tr><td style="padding:8px;background:#fee2e2;"><strong>Total deductions</strong></td><td style="padding:8px;text-align:right;background:#fee2e2;"><strong>- RM ${money(bDed)}</strong></td></tr>
          <tr><td style="padding:8px;background:#f8fafc"><strong>Net Pay</strong></td><td style="padding:8px;text-align:right;background:#f8fafc"><strong>RM ${money(bNet)}</strong></td></tr>
        `;

      } catch (err) {
        attendanceLists.innerHTML = '<div class="salary-detail-section"><div class="info-row"><span class="info-label">—</span><span class="info-value">No records for this period.</span></div></div>';
        if (bankDetails) {
          bankDetails.innerHTML = '<div class="salary-detail-section"><div class="info-row"><span class="info-label">Bank</span><span class="info-value">—</span></div></div>';
        }
      }

      modal.style.display = 'flex';
    }


    loadData();
  });
  </script>
</body>
</html>
