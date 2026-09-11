<?php

namespace App\Http\Middleware;

use App\Models\Cms\Member;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** 从 Bearer / X-Member-Token 解析会员；mode=required 时未登录返回 401 */
class ResolveMemberApi
{
    public function handle(Request $request, Closure $next, ?string $mode = null): Response
    {
        $plain = $this->extractToken($request);
        if ($plain !== '') {
            $member = Member::findByApiToken($plain);
            if ($member) {
                Auth::guard('member')->setUser($member);
            } elseif ($mode === 'required') {
                return response()->json(['code' => 401, 'msg' => '无效的会员令牌', 'data' => null], 401);
            }
        }

        if ($mode === 'required' && ! Auth::guard('member')->check()) {
            return response()->json(['code' => 401, 'msg' => '请先登录会员', 'data' => null], 401);
        }

        return $next($request);
    }

    protected function extractToken(Request $request): string
    {
        $header = (string) $request->header('Authorization', '');
        if (preg_match('/^Bearer\s+(\S+)/i', $header, $m)) {
            return $m[1];
        }

        return trim((string) $request->header('X-Member-Token', ''));
    }
}
