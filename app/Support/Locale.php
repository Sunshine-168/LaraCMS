<?php

namespace App\Support;

use App\Models\Cms\CmsLocale;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Locale
{
    protected static ?string $current = null;

    protected static function cacheKey(string $name): string
    {
        $siteId = CurrentSite::idOrDefault() ?? 0;

        return "cms_locale.{$siteId}.{$name}";
    }

    /** @return array<string, array{label:string,native?:string}> */
    public static function all(): array
    {
        try {
            if (Schema::hasTable('cms_locales')) {
                return Cache::remember(self::cacheKey('map'), 60, function () {
                    $rows = CmsLocale::query()->where('is_active', true)->orderBy('sort')->orderBy('id')->get();
                    if ($rows->isEmpty()) {
                        return (array) config('cms.locales', []);
                    }
                    $out = [];
                    foreach ($rows as $row) {
                        $out[$row->code] = [
                            'label' => $row->label,
                            'native' => $row->native ?: $row->label,
                        ];
                    }

                    return $out;
                });
            }
        } catch (\Throwable) {
        }

        return (array) config('cms.locales', []);
    }

    public static function default(): string
    {
        try {
            if (Schema::hasTable('cms_locales')) {
                $code = Cache::remember(self::cacheKey('default'), 60, function () {
                    return CmsLocale::query()->where('is_active', true)->where('is_default', true)->value('code')
                        ?: CmsLocale::query()->where('is_active', true)->orderBy('sort')->value('code');
                });
                if ($code) {
                    return (string) $code;
                }
            }
        } catch (\Throwable) {
        }

        return (string) config('cms.default_locale', 'zh_cn');
    }

    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function set(string $locale): void
    {
        $locale = self::normalize($locale);
        if (! in_array($locale, self::codes(), true)) {
            $locale = self::default();
        }
        self::$current = $locale;
        app()->setLocale($locale === 'zh_cn' ? 'zh_CN' : $locale);
    }

    /** 每个 HTTP 请求开头清掉，避免上一请求 / 队列任务把内容语言留在进程里 */
    public static function forgetCurrent(): void
    {
        self::$current = null;
    }

    public static function current(): string
    {
        return self::$current ?: self::default();
    }

    public static function normalize(string $locale): string
    {
        return strtolower(str_replace('-', '_', $locale));
    }

    public static function toPublic(string $locale): string
    {
        $locale = self::normalize($locale);
        $parts = explode('_', $locale);
        if (count($parts) === 1) {
            return $parts[0];
        }

        return $parts[0].'-'.strtoupper($parts[1]);
    }

    public static function htmlLang(): string
    {
        $code = self::current();

        return $code === 'zh_cn' ? 'zh-CN' : self::toPublic($code);
    }

    public static function isEnglish(): bool
    {
        return str_starts_with(self::current(), 'en');
    }

    public static function hideDefault(): bool
    {
        try {
            if (Schema::hasTable('cms_locales')) {
                $hide = Cache::remember(self::cacheKey('hide_default'), 60, function () {
                    $row = CmsLocale::query()->where('is_default', true)->where('is_active', true)->first();

                    return $row?->hide_default;
                });
                if ($hide !== null) {
                    return (bool) $hide;
                }
            }
        } catch (\Throwable) {
        }

        return (bool) config('cms.hide_default_locale', true);
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::cacheKey('map'));
        Cache::forget(self::cacheKey('default'));
        Cache::forget(self::cacheKey('hide_default'));
        Cache::forget(self::cacheKey('configured'));
    }

    /** 含停用：后台表单/主题文案用，前台切换仍走 all() */
    public static function configured(): array
    {
        try {
            if (Schema::hasTable('cms_locales')) {
                return Cache::remember(self::cacheKey('configured'), 60, function () {
                    $rows = CmsLocale::query()->orderBy('sort')->orderBy('id')->get();
                    if ($rows->isEmpty()) {
                        return self::all();
                    }
                    $out = [];
                    foreach ($rows as $row) {
                        $out[$row->code] = [
                            'label' => $row->label,
                            'native' => $row->native ?: $row->label,
                            'active' => (bool) $row->is_active,
                        ];
                    }

                    return $out;
                });
            }
        } catch (\Throwable) {
        }

        return self::all();
    }

    /** @return list<string> */
    public static function configuredCodes(): array
    {
        return array_keys(self::configured());
    }

    /** @return list<string> */
    public static function codesDefaultFirst(): array
    {
        $codes = self::codes();
        $default = self::default();
        if ($codes === []) {
            return $default !== '' ? [$default] : [];
        }

        return array_values(array_unique(array_merge([$default], $codes)));
    }
}
