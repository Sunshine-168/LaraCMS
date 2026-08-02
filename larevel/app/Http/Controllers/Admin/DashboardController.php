<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Category;
use App\Models\Cms\ContentModel;
use App\Models\Cms\Entry;
use App\Models\Cms\FormSubmission;
use App\Models\Cms\Media;
use App\Models\Cms\Setting;
use App\Services\Cms\HtmlCacheService;
use App\Services\Cms\Stats\StatService;
use App\Support\CmsRuntime;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly StatService $stats,
        private readonly HtmlCacheService $htmlCache,
    ) {
    }

    /** 后台首页仪表盘 */
    public function index(): View
    {
        $isSuper = auth('admin')->user()?->isSuperAdmin();

        $quickNav = [
            ['title' => '内容', 'icon' => 'fas fa-file-alt', 'url' => route('admin.entries.index')],
            ['title' => '栏目', 'icon' => 'fas fa-sitemap', 'url' => route('admin.categories.index')],
            ['title' => '媒体', 'icon' => 'fas fa-photo-video', 'url' => route('admin.media.index')],
            ['title' => '表单', 'icon' => 'fas fa-wpforms', 'url' => route('admin.forms.index')],
            ['title' => '留言', 'icon' => 'fas fa-inbox', 'url' => route('admin.form-submissions.index')],
            ['title' => '统计', 'icon' => 'fas fa-chart-line', 'url' => route('admin.stats.index')],
            ['title' => '菜单', 'icon' => 'fas fa-bars', 'url' => route('admin.menus.index')],
            ['title' => '前台', 'icon' => 'fas fa-home', 'url' => url('/')],
        ];

        if ($isSuper) {
            $quickNav = array_merge($quickNav, [
                ['title' => '推荐位', 'icon' => 'fas fa-th-large', 'url' => route('admin.recommends.index')],
                ['title' => '模型', 'icon' => 'fas fa-cube', 'url' => route('admin.models.index')],
                ['title' => '采集', 'icon' => 'fas fa-cloud-download-alt', 'url' => route('admin.collectors.index')],
                ['title' => '缓存', 'icon' => 'fas fa-bolt', 'url' => route('admin.html-cache.edit')],
                ['title' => '设置', 'icon' => 'fas fa-cog', 'url' => route('admin.settings.edit')],
            ]);
        }

        return view('admin.dashboard', [
            'counts' => [
                'entries' => Entry::query()->count(),
                'categories' => Category::query()->count(),
                'models' => ContentModel::query()->count(),
                'media' => Media::query()->count(),
                'forms' => FormSubmission::query()->count(),
            ],
            'traffic' => $this->stats->overview()['today'],
            'quickNav' => $quickNav,
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
