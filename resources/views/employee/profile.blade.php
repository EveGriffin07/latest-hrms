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
      <a href="{{ route('employee.profile') }}" style="color:inherit; text-decoration:none;">
        <i class="fa-regular fa-bell"></i> &nbsp; {{ Auth::user()->name ?? 'Employee' }}
      </a>
    </div>
  </header>

  <div class="container">
    @include('employee.layout.sidebar')

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

      <form action="{{ route('employee.profile.update') }}" method="POST" enctype="multipart/form-data">
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
                <span class="num">{{ $stats['announcements'] }}</span>
                <span class="label">Announcements</span>
              </div>
              <div class="stat">
                <span class="num">{{ $stats['leave_requests'] }}</span>
                <span class="label">Leave Requests</span>
              </div>
              <div class="stat">
                <span class="num">{{ $stats['ot_claims'] }}</span>
                <span class="label">OT Claims</span>
              </div>
            </div>
          </div>

          <div class="profile-content">
            <h3 class="section-title"><i class="fa-solid fa-user"></i> Personal Information</h3>

            <div class="form-row">
              <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
              </div>
              <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" value="{{ old('phone', $employee?->phone ?? '') }}" placeholder="+60...">
              </div>
              <div class="form-group">
                <label>Department</label>
                <input type="text" value="{{ $employee?->department?->department_name ?? 'Not Assigned' }}" readonly style="background-color: #f3f4f6; cursor: not-allowed;">
              </div>
            </div>

            <h3 class="section-title"><i class="fa-solid fa-lock"></i> Account Security</h3>

            <div class="form-row">
              <div class="form-group">
                <label>Username (Role)</label>
                <input type="text" value="{{ $user->role }}" readonly style="background-color: #f3f4f6;">
              </div>
              <div class="form-group">
                <label>New Password</label>
                <input type="password" name="password" placeholder="Leave blank to keep current">
              </div>
              <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="password_confirmation">
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn-save"><i class="fa-solid fa-save"></i> Save Changes</button>
            </div>
          </div>
        </div>
      </form>

      <footer>© 2025 Web-Based HRMS. All Rights Reserved.</footer>
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
  </script>
</body>
</html>
