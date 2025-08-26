<?php

return [
    'paths' => ['api/*'], // no usamos cookies ni csrf-cookie
    'allowed_methods' => ['*'],

    // Para probar desde Flutter Web/Chrome en localhost y puertos varios
    'allowed_origins' => ['http://localhost', 'http://127.0.0.1'],

    // Patrón para permitir cualquier puerto y también IPs de tu LAN (192.168.x.x / 10.x.x.x / 172.16-31.x.x)
    'allowed_origins_patterns' => [
        '#^http://localhost(:\d+)?$#',
        '#^http://127\.0\.0\.1(:\d+)?$#',
        '#^http://192\.168\.\d{1,3}\.\d{1,3}(:\d+)?$#',
        '#^http://10\.\d{1,3}\.\d{1,3}\.\d{1,3}(:\d+)?$#',
        '#^http://172\.(1[6-9]|2\d|3[0-1])\.\d{1,3}\.\d{1,3}(:\d+)?$#',
    ],

    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
