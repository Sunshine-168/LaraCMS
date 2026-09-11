<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Redirect extends Model
{
    use BelongsToSite;

    protected $table = 'cms_redirects';

    protected $fillable = [
        'site_id', 'source_path', 'target_url', 'status_code',
        'is_active', 'note', 'hits', 'last_hit_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'status_code' => 'integer',
            'hits' => 'integer',
            'last_hit_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn (self $r) => self::forgetCache((int) $r->site_id));
        static::deleted(fn (self $r) => self::forgetCache((int) $r->site_id));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function cacheKey(int $siteId): string
    {
        return 'cms.redirects.map.'.$siteId;
    }

    public static function forgetCache(int $siteId): void
    {
        Cache::forget(self::cacheKey($siteId));
    }
}
