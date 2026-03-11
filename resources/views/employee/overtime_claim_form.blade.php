<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ isset($claim) ? 'Edit' : 'New' }} OT Claim - HRMS</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    main { padding:1.5rem 2rem; }
    .card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:18px; margin-bottom:16px; }
    .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; }
    label { font-weight:600; display:block; margin-bottom:4px; font-size:14px; }
    input, select, textarea { width:100%; padding:10px 12px; border:1px solid #e5e7eb; border-radius:10px; font-size:14px; }
    textarea { min-height:90px; resize:vertical; }
    .rules-box { font-size:12px; color:#64748b; background:#f8fafc; padding:10px 14px; border-radius:10px; margin-bottom:16px; line-height:1.5; }
    .duplicate-warn { background:#fef3c7; border:1px solid #f59e0b; color:#92400e; padding:14px; border-radius:10px; margin-bottom:14px; }
    .duplicate-warn h4 { margin:0 0 8px; font-size:14px; }
    .duplicate-warn .actions { margin-top:10px; display:flex; gap:8px; flex-wrap:wrap; }
    .supervisor-line { background:#e0f2fe; color:#0369a1; padding:10px 12px; border-radius:10px; margin-bottom:14px; font-size:14px; }
    .supervisor-line.no-supervisor { background:#fee2e2; color:#991b1b; }
    .summary-card { background:#f0fdf4; border:1px solid #86efac; border-radius:10px; padding:14px; margin-top:14px; font-size:13px; }
    .summary-card .row { display:flex; justify-content:space-between; margin-bottom:6px; }
    .location-section { margin-top:14px; padding:12px; background:#f8fafc; border-radius:10px; }
    #outside-fields, #location-other-wrap { display:none; }
    .btn { padding:10px 16px; border-radius:10px; border:none; cursor:pointer; font-weight:600; text-decoration:none; display:inline-block; font-size:14px; }
    .btn-primary { background:#2563eb; color:#fff; }
    .btn-secondary { background:#e2e8f0; color:#374151; }
    .btn:disabled { opacity:0.6; cursor:not-allowed; }
    #hours_display { font-weight:700; color:#0f172a; background:#f1f5f9; padding:10px 12px; border-radius:10px; }
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
      <div class="breadcrumb">Attendance · OT Claims · {{ isset($claim) ? 'Edit' : 'New' }}</div>
      <h2 style="margin:0 0 .3rem 0;">{{ isset($claim) ? 'Edit OT Claim' : 'Claim OT' }}</h2>

      <div class="rules-box">
        <strong>Rules:</strong> Date must not be in the future. Minimum claim: 0.5 hour. Maximum per day: 8 hours. Reason required (min 10 characters). Hours are auto-calculated from start, end and break.
      </div>

      @if(isset($hasSupervisor) && !$hasSupervisor)
        <div class="supervisor-line no-supervisor">No supervisor assigned to your department. Contact HR before submitting.</div>
      @elseif(isset($supervisorName) && isset($departmentName))
        <div class="supervisor-line">Will be sent to: <strong>{{ $supervisorName }}</strong> ({{ $departmentName }} Supervisor)</div>
      @endif

      @if($errors->any())
        <div class="notice error" style="padding:10px; background:#fee2e2; border-radius:10px; margin-bottom:12px;">{{ $errors->first() }}</div>
      @endif

      <div id="duplicate-warning" class="duplicate-warn" style="display:none;">
        <h4><i class="fa-solid fa-triangle-exclamation"></i> You already submitted an OT claim for <span id="dup-date"></span>.</h4>
        <p style="margin:0 0 6px;" id="dup-summary"></p>
        <div class="actions">
          <a href="#" id="dup-view-link" class="btn btn-secondary">View existing claim</a>
          <a href="#" id="dup-edit-link" class="btn btn-primary" style="display:none;">Edit existing claim</a>
          <span style="font-size:12px; color:#64748b;">Or choose another date to create a new claim.</span>
        </div>
      </div>

      <div class="card">
        <form method="POST" action="{{ isset($claim) ? route('employee.ot_claims.update', $claim) : route('employee.ot_claims.store') }}" enctype="multipart/form-data" id="otClaimForm">
          @csrf
          @if(isset($claim)) @method('PUT') @endif

          <div class="grid">
            <div>
              <label for="date">Date *</label>
              <input type="date" id="date" name="date" value="{{ old('date', isset($claim) ? $claim->date->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
            </div>
            <div>
              <label for="start_time">Start time *</label>
              <input type="time" id="start_time" name="start_time" value="{{ old('start_time', isset($claim) && $claim->start_time ? \Carbon\Carbon::parse($claim->start_time)->format('H:i') : '18:00') }}" required>
            </div>
            <div>
              <label for="end_time">End time *</label>
              <input type="time" id="end_time" name="end_time" value="{{ old('end_time', isset($claim) && $claim->end_time ? \Carbon\Carbon::parse($claim->end_time)->format('H:i') : '20:00') }}" required>
            </div>
            <div>
              <label for="break_minutes">Break deduction</label>
              <select id="break_minutes" name="break_minutes">
                <option value="0" {{ old('break_minutes', isset($claim) ? ($claim->break_minutes ?? 0) : 0) == 0 ? 'selected' : '' }}>0 min</option>
                <option value="15" {{ old('break_minutes', isset($claim) ? ($claim->break_minutes ?? 0) : 0) == 15 ? 'selected' : '' }}>15 min</option>
                <option value="30" {{ old('break_minutes', isset($claim) ? ($claim->break_minutes ?? 0) : 0) == 30 ? 'selected' : '' }}>30 min</option>
                <option value="45" {{ old('break_minutes', isset($claim) ? ($claim->break_minutes ?? 0) : 0) == 45 ? 'selected' : '' }}>45 min</option>
                <option value="60" {{ old('break_minutes', isset($claim) ? ($claim->break_minutes ?? 0) : 0) == 60 ? 'selected' : '' }}>60 min</option>
              </select>
            </div>
            <div>
              <label>Hours (auto)</label>
              <div id="hours_display">—</div>
              <input type="hidden" id="hours" name="hours" value="{{ old('hours', isset($claim) ? $claim->hours : '') }}">
            </div>
          </div>
          <p id="hours_detail" style="margin:4px 0 0; font-size:12px; color:#64748b;"></p>

          <div class="grid" style="margin-top:14px;">
            <div>
              <label for="rate_type">Rate (multiplier)</label>
              <select id="rate_type" name="rate_type">
                <option value="1.5" {{ old('rate_type', isset($claim) ? $claim->rate_type : 1.5) == 1.5 ? 'selected' : '' }}>1.5x — Normal OT</option>
                <option value="2" {{ old('rate_type', isset($claim) ? $claim->rate_type : 1.5) == 2 ? 'selected' : '' }}>2.0x — Rest day</option>
                <option value="3" {{ old('rate_type', isset($claim) ? $claim->rate_type : 1.5) == 3 ? 'selected' : '' }}>3.0x — Public holiday</option>
              </select>
            </div>
          </div>

          <div class="location-section" style="margin-top:14px;">
            <label>Location *</label>
            <div style="display:flex; flex-wrap:wrap; gap:12px; margin-top:6px;">
              <label style="display:inline-flex; align-items:center; font-weight:500;"><input type="radio" name="location_type" value="INSIDE" {{ old('location_type', isset($claim) ? ($claim->location_type ?? 'INSIDE') : 'INSIDE') === 'INSIDE' ? 'checked' : '' }}> Inside office</label>
              <label style="display:inline-flex; align-items:center; font-weight:500;"><input type="radio" name="location_type" value="OUTSIDE" {{ old('location_type', isset($claim) ? $claim->location_type : '') === 'OUTSIDE' ? 'checked' : '' }}> Outside</label>
              <label style="display:inline-flex; align-items:center; font-weight:500;"><input type="radio" name="location_type" value="CLIENT_SITE" {{ old('location_type', isset($claim) ? $claim->location_type : '') === 'CLIENT_SITE' ? 'checked' : '' }}> Client site</label>
              <label style="display:inline-flex; align-items:center; font-weight:500;"><input type="radio" name="location_type" value="REMOTE_WFH" {{ old('location_type', isset($claim) ? $claim->location_type : '') === 'REMOTE_WFH' ? 'checked' : '' }}> Remote / WFH</label>
              <label style="display:inline-flex; align-items:center; font-weight:500;"><input type="radio" name="location_type" value="OTHER" {{ old('location_type', isset($claim) ? $claim->location_type : '') === 'OTHER' ? 'checked' : '' }}> Other</label>
            </div>
            <div id="location-other-wrap" class="location-section" style="margin-top:8px;">
              <label for="location_other">Specify (Other)</label>
              <input type="text" id="location_other" name="location_other" value="{{ old('location_other', $claim->location_other ?? '') }}" placeholder="e.g. Training venue">
            </div>
          </div>

          @if(!empty($areas) && $areas->count() > 0)
          <div class="location-section" style="margin-top:8px;">
            <label for="route_to_area_id">Route to area (optional)</label>
            <select id="route_to_area_id" name="route_to_area_id">
              <option value="">— My department (default) —</option>
              @foreach($areas as $a)
                <option value="{{ $a->id }}" {{ old('route_to_area_id', isset($claim) ? $claim->area_id : '') == $a->id ? 'selected' : '' }}>{{ $a->name }}</option>
              @endforeach
            </select>
          </div>
          @endif

          <div id="outside-fields" class="location-section" style="margin-top:8px;">
            <label for="proof_image">Proof image (required for Client site / Other)</label>
            <input type="file" id="proof_image" name="proof_image" accept="image/*">
            @if(isset($claim) && $claim->proof_image_path)
              <p style="margin-top:6px; font-size:13px;">Current: <a href="{{ asset('storage/'.$claim->proof_image_path) }}" target="_blank">View proof</a></p>
            @endif
            <label for="missing_proof_reason" style="margin-top:10px;">If no proof, reason *</label>
            <textarea id="missing_proof_reason" name="missing_proof_reason" placeholder="Explain why proof is not attached">{{ old('missing_proof_reason', $claim->missing_proof_reason ?? '') }}</textarea>
          </div>

          <div style="margin-top:14px;">
            <label for="reason">Reason * (min 10 characters)</label>
            <textarea id="reason" name="reason" required minlength="10" maxlength="500" placeholder="E.g. Release deployment, urgent client request">{{ old('reason', $claim->reason ?? '') }}</textarea>
          </div>
          <div style="margin-top:12px;">
            <label for="attachment">Attachment (optional)</label>
            <input type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
            <small style="color:#64748b;">Photo, email approval, or job order. Max 10MB.</small>
          </div>
          <div style="margin-top:12px;">
            <label for="supporting_info">Supporting info (optional)</label>
            <textarea id="supporting_info" name="supporting_info" placeholder="Additional details">{{ old('supporting_info', $claim->supporting_info ?? '') }}</textarea>
          </div>

          <div class="summary-card" id="summary-card">
            <strong>Summary</strong>
            <div class="row"><span>Date</span><span id="sum-date">—</span></div>
            <div class="row"><span>Start – End</span><span id="sum-times">—</span></div>
            <div class="row"><span>Break</span><span id="sum-break">—</span></div>
            <div class="row"><span>Final hours</span><span id="sum-hours">—</span></div>
            <div class="row"><span>Rate</span><span id="sum-rate">—</span></div>
            <div class="row"><span>Sent to</span><span id="sum-supervisor">{{ $supervisorName ?? '—' }} ({{ $departmentName ?? '—' }})</span></div>
          </div>

          <input type="hidden" name="submit_now" id="submit_now_val" value="{{ old('submit_now', !isset($claim) || $claim->status !== 'DRAFT') ? '1' : '0' }}">
          <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
            <button type="submit" name="action" value="draft" class="btn btn-secondary">Save Draft</button>
            <button type="submit" name="action" value="submit" id="btn_submit" class="btn btn-primary">Submit to Supervisor</button>
            <a href="{{ route('employee.ot_claims.index') }}" class="btn btn-secondary">Back to list</a>
          </div>
        </form>
      </div>
    </main>
  </div>

  <script>
  (function() {
    var form = document.getElementById('otClaimForm');
    var dateEl = document.getElementById('date');
    var startEl = document.getElementById('start_time');
    var endEl = document.getElementById('end_time');
    var breakEl = document.getElementById('break_minutes');
    var hoursDisplay = document.getElementById('hours_display');
    var hoursInput = document.getElementById('hours');
    var hoursDetail = document.getElementById('hours_detail');
    var submitNowVal = document.getElementById('submit_now_val');
    var btnSubmit = document.getElementById('btn_submit');
    var duplicateWarn = document.getElementById('duplicate-warning');
    var checkUrl = "{{ route('employee.ot_claims.check_duplicate') }}";
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function parseTime(s) {
      var p = s.split(':');
      return parseInt(p[0],10)*60 + parseInt(p[1]||0,10);
    }
    function formatHours(h) {
      var m = Math.round(h*60);
      var H = Math.floor(m/60);
      var M = m % 60;
      if (H && M) return H + 'h ' + M + 'm';
      if (H) return H + 'h';
      return M + 'm';
    }
    function computeHours() {
      var start = parseTime(startEl.value);
      var end = parseTime(endEl.value);
      var breakM = parseInt(breakEl.value,10) || 0;
      if (end <= start) end += 24*60;
      var totalM = Math.max(0, end - start - breakM);
      var rawHours = totalM / 60;
      var rounded = Math.round(rawHours * 2) / 2;
      return { raw: rawHours, rounded: rounded, totalM: totalM };
    }
    function updateHoursUI() {
      var r = computeHours();
      hoursDisplay.textContent = r.rounded.toFixed(1) + ' h';
      hoursInput.value = r.rounded;
      hoursDetail.textContent = 'Total: ' + formatHours(r.raw) + ' (rounded to ' + r.rounded + 'h)';
      updateSummary();
    }
    function updateSummary() {
      document.getElementById('sum-date').textContent = dateEl.value || '—';
      document.getElementById('sum-times').textContent = (startEl.value || '—') + ' – ' + (endEl.value || '—');
      document.getElementById('sum-break').textContent = (breakEl.value || '0') + ' min';
      var r = computeHours();
      document.getElementById('sum-hours').textContent = r.rounded + ' h';
      var rate = document.getElementById('rate_type');
      document.getElementById('sum-rate').textContent = (rate ? rate.options[rate.selectedIndex].text : '—');
    }

    function checkDuplicate() {
      var d = dateEl.value;
      if (!d) { duplicateWarn.style.display = 'none'; return; }
      fetch(checkUrl + '?date=' + encodeURIComponent(d) + '&_token=' + encodeURIComponent(csrf), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(res) { return res.json(); })
        .then(function(data) {
          if (!data.has_duplicate) {
            duplicateWarn.style.display = 'none';
            return;
          }
          var c = data.claim;
          duplicateWarn.style.display = 'block';
          document.getElementById('dup-date').textContent = c.date;
          document.getElementById('dup-summary').textContent = 'Status: ' + c.status + ' | Hours: ' + c.hours + ' | Submitted: ' + (c.submitted_at || '—');
          var viewL = document.getElementById('dup-view-link');
          var editL = document.getElementById('dup-edit-link');
          viewL.href = c.view_url || '#';
          if (c.is_editable && c.edit_url) {
            editL.href = c.edit_url;
            editL.style.display = 'inline-block';
          } else {
            editL.style.display = 'none';
          }
        })
        .catch(function() { duplicateWarn.style.display = 'none'; });
    }

    dateEl.addEventListener('change', function() { checkDuplicate(); updateSummary(); });
    startEl.addEventListener('input', updateHoursUI);
    startEl.addEventListener('change', updateHoursUI);
    endEl.addEventListener('input', updateHoursUI);
    endEl.addEventListener('change', updateHoursUI);
    breakEl.addEventListener('change', updateHoursUI);
    document.getElementById('rate_type').addEventListener('change', updateSummary);

    form.addEventListener('submit', function(e) {
      var submitBtn = e.submitter && e.submitter.value === 'submit';
      submitNowVal.value = submitBtn ? '1' : '0';
      if (submitBtn) {
        var r = computeHours();
        if (r.rounded < 0.25) {
          e.preventDefault();
          alert('Hours must be at least 0.5.');
          return;
        }
        if (document.getElementById('reason').value.trim().length < 10) {
          e.preventDefault();
          alert('Reason must be at least 10 characters.');
          return;
        }
        if (duplicateWarn.style.display === 'block') {
          if (!confirm('You have an existing claim for this date. Submit anyway with a new date or go back and edit the existing one.')) {
            e.preventDefault();
            return;
          }
        }
      }
    });

    var locRadios = document.querySelectorAll('input[name="location_type"]');
    var outsideFields = document.getElementById('outside-fields');
    var locationOtherWrap = document.getElementById('location-other-wrap');
    function toggleLocation() {
      var other = document.querySelector('input[name="location_type"]:checked');
      var v = other ? other.value : '';
      outsideFields.style.display = (v === 'OUTSIDE' || v === 'CLIENT_SITE' || v === 'OTHER') ? 'block' : 'none';
      locationOtherWrap.style.display = v === 'OTHER' ? 'block' : 'none';
    }
    locRadios.forEach(function(r){ r.addEventListener('change', toggleLocation); });
    toggleLocation();

    var hasSupervisor = {{ isset($hasSupervisor) && $hasSupervisor ? 'true' : 'false' }};
    if (!hasSupervisor && btnSubmit) btnSubmit.disabled = true;

    updateHoursUI();
    checkDuplicate();
  })();
  </script>
</body>
</html>
