<?php
// +----------------------------------------------------------------------
// | API站点开启状态
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\api\middleware;

use app\common\services\system\ApiResponseService;
use app\Request;
use think\exception\HttpResponseException;

class SiteStatusMiddleware
{
    public function handle(Request $request, \Closure $next)
    {
        $site = site();
        $apiSer = app()->make(ApiResponseService::class);
        if ($site['web_status']==0) { // 是否临时关闭网站
            throw new HttpResponseException($apiSer->error("站点已关闭"));
        }
        if (config('base.api_enable')!=true) {
            throw new HttpResponseException($apiSer->error("API模块未开启"));
        }
        return $next($request);
    }
}