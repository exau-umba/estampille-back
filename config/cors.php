<?php

$allowedOrigins = array_values(array_filter(array_map(
    static fn ($origin) => trim($origin),
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'https://integration.occdcpl.cd'))
)));

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => $allowedOrigins,
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
