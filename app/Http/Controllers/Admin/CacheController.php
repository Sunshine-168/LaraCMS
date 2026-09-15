<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\SiteBackupService;
use App\Services\Cms\HtmlCacheService;
use Database\Seeders\CmsSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CacheController extends Controller
{
    public function __construct(
        private readonly HtmlCacheService $htmlCache,
        private readonly SiteBackupService $backups,
    ) {}

    /** 旧「系统工具」入口，改走主题页 */
    public function edit(): RedirectResponse
    {
        return redirect()->route('admin.themes.index');
    }

    /** 一键清理配置 / 视图 / 应用 / 全页缓存 */
    public function clear(): RedirectResponse
    {
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        $this->htmlCache->forgetAll('admin:global-clear');

        return redirect()->route('admin.themes.index')->with('status', '缓存已清空');
    }

    /** 重新导入演示数据（幂等 updateOrCreate） */
    public function seedDemo(): RedirectResponse
    {
        try {
            Artisan::call('db:seed', ['--class' => CmsSeeder::class, '--force' => true]);
            Artisan::call('db:seed', ['--class' => DemoDataSeeder::class, '--force' => true]);
            if (! is_file(storage_path('app/install.lock'))) {
                file_put_contents(storage_path('app/install.lock'), 'installed_at='.now()->toDateTimeString()."\nseed_demo=1\n");
            }
        } catch (\Throwable $e) {
            return $this->toSettingsMore()->with('error', '导入失败：'.$e->getMessage());
        }

        return $this->toSettingsMore()->with('status', '演示数据已更新');
    }

    public function backup(): RedirectResponse
    {
        $result = $this->backups->create();

        return $this->toSettingsMore()
            ->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }

    public function downloadBackup(string $file): BinaryFileResponse|RedirectResponse
    {
        $path = $this->backups->pathFor($file);
        if (! $path) {
            return $this->toSettingsMore()->with('error', '备份不存在');
        }

        return response()->download($path, $file);
    }

    public function restoreBackup(string $file): RedirectResponse
    {
        $result = $this->backups->restore($file);

        return $this->toSettingsMore()
            ->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }

    protected function toSettingsMore(): RedirectResponse
    {
        return redirect()->route('admin.settings.edit', ['tab' => 'more']);
    }
}
