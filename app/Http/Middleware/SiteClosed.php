<?php

namespace App\Http\Middleware;

use App\Models\Cms\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** 前台关站：后台与已登录超管仍可访问 */
class SiteClosed
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) (int) Setting::getValue('site_closed', '0')) {
            return $next($request);
        }

        if (
            $request->is('admin')
            || $request->is('admin/*')
            || $request->is('install')
            || $request->is('install/*')
            || $request->is('preview/*')
            || $request->is('*/preview/*')
            || $request->is('robots.txt')
            || $request->is('*/robots.txt')
            || $request->is('sitemap.xml')
            || $request->is('*/sitemap.xml')
        ) {
            return $next($request);
        }

        $msg = trim((string) Setting::getValue('site_closed_message', '站点维护中，请稍后再访。'));

        return response()->view('errors.site-closed', [
            'message' => $msg !== '' ? $msg : '站点维护中，请稍后再访。',
            'siteName' => Setting::getValue('site_name', config('app.name')),
        ], 503);
    }
}
