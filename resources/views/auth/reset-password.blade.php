<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password – HRMS</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="auth-page">

<div class="auth-card">
    <div class="auth-header">
        <h1 class="auth-title">Create new password</h1>
        <p class="auth-subtitle">Please enter your new password below.</p>
    </div>

    @if ($errors->any())
        <div class="auth-alert auth-alert-error">
            {{ $errors->first() }}
        </div>
    @endif

    <form class="auth-form" method="POST" action="{{ route('password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div class="form-group">
            <label class="form-label" for="email">Email address</label>
            <input
                id="email"
                type="email"
                name="email"
                class="form-input"
                value="{{ $email ?? old('email') }}"
                required
                readonly
                style="background-color: #f3f4f6;"
            >
        </div>

        <div class="form-group">
            <label class="form-label" for="password">New Password</label>
            <input
                id="password"
                type="password"
                name="password"
                class="form-input"
                required
                autofocus
            >
        </div>

        <div class="form-group">
            <label class="form-label" for="password_confirmation">Confirm New Password</label>
            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                class="form-input"
                required
            >
        </div>

        <button type="submit" class="auth-button">
            Reset Password
        </button>
    </form>
</div>

</body>
</html>