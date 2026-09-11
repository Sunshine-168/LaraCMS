<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\SiteBackupService;
use App\Services\Cms\HtmlCacheService;
use Database\Seeders\CmsSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CacheController extends Controller
{
    public function __construct(
        private readonly HtmlCacheService $htmlCache,
        private readonly SiteBackupService $backups,
    ) {}

    public function edit(): View
    {
        return view('admin.cache.edit', [
            'hasLock' => is_file(storage_path('app/install.lock')),
            'backups' => $this->backups->list(),
        ]);
    }

    /** 一键清理配置 / 视图 / 应用 / 全页缓存 */
    public function clear(): RedirectResponse
    {
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        $this->htmlCache->forgetAll('admin:global-clear');

        return redirect()->route('admin.cache.edit')->with('status', '已清理：config / view / cache / route / 全页 HTML');
    }

    /** 重新导入演示数据（幂等 updateOrCreate） */
    public function seedDemo(): RedirectResponse
    {
        try {
            Artisan::call('db:seed', ['--class' => CmsSeeder::class, '--force' => true]);
            if (! is_file(storage_path('app/install.lock'))) {
                file_put_contents(storage_path('app/install.lock'), 'installed_at='.now()->toDateTimeString()."\nseed_demo=1\n");
            }
        } catch (\Throwable $e) {
            return redirect()->route('admin.cache.edit')->with('error', '导入失败：'.$e->getMessage());
        }

        return redirect()->route('admin.cache.edit')->with('status', '演示数据已导入/更新（账号见 README）');
    }

    public function backup(): RedirectResponse
    {
        $result = $this->backups->create();

        return redirect()->route('admin.cache.edit')
            ->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }

    public function downloadBackup(string $file): BinaryFileResponse|RedirectResponse
    {
        $path = $this->backups->pathFor($file);
        if (! $path) {
            return redirect()->route('admin.cache.edit')->with('error', '备份不存在');
        }

        return response()->download($path, $file);
    }

    public function restoreBackup(string $file): RedirectResponse
    {
        $result = $this->backups->restore($file);

        return redirect()->route('admin.cache.edit')
            ->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }
}
