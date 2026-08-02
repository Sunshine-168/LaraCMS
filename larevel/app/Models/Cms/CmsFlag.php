<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CmsFlag extends Model
{
    protected $table = 'cms_flags';

    protected $fillable = ['name', 'title', 'sort', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return array<string, string> name => title */
    public static function options(): array
    {
        return Cache::remember('cms_flags_options', 60, function () {
            return static::query()->where('is_active', true)->orderBy('sort')->pluck('title', 'name')->all();
        });
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('cms_flags_options'));
        static::deleted(fn () => Cache::forget('cms_flags_options'));
    }
}
