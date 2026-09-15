<?php

namespace App\Services\Cms;

use App\Models\Cms\Category;
use App\Models\Cms\Entry;
use App\Models\Cms\Setting;
use App\Support\CurrentSite;
use App\Support\Locale;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/** 整站磁盘 HTML：写入 public/{path}/site_{id}/…/index.html */
class DiskHtmlService
{
    public const JOB_TTL = 3600;

    public function enabled(): bool
    {
        $setting = Setting::getValue('disk_html_enabled');
        if ($setting !== null && $setting !== '') {
            return (bool) (int) $setting;
        }

        return (bool) config('cms.disk_html.enabled', false);
    }

    /** 已生成的磁盘 HTML，给本机 / PHP 直接吐文件用 */
    public function htmlForRequest(Request $request): ?string
    {
        if (! $this->canServe($request)) {
            return null;
        }
        $path = '/'.ltrim($request->path(), '/');
        if ($path === '//') {
            $path = '/';
        }
        $file = $this->pathForUrl($this->rootPath(), $path);
        if (! is_file($file)) {
            return null;
        }
        $html = File::get($file);

        return is_string($html) && $html !== '' ? $html : null;
    }

    public function canServe(Request $request): bool
    {
        if (! $this->enabled()) {
            return false;
        }
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return false;
        }
        if ($request->ajax() || $request->expectsJson()) {
            return false;
        }
        if (auth('member')->check()) {
            return false;
        }

        $path = trim($request->path(), '/');
        $first = $path === '' ? '' : explode('/', $path)[0];
        if (HtmlCacheService::isVolatileFrontPath($path)) {
            return false;
        }
        foreach ((array) config('cms.html_cache.exclude_prefixes', []) as $prefix) {
            if ($first === $prefix || str_starts_with($path, $prefix.'/')) {
                return false;
            }
        }
        foreach ((array) config('cms.html_cache.exclude_path_prefixes', []) as $prefix) {
            if ($first === $prefix || str_starts_with($path, $prefix.'/')) {
                return false;
            }
        }
        foreach ($request->query() as $key => $value) {
            $key = strtolower((string) $key);
            if (in_array($key, ['page', 'p'], true) || str_starts_with($key, 'utm_')) {
                continue;
            }

            return false;
        }

