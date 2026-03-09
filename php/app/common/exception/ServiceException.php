<?php
// +----------------------------------------------------------------------
// | 服务内部异常
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\exception;

use think\Exception;

class ServiceException extends Exception
{
    /**
     * @var int http 状态码
     */
    protected $httpCode;

    public function __construct($message = "", $code = -1000, \Throwable $previous = null)
    {
        $this->httpCode = 200;
        parent::__construct(__($message), $code, $previous);
    }

    /**
     * 获取http状态码
     * @return int
     */
    public function httpCode(): int
    {
        return $this->httpCode;
    }
}