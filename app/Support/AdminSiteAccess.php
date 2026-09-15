<?php

namespace App\Support;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Site;
use Illuminate\Support\Collection;

class AdminSiteAccess
{
    /** @return list<int>|null null = 全部站点（超管） */
    public static function allowedIds(?AdminUser $admin): ?array
    {
        if (! $admin) {
            return [];
        }
        if ($admin->isSuperAdmin()) {
            return null;
        }
        $admin->loadMissing('sites');

        return $admin->sites->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    public static function canAccess(?AdminUser $admin, int $siteId): bool
    {
        $ids = self::allowedIds($admin);
        if ($ids === null) {
            return true;
        }

        return in_array($siteId, $ids, true);
    }

    /** @return Collection<int, Site> */
    public static function manageableSites(?AdminUser $admin): Collection
    {
        $memoKey = 'admin.manageable_sites.'.($admin?->id ?? 0);
        $request = request();
        if ($request && $request->attributes->has($memoKey)) {
            return $request->attributes->get($memoKey);
        }

        $q = Site::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('id');
        $ids = self::allowedIds($admin);
        if ($ids === null) {
            $sites = $q->get();
        } elseif ($ids === []) {
            $sites = collect();
        } else {
            $sites = $q->whereIn('id', $ids)->get();
        }
        $request?->attributes->set($memoKey, $sites);

        return $sites;
    }

    public static function resolveForAdmin(?AdminUser $admin, ?int $preferredId = null): ?Site
    {
        $sites = self::manageableSites($admin);
        if ($sites->isEmpty()) {
            // 超管且库中无激活站时回落；普通管理员无绑站则无权
            return $admin?->isSuperAdmin() ? Site::default() : null;
        }
        if ($preferredId && $sites->firstWhere('id', $preferredId)) {
            return $sites->firstWhere('id', $preferredId);
        }

        return $sites->firstWhere('is_default', true) ?: $sites->first();
    }
}