        return true;
    }

    public function rootPath(): string
    {
        $override = config('cms.disk_html.root');
        if (is_string($override) && $override !== '') {
            return $override;
        }

        $rel = trim((string) config('cms.disk_html.path', 'html'), '/\\');
        $siteId = CurrentSite::idOrDefault() ?? 0;

        return public_path($rel.($siteId ? '/site_'.$siteId : ''));
    }

    public function jobKey(): string
    {
        return 'cms.disk_html.job.'.(CurrentSite::idOrDefault() ?? 0);
    }

    /**
     * @return array{status:string,kind:string,done:int,total:int,ok:int,fail:int,percent:int,current:string,message:string,file_count:int,errors:list<string>}
     */
    public function publicJob(): array
    {
        return $this->toPublic($this->rawJob());
    }

    public function clear(): int
    {
        $files = $this->listHtmlFiles();
        foreach ($files as $file) {
            File::delete($file);
        }
        $this->pruneEmptyDirs($this->rootPath());

        return count($files);
    }

    public function fileCount(): int
    {
        return count($this->listHtmlFiles());
    }

    /** @return list<string> */
    public function listHtmlFiles(): array
    {
        $root = $this->rootPath();
        if (! is_dir($root)) {
            return [];
        }
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $item) {
            if ($item->isFile() && strtolower($item->getExtension()) === 'html') {
                $files[] = $item->getPathname();
            }
        }

        return $files;
    }

    /**
     * @return array{ok:int,fail:int,files:list<string>}
     */
    public function build(int $entryLimit = 200): array
    {
        File::ensureDirectoryExists($this->rootPath());
        $urls = $this->collectUrls($entryLimit);
        $ok = 0;
        $fail = 0;
        $files = [];
        foreach ($urls as $urlPath) {
            $result = $this->capture($urlPath);
            if ($result['file']) {
                $files[] = $result['file'];
                $ok++;
            } else {
                $fail++;
            }
        }

        return compact('ok', 'fail', 'files');
    }

    /**
     * @return array{status:string,kind:string,done:int,total:int,ok:int,fail:int,percent:int,current:string,message:string,file_count:int,errors:list<string>}
     */
    public function startJob(int $entryLimit = 0): array
    {
        $busy = $this->busyPublic('build');
        if ($busy !== null) {
            return $busy;
        }

        $urls = $this->collectUrls($entryLimit);
        $job = $this->emptyJob();
        $job['kind'] = 'build';
        $job['status'] = $urls === [] ? 'done' : 'running';
        $job['urls'] = $urls;
        $job['total'] = count($urls);
        $job['current'] = $urls[0] ?? '';
        $job['started_at'] = now()->toIso8601String();
        if ($urls === []) {
            $job['finished_at'] = now()->toIso8601String();
            $job['message'] = '没有可生成的页面';
        } else {
            $job['message'] = '准备写 '.$job['total'].' 个页面';
        }
        $this->putJob($job);

        return $this->toPublic($job);
    }

    /**
     * @return array{status:string,kind:string,done:int,total:int,ok:int,fail:int,percent:int,current:string,message:string,file_count:int,errors:list<string>}
     */
    public function startClearJob(): array
    {
        $busy = $this->busyPublic('clear');
        if ($busy !== null) {
            return $busy;
        }

        $files = $this->listHtmlFiles();
        $job = $this->emptyJob();
        $job['kind'] = 'clear';
        $job['status'] = $files === [] ? 'done' : 'running';
        $job['files'] = $files;
        $job['total'] = count($files);
        $job['current'] = $files === [] ? '' : $this->relativeHtmlPath($files[0]);
        $job['started_at'] = now()->toIso8601String();
        if ($files === []) {
            $job['finished_at'] = now()->toIso8601String();
            $job['message'] = '没有可删的静态文件';
        } else {
            $job['message'] = '准备删 '.$job['total'].' 个文件';
        }
        $this->putJob($job);

        return $this->toPublic($job);
    }

    /**
     * @return array{status:string,kind:string,done:int,total:int,ok:int,fail:int,percent:int,current:string,message:string,file_count:int,errors:list<string>}
     */
    public function stepJob(int $chunk = 6): array
    {
        $job = $this->rawJob();
        if (($job['status'] ?? '') !== 'running') {
            return $this->toPublic($job);
        }

        if (($job['kind'] ?? 'build') === 'clear') {
            return $this->stepClearJob($job, $chunk);
        }

        File::ensureDirectoryExists($this->rootPath());
        $chunk = max(1, min(20, $chunk));
        $urls = $job['urls'] ?? [];
        $end = min((int) $job['index'] + $chunk, (int) $job['total']);
        for ($i = (int) $job['index']; $i < $end; $i++) {
            $url = (string) ($urls[$i] ?? '');
            $job['current'] = $url;
            $job['message'] = $url === '' ? '正在写…' : '正在写 '.$url;
            $result = $url === '' ? ['file' => null, 'error' => '空地址'] : $this->capture($url);
            if ($result['file']) {
                $job['ok']++;
            } else {
                $job['fail']++;
                $job['errors'][] = $result['error'] ?? $url;
            }
            $job['index'] = $i + 1;
        }

        if ((int) $job['index'] >= (int) $job['total']) {
            $job['status'] = 'done';
            $job['current'] = '';
            $job['finished_at'] = now()->toIso8601String();
            $job['message'] = '写好了 '.$job['ok'].' 个文件';
            if ((int) $job['fail'] > 0) {
                $shown = array_slice($job['errors'] ?? [], 0, 5);
                $job['message'] .= '，'.$job['fail'].' 页没写上';
                if ($shown !== []) {
                    $job['message'] .= '：'.implode('、', $shown);
                }
            }
            unset($job['urls']);
        }

        $this->putJob($job);

        return $this->toPublic($job);
    }

    /**
     * @return array{status:string,kind:string,done:int,total:int,ok:int,fail:int,percent:int,current:string,message:string,file_count:int,errors:list<string>}
     */
    public function cancelJob(): array
    {
        $job = $this->rawJob();
        if (($job['status'] ?? '') === 'running') {
            $kind = (string) ($job['kind'] ?? 'build');
            $job['status'] = 'cancelled';
            $job['finished_at'] = now()->toIso8601String();
            if ($kind === 'clear') {
                $this->pruneEmptyDirs($this->rootPath());
                $job['message'] = '已停止。已经删掉 '.$job['ok'].' 个文件';
                unset($job['files']);
            } else {
                $job['message'] = '已停止。已经写好 '.$job['ok'].' 个文件';
                unset($job['urls']);
            }
            $this->putJob($job);
        }

        return $this->toPublic($job);
    }

    /** @return list<string> */
    public function collectUrls(int $entryLimit = 0): array
    {
        $previous = Locale::current();
        try {
            $urls = ['/'];
            foreach (Locale::codes() as $code) {
                if ($code === Locale::default() && Locale::hideDefault()) {
                    continue;
                }
                $urls[] = '/'.Locale::toPublic($code);
            }

            Category::query()->active()->orderBy('sort_order')->limit(500)->each(function (Category $cat) use (&$urls) {
                if ($cat->type === 'link') {
                    return;
                }
                foreach (Locale::codes() as $code) {
                    Locale::set($code);
                    $this->pushUrl($urls, $cat->frontUrl());
                }
            });

            $entries = Entry::query()->published()->visibleToGuests()->with(['category', 'modelDef'])
                ->orderByDesc('published_at');
            $entries->limit($entryLimit > 0 ? $entryLimit : 5000);
            $entries->each(function (Entry $entry) use (&$urls) {
                if ($entry->modelDef?->kind === 'single' || $entry->modelDef?->slug === 'link' || $entry->isMemberGated()) {
                    return;
                }
                foreach (Locale::codes() as $code) {
                    Locale::set($code);
                    $this->pushUrl($urls, (string) $entry->url);
                }
            });

            $out = [];
            foreach ($urls as $url) {
                $norm = $this->normalizeUrl((string) $url);
                if ($norm !== null) {
                    $out[] = $norm;
                }
            }

            return array_values(array_unique($out));
        } finally {
            Locale::set($previous);
        }
    }

    protected function writeUrl(string $urlPath): ?string
    {
        return $this->capture($urlPath)['file'];
    }

    /** @return array{file:?string,error:?string} */
    protected function capture(string $urlPath): array
    {
        $urlPath = $this->normalizeUrl($urlPath);
        if ($urlPath === null) {
            return ['file' => null, 'error' => '地址无效'];
        }

        $site = CurrentSite::model();
        $locale = Locale::current();
        try {
            /** @var HttpKernel $kernel */
            $kernel = app(HttpKernel::class);
            $request = Request::create($urlPath, 'GET', [], [], [], [
                'HTTP_USER_AGENT' => 'CMS-DiskHtml/1.0',
                'HTTP_ACCEPT' => 'text/html',
            ]);
            $response = $kernel->handle($request);
            $status = $response->getStatusCode();
            $html = $response->getContent();
            $kernel->terminate($request, $response);
            if ($status !== 200 || ! is_string($html) || $html === '') {
                return ['file' => null, 'error' => $urlPath.'（HTTP '.$status.'）'];
            }
            $file = $this->pathForUrl($this->rootPath(), $urlPath);
            File::ensureDirectoryExists(dirname($file));
            File::put($file, $html);

            return ['file' => $file, 'error' => null];
        } catch (\Throwable $e) {
            return ['file' => null, 'error' => $urlPath.'（'.$e->getMessage().'）'];
        } finally {
            if ($site) {
                CurrentSite::set($site);
            }
            Locale::set($locale);
        }
    }

    protected function pathForUrl(string $root, string $urlPath): string
    {
        $path = trim($urlPath, '/');
        if ($path === '') {
            return $root.DIRECTORY_SEPARATOR.'index.html';
        }
        $segments = explode('/', $path);
        $dir = $root.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $segments);

        return $dir.DIRECTORY_SEPARATOR.'index.html';
    }

    /** @param list<string> $urls */
    protected function pushUrl(array &$urls, string $url): void
    {
        if ($url === '' || $url === '#' || str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return;
        }
        $urls[] = $url;
    }

    protected function normalizeUrl(string $urlPath): ?string
    {
        $urlPath = trim($urlPath);
        if ($urlPath === '' || $urlPath === '#' || str_starts_with($urlPath, 'http://') || str_starts_with($urlPath, 'https://')) {
            return null;
        }
        $urlPath = '/'.ltrim($urlPath, '/');
        if ($urlPath === '//') {
            return '/';
        }

        return $urlPath;
    }

    /**
     * @param  array<string, mixed>  $job
     * @return array{status:string,kind:string,done:int,total:int,ok:int,fail:int,percent:int,current:string,message:string,file_count:int,errors:list<string>}
     */
    protected function stepClearJob(array $job, int $chunk): array
    {
        $chunk = max(1, min(80, $chunk));
        $files = $job['files'] ?? [];
        $end = min((int) $job['index'] + $chunk, (int) $job['total']);
        for ($i = (int) $job['index']; $i < $end; $i++) {
            $file = (string) ($files[$i] ?? '');
            $job['current'] = $file === '' ? '' : $this->relativeHtmlPath($file);
            $job['message'] = $file === '' ? '正在删…' : '正在删 '.$job['current'];
            if ($file === '' || ! is_file($file) || File::delete($file)) {
                $job['ok']++;
            } else {
                $job['fail']++;
                $job['errors'][] = $job['current'];
            }
            $job['index'] = $i + 1;
        }

        if ((int) $job['index'] >= (int) $job['total']) {
            $this->pruneEmptyDirs($this->rootPath());
            $job['status'] = 'done';
            $job['current'] = '';
            $job['finished_at'] = now()->toIso8601String();
            $job['message'] = '已删掉 '.$job['ok'].' 个文件';
            if ((int) $job['fail'] > 0) {
                $job['message'] .= '，'.$job['fail'].' 个没删掉';
            }
            unset($job['files']);
        }

        $this->putJob($job);

        return $this->toPublic($job);
    }

    protected function pruneEmptyDirs(string $root): void
    {
        if (! is_dir($root)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            }
        }
    }

    protected function relativeHtmlPath(string $file): string
    {
        $root = rtrim(str_replace('\\', '/', $this->rootPath()), '/');
        $norm = str_replace('\\', '/', $file);
        if (str_starts_with($norm, $root.'/')) {
            return substr($norm, strlen($root) + 1);
        }

        return basename($file);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function busyPublic(string $wantKind): ?array
    {
        $current = $this->rawJob();
        if (($current['status'] ?? '') !== 'running') {
            return null;
        }
        $pub = $this->toPublic($current);
        if ((string) ($current['kind'] ?? 'build') !== $wantKind) {
            $pub['conflict'] = true;
            $pub['message'] = ($current['kind'] ?? 'build') === 'clear'
                ? '正在删除，请先停止'
                : '正在生成，请先停止';
        }

        return $pub;
    }

    /** @return array<string, mixed> */
    protected function emptyJob(): array
    {
        return [
            'status' => 'idle',
            'kind' => 'build',
            'urls' => [],
            'files' => [],
            'index' => 0,
            'total' => 0,
            'ok' => 0,
            'fail' => 0,
            'errors' => [],
            'current' => '',
            'message' => '',
            'started_at' => null,
            'finished_at' => null,
        ];
    }

    /** @return array<string, mixed> */
    protected function rawJob(): array
    {
        $job = Cache::get($this->jobKey());

        return is_array($job) ? array_merge($this->emptyJob(), $job) : $this->emptyJob();
    }

    /** @param array<string, mixed> $job */
    protected function putJob(array $job): void
    {
        Cache::put($this->jobKey(), $job, self::JOB_TTL);
    }

    /**
     * @param  array<string, mixed>  $job
     * @return array{status:string,kind:string,done:int,total:int,ok:int,fail:int,percent:int,current:string,message:string,file_count:int,errors:list<string>}
     */
    protected function toPublic(array $job): array
    {
        $total = (int) ($job['total'] ?? 0);
        $done = (int) ($job['index'] ?? 0);
        $kind = (string) ($job['kind'] ?? 'build');
        $status = (string) ($job['status'] ?? 'idle');
        $fileCount = ($status === 'running' && $kind === 'clear')
            ? max(0, $total - $done)
            : $this->fileCount();

        return [
            'status' => $status,
            'kind' => $kind,
            'done' => $done,
            'total' => $total,
            'ok' => (int) ($job['ok'] ?? 0),
            'fail' => (int) ($job['fail'] ?? 0),
            'percent' => $total > 0 ? (int) floor($done * 100 / $total) : 0,
            'current' => (string) ($job['current'] ?? ''),
            'message' => (string) ($job['message'] ?? ''),
            'file_count' => $fileCount,
            'errors' => array_values(array_slice($job['errors'] ?? [], 0, 10)),
        ];
    }
}
