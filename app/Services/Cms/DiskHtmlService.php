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
     * @return array{status:string,done:int,total:int,ok:int,fail:int,percent:int,current:string,message:string,file_count:int}
     */
    public function publicJob(): array
    {
        return $this->toPublic($this->rawJob());
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
        foreach (array_reverse(File::directories($root)) as $dir) {
            if (count(File::allFiles($dir)) === 0 && count(File::directories($dir)) === 0) {
                @rmdir($dir);
            }
        }

        return $count;
    }

    public function fileCount(): int
    {
        $root = $this->rootPath();
        if (! is_dir($root)) {
            return 0;
        }
        $n = 0;
        foreach (File::allFiles($root) as $file) {
            if ($file->getExtension() === 'html') {
                $n++;
            }
        }

        return $n;
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
            $file = $this->writeUrl($urlPath);
            if ($file) {
                $files[] = $file;
                $ok++;
            } else {
                $fail++;
            }
        }

        return compact('ok', 'fail', 'files');
    }

    /**
     * @return array{status:string,done:int,total:int,ok:int,fail:int,percent:int,current:string,message:string,file_count:int}
     */
    public function startJob(int $entryLimit = 0): array
    {
        $urls = $this->collectUrls($entryLimit);
        $job = $this->emptyJob();
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
     * @return array{status:string,done:int,total:int,ok:int,fail:int,percent:int,current:string,message:string,file_count:int}
     */
    public function stepJob(int $chunk = 6): array
    {
        $job = $this->rawJob();
        if (($job['status'] ?? '') !== 'running') {
            return $this->toPublic($job);
        }

        File::ensureDirectoryExists($this->rootPath());
        $chunk = max(1, min(20, $chunk));
        $urls = $job['urls'] ?? [];
        $end = min((int) $job['index'] + $chunk, (int) $job['total']);
        for ($i = (int) $job['index']; $i < $end; $i++) {
            $url = (string) ($urls[$i] ?? '');
            $job['current'] = $url;
            $job['message'] = $url === '' ? '正在写…' : '正在写 '.$url;
            if ($url !== '' && $this->writeUrl($url)) {
                $job['ok']++;
            } else {
                $job['fail']++;
            }
            $job['index'] = $i + 1;
        }

        if ((int) $job['index'] >= (int) $job['total']) {
            $job['status'] = 'done';
            $job['current'] = '';
            $job['finished_at'] = now()->toIso8601String();
            $job['message'] = '写好了 '.$job['ok'].' 个文件';
            if ((int) $job['fail'] > 0) {
                $job['message'] .= '，'.$job['fail'].' 页没写上';
            }
            unset($job['urls']);
        }

        $this->putJob($job);

        return $this->toPublic($job);
    }

    /**
     * @return array{status:string,done:int,total:int,ok:int,fail:int,percent:int,current:string,message:string,file_count:int}
     */
    public function cancelJob(): array
    {
        $job = $this->rawJob();
        if (($job['status'] ?? '') === 'running') {
            $job['status'] = 'cancelled';
            $job['finished_at'] = now()->toIso8601String();
            $job['message'] = '已停止。已经写好 '.$job['ok'].' 个文件';
            unset($job['urls']);
            $this->putJob($job);
        }

        return $this->toPublic($job);
    }

    /** @return list<string> */
    public function collectUrls(int $entryLimit = 0): array
    {
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

        $entries = Entry::query()->published()->with(['category', 'modelDef'])
            ->orderByDesc('published_at');
        $entries->limit($entryLimit > 0 ? $entryLimit : 5000);
        $entries->each(function (Entry $entry) use (&$urls) {
            if ($entry->modelDef?->kind === 'single' || $entry->modelDef?->slug === 'link') {
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
    }

    protected function writeUrl(string $urlPath): ?string
    {
        $urlPath = $this->normalizeUrl($urlPath);
        if ($urlPath === null) {
            return null;
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
                return null;
            }
            $file = $this->pathForUrl($this->rootPath(), $urlPath);
            File::ensureDirectoryExists(dirname($file));
            File::put($file, $html);

            return $file;
        } catch (\Throwable) {
            return null;
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

    /** @return array<string, mixed> */
    protected function emptyJob(): array
    {
        return [
            'status' => 'idle',
            'urls' => [],
            'index' => 0,
            'total' => 0,
            'ok' => 0,
            'fail' => 0,
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
     * @return array{status:string,done:int,total:int,ok:int,fail:int,percent:int,current:string,message:string,file_count:int}
     */
    protected function toPublic(array $job): array
    {
        $total = (int) ($job['total'] ?? 0);
        $done = (int) ($job['index'] ?? 0);

        return [
            'status' => (string) ($job['status'] ?? 'idle'),
            'done' => $done,
            'total' => $total,
            'ok' => (int) ($job['ok'] ?? 0),
            'fail' => (int) ($job['fail'] ?? 0),
            'percent' => $total > 0 ? (int) floor($done * 100 / $total) : 0,
            'current' => (string) ($job['current'] ?? ''),
            'message' => (string) ($job['message'] ?? ''),
            'file_count' => $this->fileCount(),
        ];
    }
}
