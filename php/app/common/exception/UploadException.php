<?php
// +----------------------------------------------------------------------
// | HkCms 文件上传异常类
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\common\exception;

use think\Exception;
use Throwable;

class UploadException extends Exception
{
    private $statusCode;

    public function __construct($message = "", $code = -1000, Throwable $previous = null)
    {
        $this->statusCode = $code;
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}