<?php

namespace App\Models\Cms;

use App\Models\Admin\AdminUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

class Site extends Model
{
    protected $table = 'cms_sites';

    protected $fillable = [
        'name', 'domain', 'is_default', 'is_active', 'theme', 'remark',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Site $site) {
            $site->domain = self::normalizeDomain((string) $site->domain);
        });

        static::saved(function (Site $site) {
            if ($site->is_default) {
                static::query()->where('id', '!=', $site->id)->update(['is_default' => false]);
            }
            Cache::forget('cms.site.default_id');
            Cache::forget('cms.site.by_domain.'.$site->domain);
        });

        static::deleted(function (Site $site) {
            Cache::forget('cms.site.default_id');
            Cache::forget('cms.site.by_domain.'.$site->domain);
        });
    }

    public static function normalizeDomain(string $domain): string
    {
        $domain = trim(strtolower($domain));
        $domain = preg_replace('#^https?://#', '', $domain) ?? $domain;
        $domain = explode('/', $domain)[0] ?? $domain;
        $domain = preg_replace('/:\d+$/', '', $domain) ?? $domain;

        return rtrim($domain, '.') ?: 'localhost';
    }

    public static function defaultId(): ?int
    {
        try {
            return Cache::remember('cms.site.default_id', 3600, function () {
                $id = static::query()->where('is_default', true)->where('is_active', true)->value('id')
                    ?: static::query()->where('is_active', true)->orderBy('id')->value('id');

                return $id ? (int) $id : null;
            });
        } catch (\Throwable) {
            return null;
        }
    }

    public static function default(): ?self
    {
        $id = self::defaultId();

        return $id ? static::query()->find($id) : null;
    }

    public static function themeLabel(?string $theme): string
    {
        return match ($theme) {
            'enterprise' => '企业',
            'mobile' => '移动',
            'download' => '下载站',
            'nav' => '导航',
            'source' => '源码',
            'blog' => '博客',
            'manga' => '漫画',
            'sports' => '体育',
            'default' => '默认',
            '', null => '跟站点设置',
            default => $theme,
        };
    }

    public static function findByDomain(string $domain): ?self
    {
        $domain = self::normalizeDomain($domain);
        $key = 'cms.site.by_domain.'.$domain;
        $cached = Cache::get($key);
        if ($cached !== null && ! is_int($cached)) {
            Cache::forget($key);
            $cached = null;
        }

        $id = $cached ?? Cache::remember($key, 300, function () use ($domain) {
            $found = static::query()->where('domain', $domain)->where('is_active', true)->value('id');

            return $found ? (int) $found : 0;
        });

        return $id ? static::query()->find($id) : null;
    }

    public function adminUsers(): BelongsToMany
    {
        return $this->belongsToMany(AdminUser::class, 'admin_user_site', 'site_id', 'admin_user_id');
    }

    /** 从源站复制基础 settings / locales / flags / member groups（不含内容） */
    public function copyBasicsFrom(Site $source): void
    {
        $now = now();
        foreach (Setting::query()->withoutGlobalScopes()->where('site_id', $source->id)->get() as $row) {
            Setting::query()->withoutGlobalScopes()->updateOrCreate(
                ['site_id' => $this->id, 'key' => $row->key],
                ['value' => $row->value]
            );
        }
        Setting::query()->withoutGlobalScopes()
            ->where('site_id', $this->id)
            ->where('key', 'site_name')
            ->update(['value' => $this->name]);

        foreach (CmsLocale::query()->withoutGlobalScopes()->where('site_id', $source->id)->get() as $row) {
            CmsLocale::query()->withoutGlobalScopes()->updateOrCreate(
                ['site_id' => $this->id, 'code' => $row->code],
                [
                    'label' => $row->label,
                    'native' => $row->native,
                    'is_default' => $row->is_default,
                    'hide_default' => $row->hide_default,
                    'is_active' => $row->is_active,
                    'sort' => $row->sort,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
        foreach (CmsFlag::query()->withoutGlobalScopes()->where('site_id', $source->id)->get() as $row) {
            CmsFlag::query()->withoutGlobalScopes()->updateOrCreate(
                ['site_id' => $this->id, 'name' => $row->name],
                [
                    'title' => $row->title,
                    'sort' => $row->sort,
                    'is_active' => $row->is_active,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
        foreach (MemberGroup::query()->withoutGlobalScopes()->where('site_id', $source->id)->get() as $row) {
            MemberGroup::query()->withoutGlobalScopes()->updateOrCreate(
                ['site_id' => $this->id, 'slug' => $row->slug],
                [
                    'name' => $row->name,
                    'sort' => $row->sort,
                    'is_active' => $row->is_active,
                    'permissions' => $row->permissions,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
