<?php

// +----------------------------------------------------------------------
// | token 服务
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\common\services\user;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class TokenService
{
    /**
     * 创建token
     * @param int $id 唯一值
     * @param string $client 客户端类型
     * @param string $mark 标志
     * @param int $exp 过期时间（时间戳）
     * @param array $payload 扩展字段
     * @return array
     */
    public function create(int $id, string $client, string $mark = 'api', int $exp = 0, array $payload = []): array
    {
        $host = request()->host();
        $time = time();
        $jwt = config('jwt.client');
        $expire = $jwt[$client]['expire']*60*60*24;
        $exp = $exp ?: $time + $expire;
        $payload += [
            // JWT的签发者
            'iss'=>$host,
            'aud'=>$host,
            // 签发时间
            'iat'=>$time,
            // 在某个时间之后才能被使用
            'nbf'=>$time,
            // 过期时间
            'exp'=>$exp,
        ];
        // 身份标识
        $payload['jti'] = [$id, $mark, $client];
        $token = JWT::encode($payload, config('app.app_key'), 'HS256');
        $expire = $exp - time();
        $exp = date('Y-m-d H:i:s', $exp);
        return compact('token', 'exp', 'expire');
    }

    /**
     * 解析token
     * @param string $jwt
     * @return \stdClass
     */
    public function decode(string $jwt)
    {
        JWT::$leeway = 60;
        return JWT::decode($jwt, new Key(config('app.app_key'), 'HS256'));
    }
}