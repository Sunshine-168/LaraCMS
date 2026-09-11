<?php

namespace App\Services\Cms;

use App\Models\Cms\Category;
use App\Models\Cms\Entry;
use App\Support\CurrentSite;
use App\Support\Locale;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

/** 整站磁盘 HTML：写入 public/{path}/，可用 nginx try_files 直出 */
class DiskHtmlService
{
    public function enabled(): bool
    {
        return (bool) config('cms.disk_html.enabled', false);
    }

    public function rootPath(): string
    {
        $rel = trim((string) config('cms.disk_html.path', 'html'), '/\\');
        $siteId = CurrentSite::idOrDefault() ?? 0;

        return public_path($rel.($siteId ? '/site_'.$siteId : ''));
    }

    public function clear(): int
    {
        $root = $this->rootPath();
        if (! is_dir($root)) {
            return 0;
        }
        $count = 0;
        foreach (File::allFiles($root) as $file) {
            if ($file->getExtension() === 'html') {
                File::delete($file->getPathname());
                $count++;
            }
        }
        // 清理空目录
        foreach (array_reverse(File::directories($root)) as $dir) {
            if (count(File::allFiles($dir)) === 0 && count(File::directories($dir)) === 0) {
                @rmdir($dir);
            }
        }

        return $count;
    }

    /**
     * @return array{ok:int,fail:int,files:list<string>}
     */
    public function build(int $entryLimit = 200): array
    {
        $root = $this->rootPath();
        File::ensureDirectoryExists($root);

        $urls = $this->collectUrls($entryLimit);
        $ok = 0;
        $fail = 0;
        $files = [];
        /** @var HttpKernel $kernel */
        $kernel = app(HttpKernel::class);

        foreach (array_unique($urls) as $urlPath) {
            $urlPath = '/'.ltrim((string) $urlPath, '/');
            if ($urlPath === '//') {
                $urlPath = '/';
            }
            try {
                $request = Request::create($urlPath, 'GET');
                $response = $kernel->handle($request);
                $status = $response->getStatusCode();
                $html = $response->getContent();
                $kernel->terminate($request, $response);
                if ($status !== 200 || ! is_string($html) || $html === '') {
                    $fail++;
                    continue;
                }
                $file = $this->pathForUrl($root, $urlPath);
                File::ensureDirectoryExists(dirname($file));
                File::put($file, $html);
                $files[] = $file;
                $ok++;
            } catch (\Throwable) {
                $fail++;
            }
        }

        return compact('ok', 'fail', 'files');
    }

    /** @return list<string> */
    protected function collectUrls(int $entryLimit): array
    {
        $urls = ['/'];
        foreach (Locale::codes() as $code) {
            if ($code === Locale::default() && Locale::hideDefault()) {
                continue;
            }
            $urls[] = '/'.Locale::toPublic($code);
        }

        Category::query()->active()->orderBy('sort_order')->limit(200)->each(function (Category $cat) use (&$urls) {
            if ($cat->type === 'link') {
                return;
            }
            foreach (Locale::codes() as $code) {
                Locale::set($code);
                $urls[] = $cat->frontUrl();
            }
        });

        Entry::query()->published()->with(['category', 'modelDef'])
            ->orderByDesc('published_at')
            ->limit(max(1, $entryLimit))
            ->each(function (Entry $entry) use (&$urls) {
                if ($entry->modelDef?->kind === 'single' || $entry->modelDef?->slug === 'link') {
                    return;
                }
                foreach (Locale::codes() as $code) {
                    Locale::set($code);
                    $urls[] = $entry->url;
                }
            });

        return array_values(array_unique(array_filter($urls)));
    }

    protected function pathForUrl(string $root, string $urlPath): string
    {
        $path = trim($urlPath, '/');
        if ($path === '') {
            return $root.DIRECTORY_SEPARATOR.'index.html';
        }
        // 去掉开头 locale 后仍按完整路径落盘（含语言前缀）
        $segments = explode('/', $path);
        $dir = $root.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $segments);

        return $dir.DIRECTORY_SEPARATOR.'index.html';
    }
}
