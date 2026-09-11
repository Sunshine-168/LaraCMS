<?php

namespace App\Providers;

use App\Cms\Blade\CmsDirectiveRegistrar;
use App\Cms\CmsViewContext;
use Illuminate\Support\ServiceProvider;

class CmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CmsViewContext::class);
    }

    public function boot(CmsDirectiveRegistrar $directives): void
    {
        $directives->register();
    }
}
