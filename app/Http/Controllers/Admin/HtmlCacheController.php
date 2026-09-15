<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\SettingService;
use App\Services\Cms\DiskHtmlService;
use App\Services\Cms\HtmlCacheService;
use Illuminate\Http\JsonResponse;
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
        $ttl = ($cfg['html_cache_ttl'] !== null && $cfg['html_cache_ttl'] !== '')
            ? (int) $cfg['html_cache_ttl']
            : (int) config('cms.html_cache.ttl', 3600);
        $lastBust = $this->htmlCache->lastBust();
        $ttlOptions = HtmlCacheService::ttlOptions();
        if (! array_key_exists($ttl, $ttlOptions)) {
            $ttlOptions[$ttl] = '当前 '.$this->ttlPlain($ttl);
        }

        return view('admin.html_cache.edit', [
            'enabled' => ($cfg['html_cache_enabled'] !== null && $cfg['html_cache_enabled'] !== '')
                ? (bool) (int) $cfg['html_cache_enabled']
                : (bool) config('cms.html_cache.enabled'),
            'ttl' => $ttl,
            'ttlOptions' => $ttlOptions,
            'lastBust' => $lastBust,
            'lastBustLabel' => $this->htmlCache->bustLabel($lastBust),
            'diskEnabled' => $this->diskHtml->enabled(),
            'diskCount' => $this->diskHtml->fileCount(),
            'diskJob' => $this->diskHtml->publicJob(),
        ]);
    }

    /** 打开或关闭磁盘静态页 */
    public function updateDisk(Request $request): RedirectResponse
    {
        $on = $request->boolean('disk_html_enabled');
        $this->settings->save([
            'disk_html_enabled' => $on ? '1' : '0',
        ]);

        return redirect()->route('admin.html-cache.edit')->with(
            'status',
            $on ? '已打开磁盘静态页，可以开始生成' : '已关闭磁盘静态页'
        );
    }

    /** 保存开关与保存时长 */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'html_cache_ttl' => 'nullable|integer|min:0|max:2592000',
        ]);

        $this->settings->save([
            'html_cache_enabled' => $request->boolean('html_cache_enabled') ? '1' : '0',
            'html_cache_ttl' => (string) max(0, (int) $request->input('html_cache_ttl', 3600)),
        ]);

        return redirect()->route('admin.html-cache.edit')->with(
            'status',
            $request->boolean('html_cache_enabled') ? '已开启全页缓存' : '已关闭全页缓存'
        );
    }

    /** 清空 */
    public function clear(): RedirectResponse
    {
        $this->htmlCache->forgetAll('admin:clear');

        return redirect()->route('admin.html-cache.edit')->with('status', '已清空。访客下一次打开会重新生成页面。');
    }

    /** 预热 */
    public function warm(Request $request): RedirectResponse
    {
        if (! $this->htmlCache->enabled()) {
            return redirect()->route('admin.html-cache.edit')->with('error', '请先打开全页缓存并保存');
        }

        $result = $this->htmlCache->warm((int) $request->input('entries', 30));
        $msg = '已预热 '.$result['ok'].' 页';
        if ($result['fail'] > 0) {
            $msg .= '，'.$result['fail'].' 页没生成好';
        }

        return redirect()->route('admin.html-cache.edit')->with('status', $msg);
    }

    /** 开始生成磁盘 HTML（JSON 走进度；普通提交一次写完） */
    public function buildDisk(Request $request): JsonResponse|RedirectResponse
    {
        if (! $this->diskHtml->enabled()) {
            $msg = '请先打开磁盘静态页并保存';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg, 'status' => 'error'], 422);
            }

            return redirect()->route('admin.html-cache.edit')->with('error', $msg);
        }

        $limit = (int) $request->input('entries', 0);
        if ($request->expectsJson()) {
            return response()->json($this->diskHtml->startJob($limit));
        }

        $result = $this->diskHtml->build($limit > 0 ? $limit : 200);
        $msg = '已写成 '.$result['ok'].' 个静态文件';
        if ($result['fail'] > 0) {
            $msg .= '，'.$result['fail'].' 页没写上';
        }

        return redirect()->route('admin.html-cache.edit')->with('status', $msg);
    }

    /** 再写一批静态文件 */
    public function stepDisk(Request $request): JsonResponse
    {
        if (! $this->diskHtml->enabled()) {
            return response()->json(['message' => '请先打开磁盘静态页并保存', 'status' => 'error'], 422);
        }

        return response()->json($this->diskHtml->stepJob((int) $request->input('chunk', 6)));
    }

    /** 当前生成进度 */
    public function diskStatus(): JsonResponse
    {
        return response()->json($this->diskHtml->publicJob());
    }

    /** 停下正在生成的任务 */
    public function cancelDisk(): JsonResponse
    {
        return response()->json($this->diskHtml->cancelJob());
    }

    /** 清空磁盘 HTML */
    public function clearDisk(): RedirectResponse
    {
        $n = $this->diskHtml->clear();

        return redirect()->route('admin.html-cache.edit')->with(
            'status',
            $n > 0 ? '已删掉 '.$n.' 个静态文件' : '没有可删的静态文件'
        );
    }

    protected function ttlPlain(int $seconds): string
    {
        if ($seconds <= 0) {
            return '改内容后马上换新';
        }
        if ($seconds % 86400 === 0) {
            return ($seconds / 86400).' 天';
        }
        if ($seconds % 3600 === 0) {
            return ($seconds / 3600).' 小时';
        }
        if ($seconds % 60 === 0) {
            return ($seconds / 60).' 分钟';
        }

        return $seconds.' 秒';
    }
}
