<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\SettingService;
use App\Services\Cms\DiskHtmlService;
use App\Services\Cms\HtmlCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HtmlCacheController extends Controller
{
    public function __construct(
        private readonly HtmlCacheService $htmlCache,
        private readonly DiskHtmlService $diskHtml,
        private readonly SettingService $settings,
    ) {
    }

    /** 全页缓存管理 */
    public function edit(): View
    {
        $cfg = $this->settings->getMany(['html_cache_enabled', 'html_cache_ttl']);

        return view('admin.html_cache.edit', [
            'enabled' => ($cfg['html_cache_enabled'] !== null && $cfg['html_cache_enabled'] !== '')
                ? (bool) (int) $cfg['html_cache_enabled']
                : (bool) config('cms.html_cache.enabled'),
            'ttl' => ($cfg['html_cache_ttl'] !== null && $cfg['html_cache_ttl'] !== '')
                ? (int) $cfg['html_cache_ttl']
                : (int) config('cms.html_cache.ttl', 3600),
            'lastBust' => $this->htmlCache->lastBust(),
            'tagged' => $this->htmlCache->canTag(),
            'diskEnabled' => $this->diskHtml->enabled(),
            'diskRoot' => $this->diskHtml->rootPath(),
        ]);
    }

    /** 保存开关与 TTL */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'html_cache_ttl' => 'nullable|integer|min:0|max:2592000',
        ]);

        $this->settings->save([
            'html_cache_enabled' => $request->boolean('html_cache_enabled') ? '1' : '0',
            'html_cache_ttl' => (string) max(0, (int) $request->input('html_cache_ttl', 3600)),
        ]);

        return redirect()->route('admin.html-cache.edit')->with('status', '全页缓存设置已保存');
    }

    /** 清空 */
    public function clear(): RedirectResponse
    {
        $this->htmlCache->forgetAll('admin:clear');

        return redirect()->route('admin.html-cache.edit')->with('status', '全页 HTML 缓存已清空');
    }

    /** 预热 */
    public function warm(Request $request): RedirectResponse
    {
        if (! $this->htmlCache->enabled()) {
            return redirect()->route('admin.html-cache.edit')->with('error', '请先启用全页缓存');
        }

        $result = $this->htmlCache->warm((int) $request->input('entries', 30));

        return redirect()->route('admin.html-cache.edit')->with(
            'status',
            "预热完成：成功 {$result['ok']}，失败 {$result['fail']}"
        );
    }

    /** 生成磁盘整站 HTML */
    public function buildDisk(Request $request): RedirectResponse
    {
        if (! $this->diskHtml->enabled()) {
            return redirect()->route('admin.html-cache.edit')->with('error', '磁盘静态化未启用（CMS_DISK_HTML）');
        }
        $result = $this->diskHtml->build((int) $request->input('entries', 200));

        return redirect()->route('admin.html-cache.edit')->with(
            'status',
            "磁盘 HTML 生成：成功 {$result['ok']}，失败 {$result['fail']} → ".$this->diskHtml->rootPath()
        );
    }

    /** 清空磁盘 HTML */
    public function clearDisk(): RedirectResponse
    {
        $n = $this->diskHtml->clear();

        return redirect()->route('admin.html-cache.edit')->with('status', "已删除 {$n} 个磁盘 HTML 文件");
    }
}
