<?php

namespace App\Services\Cms;

use App\Models\Cms\SiteConfig;
use Illuminate\Support\Facades\Cache;

class SiteService
{
    public function clearCache(?string $locale = null): void
    {
        $locales = array_filter(array_unique([
            $locale,
            $this->locale(),
            config('cms.default_locale', 'zh-cn'),
        ]));

        foreach ($locales as $item) {
            Cache::forget("cms.site.config.{$item}");
        }
    }

    public function all(?string $locale = null): array
    {
        $locale = $locale ?: $this->locale();

        return Cache::remember(
            "cms.site.config.{$locale}",
            config('cms.config_cache_ttl', 3600),
            function () use ($locale): array {
                $rows = SiteConfig::query()
                    ->whereIn('lang', [$locale, '-1', -1])
                    ->orderByRaw("case when lang = ? then 0 else 1 end", [$locale])
                    ->orderBy('weigh')
                    ->get(['name', 'value', 'type', 'lang']);

                $config = [];

                foreach ($rows as $row) {
                    if (array_key_exists($row->name, $config)) {
                        continue;
                    }

                    $config[$row->name] = $this->castValue($row->value, $row->type);
                }

                return $config;
            }
        );
    }

    public function get(string $key, mixed $default = null, ?string $locale = null): mixed
    {
        return $this->all($locale)[$key] ?? $default;
    }

    public function locale(): string
    {
        return app()->getLocale() ?: config('cms.default_locale', 'zh-cn');
    }

    private function castValue(?string $value, ?string $type): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (in_array($type, ['array', 'json'], true)) {
            $decoded = json_decode($value, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        }

        return $value;
    }
}
