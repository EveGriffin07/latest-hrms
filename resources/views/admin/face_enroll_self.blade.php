<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Face Enrollment - Upload Photo</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    .enroll-page { max-width: 480px; margin: 0 auto; padding: 24px; }
    .enroll-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 24px; box-shadow: 0 8px 20px rgba(15,23,42,0.06); }
    .enroll-card h1 { margin: 0 0 8px; font-size: 1.25rem; color: #0f172a; }
    .enroll-card p { margin: 0 0 20px; color: #64748b; font-size: 14px; line-height: 1.5; }
    .notice { padding: 12px 14px; border-radius: 10px; margin-bottom: 16px; font-size: 14px; }
    .notice.success { background: #ecfdf3; border: 1px solid #bbf7d0; color: #166534; }
    .notice.error { background: #fef2f2; border: 1px solid #fecdd3; color: #991b1b; }
    .upload-zone {
      border: 2px dashed #cbd5e1;
      border-radius: 12px;
      padding: 28px;
      text-align: center;
      background: #f8fafc;
      cursor: pointer;
      transition: border-color 0.2s, background 0.2s;
    }
    .upload-zone:hover, .upload-zone.dragover { border-color: #0ea5e9; background: #f0f9ff; }
    .upload-zone input[type="file"] { display: none; }
    .upload-zone .icon { font-size: 2.5rem; color: #94a3b8; margin-bottom: 8px; }
    .upload-zone .hint { color: #64748b; font-size: 14px; }
    .preview-wrap { margin-top: 16px; text-align: center; }
    .preview-wrap img { max-width: 100%; max-height: 240px; border-radius: 10px; border: 1px solid #e5e7eb; object-fit: contain; }
    .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 10px; font-weight: 600; cursor: pointer; border: none; font-size: 14px; }
    .btn-primary { background: #0ea5e9; color: #fff; }
    .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
    .btn-secondary { background: #e2e8f0; color: #475569; }
    .mt-16 { margin-top: 16px; }
    .back-link { color: #64748b; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 16px; }
    .back-link:hover { color: #0f172a; }
    .enrolled-badge { display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px; background: #ecfdf3; color: #166534; border-radius: 8px; font-size: 14px; font-weight: 600; margin-bottom: 12px; }
    .enrolled-photo { max-width: 120px; max-height: 120px; border-radius: 8px; object-fit: cover; border: 1px solid #e5e7eb; }
    .enrolled-exact-wrap { margin-top: 12px; margin-bottom: 16px; }
    .enrolled-exact-wrap .enrolled-exact-label { font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; display: block; }
    .enrolled-exact-wrap .enrolled-exact-img { display: block; max-width: 100%; max-height: 320px; width: auto; height: auto; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 12px rgba(0,0,0,0.08); object-fit: contain; background: #f8fafc; }
    .or-divider { display: flex; align-items: center; gap: 12px; margin: 16px 0; color: #94a3b8; font-size: 13px; }
    .or-divider::before, .or-divider::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }
    .camera-wrap { display: none; margin-top: 12px; }
    .camera-wrap.active { display: block; }
    .camera-wrap video { width: 100%; max-height: 280px; border-radius: 12px; background: #0f172a; }
    .camera-actions { display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap; }
    .camera-actions .btn { flex: 1; min-width: 120px; justify-content: center; }
    .switch-mode { margin-top: 12px; }
    .switch-mode button { background: none; border: none; color: #64748b; font-size: 13px; cursor: pointer; text-decoration: underline; padding: 0; }
    .switch-mode button:hover { color: #0ea5e9; }
  </style>
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
      <a href="{{ route('admin.profile') }}" style="color:inherit; text-decoration:none;"><i class="fa-regular fa-bell"></i> &nbsp; {{ Auth::user()->name ?? 'HR Admin' }}</a>
    </div>
  </header>
  <div class="container">
    @include('admin.layout.sidebar')
    <main>
      <a href="{{ route('admin.dashboard') }}" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back</a>

      <div class="enroll-page">
        <div class="enroll-card">
          <h1><i class="fa-solid fa-face-smile"></i> Face Enrollment</h1>
          <p>Upload a clear photo of your face, or take a picture with your camera. It will be stored and used to verify you when you check in/out.</p>

          @if(session('success'))
            <div class="notice success">{{ session('success') }}</div>
          @endif
          @if($errors->any())
            <div class="notice error">{{ $errors->first() }}</div>
          @endif

          @if($faceData ?? null)
            <div class="enrolled-badge"><i class="fa-solid fa-check-circle"></i> Face enrolled</div>
            @if($faceData->photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($faceData->photo_path))
              <div class="enrolled-exact-wrap">
                <span class="enrolled-exact-label"><i class="fa-solid fa-image"></i> Enrolled photo (exact image on file)</span>
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($faceData->photo_path) }}" alt="Enrolled photo" class="enrolled-exact-img">
              </div>
            @endif
            <p style="margin-top:12px; color:#64748b; font-size:13px;">You can use face attendance (camera scan) to check in and check out.</p>
            <div class="re-enroll-section" style="margin-top:20px; padding-top:20px; border-top:1px solid #e5e7eb;">
              <h3 style="margin:0 0 8px; font-size:1rem; color:#0f172a;"><i class="fa-solid fa-rotate-right"></i> Re-enroll face</h3>
              <p style="margin:0 0 12px; color:#64748b; font-size:13px;">Reset your enrollment to upload a new photo. Your existing face data will be removed (audit logged).</p>
              <button type="button" class="btn btn-secondary" id="show-reenroll-prompt" style="margin-bottom:0;">
                <i class="fa-solid fa-rotate-right"></i> Re-enroll face
              </button>
              <div id="reenroll-password-prompt" style="display:none; margin-top:16px; padding:16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; max-width:320px;">
                <p style="margin:0 0 12px; font-size:14px; font-weight:600; color:#0f172a;">Enter your password to proceed</p>
                <form method="POST" action="{{ route('admin.face.reset', $employee) }}" id="reenroll-form">
                  @csrf
                  <div style="margin-bottom:12px;">
                    <label for="reenroll-password" style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;">Password <span style="color:#b91c1c;">*</span></label>
                    <input type="password" id="reenroll-password" name="password" autocomplete="current-password" placeholder="Enter your password" required style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
                    @error('password')
                      <span style="color:#b91c1c; font-size:13px; margin-top:4px; display:block;">{{ $message }}</span>
                    @enderror
                  </div>
                  <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Reset your face enrollment? You will need to upload a new photo to enroll again.');">
                      <i class="fa-solid fa-check"></i> Proceed
                    </button>
                    <button type="button" class="btn btn-secondary" id="cancel-reenroll-prompt">Cancel</button>
                  </div>
                </form>
              </div>
            </div>
          @else
            <form method="POST" action="{{ route('admin.face.enroll.self.post') }}" enctype="multipart/form-data" id="enroll-form">
              @csrf
              <input type="file" id="photo" name="photo" accept="image/jpeg,image/jpg,image/png" required style="display:none;">
              <div class="upload-zone" id="upload-zone" role="button" tabindex="0">
                <div class="icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                <div class="hint">Click or drag a photo here</div>
                <div class="hint" style="margin-top:4px;">One person, face clearly visible, good lighting (JPG/PNG, max 5MB)</div>
              </div>
              <div class="or-divider">or</div>
              <div>
                <button type="button" class="btn btn-secondary" id="take-picture-btn"><i class="fa-solid fa-camera"></i> Take picture</button>
              </div>
              <div class="camera-wrap" id="camera-wrap">
                <video id="camera-video" autoplay playsinline muted></video>
                <div class="camera-actions">
                  <button type="button" class="btn btn-primary" id="capture-btn" style="display:none;"><i class="fa-solid fa-circle-dot"></i> Capture</button>
                  <button type="button" class="btn btn-secondary" id="stop-camera-btn" style="display:none;">Stop camera</button>
                </div>
                <div class="switch-mode mt-16"><button type="button" id="back-to-upload">Use uploaded photo instead</button></div>
              </div>
              <div class="preview-wrap" id="preview-wrap" style="display:none;">
                <img id="preview-img" src="" alt="Preview">
                <p style="margin:8px 0 0; font-size:13px; color:#64748b;">Selected photo</p>
              </div>
              <div class="mt-16">
                <button type="submit" class="btn btn-primary" id="submit-btn" disabled><i class="fa-solid fa-upload"></i> Upload &amp; Enroll</button>
              </div>
            </form>
          @endif
        </div>
      </div>
    </main>
  </div>
  <script>
    (function () {
      const zone = document.getElementById('upload-zone');
      const input = document.getElementById('photo');
      const previewWrap = document.getElementById('preview-wrap');
      const previewImg = document.getElementById('preview-img');
      const submitBtn = document.getElementById('submit-btn');
      const takePictureBtn = document.getElementById('take-picture-btn');
      const cameraWrap = document.getElementById('camera-wrap');
      const cameraVideo = document.getElementById('camera-video');
      const captureBtn = document.getElementById('capture-btn');
      const stopCameraBtn = document.getElementById('stop-camera-btn');
      const backToUpload = document.getElementById('back-to-upload');

      if (!zone || !input) return;

      let stream = null;

      function showPreview(file) {
        if (!file || !file.type.startsWith('image/')) return;
        if (previewImg.src) URL.revokeObjectURL(previewImg.src);
        previewImg.src = URL.createObjectURL(file);
        previewWrap.style.display = 'block';
        submitBtn.disabled = false;
      }

      function setInputFile(file) {
        try {
          const dt = new DataTransfer();
          dt.items.add(file);
          input.files = dt.files;
        } catch (e) {
          input.files = null;
        }
        showPreview(file);
      }

      function stopCamera() {
        if (stream) {
          stream.getTracks().forEach(function (t) { t.stop(); });
          stream = null;
        }
        if (cameraWrap) cameraWrap.classList.remove('active');
        if (cameraVideo) cameraVideo.srcObject = null;
        if (captureBtn) captureBtn.style.display = 'none';
        if (stopCameraBtn) stopCameraBtn.style.display = 'none';
      }

      zone.addEventListener('click', function () { input.click(); });
      zone.addEventListener('dragover', function (e) { e.preventDefault(); zone.classList.add('dragover'); });
      zone.addEventListener('dragleave', function () { zone.classList.remove('dragover'); });
      zone.addEventListener('drop', function (e) {
        e.preventDefault();
        zone.classList.remove('dragover');
        const file = e.dataTransfer.files[0];
        if (file) { setInputFile(file); stopCamera(); }
      });
      input.addEventListener('change', function () {
        const file = this.files[0];
        if (file) { showPreview(file); stopCamera(); }
      });

      takePictureBtn.addEventListener('click', async function () {
        try {
          stream = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480, facingMode: 'user' } });
          cameraVideo.srcObject = stream;
          cameraWrap.classList.add('active');
          captureBtn.style.display = 'inline-flex';
          stopCameraBtn.style.display = 'inline-flex';
        } catch (err) {
          alert('Could not access camera: ' + (err.message || 'Permission denied'));
        }
      });

      captureBtn.addEventListener('click', function () {
        if (!cameraVideo.srcObject || cameraVideo.readyState < 2) return;
        const canvas = document.createElement('canvas');
        canvas.width = cameraVideo.videoWidth;
        canvas.height = cameraVideo.videoHeight;
        canvas.getContext('2d').drawImage(cameraVideo, 0, 0);
        canvas.toBlob(function (blob) {
          if (!blob) return;
          const file = new File([blob], 'capture.jpg', { type: 'image/jpeg' });
          setInputFile(file);
          stopCamera();
        }, 'image/jpeg', 0.92);
      });

      stopCameraBtn.addEventListener('click', stopCamera);
      backToUpload.addEventListener('click', stopCamera);

      window.addEventListener('beforeunload', stopCamera);
    })();

    (function () {
      const showBtn = document.getElementById('show-reenroll-prompt');
      const promptEl = document.getElementById('reenroll-password-prompt');
      const cancelBtn = document.getElementById('cancel-reenroll-prompt');
      if (showBtn && promptEl) {
        showBtn.addEventListener('click', function () {
          promptEl.style.display = 'block';
          document.getElementById('reenroll-password')?.focus();
        });
      }
      if (cancelBtn && promptEl) {
        cancelBtn.addEventListener('click', function () {
          promptEl.style.display = 'none';
          var pwd = document.getElementById('reenroll-password');
          if (pwd) pwd.value = '';
        });
      }
    })();
  </script>
</body>
</html>

