<?php
// +----------------------------------------------------------------------
// | HkCms API总控制器
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types = 1);

namespace app\api\controller;

use app\common\services\system\ApiResponseService;
use libs\Macroable;
use think\App;
use think\Cache;
use think\Config;
use think\contract\Arrayable;
use think\exception\ValidateException;
use think\response\Json;
use think\Validate;
use think\Request;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var Request
     * @var Macroable
     */
    protected $request;

    /**
     * 应用实例
     * @var App
     */
    protected $app;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 配置
     * @var Config
     */
    protected $config;

    /**
     * 缓存
     * @var Cache
     */
    protected $cache;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;
        $this->config = $this->app->config;
        $this->cache = $this->app->cache;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
        // 初始化站点配置
        //$site = site();
        // 多语言内容配置
        //$lang = $site['content_lang_on']==1?$this->app->lang->getLangset():'';
        //$this->config->set($site, 'api'.$lang.'_site');
        // 加载当前控制器语言包
        loadlang($this->request->controller());
        // api配置初始化事件
        //hook('configInit', $site);
        // 定位模板位置，如果需要
        $this->app->view->config(['view_path'=>root_path().'app'.DIRECTORY_SEPARATOR.'api'.DIRECTORY_SEPARATOR.'view'.DIRECTORY_SEPARATOR]);
    }

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证-
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    /**
     * 失败的响应
     * @param array | string | Arrayable | null $msg
     * @param array | Arrayable | null $data
     * @return Json
     */
    public function error($msg = ApiResponseService::ERROR_MSG, $data = null): Json
    {
        return app()->make(ApiResponseService::class)->error($msg, $data);
    }

    /**
     * 成功的响应
     * @param array | string | Arrayable | null $msg
     * @param array | Arrayable | null $data
     * @return Json
     */
    public function success($msg = ApiResponseService::SUCCESS_MSG, $data = null): Json
    {
        return app()->make(ApiResponseService::class)->success($msg, $data);
    }

    /**
     * 获取分页配置
     * @param bool $isLimit 是否开启分页大小限制
     * @return int[]
     */
    public function getPage(bool $isLimit = true): array
    {
        // 页码
        $page = app()->request->param('page', 1, 'intval');
        // 分页大小
        $limit = app()->request->param('limit', $this->config->get('base.default_limit'), 'intval');
        // 最大分页大小
        $limitMax = $this->config->get('base.limit_max');
        if ($limit > $limitMax && $isLimit) {
            $limit = $limitMax;
        }
        return [(int)$page, (int)$limit];
    }
}
