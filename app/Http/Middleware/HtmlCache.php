<?php

namespace App\Http\Middleware;

use App\Services\Cms\DiskHtmlService;
use App\Services\Cms\HtmlCacheService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** 全页 HTML 缓存：先读磁盘静态页，再读内存缓存，未命中才渲染 */
class HtmlCache
{
    public function __construct(
        private readonly HtmlCacheService $cache,
        private readonly DiskHtmlService $diskHtml,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($html = $this->diskHtml->htmlForRequest($request)) {
            return response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'X-CMS-Disk-Html' => 'HIT',
            ]);
        }

        if ($html = $this->cache->get($request)) {
            return response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'X-CMS-Html-Cache' => 'HIT',
            ]);
        }

        /** @var Response $response */
        $response = $next($request);

        if (
            $this->cache->isCacheable($request)
            && $response->getStatusCode() === 200
            && str_contains((string) $response->headers->get('Content-Type', 'text/html'), 'text/html')
        ) {
            $content = $response->getContent();
            if (is_string($content) && $content !== '') {
                $this->cache->put($request, $content);
                $response->headers->set('X-CMS-Html-Cache', 'MISS');
            }
        }

        return $response;
    }
}
