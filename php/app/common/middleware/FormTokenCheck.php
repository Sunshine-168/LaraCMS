<?php
// +----------------------------------------------------------------------
// | HkCms 表单验证
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: HkCms team <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\common\middleware;

use Closure;
use think\exception\HttpResponseException;
use think\Request;
use think\Response;

class FormTokenCheck
{
    /**
     * 表单令牌检测
     * @access public
     * @param Request $request
     * @param Closure $next
     * @param string  $token 表单令牌Token名称
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $token = null)
    {
        $check = $request->checkToken($token ?: '__token__');

        if (false === $check) {
            $result = [
                'code' => -1000,
                'msg' => lang('invalid token'),
                'data' => ['__token__' => $request->buildToken()],
                'url' => '',
                'wait' => 0,
            ];

            throw new HttpResponseException(json($result));
        }

        return $next($request);
    }
}