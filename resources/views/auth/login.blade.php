<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login – HRMS</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="auth-page">

<div class="auth-card">
    <div class="auth-header">
        <h1 class="auth-title">Sign in to HRMS</h1>
        <p class="auth-subtitle">Access your HR management workspace.</p>
    </div>

    @if (session('success'))
        <div class="auth-alert auth-alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="auth-alert auth-alert-error">
            {{ $errors->first() }}
        </div>
    @endif

    <form class="auth-form" method="POST" action="{{ route('login.submit') }}">
        @csrf

        <div class="form-group">
            <label class="form-label" for="email">Email address</label>
            <input
                id="email"
                type="email"
                name="email"
                class="form-input"
                value="{{ old('email') }}"
                required
                autofocus
            >
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input
                id="password"
                type="password"
                name="password"
                class="form-input"
                required
            >
        </div>

        <button type="submit" class="auth-button">
            Sign in
        </button>
    </form>

    <div class="auth-footer">
        <div class="auth-inline-links">
            <a href="{{ route('password.request') }}">Forgot password?</a>
            <a href="{{ route('register') }}">Create an account</a>
        </div>
    </div>
</div>

</body>
</html>
