<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/** 未安装时引导到 /install（存在 install.lock 则放行） */
class CheckInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        $installing = ! is_file(storage_path('app/install.lock'));
        if ($installing || $request->is('install') || $request->is('install/*')) {
            URL::forceRootUrl($request->getSchemeAndHttpHost());
        }
        if ($installing && config('session.driver') !== 'array') {
            config([
                'session.driver' => 'file',
                'session.encrypt' => false,
                'session.secure' => false,
                'session.domain' => null,
                'session.same_site' => 'lax',
                'session.cookie' => 'cms_install_session',
                'session.path' => '/',
            ]);
            $sessionDir = storage_path('framework/sessions');
            if (! is_dir($sessionDir)) {
                @mkdir($sessionDir, 0775, true);
            }
        }

        if (! $installing) {
            return $next($request);
        }

        if (
            $request->is('install')
            || $request->is('install/*')
            || $request->is('up')
            || $request->is('storage/*')
        ) {
            return $next($request);
        }

        return redirect('/install');
    }
}
