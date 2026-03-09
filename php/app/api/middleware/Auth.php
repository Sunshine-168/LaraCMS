<?php
// +----------------------------------------------------------------------
// | HkCms api 权限认证
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\api\middleware;

class Auth
{
    /**
     * @param \think\Response $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        hook('apiAuthMiddleware', $request);

        return $next($request);
    }
}