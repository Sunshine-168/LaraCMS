<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** 未安装时引导到 /install（存在 install.lock 则放行） */
class CheckInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (is_file(storage_path('app/install.lock'))) {
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
