<?php

namespace App\Providers;

use App\Models\Cms\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Password::defaults(function () {
            $min = (int) config('security.password.min', 10);

            return Password::min(max(8, $min))->letters()->numbers();
        });

        try {
            if (Schema::hasTable('cms_settings')) {
                $theme = Setting::getValue('theme');
                if (is_string($theme) && $theme !== '') {
                    config(['cms.theme' => $theme]);
                }
            }
        } catch (\Throwable) {
            // 迁移前忽略
        }
    }
}
