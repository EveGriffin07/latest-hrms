<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Face Attendance</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    main { padding:2rem; }
    .page-title { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
    .card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:18px; box-shadow:0 8px 18px rgba(15,23,42,0.08); }
    .info-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px; margin-bottom:14px; }
    .info-pill { border:1px solid #e5e7eb; border-radius:10px; padding:10px 12px; background:#f8fafc; font-weight:600; }
    .label { font-size:12px; color:#94a3b8; text-transform:uppercase; letter-spacing:.02em; display:block; }
    .value { color:#0f172a; font-weight:700; }
    video, canvas { width:100%; max-width:520px; border-radius:12px; background:#0f172a; }
    #snap-preview { margin-top:10px; width:100%; max-width:520px; border-radius:12px; display:none; border:1px solid #e5e7eb; }
    .controls { display:flex; gap:10px; margin-top:10px; flex-wrap:wrap; align-items:center; }
    .notice { padding:12px 14px; border-radius:10px; margin-bottom:14px; }
    .success { background:#ecfdf3; border:1px solid #bbf7d0; color:#166534; }
    .error { background:#fef2f2; border:1px solid #fecdd3; color:#991b1b; }
    .error + .try-again { margin-top: -8px; margin-bottom: 14px; }
    .try-again { font-size: 13px; color: #64748b; }
    .two-col { display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:16px; }
    .subtle { color:#64748b; font-size:12px; margin-top:6px; }
    .status { font-size:12px; color:#64748b; margin-top:6px; }
    .mode-pill { padding:6px 12px; border-radius:8px; font-weight:600; font-size:14px; }
    .mode-check-in { background:#dbeafe; color:#1e40af; border:1px solid #93c5fd; }
    .mode-check-out { background:#fef3c7; color:#b45309; border:1px solid #fcd34d; }
    .mode-done { background:#e5e7eb; color:#4b5563; border:1px solid #d1d5db; }
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
      <div class="page-title">
        <h2 style="margin:0;">Face Attendance</h2>
        <span class="info-pill" style="padding:6px 10px; background:#e0f2fe; color:#0369a1; border-color:#bae6fd;">Self check</span>
        @if(isset($mode))
          @if($mode === 'CHECK_IN')
            <span class="mode-pill mode-check-in"><i class="fa-solid fa-sign-in-alt"></i> Check In</span>
          @elseif($mode === 'CHECK_OUT')
            <span class="mode-pill mode-check-out"><i class="fa-solid fa-sign-out-alt"></i> Check Out</span>
          @else
            <span class="mode-pill mode-done">Already checked out today</span>
          @endif
        @endif
      </div>
      <p class="subtitle">The system compares your face from the camera with your enrolled photo to record check-in or check-out.</p>

      @if(!$hasEnrollment)
        <div class="notice error">Upload your photo first at <a href="{{ route('employee.face.enroll') }}">Face Enrollment</a>, then you can use camera to check in/out.</div>
      @endif

      @if(session('success'))
        <div class="notice success">{{ session('success') }}</div>
      @endif
      @if($errors->any())
        <div class="notice error" id="error-notice">{{ $errors->first() }}</div>
        <p class="try-again">You can correct the issue and submit again.</p>
      @endif

      <div class="card" style="margin-top:10px;">
        <div class="info-grid">
          <div class="info-pill">
            <span class="label">Employee</span>
            <span class="value">{{ $employee->employee_code ?? 'N/A' }} - {{ $employee->user->name ?? 'You' }}</span>
          </div>
          <div class="info-pill">
            <span class="label">Enrollment</span>
            <span class="value">{{ ($hasEnrollment ?? false) ? 'Found' : 'Not enrolled' }}</span>
          </div>
          @if(isset($todayRecord) && $todayRecord)
            <div class="info-pill">
              <span class="label">Today</span>
              <span class="value">{{ $todayRecord->clock_in_time ? \Carbon\Carbon::parse($todayRecord->clock_in_time)->format('H:i') : '—' }} / {{ $todayRecord->clock_out_time ? \Carbon\Carbon::parse($todayRecord->clock_out_time)->format('H:i') : '—' }}</span>
            </div>
          @endif
        </div>

        @if(isset($mode) && $mode !== null)
        <div class="two-col" style="margin-top:12px;">
          <div>
            <div class="label" style="margin-bottom:6px;">Live Camera</div>
            <video id="camera" autoplay playsinline muted></video>
            <div class="controls">
              <button type="button" class="btn btn-secondary btn-small" id="start"><i class="fa-solid fa-camera"></i> Start</button>
              <button type="button" class="btn btn-primary btn-small" id="capture" disabled><i class="fa-solid fa-circle-dot"></i> Capture</button>
            </div>
            <div class="subtle">Capture 1 frame (or up to 3 for better accuracy).</div>
          </div>
          <div>
            <div class="label" style="margin-bottom:6px;">Snapshot</div>
            <canvas id="frame" hidden></canvas>
            <img id="snap-preview" alt="Captured frame" style="max-width:100%; border-radius:8px; display:none;">
            <div class="controls" style="justify-content:flex-start; margin-top:8px;">
              <button type="button" class="btn btn-primary" id="submit" disabled><i class="fa-solid fa-paper-plane"></i> Submit</button>
            </div>
            <div class="subtle">Then submit to record {{ $mode === 'CHECK_IN' ? 'check-in' : 'check-out' }}.</div>
          </div>
        </div>

        <form id="attendance-form" method="POST" action="{{ route('employee.attendance.face.post') }}" enctype="multipart/form-data" style="display:none;">
          @csrf
          <input type="file" id="frame-file" name="frames[]" accept="image/jpeg,image/png,image/jpg" multiple>
        </form>
        @else
        <p class="subtle" style="margin-top:10px;">You have already checked out today. No further action needed.</p>
        @endif
      </div>
    </main>
  </div>
  <script>
    (function () {
      const hasEnrollment = {{ $hasEnrollment ? 'true' : 'false' }};
      const canScan = hasEnrollment && {{ isset($mode) && $mode !== null ? 'true' : 'false' }};
      const video = document.getElementById('camera');
      const canvas = document.getElementById('frame');
      const startBtn = document.getElementById('start');
      const captureBtn = document.getElementById('capture');
      const submitBtn = document.getElementById('submit');
      const frameInput = document.getElementById('frame-file');
      const snapPreview = document.getElementById('snap-preview');
      const form = document.getElementById('attendance-form');

      const statusEl = document.createElement('div');
      statusEl.className = 'status';
      statusEl.textContent = 'Camera idle.';
      if (video && video.parentNode) video.parentNode.insertBefore(statusEl, video.nextSibling);

      function setStatus(msg, isError) {
        statusEl.textContent = msg;
        statusEl.style.color = isError ? '#b91c1c' : '#64748b';
      }

      let stream;
      let capturedFiles = [];

      function stopStream() {
        if (stream) {
          stream.getTracks().forEach(t => t.stop());
          stream = null;
        }
      }

      if (!canScan) {
        if (startBtn) startBtn.disabled = true;
        if (captureBtn) captureBtn.disabled = true;
        if (!hasEnrollment) setStatus('Face attendance locked until enrollment succeeds.', true);
        else setStatus('Already checked out today.', false);
      } else {
        startBtn?.addEventListener('click', async () => {
          try {
            stopStream();
            stream = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480, facingMode: 'user' } });
            video.srcObject = stream;
            await video.play();
            setStatus('Camera ready. Capture and submit.');
            captureBtn.disabled = false;
          } catch (e) {
            setStatus('Cannot access camera: ' + e.message, true);
            alert('Cannot access camera: ' + e.message);
          }
        });

        captureBtn?.addEventListener('click', () => {
          if (!video.srcObject) {
            alert('Start the camera first.');
            return;
          }
          const ctx = canvas.getContext('2d');
          canvas.width = video.videoWidth || 640;
          canvas.height = video.videoHeight || 480;
          ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
          canvas.toBlob((blob) => {
            if (!blob) return;
            const file = new File([blob], 'frame.jpg', { type: 'image/jpeg' });
            capturedFiles = [file];
            try {
              const dt = new DataTransfer();
              dt.items.add(file);
              frameInput.files = dt.files;
            } catch (_) {
              frameInput.value = '';
            }
            snapPreview.src = URL.createObjectURL(file);
            snapPreview.style.display = 'block';
            submitBtn.disabled = false;
            setStatus('Frame captured. Submit to record.');
          }, 'image/jpeg', 0.9);
        });

        submitBtn?.addEventListener('click', () => {
          if (!frameInput.files || !frameInput.files.length) {
            alert('Capture a frame first.');
            return;
          }
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';
          form.submit();
        });
      }

      window.addEventListener('beforeunload', stopStream);
    })();
  </script>
</body>
</html>
