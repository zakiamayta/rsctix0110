<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration determines what cross-origin operations may execute
    | in web browsers. This is especially important for API consumed by
    | mobile apps (Flutter), web apps, and external clients.
    |
    */

    // Path mana saja yang diizinkan CORS
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
    ],

    // HTTP method yang diizinkan
    'allowed_methods' => ['*'],

    // Origin yang diizinkan
    // Untuk development: boleh '*'
    'allowed_origins' => ['*'],

    // Kalau mau lebih ketat (opsional):
    // 'allowed_origins' => [
    //     'http://localhost',
    //     'http://127.0.0.1',
    //     'http://10.0.2.2',
    // ],

    // Pattern origin (jarang dipakai)
    'allowed_origins_patterns' => [],

    // Header yang diizinkan
    'allowed_headers' => ['*'],

    // Header yang boleh di-expose ke client
    'exposed_headers' => [],

    // Cache preflight request (detik)
    'max_age' => 0,

    // Apakah mendukung cookie / auth session
    // Flutter API biasanya FALSE
    'supports_credentials' => false,

];
