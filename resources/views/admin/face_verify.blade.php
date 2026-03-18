<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Face Verification - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    main { padding: 2rem; }
    .breadcrumb { font-size: .85rem; color:#94a3b8; margin-bottom: 1rem; }
    h2 { color:#0ea5e9; margin:0 0 .25rem 0; }
    .subtitle { color:#64748b; margin-bottom:1rem; }
    .notice { padding:12px 14px; border-radius:10px; margin-bottom:14px; }
    .notice.success { background:#ecfdf3; color:#166534; border:1px solid #bbf7d0; }
    .notice.error { background:#fef2f2; color:#991b1b; border:1px solid #fecdd3; }
    .notice.info { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
    .camera-wrap { display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:16px; margin-top:8px; }
    .camera-card { background:#f8fafc; border:1px dashed #cbd5e1; border-radius:12px; padding:14px; }
    .camera-card video, .camera-card canvas { width:100%; border-radius:10px; background:#0f172a; min-height:200px; }
    .muted { color:#94a3b8; font-size:12px; margin-top:6px; display:block; }
    .pill { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; background:#ecfeff; color:#0ea5e9; font-size:12px; margin-left:8px; }
    .stat { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin:12px 0; }
    .stat .card { border:1px solid #e5e7eb; border-radius:10px; padding:12px; background:#fff; }
    .stat .card label { color:#6b7280; font-size:12px; text-transform:uppercase; letter-spacing:.02em; }
    .stat .card strong { color:#0f172a; font-size:15px; }
    .card-link { text-decoration:none; color:inherit; display:block; }
    .card-link:hover .card { box-shadow:0 10px 20px rgba(15,23,42,0.08); border-color:#bfdbfe; }
  </style>
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
      <a href="{{ route('admin.profile') }}" style="text-decoration: none; color: inherit;">
        <i class="fa-regular fa-bell"></i> &nbsp; {{ Auth::user()->name ?? 'HR Admin' }}
      </a>
    </div>
  </header>

  <div class="container">
    @include('admin.layout.sidebar')

    <main>
      <div class="breadcrumb">Attendance · Face Verification</div>
      <h2>Face Verification <span class="pill"><i class="fa-solid fa-shield-heart"></i> Self check</span></h2>
      <p class="subtitle">Verify identity with the stored face embedding. Use a clear, front-facing photo.</p>

      @if (session('success'))
        <div class="notice success">
          <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
      @endif

      @if ($errors->any())
        <div class="notice error">
          <i class="fa-solid fa-triangle-exclamation"></i> {{ $errors->first() }}
        </div>
      @endif

      <div class="form-container">
        <div class="form-card">
          <h3><i class="fa-solid fa-user-shield"></i> Face Verification</h3>

          <div class="stat">
            <div class="card">
              <label>Employee</label>
              <strong>{{ $employee->employee_code }} — {{ $employee->user->name ?? 'User' }}</strong>
            </div>
            <div class="card">
              <label>Model</label>
              <strong>{{ $faceData->model_name ?? config('services.face_api.model', 'buffalo_l') }}</strong>
            </div>
            <div class="card">
              <label>Last Updated</label>
              <strong>{{ $faceData?->updated_at?->format('M d, Y H:i') ?? 'Not enrolled' }}</strong>
            </div>
          </div>

          @php
            $today = now()->toDateString();
            $todayIn = $todayRecord?->clock_in_time ? \Carbon\Carbon::parse($todayRecord->clock_in_time)->format('H:i') : '—';
            $todayOut = $todayRecord?->clock_out_time ? \Carbon\Carbon::parse($todayRecord->clock_out_time)->format('H:i') : '—';
            $todayStatus = $todayRecord?->at_status ?? 'not set';
          @endphp

          <div class="stat" style="margin-top:8px;">
            <a href="{{ route('employee.attendance.log') }}" class="card-link">
              <div class="card">
                <label>Today</label>
                <strong>{{ ucfirst($todayStatus) }}</strong>
                <div style="margin-top:6px; font-size:13px; color:#64748b;">
                  <span style="display:inline-block; margin-right:8px;">In: <strong style="color:#0f172a;">{{ $todayIn }}</strong></span>
                  <span>Out: <strong style="color:#0f172a;">{{ $todayOut }}</strong></span>
                </div>
              </div>
            </a>
          </div>

          <form id="face-verify-form" method="POST" enctype="multipart/form-data" action="{{ route('admin.face.verify.post', $employee->employee_id) }}">
            @csrf

            <div class="camera-wrap">
              <div class="camera-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                  <span style="font-weight:600; color:#0f172a;">Live Camera</span>
                </div>
                <video id="camera-preview" autoplay playsinline muted></video>
                <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:10px;">
                  <button type="button" class="btn btn-primary btn-small" id="scan-face" {{ $faceData ? '' : 'disabled' }}>
                    <i class="fa-solid fa-circle-dot"></i> Scan &amp; Verify
                  </button>
                </div>
                <small class="muted" id="snapshot-status">Click "Scan &amp; Verify" and look at the camera.</small>
              </div>
            </div>

            <canvas id="snapshot" aria-label="Captured preview" style="display:none;"></canvas>

            <input type="file" id="verify-image" name="image" accept="image/*" capture="user" hidden>
          </form>

          @if($verifyResult)
            <div class="notice {{ ($verifyResult['matched'] ?? false) ? 'success' : 'error' }}" style="margin-top:16px;">
              <strong>{{ ($verifyResult['matched'] ?? false) ? 'Match' : 'No Match' }}</strong>
              <br>
              Score: {{ number_format($verifyResult['score'] ?? 0, 4) }} &nbsp; · &nbsp;
              Threshold: {{ $verifyResult['threshold'] ?? config('services.face_api.threshold', 0.35) }}
            </div>
          @endif
        </div>
      </div>
    </main>
  </div>

  <script>
    (function() {
      const fileInput = document.getElementById('verify-image');
      const scanBtn = document.getElementById('scan-face');
      const form = document.getElementById('face-verify-form');
      const video = document.getElementById('camera-preview');
      const canvas = document.getElementById('snapshot');
      const statusEl = document.getElementById('snapshot-status');
      let stream;
      let isScanning = false;
      let stopLoop = false;

      function setStatus(text) {
        if (statusEl) statusEl.textContent = text;
      }

      async function startCameraIfNeeded() {
        if (stream || !video) return;
        try {
          stream = await navigator.mediaDevices.getUserMedia({
            video: { width: 640, height: 480, facingMode: 'user' }
          });
          video.srcObject = stream;
          setStatus('Camera ready.');
        } catch (err) {
          setStatus('Camera not available: ' + (err?.message || 'Permission denied'));
        }
      }

      async function verifyOnce() {
        return new Promise((resolve) => {
          const blobs = [];
          const captureN = (n, delayMs) => new Promise((res) => {
            const take = () => {
              if (!video.srcObject) return res();
              const ctx = canvas.getContext('2d');
              canvas.width = video.videoWidth || 640;
              canvas.height = video.videoHeight || 480;
              ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
              canvas.toBlob((blob) => {
                if (blob) blobs.push(blob);
                if (blobs.length >= n) return res();
                setTimeout(take, delayMs);
              }, 'image/jpeg', 0.85);
            };
            take();
          });

          captureN(3, 120).then(async () => {
            try {
              const tokenEl = form?.querySelector('input[name="_token"]');
              const token = tokenEl ? tokenEl.value : '';
              const fd = new FormData();
              if (token) fd.append('_token', token);
              blobs.forEach((b, idx) => fd.append('images[]', new File([b], `frame_${idx}.jpg`, { type: 'image/jpeg' })));
              const resp = await fetch(form.action, {
                method: 'POST',
                headers: {
                  'Accept': 'application/json',
                  'X-Requested-With': 'XMLHttpRequest',
                },
                body: fd,
                credentials: 'same-origin',
              });
              const json = await resp.json().catch(() => null);
              if (!resp.ok || !json) {
                resolve({ ok: false, message: (json && json.message) ? json.message : 'Verification failed.' });
                return;
              }
              resolve(json);
            } catch (e) {
              resolve({ ok: false, message: e?.message || 'Network error.' });
            }
          });
        });
      }

      scanBtn?.addEventListener('click', async () => {
        if (isScanning) return;
        isScanning = true;
        stopLoop = false;
        try {
          scanBtn.disabled = true;
          scanBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Scanning…';

          await startCameraIfNeeded();
          if (!stream) return;

          const maxAttempts = 20;
          let attempt = 0;

          setStatus('Scanning… keep the face centered.');
          while (!stopLoop && attempt < maxAttempts) {
            attempt++;
            const res = await verifyOnce();
            if (!res.ok) {
              const fr = res.failure_reason ? ` [${res.failure_reason}]` : '';
              setStatus((res.message || 'Verification failed.') + fr + ` (try ${attempt}/${maxAttempts})`);
            } else if (res.matched) {
              setStatus('Match! Score: ' + (res.score !== null && res.score !== undefined ? Number(res.score).toFixed(4) : 'N/A'));
              stopLoop = true;
              setTimeout(() => { window.location.reload(); }, 800);
              break;
            } else {
              const scoreText = (res.score !== null && res.score !== undefined) ? Number(res.score).toFixed(4) : 'N/A';
              setStatus('No match (score ' + scoreText + '). Retrying… (' + attempt + '/' + maxAttempts + ')');
            }
            await new Promise(r => setTimeout(r, 900));
          }

          if (!stopLoop) {
            setStatus('Stopped. No match after ' + maxAttempts + ' tries. Improve lighting and try again.');
          }
        } catch (err) {
          setStatus('Scan failed: ' + (err?.message || 'unknown error'));
        } finally {
          isScanning = false;
          if (scanBtn) {
            scanBtn.disabled = false;
            scanBtn.innerHTML = '<i class="fa-solid fa-circle-dot"></i> Scan &amp; Verify';
          }
        }
      });

      window.addEventListener('beforeunload', () => {
        stopLoop = true;
        if (stream) {
          stream.getTracks().forEach(t => t.stop());
        }
      });
    })();
  </script>
</body>
</html>

