<?php

namespace App\Services\Cms;

use App\Models\Cms\Category;
use App\Models\Cms\Entry;
use App\Models\Cms\Setting;
use App\Support\CurrentSite;
use App\Support\Locale;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HtmlCacheService
{
    public const EPOCH_KEY = 'cms.html_cache.epoch';

    public function enabled(): bool
    {
        $setting = Setting::getValue('html_cache_enabled');
        if ($setting !== null && $setting !== '') {
            return (bool) (int) $setting;
        }

        return (bool) config('cms.html_cache.enabled', false);
    }

    public function ttl(): int
    {
        $setting = Setting::getValue('html_cache_ttl');
        if ($setting !== null && $setting !== '') {
            return max(0, (int) $setting);
        }

        return max(0, (int) config('cms.html_cache.ttl', 3600));
    }

    public function store(): Repository
    {
        $name = config('cms.html_cache.store');

        return $name ? Cache::store($name) : Cache::store();
    }

    /** sitemap / robots 随设置立刻变，不要进全页缓存或磁盘静态页 */
    public static function isVolatileFrontPath(string $path): bool
    {
        $path = trim($path, '/');

        return (bool) preg_match('#(?:^|/)(sitemap\.xml|robots\.txt|sitemap)$#i', $path);
    }

    /** 是否允许缓存该请求 */
    public function isCacheable(Request $request): bool
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
        if (auth('admin')->check() || auth('member')->check()) {
            return false;
        }

        $path = trim($request->path(), '/');
        $first = $path === '' ? '' : explode('/', $path)[0];
        if (self::isVolatileFrontPath($path)) {
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
            if (in_array($key, ['page', 'p'], true)) {
                continue;
            }
            if (str_starts_with($key, 'utm_')) {
                continue;
            }

            return false;
        }

        return true;
    }

    public function key(Request $request): string
    {
        $locale = Locale::current();
        $theme = (string) (Setting::getValue('theme') ?: config('cms.theme', 'enterprise'));
        $path = '/'.ltrim($request->path(), '/');
        if ($path === '//') {
            $path = '/';
        }
        $query = $this->normalizeQuery($request);
        $epoch = (int) $this->store()->get(self::EPOCH_KEY, 1);

        $siteId = CurrentSite::idOrDefault() ?? 0;

        return 'cms_html:'.$siteId.':'.$epoch.':'.$locale.':'.$theme.':'.sha1($path.'?'.$query);
    }

    public function get(Request $request): ?string
    {
        if (! $this->isCacheable($request)) {
            return null;
        }

        $html = $this->repositoryForTags($this->tagsForRequest($request))->get($this->key($request));

        return is_string($html) && $html !== '' ? $html : null;
    }

    public function put(Request $request, string $html, array $extraTags = []): void
    {
        if (! $this->isCacheable($request) || $html === '') {
            return;
        }

        $ttl = $this->ttl();
        $repo = $this->repositoryForTags(array_values(array_unique(array_merge(
            $this->tagsForRequest($request),
            $extraTags
        ))));

        if ($ttl > 0) {
            $repo->put($this->key($request), $html, $ttl);
        } else {
            $repo->forever($this->key($request), $html);
        }
    }

    /** 全站失效（epoch；Redis/Memcached 另刷标签） */
    public function forgetAll(?string $reason = null): void
    {
        $this->bumpEpoch();

        if ($this->canTag()) {
            try {
                $this->store()->tags(['html'])->flush();
            } catch (\Throwable) {
            }
        }

        if ($reason) {
            $this->rememberBust($reason);
        }
    }

    public function forgetByEntry(Entry $entry): void
    {
        $this->bumpEpoch();

        if ($this->canTag()) {
            try {
                $tags = ['html', 'html:home', 'html:entry:'.$entry->id];
                if ($entry->category_id) {
                    $tags[] = 'html:cat:'.$entry->category_id;
                }
                $this->store()->tags($tags)->flush();
            } catch (\Throwable) {
            }
        }

        $this->rememberBust('entry:'.$entry->id);
    }

    public function forgetByCategory(Category $category): void
    {
        $this->bumpEpoch();

        if ($this->canTag()) {
            try {
                $this->store()->tags(['html', 'html:home', 'html:cat:'.$category->id])->flush();
            } catch (\Throwable) {
            }
        }

        $this->rememberBust('category:'.$category->id);
    }

    public function canTag(): bool
    {
        try {
            $name = class_basename($this->store()->getStore());

            return in_array($name, ['RedisStore', 'MemcachedStore'], true);
        } catch (\Throwable) {
            return false;
        }
    }

    public function lastBust(): ?array
    {
        $v = $this->store()->get('cms.html_cache.last_bust');

        return is_array($v) ? $v : null;
    }

    /** @return array<int, string> */
    public static function ttlOptions(): array
    {
        return [
            0 => '改内容后马上换新',
            900 => '15 分钟',
            3600 => '1 小时',
            21600 => '6 小时',
            86400 => '1 天',
        ];
    }

    public function bustLabel(?array $bust): string
    {
        $reason = (string) ($bust['reason'] ?? '');
        if ($reason === '') {
            return '还没有刷新过';
        }

        return match (true) {
            $reason === 'admin:clear' => '你手动清空了页面缓存',
            $reason === 'admin:global-clear' => '清空了缓存',
            $reason === 'settings' => '改了站点设置',
            $reason === 'backup-restore' => '还原了备份',
            $reason === 'artisan:html-clear' => '用命令行清空了缓存',
            str_starts_with($reason, 'entry:batch') => '批量改了内容',
            str_starts_with($reason, 'entry:') => '改了内容',
            str_starts_with($reason, 'category:') => '改了栏目',
            str_starts_with($reason, 'collector:') => '采集写入了内容',
            str_starts_with($reason, 'recommend:') => '改了推荐位',
            default => '内容有更新',
        };
    }

    /**
     * 预热：首页 + 栏目 + 最近发布内容（内核内请求，无需外部 HTTP）
     *
     * @return array{ok:int,fail:int,urls:list<string>}
     */
    public function warm(int $entryLimit = 30): array
    {
        if (! $this->enabled()) {
            return ['ok' => 0, 'fail' => 0, 'urls' => []];
        }

        $previous = Locale::current();
        $site = CurrentSite::model();
        try {
            $urls = ['/'];
            foreach (Locale::codes() as $code) {
                if ($code === Locale::default() && Locale::hideDefault()) {
                    continue;
                }
                $urls[] = '/'.Locale::toPublic($code);
            }

            Category::query()->published()->orderBy('sort_order')->limit(50)->each(function (Category $cat) use (&$urls) {
                foreach (Locale::codes() as $code) {
                    Locale::set($code);
                    $urls[] = $cat->frontUrl();
                }
            });

            Entry::query()->published()->visibleToGuests()->with(['category', 'modelDef'])->orderByDesc('id')->limit($entryLimit)
                ->each(function (Entry $entry) use (&$urls) {
                    if ($entry->modelDef?->kind === 'single' || $entry->modelDef?->slug === 'link' || $entry->isMemberGated()) {
                        return;
                    }
                    foreach (Locale::codes() as $code) {
                        Locale::set($code);
                        $url = $entry->url;
                        if ($url && ! str_starts_with($url, 'http') && $url !== '#') {
                            $urls[] = $url;
                        }
                    }
                });

            Locale::set($previous);
            $urls = array_values(array_unique($urls));
            $ok = 0;
            $fail = 0;
            /** @var HttpKernel $kernel */
            $kernel = app(HttpKernel::class);

            foreach ($urls as $url) {
                try {
                    $request = Request::create($url, 'GET', [], [], [], [
                        'HTTP_USER_AGENT' => 'CMS-HtmlWarm/1.0',
                        'HTTP_ACCEPT' => 'text/html',
                    ]);
                    $response = $kernel->handle($request);
                    if ($response->getStatusCode() === 200) {
                        $ok++;
                    } else {
                        $fail++;
                    }
                    $kernel->terminate($request, $response);
                } catch (\Throwable) {
                    $fail++;
                }
            }

            return compact('ok', 'fail', 'urls');
        } finally {
            if ($site) {
                CurrentSite::set($site);
            }
            Locale::set($previous);
        }
    }

    /** @return list<string> */
    protected function tagsForRequest(Request $request): array
    {
        $tags = ['html'];
        $path = trim($request->path(), '/');
        if ($path === '' || preg_match('#^[a-z]{2}(-[A-Z]{2})?$#', $path)) {
            $tags[] = 'html:home';
        }
        $theme = (string) (Setting::getValue('theme') ?: config('cms.theme', 'enterprise'));
        $tags[] = 'html:theme:'.$theme;

        return $tags;
    }

    /** @param  list<string>  $tags */
    protected function repositoryForTags(array $tags): Repository
    {
        $store = $this->store();
        if ($this->canTag() && $tags !== []) {
            try {
                return $store->tags($tags);
            } catch (\Throwable) {
            }
        }

        return $store;
    }

    protected function bumpEpoch(): void
    {
        $store = $this->store();
        $epoch = (int) $store->get(self::EPOCH_KEY, 1);
        $store->forever(self::EPOCH_KEY, $epoch + 1);
    }

    protected function rememberBust(string $reason): void
    {
        $this->store()->put('cms.html_cache.last_bust', [
            'reason' => $reason,
            'at' => now()->toDateTimeString(),
        ], 86400 * 7);
    }

    protected function normalizeQuery(Request $request): string
    {
        $q = [];
        foreach ($request->query() as $key => $value) {
            $key = strtolower((string) $key);
            if (str_starts_with($key, 'utm_')) {
                continue;
            }
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $q[$key] = (string) $value;
        }
        ksort($q);

        return http_build_query($q);
    }
}
