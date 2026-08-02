<?php

use App\Http\Middleware\AdminAuthenticate;
use App\Http\Middleware\AdminPermission;
use App\Http\Middleware\AdminRole;
use App\Http\Middleware\ApiToken;
use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\HandleCmsRedirect;
use App\Http\Middleware\HtmlCache;
use App\Http\Middleware\LogAdminAction;
use App\Http\Middleware\RecordVisit;
use App\Http\Middleware\ResolveSite;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SiteClosed;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Illuminate\Support\Facades\Route::middleware('web')->group(function () {
                Illuminate\Support\Facades\Route::get('/install', [\App\Http\Controllers\Web\InstallController::class, 'index'])->name('install');
                Illuminate\Support\Facades\Route::post('/install', [\App\Http\Controllers\Web\InstallController::class, 'run'])->name('install.run');
            });
            // API / 后台先注册，避免被前台 /{cat}/{slug} 吃掉
            Illuminate\Support\Facades\Route::middleware('api')
                ->group(base_path('routes/api.php'));
            Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/admin.php'));
            Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/web.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $trusted = env('TRUST_PROXIES', '*');
        $middleware->trustProxies(
            at: $trusted === '*' ? '*' : array_values(array_filter(array_map('trim', explode(',', (string) $trusted)))),
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        $middleware->alias([
            'admin.auth' => AdminAuthenticate::class,
            'admin.perm' => AdminPermission::class,
            'admin.role' => AdminRole::class, // 兼容旧用法，新代码请用 admin.perm
            'admin.log' => LogAdminAction::class,
            'locale' => SetLocale::class,
            'html.cache' => HtmlCache::class,
            'api.token' => ApiToken::class,
        ]);

        $middleware->web(prepend: [
            CheckInstalled::class,
        ]);

        // ResolveSite 需在 StartSession 之后；重定向依赖当前站
        $middleware->web(append: [
            ResolveSite::class,
            HandleCmsRedirect::class,
            SecurityHeaders::class,
            SiteClosed::class,
            RecordVisit::class,
        ]);

        $middleware->api(prepend: [
            ResolveSite::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
