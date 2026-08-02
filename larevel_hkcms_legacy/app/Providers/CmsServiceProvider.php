<?php

namespace App\Providers;

use App\Cms\Blade\CmsDirectiveRegistrar;
use App\Cms\CmsViewContext;
use App\Services\Cms\Theme\ThemeResolver;
use Illuminate\Support\ServiceProvider;

class CmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CmsViewContext::class);
        $this->app->singleton(ThemeResolver::class);
    }

    public function boot(ThemeResolver $themeResolver, CmsDirectiveRegistrar $directives): void
    {
        // Use config theme at boot time to avoid DB access during package:discover.
        // Controllers re-register after SiteService loads index_theme.
        $themeResolver->registerViewNamespace((string) config('cms.theme', 'default'));
        $directives->register();
    }
}
