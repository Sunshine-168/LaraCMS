<?php
// +----------------------------------------------------------------------
// | api登录中间件
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\api\middleware;

use app\common\exception\ServiceException;
use app\common\services\user\TokenService;
use app\common\services\user\UserService;
use app\Request;
use Closure;
use Firebase\JWT\ExpiredException;
use think\facade\Cache;
use think\facade\Config;

class ApiLoginMiddleware
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('X-Token');
        if (empty($token)) {
            throw new ServiceException(__('Please login to operate'), 401);
        }
        if (strpos($token, 'Bearer') === 0) {
            $token = trim(substr(trim($token), 6));
        }
        if (empty($token)) {
            throw new ServiceException(__('Please login to operate'), 401);
        }
        try {
            $key = md5($token);
            $cacheInfo = Cache::get($key);
            if (empty($cacheInfo)) {
                throw new ServiceException(__('Token has since expired'));
            }
            // 验证token
            $tokenSer = new TokenService();
            $obj = $tokenSer->decode($token);
            // 验证用户
            $user = app()->make(UserService::class)->getOne($obj->jti[0]);
            if (empty($user)) {
                throw new ServiceException(__('User not exists'), 401);
            }
            if ($user['status']!='normal') {
                throw new ServiceException(__('The account has been disabled'), 401);
            }
            // 延长过期时间
            Cache::set($key, $cacheInfo, Config::get('jwt.expire'));
        } catch (ExpiredException $exception) {
            throw new ServiceException(__('Token has since expired'), 401);
        } catch (\Exception $exception) {
            throw new ServiceException($exception->getMessage(), 401);
        }
        // 写入
        $request->macro('token', function () use ($token){
            return $token;
        });
        $request->macro('userInfo', function () use ($user){
            return $user;
        });
        $request->macro('userId', function () use ($user){
            return $user['id'];
        });
        hook('apiLoginMiddleware', $request);
        return $next($request);
    }
}