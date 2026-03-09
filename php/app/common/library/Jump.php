<?php

namespace app\common\library;

use think\exception\HttpResponseException;

/**
 * Trait Jump, 依赖app容器
 * @property \think\App app
 * @package app\common\library
 */
trait Jump
{
    /**
     * 操作成功跳转
     * @access protected
     * @param  mixed $msg 提示信息
     * @param  string $url 跳转的URL地址
     * @param  mixed $data 返回的数据
     * @param  integer $wait 跳转等待时间
     * @param  array $header 发送的Header信息
     * @return void
     */
    protected function success($msg = '', $url = null, $data = '', int $wait = 3, array $header = []): void
    {
        if (is_null($url) && isset($_SERVER["HTTP_REFERER"])) {
            $url = $_SERVER["HTTP_REFERER"];
        } elseif ($url) {
            $url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : (string)app('route')->buildUrl($url);
        }

        $result = [
            'code' => 200,
            'msg' => empty($msg)?__("Operation completed"):$msg,
            'data' => $data,
            'url' => $url,
            'wait' => $wait,
        ];

        $request = app('request');
        if ($request->isJson() || $request->isAjax()) {
            $response = json($result)->header($header);
        } else {
            app()->view->layout(false);
            $response = view($this->success_tmpl, $result)->header($header);
        }
        throw new HttpResponseException($response);
    }

    /**
     * 操作错误跳转
     * @access protected
     * @param  mixed $msg 提示信息
     * @param  string $url 跳转的URL地址
     * @param  mixed $data 返回的数据
     * @param  integer $wait 跳转等待时间
     * @param  array $header 发送的Header信息
     * @return void
     */
    protected function error($msg = '', string $url = null, $data = '', int $wait = 3, array $header = []): void
    {
        $request = app('request');
        if (is_null($url)) {
            $url = $request->isAjax() ? '' : 'javascript:history.go(-1);';
        } elseif ($url) {
            $url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : (string)app('route')->buildUrl($url);
        }

        $result = [
            'code' => -1000,
            'msg' => $msg,
            'data' => $data,
            'url' => $url,
            'wait' => $wait,
        ];

        if ($request->isJson() || $request->isAjax()) {
            $response = json($result)->header($header);
        } else {
            app()->view->layout(false);
            $response = view($this->error_tmpl, $result)->header($header);
        }

        throw new HttpResponseException($response);
    }

    /**
     * 指定API 返回数据类型
     * @access protected
     * @param  mixed $data 要返回的数据
     * @param  integer $code 返回的code
     * @param  mixed $msg 提示信息
     * @param  string $type 返回数据格式
     * @param  array $header 发送的Header信息
     * @return void
     */
    protected function result($msg = '', $data = [], $code = 200,  $type = 'json', array $header = []): void
    {
        $result = [
            'code' => $code,
            'msg' => $msg,
            'time' => time(),
            'data' => $data,
        ];

        if ($type=='jsonp') {
            $response = jsonp($result)->header($header);
        } elseif ($type=='xml') {
            $response = xml($result)->header($header);
        } else {
            $response = json($result)->header($header);
        }

        throw new HttpResponseException($response);
    }
}