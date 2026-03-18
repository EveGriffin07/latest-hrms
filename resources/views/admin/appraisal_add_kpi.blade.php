<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Initiate Appraisal - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
  <style>
      .form-section { background: white; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
      .section-title { font-size: 18px; font-weight: 600; color: #0f172a; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; }
      .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: #334155; }
      .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; margin-bottom: 20px; font-family: inherit; font-size: 14px; outline: none; transition: 0.2s; }
      .ts-control { padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-family: inherit; font-size: 14px; box-shadow: none; margin-bottom: 20px; }
      .ts-control.focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
  </style>
</head>
<body>
<header>
  <div class="title">Web-Based HRMS</div>
  <div class="user-info">
    <a href="{{ route('admin.profile') }}" style="text-decoration: none; color: inherit;"><i class="fa-regular fa-bell"></i> &nbsp; HR Admin</a>
  </div>
</header>

<div class="container">
  @include('admin.layout.sidebar')

  <main>
    <div style="margin-bottom: 30px;">
        <div class="breadcrumb" style="color: #64748b; font-size: 14px; margin-bottom: 5px;">Home > Performance > <span style="color: #0f172a; font-weight: 500;">Initiate Review</span></div>
        <h2 style="margin:0; font-size:28px; color:#0f172a;">Initiate Appraisal Cycle</h2>
        <p style="color: #64748b; margin-top: 5px; font-size: 15px;">Set up a new performance review for an employee and assign their evaluator.</p>
    </div>

    @if(session('error'))
        <div style="background:#fee2e2; color:#b91c1c; padding:15px 20px; border-radius:8px; margin-bottom:20px; border:1px solid #fecaca; font-weight: 500;"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
    @endif

    <form action="{{ route('admin.appraisal.store') }}" method="POST">
        @csrf
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-calendar-check" style="color: #2563eb; margin-right:8px;"></i> Cycle Details</h3>
            <div class="form-group">
                <label>Review Period <span style="color:#dc2626">*</span></label>
                <select name="review_period" required>
                    <option value="">-- Select Review Period --</option>
                    <option value="Annual Review 2026">Annual Review 2026</option>
                    <option value="Mid-Year Review 2026">Mid-Year Review 2026</option>
                    <option value="Probationary Review">Probationary Review (3 Months)</option>
                </select>
            </div>
            
            <h3 class="section-title" style="margin-top: 30px;"><i class="fa-solid fa-users" style="color: #2563eb; margin-right:8px;"></i> Participants</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Target Employee <span style="color:#dc2626">*</span></label>
                    <select name="employee_id" id="employeeSelect" required placeholder="Search Employee...">
                        <option value="">-- Search Employee --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->employee_id }}">{{ $emp->user->name ?? 'Unknown' }} ({{ $emp->department->department_name ?? 'N/A' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Assigned Evaluator (Manager) <span style="color:#dc2626">*</span></label>
                    <select name="evaluator_id" id="evaluatorSelect" required placeholder="Search Manager...">
                        <option value="">-- Search Manager --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->employee_id }}">{{ $emp->user->name ?? 'Unknown' }} ({{ $emp->position->position_name ?? 'N/A' }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div style="text-align: right;">
            <a href="{{ route('admin.appraisal') }}" style="background: #fff; color: #475569; border: 1px solid #cbd5e1; padding: 12px 24px; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-block; margin-right: 10px;">Cancel</a>
            <button type="submit" style="background: #2563eb; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer;"><i class="fa-solid fa-paper-plane"></i> Initiate Review</button>
        </div>
    </form>
  </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        new TomSelect('#employeeSelect', { create: false, sortField: { field: "text", direction: "asc" } });
        new TomSelect('#evaluatorSelect', { create: false, sortField: { field: "text", direction: "asc" } });
    });
</script>
</body>
</html>