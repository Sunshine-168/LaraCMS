<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated 请改用 admin.perm:模块键。此处按角色 slug 兼容旧路由。
 */
class AdminRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::guard('admin')->user();
        if (! $user) {
            return redirect()->route('admin.login');
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $slug = $user->adminRole?->slug;
        if ($roles !== [] && ! in_array($slug, $roles, true)) {
            abort(403, '无权访问该功能（需要角色：'.implode('/', $roles).'）');
        }

        return $next($request);
    }
}
