<?php
namespace app;

use app\common\exception\ServiceException;
use app\common\exception\UploadException;
use app\common\services\system\ApiResponseService;
use think\addons\AddonsException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\facade\Config;
use think\Response;
use Throwable;
use think\Request;

/**
 * 应用异常处理类
 */
class ExceptionHandle extends Handle
{
    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
        ServiceException::class // 服务层内部异常（数据校验等异常非系统错误）
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     *
     * @access public
     * @param  Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        // 使用内置的方式记录异常日志
        //parent::report($exception);
        if (!$this->isIgnoreReport($exception)) {
            // 收集异常数据
            if ($this->app->isDebug()) {
                $data = [
                    'file'    => $exception->getFile(),
                    'line'    => $exception->getLine(),
                    'message' => $this->getMessage($exception),
                    'code'    => $this->getCode($exception),
                ];
                $log = "[{$data['code']}]{$data['message']}[{$data['file']}:{$data['line']}]";
            } else {
                $data = [
                    'file'    => $exception->getFile(),
                    'line'    => $exception->getLine(),
                    'code'    => $this->getCode($exception),
                    'message' => $this->getMessage($exception),
                ];
                $log = "[{$data['code']}]{$data['message']}[{$data['file']}:{$data['line']}]";
            }

            if ($this->app->config->get('log.record_trace')) {
                $log .= PHP_EOL . $exception->getTraceAsString();
            }
            try {
                $this->app->log->record($log, 'error');
            } catch (\Exception $e) {}
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param Request  $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        $this->report($e);
        if (app()->http->getName()=='api') {
            return $this->apiException($request, $e);
        }

        // 添加自定义异常处理机制
        if ($e instanceof ValidateException && $request->isAjax()) {
            return json(['code' => -1000, 'msg' => $e->getMessage(), 'data' => []]);
        }

        // 插件异常处理
        if ($e instanceof AddonsException) {
            if ($request->isAjax()) {
                return json(['code' => -1000, 'msg' => $e->getMessage(), 'data' => []], $e->getStatusCode());
            } else {
                return response($e->getMessage(), $e->getStatusCode());
            }
        }

        // 文件上传异常处理
        if ($e instanceof UploadException) {
            if ($request->isAjax()) {
                return json(['code' => -1000, 'msg' => $e->getMessage(), 'data' => []], 200);
            } else {
                return response($e->getMessage(), $e->getStatusCode());
            }
        }
        if ($e instanceof ServiceException) {
            if ($request->isAjax()) {
                return json(['code'=>$e->getCode(), 'msg'=>$e->getMessage()], $e->httpCode());
            } else {
                return response($e->getMessage(), $e->httpCode());
            }
        }
        if (!app()->isDebug() && $e instanceof Exception && $request->isAjax()) {
            return json(['code' => -1000, 'msg' => $e->getMessage(), 'data' => []], 200);
        }

        // 其他错误交给系统处理
        return parent::render($request, $e);
    }

    /**
     * api模块异常处理
     * @param Request $request
     * @param Throwable $e
     * @return Response
     */
    public function apiException(Request $request, Throwable $e): Response
    {
        if ($e instanceof ServiceException) {
            return app()->make(ApiResponseService::class)->response($e->getMessage(), null, $e->getCode())->header($this->header($request));
        }
        if ($e instanceof \think\exception\ErrorException) {
            $msg = __('System error');
            $data = [];
            if ($this->app->isDebug()) {
                $result = $this->convertExceptionToArray($e);
                $msg = $result['message'];
                $data['traces'] = $result['traces'];
                $data['tables'] = $result['tables'];
            }
            return app()->make(ApiResponseService::class)->response($msg, $data, 500)->code(500)->header($this->header($request));
        }
        if ($e instanceof HttpException) {
            $status   = $e->getStatusCode();
            $msg = __('System error');
            if ($status==404) {
                $msg = __('404 Not found');
            }
            if ($this->app->isDebug()) {
                $msg = $e->getMessage();
            }
            return app()->make(ApiResponseService::class)->response($msg, null, $status)->code($status)->header($this->header($request));
        }

        return parent::render($request, $e);
    }

    /**
     * 处理错误返回头部信息
     * @param Request $request
     * @return array
     */
    public function header(Request $request): array
    {
        $header = $request->header();
        $cookieDomain = Config::get('cookie.domain');
        if (!isset($header['Access-Control-Allow-Origin'])) {
            $origin = $request->header('origin');
            if ($origin && ('' == $cookieDomain || strpos($origin, $cookieDomain))) {
                $header['Access-Control-Allow-Origin'] = $origin;
            } else {
                $header['Access-Control-Allow-Origin'] = '*';
            }
        }
        return ['Access-Control-Allow-Origin'=>$header['Access-Control-Allow-Origin']];
    }
}
