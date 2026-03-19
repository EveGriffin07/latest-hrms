@php
    $role = strtolower(Auth::user()->role ?? 'admin');
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - HRMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
</head>

<body>

    <header>
        <div class="title">Web-Based HRMS</div>
        <div class="user-info">
            <a href="{{ route('admin.profile') }}" style="text-decoration: none; color: inherit;">
                <i class="fa-regular fa-bell"></i> &nbsp; {{ Auth::user()->name }}
            </a>
        </div>
    </header>

    <div class="container">
        @include('admin.layout.sidebar')

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

            <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data">
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
                        <p class="profile-role">{{ ucfirst($user->role) }}</p>

                        <div class="profile-stats">
                            <div class="stat">
                                <span class="num">{{ $stats['announcements'] ?? 0 }}</span>
                                <span class="label">Announcements</span>
                            </div>
                            <div class="stat">
                                <span class="num">{{ $stats['employees'] ?? 0 }}</span>
                                <span class="label">Employees</span>
                            </div>
                            <div class="stat">
                                <span class="num">{{ $stats['users'] ?? 0 }}</span>
                                <span class="label">Total Users</span>
                            </div>
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
                                <input type="text" id="profile-phone" name="phone" value="{{ old('phone', $user->employee->phone ?? '') }}" placeholder="+60..." readonly style="background-color: #f8fafc; cursor: not-allowed;">
                            </div>
                            <div class="form-group">
                                <label>Department</label>
                                <input type="text" value="{{ $user->employee->department->department_name ?? 'Not Assigned' }}" readonly style="background-color: #f8fafc; cursor: not-allowed;">
                            </div>
                        </div>

                        <h3 class="section-title"><i class="fa-solid fa-building-columns"></i> Bank Account Details</h3>
                        <p class="subtitle" style="margin: 0 0 12px 0; font-size: 0.9rem;">For salary and reimbursement. Optional. Click Edit above to change.</p>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="bank_code">Bank Name</label>
                                <select name="bank_code" id="bank_code" disabled style="width:100%; padding:10px 12px; border:1px solid #e5e7eb; border-radius:10px; font-size:0.95rem; background-color:#f8fafc; cursor:not-allowed;">
                                    <option value="">— Select bank —</option>
                                    @foreach(config('hrms.banks', []) as $code => $name)
                                        <option value="{{ $code }}" {{ old('bank_code', $user->employee->bank_code ?? '') == $code ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="bank_account_holder">Account Holder Name</label>
                                <input type="text" id="bank_account_holder" name="bank_account_holder" value="{{ old('bank_account_holder', $user->employee->bank_account_holder ?? '') }}" placeholder="Name as on bank account" readonly style="background-color:#f8fafc; cursor:not-allowed;">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="bank_account_number">Account Number</label>
                                <input type="text" id="bank_account_number" name="bank_account_number" value="{{ old('bank_account_number', $user->employee->bank_account_number ?? '') }}" placeholder="Digits only" readonly style="background-color:#f8fafc; cursor:not-allowed;">
                            </div>
                            <div class="form-group">
                                <label for="account_type">Account Type</label>
                                <select name="account_type" id="account_type" disabled style="width:100%; padding:10px 12px; border:1px solid #e5e7eb; border-radius:10px; font-size:0.95rem; background-color:#f8fafc; cursor:not-allowed;">
                                    <option value="">— Select type —</option>
                                    @foreach(config('hrms.bank_account_types', []) as $value => $label)
                                        <option value="{{ $value }}" {{ old('account_type', $user->employee->account_type ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
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
        // Avatar Preview logic
        document.getElementById('avatarInput').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatarPreview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Personal Information & Bank Fields Toggle
        (function() {
            var editBtn = document.getElementById('toggle-profile-edit');
            var nameEl = document.getElementById('profile-name');
            var emailEl = document.getElementById('profile-email');
            var phoneEl = document.getElementById('profile-phone');
            var bankFields = [
                document.getElementById('bank_code'),
                document.getElementById('bank_account_holder'),
                document.getElementById('bank_account_number'),
                document.getElementById('account_type')
            ];

            function setFieldsEditable(editable) {
                [nameEl, emailEl, phoneEl].concat(bankFields).forEach(function(el) {
                    if (!el) return;
                    if (el.tagName === 'SELECT') {
                        el.disabled = !editable;
                    } else {
                        el.readOnly = !editable;
                    }
                    el.style.backgroundColor = editable ? '' : '#f8fafc';
                    el.style.cursor = editable ? '' : 'not-allowed';
                });
            }

            if (editBtn) {
                editBtn.addEventListener('click', function() {
                    if (nameEl.readOnly) {
                        setFieldsEditable(true);
                        editBtn.innerHTML = '<i class="fa-solid fa-times"></i> Cancel';
                    } else {
                        setFieldsEditable(false);
                        editBtn.innerHTML = '<i class="fa-solid fa-pen"></i> Edit';
                    }
                });
            }
        })();

        // Password Toggle logic
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