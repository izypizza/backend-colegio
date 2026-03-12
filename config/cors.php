<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'https://frontend-colegio-nine.vercel.app',
    ],

    'allowed_origins_patterns' => [
        // En desarrollo: acepta cualquier IP en puerto 3000
        env('APP_ENV') === 'local' ? '/^http:\/\/[\d.]+:3000$/' : '/^$/',
        // Producción: solo IPs específicas de redes privadas
        '/^http:\/\/192\.168\.\d{1,3}\.\d{1,3}:3000$/', // Cualquier IP 192.168.x.x
        '/^http:\/\/10\.\d{1,3}\.\d{1,3}\.\d{1,3}:3000$/', // Cualquier IP 10.x.x.x
        '/^http:\/\/172\.(1[6-9]|2[0-9]|3[0-1])\.\d{1,3}\.\d{1,3}:3000$/', // IP 172.16-31.x.x
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];