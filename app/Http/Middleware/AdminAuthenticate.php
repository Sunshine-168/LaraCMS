<?php

namespace App\Http\Middleware;

use App\Models\Admin\AdminUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** 后台登录鉴权 */
class AdminAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $user = Auth::guard('admin')->user();
        if ($user instanceof AdminUser) {
            $user->loadMissing('adminRole');
        }

        return $next($request);
    }
}
