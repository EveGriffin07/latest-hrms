<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Email – HRMS</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <style>
        /* Small additions to enhance the verify page layout */
        .verify-icon-container {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        .verify-icon {
            width: 64px;
            height: 64px;
            color: #0066cc; /* You can change this to match your HRMS brand color */
            background-color: #f0f7ff;
            padding: 12px;
            border-radius: 50%;
        }
        .text-center {
            text-align: center;
        }
        .text-muted {
            color: #6b7280;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 1.5rem;
        }
        .button-group {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            align-items: center;
            margin-top: 1.5rem;
        }
        .link-button {
            background: transparent;
            border: none;
            color: #6b7280;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
            transition: color 0.2s;
        }
        .link-button:hover {
            color: #0066cc;
            text-decoration: underline;
        }
    </style>
</head>
<body class="auth-page">

<div class="auth-card">
    <div class="auth-header text-center">
        
        <div class="verify-icon-container">
            <svg class="verify-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
            </svg>
        </div>

        <h1 class="auth-title">Check your inbox</h1>
        <p class="auth-subtitle text-muted">
            We've sent a verification link to your email address. 
            Please click the link to activate your account and access the HRMS portal.
        </p>
    </div>

    @if (session('message'))
        <div class="auth-alert auth-alert-success">
            {{ session('message') }}
        </div>
    @endif

    <div class="button-group">
        <form method="POST" action="{{ route('verification.send') }}" style="width: 100%;">
            @csrf
            <button type="submit" class="auth-button" style="width: 100%;">
                Resend Verification Email
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="link-button">
                &larr; Use a different account
            </button>
        </form>
    </div>
</div>

</body>
</html>