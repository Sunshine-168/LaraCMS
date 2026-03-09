<?php
// +----------------------------------------------------------------------
// | HkCms 前台总控制器
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types = 1);

namespace app\index\controller;

use app\admin\model\routine\Config;
use app\common\services\lang\LangService;
use think\App;
use think\exception\ValidateException;
use think\Validate;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    use \app\common\library\Jump;

    /**
     * 错误模板，主题文件夹下
     * @var string
     */
    protected $error_tmpl = '/error';
    protected $success_tmpl = '/success';

    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 视图
     * @var \think\View | \think\Template
     */
    protected $view;

    /**
     * 配置
     * @var \think\Config
     */
    protected $config;

    /**
     * 缓存
     * @var \think\Cache
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
        $this->view = $this->app->view;
        $this->config = $this->app->config;
        $this->cache = $this->app->cache;
        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
        if ($this->app->isDebug()) { // 在调试模式下，需要关闭的功能
            $this->view->config(['tpl_cache'=>false,'display_cache'=>false]);
        }

        // 加载当前控制器语言包
        loadlang($this->request->controller());
        // 加载模板语言包
        $Lang = load_template_lang($this->request->controller());
        // 初始化配置
        $site = site();
        $site = array_merge($site,[
            'root_domain' => $this->request->baseFile(true), // 域名地址
            'root_file' => trim($this->request->baseFile(), '/'), // 入口文件
            'static_path' => $site['cdn'].'/static/module/index/'.$site['index_theme'], // 模板静态目录
        ]);

        $lang = $site['content_lang_on']==1?$this->app->lang->getLangset():app()->make(LangService::class)->getDefaultLang('index');
        $this->config->set($site, 'index'.$lang.'_site');
        hook('configInit', $site);

        $this->view->config(['view_path'=>$this->getTemplatePath()]);
        if ($site['web_status']==0) { // 是否临时关闭网站
            $this->closePage($site);
        }

        // 初始化模板配置
        $tpl = get_addons_config('template', $site['index_theme'],'index');
        // 设置输出替换
        $this->view->config([
            'tpl_replace_string'=>array_merge(config('view.tpl_replace_string'),[
                '__static__'=>$site['static_path'],
                '__libs__'=>$site['cdn'].'/static/libs'
            ])
        ]);
        // 设置视图过滤
        $this->view->filter(function ($content){
            if ($code = site(app()->request->isMobile()?'thirdcode_mobile':'thirdcode_pc')) {
                $content = str_ireplace('</body>',$code.'</body>', $content);
            }
            return $content;
        });

        $this->view->assign('site', array_diff_key(site(),['mail_password'=>'','mail_user'=>'','mail_from'=>'','mail_server'=>'','mail_port'=>'']));
        $this->view->assign('Tpl', $tpl);
        $this->view->assign('Lang', $Lang);
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

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    /**
     * 获取当前模板路径
     * @return mixed|string
     */
    public function getTemplatePath()
    {
        // 定位模板位置
        $themePath = $this->config->get('cms.tpl_path').'index'.DIRECTORY_SEPARATOR.site('index_theme').DIRECTORY_SEPARATOR;

        $themeType = $this->cache->get('theme_type_'.site('index_theme'));
        if (empty($themeType)) {
            $tempArr = glob($themePath.'*');
            $dirArr = [];
            foreach ($tempArr as $key=>$value) {
                if (is_dir($value)) {
                    $temp = basename($value);
                    if ($temp=='pc' || $temp=='mobile') {
                        $dirArr[] = $temp;
                    }
                }
            }
            $themeType = count($dirArr)==2; // true-非响应式，false-响应式
            $this->cache->set('theme_type_'.site('index_theme'),$themeType,7200);
        }

        $mobile_domain = site('mobile_domain'); // 获取后台是否填写手机域名
        $subDo = $this->request->subDomain(); // 子域名

        // 手机访问主域名，设置手机域名情况下跳转到手机域名
        $dev = $this->request->isMobile() ? 'mobile' : 'pc';
        if ($mobile_domain && $subDo!=$mobile_domain && $dev=='mobile') {
            $url = str_replace($this->request->host(),$mobile_domain.'.'.$this->request->rootDomain(), $this->request->url(true));
            header('Location: '.$url);
            exit;
        }

        // 非响应式模板位置重新定位
        if ($themeType) {
            $themePath = $mobile_domain && $subDo==$mobile_domain ? $themePath.'mobile'.DIRECTORY_SEPARATOR : $themePath.$dev.DIRECTORY_SEPARATOR;
        }

        return $themePath;
    }

    /**
     * 站点关闭页
     * @param $site
     */
    private function closePage($site)
    {
        $path = $this->getTemplatePath();
        $path = $path . 'close' . '.' . ltrim($this->config->get('view.view_suffix'), '.');
        if (is_file($path)) {
            throw new \think\exception\HttpResponseException(view('/close', ['site'=>$site])->header([]));
        } else {
            $html = <<<EOF
<!DOCTYPE html>
<html lang="zh">
<head>
    {hkcms:seo /}
    <link rel="Shortcut Icon" href="{$site['favicon']}" type="image/x-icon" />
    <style>html,body {width: 100%;height: 100%;margin: 0}div {font-size: 28px;padding-top: 200px;}</style>
</head>
<body><div><p align="center">站点临时关闭维护中...</p></div></body>
</html>
EOF;
            throw new \think\exception\HttpResponseException(display($html, ['site'=>$site]));
        }
    }
}
