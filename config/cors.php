<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | When using Sanctum SPA authentication with credentials (cookies), you must
    | specify explicit allowed origins. Wildcard '*' is not allowed with credentials.
    | Use CORS_ALLOWED_ORIGINS in .env (comma-separated) for production, or
    | it falls back to SANCTUM_STATEFUL_DOMAINS so one source of truth.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'register', 'forgot-password', 'reset-password', 'two-factor-challenge', 'user/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', env('SANCTUM_STATEFUL_DOMAINS', 'http://localhost:5173,http://localhost:3000,http://127.0.0.1:5173')))))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 600,

    'supports_credentials' => true,

];
