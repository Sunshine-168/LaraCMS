<?php

namespace App\Http\Middleware;

use App\Services\Admin\AdminAuthService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPermission
{
    public function __construct(private readonly AdminAuthService $auth)
    {
    }

    public function handle(Request $request, Closure $next, string $permission = ''): Response
    {
        if ($permission === '' || $this->auth->hasPermission($permission)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return new JsonResponse([
                'code' => -1001,
                'msg' => '没有权限',
                'data' => [],
            ], 403);
        }

        abort(403, '没有权限');
    }
}
