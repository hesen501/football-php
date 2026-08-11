<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Wildcard by default for local development. Set CORS_ALLOWED_ORIGINS
    // (comma-separated) in production to the real frontend domain(s) —
    // this is a token-auth API (supports_credentials is false below), so a
    // wildcard origin isn't a credential-leak risk the way it would be for
    // cookie-based auth, but it's still worth narrowing before going live.
    'allowed_origins' => array_filter(explode(',', (string) env('CORS_ALLOWED_ORIGINS', '*'))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Bearer tokens (Sanctum personal access tokens), not cookies — no
    // cross-site cookie/credential exposure to guard against here.
    'supports_credentials' => false,

];
