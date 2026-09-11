<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Category;
use App\Models\Cms\ContentModel;
use App\Models\Cms\Entry;
use App\Models\Cms\FormSubmission;
use App\Models\Cms\Media;
use App\Models\Cms\Setting;
use App\Services\Admin\QuickPanelService;
use App\Services\Cms\HtmlCacheService;
use App\Services\Cms\Stats\StatService;
use App\Support\CmsRuntime;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly StatService $stats,
        private readonly HtmlCacheService $htmlCache,
        private readonly QuickPanelService $quickPanels,
    ) {
    }

    /** 后台首页仪表盘 */
    public function index(): View
    {
        $admin = auth('admin')->user();

        return view('admin.dashboard', [
            'counts' => [
                'entries' => Entry::query()->count(),
                'categories' => Category::query()->count(),
                'models' => ContentModel::query()->count(),
                'media' => Media::query()->count(),
                'forms' => FormSubmission::query()->count(),
            ],
            'traffic' => $this->stats->overview()['today'],
            'quickNav' => $admin ? $this->quickPanels->forAdmin($admin) : [],
            'system' => [
                'site_name' => (string) Setting::getValue('site_name', config('app.name')),
                'theme' => (string) Setting::getValue('theme', config('cms.theme')),
                'laravel' => app()->version(),
                'php' => PHP_VERSION,
                'collect_mode' => CmsRuntime::collectMode(),
                'html_cache' => $this->htmlCache->enabled(),
            ],
        ]);
    }
}
