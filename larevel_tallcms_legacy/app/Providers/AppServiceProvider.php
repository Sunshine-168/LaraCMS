<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('menu.url.resolver', \TallCms\Cms\Services\MenuUrlResolver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureHttps();
        $this->configurePasswordDefaults();
        $this->configureRateLimiting();
    }

    /**
     * 生产环境强制 HTTPS URL 生成。
     */
    protected function configureHttps(): void
    {
        if ((bool) config('security.force_https', false)) {
            URL::forceScheme('https');
        }
    }

    /**
     * 全局密码强度策略。
     */
    protected function configurePasswordDefaults(): void
    {
        Password::defaults(function () {
            $rule = Password::min((int) config('security.password.min', 10));

            if (config('security.password.require_mixed_case', true)) {
                $rule->mixedCase();
            }

            if (config('security.password.require_numbers', true)) {
                $rule->numbers();
            }

            if (config('security.password.require_symbols', false)) {
                $rule->symbols();
            }

            if (config('security.password.uncompromised', true) && ! $this->app->environment('local', 'testing')) {
                $rule->uncompromised();
            }

            return $rule;
        });
    }

    /**
     * 命名限流器：登录与 API。
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $key = strtolower((string) $request->input('email', $request->ip()));

            return Limit::perMinute((int) config('security.rate_limit.login', 5))
                ->by($key.'|'.$request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute((int) config('security.rate_limit.api', 60))
                ->by($request->user()?->getAuthIdentifier() ?: $request->ip());
        });
    }
}
