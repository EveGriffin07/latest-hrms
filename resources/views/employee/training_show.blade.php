<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $enrollment->training->training_name }} - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  
  {{-- IMPORT HTML5 QR CODE SCANNER LIBRARY --}}
  <script src="https://unpkg.com/html5-qrcode"></script>

  <style>
    /* HERO BANNER */
    .hero-banner { background: linear-gradient(135deg, #2563eb, #1e3a8a); border-radius: 16px; padding: 40px; color: white; margin-bottom: 30px; position: relative; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(37,99,235,0.2); }
    .hero-banner::after { content: ''; position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%; }
    .hero-title { font-size: 32px; font-weight: 700; margin: 0 0 10px 0; max-width: 80%; }
    .hero-meta { display: flex; gap: 20px; align-items: center; font-size: 14px; font-weight: 500; opacity: 0.9; }
    
    /* CONTENT GRID */
    .content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
    @media(max-width: 900px) { .content-grid { grid-template-columns: 1fr; } }
    
    .detail-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .card-title { font-size: 18px; font-weight: 600; color: #0f172a; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; }
    
    /* INFO LIST */
    .info-list { display: flex; flex-direction: column; gap: 15px; }
    .info-row { display: flex; gap: 15px; align-items: flex-start; }
    .info-icon { width: 40px; height: 40px; background: #f1f5f9; color: #475569; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
    .info-text h4 { margin: 0 0 2px 0; font-size: 12px; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; }
    .info-text p { margin: 0; font-size: 15px; color: #0f172a; font-weight: 500; }

    /* ACTION BUTTONS */
    .action-box { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 25px; text-align: center; margin-top: 20px; }
    .btn-primary { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 14px 24px; background: #2563eb; color: white; border-radius: 8px; font-weight: 600; text-decoration: none; width: 100%; transition: 0.2s; box-shadow: 0 4px 6px rgba(37,99,235,0.2); border: none; font-size: 15px; cursor: pointer; }
    .btn-primary:hover { background: #1d4ed8; transform: translateY(-2px); }
    
    .btn-secondary { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 14px 24px; background: #0f172a; color: white; border-radius: 8px; font-weight: 600; text-decoration: none; width: 100%; transition: 0.2s; box-shadow: 0 4px 6px rgba(15,23,42,0.2); border: none; font-size: 15px; cursor: pointer;}
    .btn-secondary:hover { background: #000; transform: translateY(-2px); }

    /* SCANNER MODAL */
    .scanner-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.9); z-index: 9999; flex-direction: column; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
    .scanner-modal.active { display: flex; }
    .scanner-container { width: 100%; max-width: 500px; background: white; padding: 20px; border-radius: 16px; text-align: center; position: relative; }
    #reader { width: 100%; border-radius: 12px; overflow: hidden; border: 2px solid #2563eb; }
    .close-scanner { background: #ef4444; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; margin-top: 20px; cursor: pointer; width: 100%; font-size: 15px; }
  </style>
</head>
<body>

  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
        <i class="fa-regular fa-bell"></i>   <a href="{{ route('employee.profile') }}" style="color:inherit; text-decoration:none;">{{ Auth::user()->name }}</a>
    </div>
  </header>

  <div class="container dashboard-shell">
    @include('employee.layout.sidebar')

    <main class="content">
      
      <div style="margin-bottom: 20px;">
        <a href="{{ route('employee.training.index') }}" style="color: #64748b; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-arrow-left"></i> Back to My Learning
        </a>
      </div>

      {{-- HERO BANNER --}}
      <div class="hero-banner">
        <h1 class="hero-title">{{ $enrollment->training->training_name }}</h1>
        <div class="hero-meta">
            <span><i class="fa-regular fa-calendar"></i> {{ \Carbon\Carbon::parse($enrollment->training->start_date)->format('d M Y') }}</span>
            <span><i class="fa-solid fa-user-tie"></i> Inst: {{ $enrollment->training->provider }}</span>
            <span style="background: rgba(255,255,255,0.2); padding: 4px 10px; border-radius: 20px; font-size: 12px;">
                {{ $enrollment->training->mode }}
            </span>
        </div>
      </div>

      <div class="content-grid">
          
          {{-- LEFT COLUMN: Details --}}
          <div class="detail-card">
              <h3 class="card-title"><i class="fa-solid fa-circle-info" style="color: #2563eb;"></i> Program Overview</h3>
              <p style="color: #475569; line-height: 1.7; font-size: 15px; margin-bottom: 30px;">
                  {{ $enrollment->training->tr_description ?? 'No specific description has been provided by HR for this course.' }}
              </p>

              <div class="info-list">
                  <div class="info-row">
                      <div class="info-icon"><i class="fa-regular fa-clock"></i></div>
                      <div class="info-text">
                          <h4>Schedule & Time</h4>
                          <p>
                              {{ \Carbon\Carbon::parse($enrollment->training->start_date)->format('D, d M Y') }}
                              @if($enrollment->training->start_time)
                                  at {{ \Carbon\Carbon::parse($enrollment->training->start_time)->format('h:i A') }}
                              @endif
                          </p>
                      </div>
                  </div>
                  
                  <div class="info-row">
                      <div class="info-icon"><i class="fa-solid fa-location-dot"></i></div>
                      <div class="info-text">
                          <h4>Location</h4>
                          <p>{{ $enrollment->training->location }}</p>
                      </div>
                  </div>

                  @if($enrollment->remarks)
                  <div class="info-row" style="margin-top: 10px;">
                      <div class="info-icon" style="background: #fef3c7; color: #b45309;"><i class="fa-solid fa-comment-dots"></i></div>
                      <div class="info-text">
                          <h4>Instructor Remarks</h4>
                          <p style="font-style: italic;">"{{ $enrollment->remarks }}"</p>
                      </div>
                  </div>
                  @endif
              </div>
          </div>

          {{-- RIGHT COLUMN: Actions --}}
          <div class="detail-card" style="align-self: start;">
              <h3 class="card-title"><i class="fa-solid fa-bolt" style="color: #f59e0b;"></i> Action Center</h3>
              
              @if($enrollment->completion_status == 'completed')
                  <div style="text-align: center; padding: 20px 0;">
                      <div style="width: 60px; height: 60px; background: #dcfce7; color: #166534; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 15px auto;">
                          <i class="fa-solid fa-check"></i>
                      </div>
                      <h4 style="margin: 0; color: #0f172a; font-size: 18px;">Training Completed</h4>
                      <p style="color: #64748b; font-size: 13px; margin-top: 5px;">You have successfully fulfilled the requirements for this program.</p>
                  </div>
              @else
                  <div class="action-box">
                      @if(Str::startsWith($enrollment->training->location, ['http://', 'https://']))
                          
                          {{-- VIRTUAL TRAINING ACTION --}}
                          <i class="fa-solid fa-laptop-code" style="font-size: 32px; color: #94a3b8; margin-bottom: 15px;"></i>
                          <p style="font-size: 13px; color: #475569; margin-bottom: 15px;">This is a virtual session. Click below to enter the meeting room.</p>
                          <a href="{{ $enrollment->training->location }}" target="_blank" class="btn-primary">
                              <i class="fa-solid fa-video"></i> Join Virtual Room
                          </a>

                      @else

                          {{-- IN-PERSON TRAINING ACTION (QR SCANNER) --}}
                          <i class="fa-solid fa-qrcode" style="font-size: 32px; color: #94a3b8; margin-bottom: 15px;"></i>
                          <p style="font-size: 13px; color: #475569; margin-bottom: 15px;">Attending in person? Have the instructor project the QR code and scan it to log attendance.</p>
                          
                          {{-- REPLACED THE FAKE BUTTON WITH THE REAL SCANNER TRIGGER --}}
                          <button onclick="startScanner()" class="btn-secondary">
                              <i class="fa-solid fa-camera"></i> Scan Attendance QR
                          </button>
                          
                          <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($enrollment->training->location) }}" target="_blank" style="display: block; margin-top: 15px; font-size: 13px; color: #2563eb; font-weight: 600; text-decoration: none;">
                              <i class="fa-solid fa-map"></i> View on Google Maps
                          </a>

                      @endif
                  </div>
              @endif

          </div>
      </div>

      <footer style="text-align: center; margin-top: 40px; color: #94a3b8; font-size: 13px;">
        © 2026 Web-Based HRMS. All Rights Reserved.
      </footer>
    </main>
  </div>

  {{-- =======================================================
       THE QR SCANNER MODAL (HIDDEN BY DEFAULT)
  ======================================================== --}}
  <div id="scannerModal" class="scanner-modal">
      <div class="scanner-container">
          <h3 style="margin: 0 0 15px 0; color: #0f172a;"><i class="fa-solid fa-qrcode" style="color: #2563eb;"></i> Scan to Attend</h3>
          <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">Point your camera at the QR code shown by the instructor.</p>
          
          {{-- The actual camera feed goes into this div --}}
          <div id="reader"></div>
          
          <button onclick="stopScanner()" class="close-scanner">Cancel Scan</button>
      </div>
  </div>

  <script>
      let html5QrcodeScanner = null;

      function startScanner() {
          document.getElementById('scannerModal').classList.add('active');

          // Initialize the scanner engine
          html5QrcodeScanner = new Html5QrcodeScanner(
              "reader", 
              { fps: 10, qrbox: {width: 250, height: 250} },
              false
          );

          // What happens when it successfully reads a QR Code
          function onScanSuccess(decodedText, decodedResult) {
              html5QrcodeScanner.clear();
              document.getElementById('scannerModal').classList.remove('active');
              
              // DEBUGGING: This will show you EXACTLY what the QR code holds!
              alert("The QR Code contains: " + decodedText);

              // Build the secure URL and redirect
              window.location.href = "{{ url('/employee/training/scan') }}/" + decodedText;
          }

          function onScanFailure(error) {
              // Continues scanning (fails silently)
          }

          // Start scanning
          html5QrcodeScanner.render(onScanSuccess, onScanFailure);
      }

      function stopScanner() {
          if (html5QrcodeScanner) {
              html5QrcodeScanner.clear();
          }
          document.getElementById('scannerModal').classList.remove('active');
      }
  </script>

</body>
</html>