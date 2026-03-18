<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password – HRMS</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="auth-page">

<div class="auth-card">
    <div class="auth-header">
        <h1 class="auth-title">Forgot your password?</h1>
        <p class="auth-subtitle">Enter your email and we’ll send reset instructions.</p>
    </div>

    @if (session('status'))
        <div class="auth-alert auth-alert-success">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="auth-alert auth-alert-error">
            {{ $errors->first() }}
        </div>
    @endif

    <form class="auth-form" method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="form-group">
            <label class="form-label" for="email">Email address</label>
            <input
                id="email"
                type="email"
                name="email"
                class="form-input"
                required
                autofocus
            >
        </div>

        <button type="submit" class="auth-button">
            Send reset link
        </button>
    </form>

    <div class="auth-footer">
        <a href="{{ route('login') }}">Back to sign in</a>
    </div>
</div>

</body>
</html>