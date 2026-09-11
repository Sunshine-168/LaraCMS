<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToSite;
use App\Support\CurrentSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CmsFlag extends Model
{
    use BelongsToSite;

    protected $table = 'cms_flags';

    protected $fillable = ['site_id', 'name', 'title', 'sort', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function cacheKey(): string
    {
        return 'cms_flags_options.'.(CurrentSite::idOrDefault() ?? 0);
    }

    /** @return array<string, string> name => title */
    public static function options(): array
    {
        return Cache::remember(self::cacheKey(), 60, function () {
            return static::query()->where('is_active', true)->orderBy('sort')->pluck('title', 'name')->all();
        });
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::cacheKey()));
        static::deleted(fn () => Cache::forget(self::cacheKey()));
    }
}
