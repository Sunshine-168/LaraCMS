<?php
// +----------------------------------------------------------------------
// | HkCms 登录验证
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\index\middleware;

use app\index\library\User;

class Login
{
    /**
     * @param \think\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        if (!$request->session('Member')) {
            if ($request->isAjax()) {
                return json(['code'=>-1000, 'msg'=>__('Please log in and operate'), 'data'=>[]]);
            }
            return redirect((string)url('/user.user/login'));
        }

        return $next($request);
    }
}