<?php

// Handle installer bootstrap before Laravel fully loads
require __DIR__.'/installer.php';

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use TallCms\Cms\Http\Middleware\MaintenanceModeMiddleware;
use TallCms\Cms\Http\Middleware\ThemePreviewMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Minimal middleware for installer - no database dependencies
            Route::middleware([
                \Illuminate\Cookie\Middleware\EncryptCookies::class,
                \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
                \Illuminate\Session\Middleware\StartSession::class,
                \Illuminate\View\Middleware\ShareErrorsFromSession::class,
                \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
                SecurityHeaders::class,
            ])->group(base_path('routes/installer.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Use env() here — config may not be fully available during early middleware binding.
        $trustedProxies = env('TRUST_PROXIES', '*');
        $middleware->trustProxies(
            at: $trustedProxies === '*' ? '*' : array_values(array_filter(array_map('trim', explode(',', (string) $trustedProxies)))),
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        $middleware->alias([
            'maintenance.mode' => MaintenanceModeMiddleware::class,
            'installer.gate' => \App\Http\Middleware\InstallerGate::class,
            'theme.preview' => ThemePreviewMiddleware::class,
            'security.headers' => SecurityHeaders::class,
        ]);

        $middleware->web(append: [
            ThemePreviewMiddleware::class,
            SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
