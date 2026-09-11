<?php

namespace App\Http\Middleware;

use App\Models\Cms\Site;
use App\Support\AdminSiteAccess;
use App\Support\Cdn;
use App\Support\CurrentSite;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/** 解析当前站点：前台按 Host；后台按 session；API 按 Host */
class ResolveSite
{
    public function handle(Request $request, Closure $next): Response
    {
        CurrentSite::clear();

        if (
            $request->is('install')
            || $request->is('install/*')
            || $request->is('up')
        ) {
            return $next($request);
        }

        try {
            if (! Schema::hasTable('cms_sites')) {
                return $next($request);
            }
        } catch (\Throwable) {
            return $next($request);
        }

        if ($request->is('admin') || $request->is('admin/*')) {
            $this->resolveAdmin($request);
        } else {
            $this->resolveByHost($request);
        }

        Cdn::applyRuntime();

        return $next($request);
    }

    protected function resolveAdmin(Request $request): void
    {
        $admin = auth('admin')->user();
        if (! $admin) {
            CurrentSite::set(Site::default());

            return;
        }

        $preferred = (int) $request->session()->get('admin_site_id', 0) ?: null;
        $site = AdminSiteAccess::resolveForAdmin($admin, $preferred);
        if (! $site) {
            abort(403, '未分配可管理站点');
        }
        if ($preferred && (int) $site->id !== $preferred && ! AdminSiteAccess::canAccess($admin, $preferred)) {
            $request->session()->flash('error', '无权切换到该站点，已回到可管理站点');
        }
        $request->session()->put('admin_site_id', $site->id);
        CurrentSite::set($site);
    }

    protected function resolveByHost(Request $request): void
    {
        $host = Site::normalizeDomain((string) $request->getHost());
        $site = Site::findByDomain($host);
        if ($site) {
            CurrentSite::set($site);

            return;
        }

        if (app()->environment('local', 'testing')) {
            CurrentSite::set(Site::default());

            return;
        }

        abort(404, '未知站点域名');
    }
}
