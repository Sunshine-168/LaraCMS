<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToSite;
use App\Support\CurrentSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use BelongsToSite;

    protected $table = 'cms_settings';

    protected $fillable = ['site_id', 'key', 'value'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $siteId = CurrentSite::idOrDefault();
        if (! $siteId) {
            return $default;
        }

        return Cache::remember("cms.setting.{$siteId}.{$key}", 3600, function () use ($key, $default, $siteId) {
            $row = static::query()->withoutGlobalScopes()
                ->where('site_id', $siteId)
                ->where('key', $key)
                ->first();

            return $row?->value ?? $default;
        });
    }

    public static function setValue(string $key, mixed $value): void
    {
        $siteId = CurrentSite::idOrDefault();
        if (! $siteId) {
            return;
        }
        static::query()->withoutGlobalScopes()->updateOrCreate(
            ['site_id' => $siteId, 'key' => $key],
            ['value' => $value]
        );
        Cache::forget("cms.setting.{$siteId}.{$key}");
    }

    public static function forgetCacheForSite(?int $siteId = null): void
    {
        $siteId ??= CurrentSite::idOrDefault();
        if (! $siteId) {
            return;
        }
        foreach (static::query()->withoutGlobalScopes()->where('site_id', $siteId)->pluck('key') as $key) {
            Cache::forget("cms.setting.{$siteId}.{$key}");
        }
    }
}
