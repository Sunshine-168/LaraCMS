<?php
// +----------------------------------------------------------------------
// | 跨域中间件
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\api\middleware;

use app\Request;
use think\exception\HttpResponseException;
use think\facade\Config;
use think\facade\Log;
use think\Response;

class AllowCrossMiddleware
{
    protected $header = [
        'Access-Control-Allow-Credentials' => 'true',
        'Access-Control-Max-Age'           => 1800,
        'Access-Control-Allow-Methods'     => 'GET, POST, PATCH, PUT, DELETE, OPTIONS',
        'Access-Control-Allow-Headers'     => 'Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-CSRF-TOKEN, X-Requested-With, X-Token, X-Form-Client',
        //'Access-Control-Allow-Origin'      => '*',
    ];

    /**
     * 允许跨域请求
     * @access public
     * @param Request $request
     * @param \Closure $next
     * @param array|null $header
     * @return Response
     */
    public function handle(Request $request, \Closure $next, ? array $header = [])
    {
        // 获取允许跨域的配置
        $allowHost = Config::get('base.cors_domain');
        $allowHost[] = $request->host(true);
        $origin = $request->server('HTTP_ORIGIN');
        if ($origin && $allowHost) {
            $domainInfo = parse_url($origin);
            $header = $this->header;
            if (in_array("*", $allowHost) || in_array($origin, $allowHost) || isset($domainInfo['host']) && in_array($domainInfo['host'], $allowHost)) {
                $header['Access-Control-Allow-Origin'] = $origin;
            } else {
                throw new HttpResponseException(Response::create('', 'html', 403));
            }
            if ($request->method(true)=='OPTIONS') {
                throw new HttpResponseException(Response::create('', 'html', 204)->header($header));
            }
        }
        return $next($request)->header($header);
    }
}