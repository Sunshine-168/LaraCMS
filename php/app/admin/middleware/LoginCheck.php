<?php
// +----------------------------------------------------------------------
// | HkCms 登录验证
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: HkCms team <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\middleware;

class LoginCheck
{
    /**
     * @param \think\Response $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        if (!$request->session('User')) {
            if ($request->isAjax()) {
                return json(['code'=>-1000, 'msg'=>__('Please log in and operate'), 'data'=>[]]);
            }
            return redirect((string)url('/login/index',['url'=>$request->controller().'/'.$request->action()]))->remember();
        }
        return $next($request);
    }
}