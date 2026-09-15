<?php

namespace App\Providers;

use App\Models\Cms\Comment;
use App\Models\Cms\Entry;
use App\Models\Cms\FormSubmission;
use App\Models\Cms\Setting;
use App\Services\Admin\DashboardService;
use Illuminate\Pagination\Paginator;
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
        Paginator::useBootstrapFour();

        Password::defaults(function () {
            $min = (int) config('security.password.min', 10);

            return Password::min(max(8, $min))->letters()->numbers();
        });

        try {
            $theme = Setting::getValue('theme');
            if (is_string($theme) && $theme !== '') {
                config(['cms.theme' => $theme]);
            }
        } catch (\Throwable) {
            // 迁移前忽略
        }

        Entry::saved(function (Entry $entry) {
            if ($entry->wasRecentlyCreated || $entry->wasChanged('status')) {
                DashboardService::bustInbox();
            }
        });
        Entry::deleted(fn () => DashboardService::bustInbox());
        Comment::saved(function (Comment $comment) {
            if ($comment->wasRecentlyCreated || $comment->wasChanged('status')) {
                DashboardService::bustInbox();
            }
        });
        FormSubmission::saved(function (FormSubmission $row) {
            if ($row->wasRecentlyCreated || $row->wasChanged('is_read')) {
                DashboardService::bustInbox();
            }
        });
    }
}
