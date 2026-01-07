<?php

declare(strict_types=1);

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

    'firebase' => [
        'credentials' => base_path(env('FIREBASE_CREDENTIALS')),
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

    'sms_login' => [
        'endpoint' => env('SMS_LOGIN_ENDPOINT'),
        'username' => env('SMS_LOGIN_USERNAME'),
        'apikey' => env('SMS_LOGIN_APIKEY'),
        'senderid' => env('SMS_LOGIN_SENDERID'),
        'otptemplateid' => env('SMS_LOGIN_OTP_TEMPLATE_ID'),
    ],

    'razorpay' => [
        'key' => env('RAZORPAY_KEY'),
        'secret' => env('RAZORPAY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
    ],

    'phonepe' => [
        'client_id' => env('PHONEPE_CLIENT_ID'),
        'client_version' => env('PHONEPE_CLIENT_VERSION', 1),
        'client_secret' => env('PHONEPE_CLIENT_SECRET'),
        'env' => env('PHONEPE_ENV', 'UAT'),
        'webhook_username' => env('PHONEPE_WEBHOOK_USERNAME'),
        'webhook_password' => env('PHONEPE_WEBHOOK_PASSWORD'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'app' => [
        'ios' => env('APP_STORE_LINK', 'https://apps.apple.com/in/app/my-tree-enviros/id6748556520'),
        'android' => env('PLAY_STORE_LINK', 'https://play.google.com/store/apps/details?id=com.mytree&pcampaignid=web_share'),
    ],
];
