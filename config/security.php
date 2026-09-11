<?php

return [
    'force_https' => (bool) env('FORCE_HTTPS', false),
    'trust_proxies' => env('TRUST_PROXIES', ''),
    'hsts_max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),
    'password' => [
        'min' => (int) env('PASSWORD_MIN_LENGTH', 10),
    ],
];
