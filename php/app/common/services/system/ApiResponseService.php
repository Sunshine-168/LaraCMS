<?php
// +----------------------------------------------------------------------
// | Api响应处理
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\services\system;

use think\contract\Arrayable;
use think\response\Json;

class ApiResponseService
{
    // 成功消息
    const SUCCESS_MSG = "success";
    // 成功错误消息
    const SUCCESS_CODE = 200;
    // 失败消息
    const ERROR_MSG = "error";
    // 失败错误消息
    const ERROR_CODE = 400;

    /**
     * json响应
     * @var Json
     */
    protected $json;

    public function __construct(Json $json)
    {
        $this->json = $json;
    }

    /**
     * 响应返回
     * @param array | string | Arrayable | null $msg 提示信息
     * @param array | Arrayable | null $data 响应数据
     * @param int $code 状态码
     * @return Json
     */
    public function response($msg, $data, int $code): Json
    {
        if (is_array($msg) || $msg instanceof Arrayable) {
            $data = $msg;
            $msg = self::SUCCESS_MSG;
        } else if (is_bool($msg)) {
            $msg = $msg ? self::SUCCESS_MSG : self::ERROR_MSG;
            $code = $msg ? self::SUCCESS_CODE : self::ERROR_CODE;
        } else if (is_null($msg)) {
            $msg = $code == self::SUCCESS_CODE ? self::SUCCESS_MSG : self::ERROR_MSG;
        }
        return $this->json->data(['msg'=>$msg,'data'=>$data,'code'=>$code]);
    }

    /**
     * 成功的响应
     * @param array | string | Arrayable | null $msg
     * @param array | Arrayable | null $data
     * @return Json
     */
    public function success($msg = self::SUCCESS_MSG, $data = null): Json
    {
        return $this->response($msg, $data, self::SUCCESS_CODE);
    }

    /**
     * 失败的响应
     * @param array | string | Arrayable | null $msg
     * @param array | Arrayable | null $data
     * @return Json
     */
    public function error($msg = self::ERROR_MSG, $data = null): Json
    {
        return $this->response($msg, $data, self::ERROR_CODE);
    }
}