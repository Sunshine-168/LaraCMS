<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Force HTTPS
    |--------------------------------------------------------------------------
    |
    | When true, generated URLs use https and HSTS header is sent.
    | Enable behind TLS-terminated reverse proxies in production.
    |
    */
    'force_https' => (bool) env('FORCE_HTTPS', false),

    /*
    |--------------------------------------------------------------------------
    | Trust Proxies
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of trusted proxy IPs, or "*" to trust all.
    | Required when running behind Nginx / Cloudflare / load balancers.
    |
    */
    'trust_proxies' => env('TRUST_PROXIES', '*'),

    /*
    |--------------------------------------------------------------------------
    | HSTS
    |--------------------------------------------------------------------------
    */
    'hsts_max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),

    /*
    |--------------------------------------------------------------------------
    | Content-Security-Policy
    |--------------------------------------------------------------------------
    |
    | Leave empty to skip CSP (recommended until you inventory all assets).
    | Example baseline for TallCMS + Filament (adjust as needed):
    | default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: https:; font-src 'self' data:; connect-src 'self' wss: https:; frame-ancestors 'self'
    |
    */
    'csp' => env('SECURITY_CSP', ''),

    /*
    |--------------------------------------------------------------------------
    | Admin MFA
    |--------------------------------------------------------------------------
    */
    'admin_mfa_required' => (bool) env('ADMIN_MFA_REQUIRED', false),

    /*
    |--------------------------------------------------------------------------
    | Password policy
    |--------------------------------------------------------------------------
    */
    'password' => [
        'min' => (int) env('PASSWORD_MIN_LENGTH', 10),
        'require_mixed_case' => (bool) env('PASSWORD_REQUIRE_MIXED_CASE', true),
        'require_numbers' => (bool) env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_symbols' => (bool) env('PASSWORD_REQUIRE_SYMBOLS', false),
        'uncompromised' => (bool) env('PASSWORD_UNCOMPROMISED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate limiting
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'login' => (int) env('RATE_LIMIT_LOGIN', 5),
        'api' => (int) env('RATE_LIMIT_API', 60),
    ],
];
