<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'face_api' => [
        'url' => env('FACE_API_URL', 'http://127.0.0.1:8001'),
        // Default lowered slightly for real-world camera noise; override via FACE_API_THRESHOLD in .env
        'threshold' => env('FACE_API_THRESHOLD', 0.30),
        'model' => env('FACE_API_MODEL', 'buffalo_l'),
        'cooldown_minutes' => (int) env('FACE_ATTENDANCE_COOLDOWN_MINUTES', 2),
    ],

];
