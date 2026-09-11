<?php

namespace App\Http\Middleware;

use App\Models\Cms\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** API 可选站点令牌：未开启则放行；开启后要求 Header X-CMS-Token 或 ?api_token= */
class ApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $enabled = (bool) (int) Setting::getValue('api_enable', '0');
        if (! $enabled) {
            return $next($request);
        }

        $token = (string) Setting::getValue('api_token', '');
        if ($token === '') {
            return response()->json(['code' => 403, 'msg' => 'API token 未配置', 'data' => null], 403);
        }

        $given = (string) ($request->header('X-CMS-Token') ?: $request->query('api_token', ''));
        if (! hash_equals($token, $given)) {
            return response()->json(['code' => 401, 'msg' => '无效的 API Token', 'data' => null], 401);
        }

        return $next($request);
    }

    /** 请求是否携带与站点设置匹配的 API Token（用于写入接口免验证码） */
    public static function providedTokenIsValid(Request $request): bool
    {
        $token = (string) Setting::getValue('api_token', '');
        if ($token === '') {
            return false;
        }
        $given = (string) ($request->header('X-CMS-Token') ?: $request->query('api_token', '') ?: $request->input('api_token', ''));

        return $given !== '' && hash_equals($token, $given);
    }
}
