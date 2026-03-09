<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Archive;
use App\Models\Cms\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends BaseAdminController
{
    public function index(): View
    {
        return $this->adminView('admin.dashboard.index', [
            'quickPanels' => $this->auth->quickPanels(),
            'stats' => [
                'admins' => AdminUser::query()->count(),
                'categories' => Category::query()->count(),
                'articles' => Archive::query()->count(),
            ],
            'systemInfo' => [
                'OS' => PHP_OS,
                'domain' => request()->getHost(),
                'phpv' => phpversion(),
                'mysqlv' => DB::selectOne('SELECT VERSION() AS version')?->version ?? '',
            ],
        ]);
    }
}
