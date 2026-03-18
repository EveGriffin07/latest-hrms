<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Face Enrollment - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    main { padding: 2rem; }
    .breadcrumb { font-size: .85rem; color:#94a3b8; margin-bottom: 1rem; }
    h2 { color:#2563eb; margin:0 0 .25rem 0; }
    .subtitle { color:#64748b; margin-bottom:1.5rem; }
    .notice { padding:12px 14px; border-radius:10px; margin-bottom:14px; }
    .notice.success { background:#ecfdf3; color:#166534; border:1px solid #bbf7d0; }
    .notice.error { background:#fef2f2; color:#991b1b; border:1px solid #fecdd3; }
    .camera-wrap { display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:16px; margin-top:8px; }
    .camera-card { background:#f8fafc; border:1px dashed #cbd5e1; border-radius:12px; padding:14px; }
    .camera-card video, .camera-card canvas { width:100%; border-radius:10px; background:#0f172a; min-height:200px; }
    .muted { color:#94a3b8; font-size:12px; margin-top:6px; display:block; }
    .pill { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; background:#eef2ff; color:#4338ca; font-size:12px; margin-left:8px; }
  </style>
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
      <a href="{{ route('admin.profile') }}" style="text-decoration: none; color: inherit;">
        <i class="fa-regular fa-bell"></i> &nbsp; {{ Auth::user()->name ?? 'Admin' }}
      </a>
    </div>
  </header>

  <div class="container">
    @include('admin.layout.sidebar')

    <main>
      <div class="breadcrumb">Attendance · Face Enrollment</div>
      <h2>Face Enrollment <span class="pill"><i class="fa-solid fa-wand-magic-sparkles"></i> FastAPI</span></h2>
      <p class="subtitle">Register an employee's face embedding and store it for verification.</p>

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
          <h3><i class="fa-solid fa-user-check"></i> Enroll Employee Face</h3>
          <p class="muted" style="margin-top:4px;">Employees cannot overwrite their template; use <strong>Reset face</strong> below to allow re-enrollment (audit logged).</p>

          @if($employees->isEmpty())
            <p>No employees found. Add employees first.</p>
          @else
            @php
              $selected = $selectedEmployeeId ?? ($employees->first()->employee_id ?? null);
            @endphp

            <form id="face-enroll-form" method="POST" enctype="multipart/form-data">
              @csrf
              <div class="form-group">
                <label for="employee-select">Employee</label>
                <select id="employee-select" name="employee_id" required>
                  @foreach($employees as $emp)
                    <option value="{{ $emp->employee_id }}" @selected($selected === $emp->employee_id)>
                      {{ $emp->employee_code }} — {{ $emp->user->name ?? 'User #'.$emp->user_id }}
                    </option>
                  @endforeach
                </select>
                <small class="muted">Re-enrolling will overwrite any existing embedding.</small>
              </div>

              <div class="form-group">
                <label for="image-input">Face Image</label>
                <input type="file" id="image-input" name="image" accept="image/*" capture="user" required>
                <small class="muted">Upload a clear, front-facing photo (JPG/PNG, max 5 MB).</small>
              </div>

              <div class="camera-wrap">
                <div class="camera-card">
                  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <span style="font-weight:600; color:#0f172a;">Live Camera</span>
                    <button type="button" class="btn btn-secondary btn-small" id="start-camera">
                      <i class="fa-solid fa-camera"></i> Start
                    </button>
                  </div>
                  <video id="camera-preview" autoplay playsinline muted></video>
                  <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:10px;">
                    <button type="button" class="btn btn-primary btn-small" id="capture-photo">
                      <i class="fa-solid fa-circle-dot"></i> Capture
                    </button>
                  </div>
                  <small class="muted">If camera capture works, it fills the file input automatically.</small>
                </div>
                <div class="camera-card">
                  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <span style="font-weight:600; color:#0f172a;">Snapshot</span>
                    <button type="button" class="btn btn-secondary btn-small" id="clear-image">
                      <i class="fa-solid fa-rotate-left"></i> Clear
                    </button>
                  </div>
                  <canvas id="snapshot" aria-label="Captured preview"></canvas>
                  <small class="muted" id="snapshot-status">No capture yet.</small>
                </div>
              </div>

              <div style="display:flex; gap:12px; margin-top:18px;">
                <button type="submit" class="btn btn-primary">
                  <i class="fa-solid fa-cloud-arrow-up"></i> Enroll Face
                </button>
                <button type="reset" class="btn btn-secondary" id="reset-form">
                  <i class="fa-solid fa-eraser"></i> Reset
                </button>
              </div>
            </form>

            <hr style="margin:24px 0 16px; border:0; border-top:1px solid #e2e8f0;">
            <h3 style="margin-bottom:10px;"><i class="fa-solid fa-unlock-keyhole"></i> Reset Face (Re-enroll)</h3>
            <p class="muted" style="margin-bottom:12px;">HR/Admin only. Invalidates the current template and allows the employee to re-enroll. Audit log records who, when, and why.</p>
            @php $resetEmployee = $employees->firstWhere('employee_id', $selected) ?? $employees->first(); @endphp
            <form method="POST" action="{{ route('admin.face.reset', ['employee' => $resetEmployee]) }}" id="reset-face-form" style="display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;">
              @csrf
              <div class="form-group" style="margin:0; flex:1; min-width:200px;">
                <label for="reset-reason">Reason (optional)</label>
                <input type="text" id="reset-reason" name="reason" placeholder="e.g. HR reset for re-enrollment" maxlength="500" style="width:100%;">
              </div>
              <button type="submit" class="btn btn-secondary" onclick="return confirm('Invalidate this employee\'s face template? They will need to re-enroll.');">
                <i class="fa-solid fa-rotate-right"></i> Reset face for selected employee
              </button>
            </form>
            <script>
              (function() {
                var sel = document.getElementById('employee-select');
                var resetForm = document.getElementById('reset-face-form');
                if (sel && resetForm) {
                  sel.addEventListener('change', function() {
                    resetForm.action = "{{ url('/admin/face/reset') }}/" + this.value;
                  });
                }
              })();
            </script>
          @endif
        </div>
      </div>
    </main>
  </div>

  <script>
    (function() {
      const form = document.getElementById('face-enroll-form');
      const select = document.getElementById('employee-select');
      const fileInput = document.getElementById('image-input');
      const basePath = "{{ url('/employees') }}";
      const cameraBtn = document.getElementById('start-camera');
      const captureBtn = document.getElementById('capture-photo');
      const clearBtn = document.getElementById('clear-image');
      const resetBtn = document.getElementById('reset-form');
      const video = document.getElementById('camera-preview');
      const canvas = document.getElementById('snapshot');
      const statusEl = document.getElementById('snapshot-status');
      let stream;

      function updateAction() {
        if (form && select) {
          form.action = `${basePath}/${select.value}/face/enroll`;
        }
      }

      function setStatus(text) {
        if (statusEl) statusEl.textContent = text;
      }

      select?.addEventListener('change', updateAction);
      updateAction();

      cameraBtn?.addEventListener('click', async () => {
        try {
          stream = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 } });
          video.srcObject = stream;
          setStatus('Camera ready.');
        } catch (err) {
          setStatus('Camera not available: ' + err.message);
        }
      });

      captureBtn?.addEventListener('click', () => {
        if (!video.srcObject) {
          setStatus('Start the camera first.');
          return;
        }
        const ctx = canvas.getContext('2d');
        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        canvas.toBlob((blob) => {
          if (!blob) return;
          const file = new File([blob], 'capture.jpg', { type: 'image/jpeg' });
          const dataTransfer = new DataTransfer();
          dataTransfer.items.add(file);
          fileInput.files = dataTransfer.files;
          setStatus('Snapshot captured and bound to the file input.');
        }, 'image/jpeg');
      });

      clearBtn?.addEventListener('click', () => {
        canvas.width = canvas.height = 0;
        setStatus('Snapshot cleared.');
        fileInput.value = '';
      });

      resetBtn?.addEventListener('click', () => {
        setStatus('No capture yet.');
        canvas.width = canvas.height = 0;
        fileInput.value = '';
        updateAction();
      });

      window.addEventListener('beforeunload', () => {
        if (stream) {
          stream.getTracks().forEach(t => t.stop());
        }
      });
    })();
  </script>
</body>
</html>
