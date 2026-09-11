<?php

namespace App\Http\Middleware;

use App\Services\Cms\RedirectService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/** 入站路径重定向：在站点解析后、业务路由前生效 */
class HandleCmsRedirect
{
    public function __construct(private readonly RedirectService $redirects)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $path = $request->path();
        $first = $path === '' ? '' : explode('/', $path)[0];
        if (in_array($first, ['admin', 'api', 'install', 'livewire', 'storage', 'up'], true)) {
            return $next($request);
        }

        try {
            if (! Schema::hasTable('cms_redirects')) {
                return $next($request);
            }
        } catch (\Throwable) {
            return $next($request);
        }

        $match = $this->redirects->match('/'.$path);
        if (! $match) {
            return $next($request);
        }

        $target = $this->redirects->resolveTargetUrl($match['target']);
        // 防自环：解析后的 URL 路径仍等于当前路径
        $targetPath = parse_url($target, PHP_URL_PATH) ?: '/';
        if (RedirectService::normalizePath((string) $targetPath) === RedirectService::normalizePath('/'.$path)
            && (parse_url($target, PHP_URL_HOST) === null || parse_url($target, PHP_URL_HOST) === $request->getHost())
        ) {
            return $next($request);
        }

        try {
            $this->redirects->recordHit((int) $match['id']);
        } catch (\Throwable) {
        }

        return redirect()->away($target, (int) $match['code']);
    }
}
