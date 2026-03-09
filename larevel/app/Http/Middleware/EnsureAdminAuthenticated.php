<?php

namespace App\Http\Middleware;

use App\Services\Admin\AdminAuthService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuthenticated
{
    public function __construct(private readonly AdminAuthService $auth)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->auth->check()) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return new JsonResponse([
                'code' => -1000,
                'msg' => '请先登录后再操作',
                'data' => [],
            ], 401);
        }

        return redirect()->route('admin.login', [
            'url' => trim($request->path(), '/'),
        ]);
    }
}
