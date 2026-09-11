<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** 后台模块权限：admin.perm:entries 或 admin.perm:entries,media */
class AdminPermission
{
    public function handle(Request $request, Closure $next, string ...$keys): Response
    {
        $user = Auth::guard('admin')->user();
        if (! $user) {
            return redirect()->route('admin.login');
        }

        if ($keys === []) {
            return $next($request);
        }

        foreach ($keys as $key) {
            if ($user->canPerm($key)) {
                return $next($request);
            }
        }

        abort(403, '无权访问该功能（需要权限：'.implode('/', $keys).'）');
    }
}
