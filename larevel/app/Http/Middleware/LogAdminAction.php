<?php

namespace App\Http\Middleware;

use App\Models\Cms\AdminLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** 记录后台写操作 */
class LogAdminAction
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }
        if (str_contains($request->path(), 'admin/login') || str_contains($request->path(), 'admin/logout')) {
            return $response;
        }

        try {
            $admin = auth('admin')->user();
            AdminLog::query()->create([
                'admin_id' => $admin?->id,
                'admin_name' => $admin?->name,
                'method' => $request->method(),
                'path' => mb_substr('/'.$request->path(), 0, 500),
                'ip' => $request->ip(),
                'status' => $response->getStatusCode(),
            ]);
        } catch (\Throwable) {
        }

        return $response;
    }
}
