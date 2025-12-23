<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    
    'allowed_methods' => ['*'],
    
    'allowed_origins' => [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'http://192.168.1.5:3000', // IP de red local
    ],
    
    'allowed_origins_patterns' => [
        '/^http:\/\/192\.168\.\d{1,3}\.\d{1,3}:3000$/', // Cualquier IP 192.168.x.x
    ],
    
    'allowed_headers' => ['*'],
    
    'exposed_headers' => [],
    
    'max_age' => 0,
    
    'supports_credentials' => true,
];