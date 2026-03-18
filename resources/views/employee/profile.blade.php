@php
  $role = strtolower(Auth::user()->role ?? 'employee');
  $isSupervisor = ($role === 'supervisor' || $role === 'manager');
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
      <a href="{{ route($isSupervisor ? 'supervisor.profile' : 'employee.profile') }}" style="color:inherit; text-decoration:none;">
        <i class="fa-regular fa-bell"></i> &nbsp; {{ Auth::user()->name ?? ($isSupervisor ? 'Supervisor' : 'Employee') }}
      </a>
    </div>
  </header>

  <div class="container">
    {{-- Dynamically load the correct sidebar path just in case --}}
    @if($isSupervisor)
      @include('supervisor.layout.sidebar')
    @else
      @include('employee.layout.sidebar')
    @endif

    <main>
      <div class="breadcrumb">Home > My Profile</div>
      <h2>My Profile</h2>
      <p class="subtitle">View and update your personal information.</p>

      @if(session('success'))
        <div style="background: #dcfce7; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #bbf7d0;">
          <i class="fa-solid fa-check-circle"></i> {{ session('success') }}
        </div>
      @endif

      @if($errors->any())
        <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fecaca;">
          <ul>
            @foreach ($errors->all() as $error)
              <li>• {{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      {{-- Dynamically route the form submission --}}
      <form action="{{ $isSupervisor ? route('supervisor.profile.update') : route('employee.profile.update') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="profile-container">
          <div class="profile-sidebar">
            <div class="avatar-wrapper">
              <img src="{{ $user->avatar_path ? asset('storage/' . $user->avatar_path) : asset('images/default-avatar.png') }}"
                   class="avatar-preview"
                   id="avatarPreview"
                   alt="Profile Avatar">
            </div>
            <input type="file" name="avatar" id="avatarInput" style="display: none;" accept="image/*">
            <button class="btn-upload" type="button" onclick="document.getElementById('avatarInput').click()">
              <i class="fa-solid fa-image"></i> Change Photo
            </button>
            <p class="avatar-note">JPG or PNG • Max 2MB</p>

            <h3 class="profile-name">{{ $user->name }}</h3>
            <p class="profile-role">{{ ucfirst($user->role ?? 'Employee') }}</p>

            <div class="profile-stats">
              <div class="stat">
                <span class="num">{{ $stats['announcements'] ?? 0 }}</span>
                <span class="label">Announcements</span>
              </div>
              
              {{-- CONDITION: Supervisor vs Employee Stats --}}
              @if($isSupervisor)
                  <div class="stat">
                    <span class="num">{{ $stats['subordinates'] ?? 0 }}</span>
                    <span class="label">Subordinates</span>
                  </div>
                  <div class="stat">
                    <span class="num">{{ $stats['pending_ot'] ?? 0 }}</span>
                    <span class="label">Pending OT</span>
                  </div>
              @else
                  <div class="stat">
                    <span class="num">{{ $stats['leave_requests'] ?? 0 }}</span>
                    <span class="label">Leave Requests</span>
                  </div>
                  <div class="stat">
                    <span class="num">{{ $stats['ot_claims'] ?? 0 }}</span>
                    <span class="label">OT Claims</span>
                  </div>
              @endif

            </div>
          </div>

          <div class="profile-content">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
              <h3 class="section-title" style="margin: 0;"><i class="fa-solid fa-user"></i> Personal Information</h3>
              <button type="button" class="btn-secondary" id="toggle-profile-edit">
                <i class="fa-solid fa-pen"></i> Edit
              </button>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Full Name</label>
                <input type="text" id="profile-name" name="name" value="{{ old('name', $user->name) }}" required readonly style="background-color: #f8fafc; cursor: not-allowed;">
              </div>
              <div class="form-group">
                <label>Email Address</label>
                <input type="email" id="profile-email" name="email" value="{{ old('email', $user->email) }}" required readonly style="background-color: #f8fafc; cursor: not-allowed;">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Phone Number</label>
                <input type="text" id="profile-phone" name="phone" value="{{ old('phone', $employee?->phone ?? '') }}" placeholder="+60..." readonly style="background-color: #f8fafc; cursor: not-allowed;">
              </div>
              <div class="form-group">
                <label>Department</label>
                <input type="text" value="{{ $employee?->department?->department_name ?? 'Not Assigned' }}" readonly style="background-color: #f8fafc; cursor: not-allowed;">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Employee ID</label>
                <input type="text" value="{{ $employee?->employee_code ?? 'Not Assigned' }}" readonly style="background-color: #f8fafc; cursor: not-allowed;">
              </div>
              <div class="form-group">
                <label>Start Working Date</label>
                <input type="text" value="{{ $employee?->hire_date ? \Carbon\Carbon::parse($employee->hire_date)->format('Y-m-d') : '—' }}" readonly style="background-color: #f8fafc; cursor: not-allowed;">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Department Supervisor</label>
                <input type="text" value="{{ $employee?->department?->manager ? $employee->department->manager->name . ($employee->department->manager->email ? ' (' . $employee->department->manager->email . ')' : '') : '—' }}" readonly style="background-color: #f8fafc; cursor: not-allowed;">
              </div>
            </div>

            <h3 class="section-title"><i class="fa-solid fa-building-columns"></i> Bank Account Details</h3>
            <p class="subtitle" style="margin: 0 0 12px 0; font-size: 0.9rem;">For salary and reimbursement. All fields optional. Click Edit above to change.</p>
            <div class="form-row">
              <div class="form-group">
                <label for="bank_code">Bank Name</label>
                <select name="bank_code" id="bank_code" disabled style="width:100%; padding:10px 12px; border:1px solid #e5e7eb; border-radius:10px; font-size:0.95rem; background-color:#f8fafc; cursor:not-allowed;">
                  <option value="">— Select bank —</option>
                  @foreach(config('hrms.banks', []) as $code => $name)
                    <option value="{{ $code }}" {{ old('bank_code', $employee?->bank_code ?? '') == $code ? 'selected' : '' }}>{{ $name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="form-group">
                <label for="bank_account_holder">Account Holder Name</label>
                <input type="text" id="bank_account_holder" name="bank_account_holder" value="{{ old('bank_account_holder', $employee?->bank_account_holder ?? '') }}" placeholder="Name as on bank account" maxlength="120" readonly style="background-color:#f8fafc; cursor:not-allowed;">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="bank_account_number">Account Number</label>
                <input type="text" id="bank_account_number" name="bank_account_number" value="{{ old('bank_account_number', $employee?->bank_account_number ?? '') }}" placeholder="Digits only" maxlength="50" inputmode="numeric" pattern="[0-9\s]*" readonly style="background-color:#f8fafc; cursor:not-allowed;">
              </div>
              <div class="form-group">
                <label for="account_type">Account Type</label>
                <select name="account_type" id="account_type" disabled style="width:100%; padding:10px 12px; border:1px solid #e5e7eb; border-radius:10px; font-size:0.95rem; background-color:#f8fafc; cursor:not-allowed;">
                  <option value="">— Select type —</option>
                  @foreach(config('hrms.bank_account_types', []) as $value => $label)
                    <option value="{{ $value }}" {{ old('account_type', $employee?->account_type ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                  @endforeach
                </select>
              </div>
            </div>

            <h3 class="section-title"><i class="fa-solid fa-lock"></i> Account Security</h3>

            <div class="form-row">
              <div class="form-group">
                <label>Username (Role)</label>
                <input type="text" value="{{ $user->role }}" readonly style="background-color: #f8fafc;">
              </div>
            </div>

            <div class="form-row" id="password-fields-wrap" style="align-items: flex-end;">
              <div class="form-group">
                <label>New Password</label>
                <input type="password" id="profile-password" name="password" placeholder="Leave blank to keep current" disabled style="background-color: #f8fafc; cursor: not-allowed;">
              </div>
              <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" id="profile-password-confirmation" name="password_confirmation" placeholder="Re-enter new password" disabled style="background-color: #f8fafc; cursor: not-allowed;">
              </div>
              <div class="form-group" style="margin-bottom: 0;">
                <button type="button" class="btn-secondary" id="toggle-password-edit">
                  <i class="fa-solid fa-key"></i> Change Password
                </button>
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn-save"><i class="fa-solid fa-save"></i> Save Changes</button>
            </div>
          </div>
        </div>
      </form>

      <footer>© 2026 Web-Based HRMS. All Rights Reserved.</footer>
    </main>
  </div>

  <script>
    document.getElementById('avatarInput').addEventListener('change', function(event) {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          document.getElementById('avatarPreview').src = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    });

    (function() {
      var editBtn = document.getElementById('toggle-profile-edit');
      var nameEl = document.getElementById('profile-name');
      var emailEl = document.getElementById('profile-email');
      var phoneEl = document.getElementById('profile-phone');
      var bankCodeEl = document.getElementById('bank_code');
      var bankHolderEl = document.getElementById('bank_account_holder');
      var bankNumberEl = document.getElementById('bank_account_number');
      var accountTypeEl = document.getElementById('account_type');
      var bankFields = [bankCodeEl, bankHolderEl, bankNumberEl, accountTypeEl];

      function setBankFieldsEditable(editable) {
        bankFields.forEach(function(el) {
          if (!el) return;
          if (el.tagName === 'SELECT') {
            el.disabled = !editable;
            el.style.backgroundColor = editable ? '' : '#f8fafc';
            el.style.cursor = editable ? '' : 'not-allowed';
          } else {
            el.readOnly = !editable;
            el.style.backgroundColor = editable ? '' : '#f8fafc';
            el.style.cursor = editable ? '' : 'not-allowed';
          }
        });
      }

      if (editBtn && nameEl && emailEl && phoneEl) {
        editBtn.addEventListener('click', function() {
          if (nameEl.readOnly) {
            nameEl.readOnly = false;
            emailEl.readOnly = false;
            phoneEl.readOnly = false;
            nameEl.style.backgroundColor = '';
            nameEl.style.cursor = '';
            emailEl.style.backgroundColor = '';
            emailEl.style.cursor = '';
            phoneEl.style.backgroundColor = '';
            phoneEl.style.cursor = '';
            setBankFieldsEditable(true);
            editBtn.innerHTML = '<i class="fa-solid fa-times"></i> Cancel';
          } else {
            nameEl.readOnly = true;
            emailEl.readOnly = true;
            phoneEl.readOnly = true;
            nameEl.style.backgroundColor = '#f8fafc';
            nameEl.style.cursor = 'not-allowed';
            emailEl.style.backgroundColor = '#f8fafc';
            emailEl.style.cursor = 'not-allowed';
            phoneEl.style.backgroundColor = '#f8fafc';
            phoneEl.style.cursor = 'not-allowed';
            setBankFieldsEditable(false);
            editBtn.innerHTML = '<i class="fa-solid fa-pen"></i> Edit';
          }
        });
      }
    })();

    (function() {
      var btn = document.getElementById('toggle-password-edit');
      var pwd = document.getElementById('profile-password');
      var conf = document.getElementById('profile-password-confirmation');
      if (!btn || !pwd || !conf) return;
      btn.addEventListener('click', function() {
        if (pwd.disabled) {
          pwd.disabled = false;
          conf.disabled = false;
          pwd.style.backgroundColor = '';
          pwd.style.cursor = '';
          conf.style.backgroundColor = '';
          conf.style.cursor = '';
          pwd.focus();
          btn.innerHTML = '<i class="fa-solid fa-times"></i> Cancel';
        } else {
          pwd.disabled = true;
          conf.disabled = true;
          pwd.value = '';
          conf.value = '';
          pwd.style.backgroundColor = '#f8fafc';
          pwd.style.cursor = 'not-allowed';
          conf.style.backgroundColor = '#f8fafc';
          conf.style.cursor = 'not-allowed';
          btn.innerHTML = '<i class="fa-solid fa-key"></i> Change Password';
        }
      });
    })();
  </script>
</body>
</html>