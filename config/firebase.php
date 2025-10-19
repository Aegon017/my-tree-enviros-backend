<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Credentials
    |--------------------------------------------------------------------------
    |
    | Path to the Firebase service account JSON file.
    | You can also set the FIREBASE_CREDENTIALS environment variable.
    |
    */

    'credentials' => [
        'file' => env('FIREBASE_CREDENTIALS'),
        // Alternative: you can also specify the credentials as a JSON string
        // 'file' => env('FIREBASE_CREDENTIALS') ?: storage_path('app/firebase/credentials.json'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Database URL
    |--------------------------------------------------------------------------
    |
    | The URL of your Firebase Realtime Database.
    |
    */

    'database' => [
        'url' => env('FIREBASE_DATABASE_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Storage Bucket
    |--------------------------------------------------------------------------
    |
    | The name of your Firebase Storage bucket.
    |
    */

    'storage' => [
        'default_bucket' => env('FIREBASE_STORAGE_BUCKET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging
    |--------------------------------------------------------------------------
    |
    | Configuration for Firebase Cloud Messaging (FCM).
    |
    */

    'fcm' => [
        // Server key for legacy FCM (if needed)
        'server_key' => env('FCM_SERVER_KEY'),

        // Default notification settings
        'default_sound' => env('FCM_DEFAULT_SOUND', 'default'),
        'default_badge' => env('FCM_DEFAULT_BADGE', '1'),

        // Notification priority
        'priority' => env('FCM_PRIORITY', 'high'),

        // Time to live (in seconds)
        'ttl' => env('FCM_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable or disable logging for Firebase operations.
    |
    */

    'logging' => [
        'enabled' => env('FIREBASE_LOGGING_ENABLED', false),
        'channel' => env('FIREBASE_LOGGING_CHANNEL', 'stack'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Store
    |--------------------------------------------------------------------------
    |
    | This is the cache store that will be used by the Firebase SDK.
    |
    */

    'cache_store' => env('FIREBASE_CACHE_STORE', 'file'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    |
    | Additional options for the HTTP client used by the Firebase SDK.
    |
    */

    'http_client_options' => [
        'timeout' => env('FIREBASE_HTTP_TIMEOUT', 30),
        'proxy' => env('FIREBASE_HTTP_PROXY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Verification
    |--------------------------------------------------------------------------
    |
    | Enable or disable verification of API requests.
    |
    */

    'verify' => env('FIREBASE_VERIFY', true),

];
