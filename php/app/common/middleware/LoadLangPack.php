<?php
// +----------------------------------------------------------------------
// | HkCms 语言包中间件
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace app\common\middleware;

use app\common\services\lang\LangService;
use Closure;
use think\App;
use think\facade\Db;
use think\Lang;
use think\Request;
use think\Response;

/**
 * 多语言加载
 */
class LoadLangPack
{
    protected $app;

    protected $lang;

    public function __construct(App $app, Lang $lang)
    {
        $this->app  = $app;
        $this->lang = $lang;
    }

    /**
     * 路由初始化（路由规则注册）
     * @access public
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle($request, Closure $next)
    {
        $name = $this->app->http->getName();
        $name = $name=='api' ? 'index':$name;
        if ($name=='admin' || $name=='index') {
            // 获取数据库设定的默认语言
            $defaultLang = app()->make(LangService::class)->getDefaultLang($name);
            // 自动侦测当前语言
            $langset = $this->detect($request);
            if (!empty($langset)) { // 覆盖默认语言
                $defaultLang = $langset;
            }

            // 缓存清理
            if ($name=='index') {
                $oldLang = $this->app->cookie->get('old_'.$name.'_'.config('lang')['cookie_var']);
                if ($defaultLang!=$oldLang) {
                    app()->make(\app\common\services\cache\CacheService::class)->clearFile('temp', 'index');
                }
            }
            if ($name=='index') {
                $this->app->cookie->set('old_'.$name.'_'.config('lang')['cookie_var'], $defaultLang);
            }
            if ('en' != $defaultLang) { // 默认en，不需要去加载en
                // 加载TP系统语言包
                $this->lang->load([
                    $this->app->getThinkPath() . 'lang' . DIRECTORY_SEPARATOR . $defaultLang . '.php',
                ]);

                // 加载系统语言包
                $files = glob($this->app->getAppPath() . 'lang' . DIRECTORY_SEPARATOR . $defaultLang . '.*');
                $this->lang->load($files);

                // 加载扩展（自定义）语言包
                $list = $this->app->config->get('lang.extend_list', []);

                if (isset($list[$defaultLang])) {
                    $this->lang->load($list[$defaultLang]);
                }
            }
            $this->app->lang->setLangSet($defaultLang);
            $this->app->cookie->set($name.'_'.config('lang')['cookie_var'], $defaultLang, 604800);
        }

        return $next($request);
    }

    /**
     * 自动侦测设置获取语言选择
     * @access public
     * @param Request $request
     * @return string
     */
    public function detect(Request $request): string
    {
        // 自动侦测设置获取语言选择
        $langSet = '';
        $config = config('lang');
        $name = $this->app->http->getName();
        $name = $name=='api' ? 'index':$name;
        // 内容多语言开启的情况下，提取出 http://xxx.com/en/ 路径中的lang语言标识
        if ($name=='index') {
            $url = $request->url();
            preg_match("/(?<=\/)[A-Za-z_\-]+(?=\/)/", $url, $lang);
            if (!empty($lang)) {
                // 判断标识是否在当前前台语言包中。
                $langSer = app()->make(LangService::class);
                // 不适用模型
                $contentLangArr = $langSer->getListByModuleCache($name);
                if (in_array($lang[0],$contentLangArr)) {
                    return $lang[0];
                }
            }
        }

        if ($request->get($config['detect_var'])) {
            // url中设置了语言变量
            $langSet = strtolower($request->get($config['detect_var']));
        } elseif ($request->header($config['header_var'])) {
            // Header中设置了语言变量
            $langSet = strtolower($request->header($config['header_var']));
        } elseif ($request->cookie(app()->http->getName().'_'.$config['cookie_var'])) {
            // Cookie中设置了语言变量
            $langSet = strtolower($request->cookie(app()->http->getName().'_'.$config['cookie_var']));
        } elseif ($request->server('HTTP_ACCEPT_LANGUAGE')) {
            // 自动侦测浏览器语言,不在检测浏览器语言【废弃】
            // $langSet = $request->server('HTTP_ACCEPT_LANGUAGE');
        }

        if (preg_match('/^([a-z\d\-]+)/i', $langSet, $matches)) {
            $langSet = strtolower($matches[1]);
            if (isset($config['accept_language'][$langSet])) { // 转义成对应语言包
                $langSet = $config['accept_language'][$langSet];
            }
        }
        if (empty($langSet)) {
            return app()->make(LangService::class)->getDefaultLang($name);
        }

        // 判断标识是否在当前前台语言包中。
        $langSer = app()->make(LangService::class);
        $langList = !empty($contentLangArr) ? $contentLangArr : $langSer->getListByModuleCache($name);
        $allow_lang_list = empty($config['allow_lang_list']) ? $langList : array_merge($config['allow_lang_list'], $langList);
        if (empty($allow_lang_list) || in_array($langSet, $allow_lang_list)) {
            // 合法的语言
            $this->lang->setLangSet($langSet);
            return $langSet;
        }
        return "";
    }
}
