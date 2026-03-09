<?php
/**
 * +----------------------------------------------------------------------
 * | think-addons [thinkphp6]
 * +----------------------------------------------------------------------
 *  .--,       .--,             | FILE: Addons.php
 * ( (  \.---./  ) )            | AUTHOR: byron
 *  '.__/o   o\__.'             | EMAIL: xiaobo.sun@qq.com
 *     {=  ^  =}                | QQ: 150093589
 *     /       \                | DATETIME: 2019/11/5 14:47
 *    //       \\               |
 *   //|   .   |\\              |
 *   "'\       /'"_.-~^`'-.     |
 *      \  _  /--'         `    |
 *    ___)( )(___               |-----------------------------------------
 *   (((__) (__)))              | 高山仰止,景行行止.虽不能至,心向往之。
 * +----------------------------------------------------------------------
 * | Copyright (c) 2019 http://www.zzstudio.net All rights reserved.
 * +----------------------------------------------------------------------
 */
declare(strict_types=1);

namespace think;

use think\App;
use think\helper\Str;
use think\facade\Config;
use think\facade\View;
use think\view\driver\Think;

abstract class Addons
{
    // app 容器
    protected $app;
    // 请求对象
    protected $request;
    // 当前插件标识
    protected $name;
    // 插件路径
    protected $addon_path;
    // 视图模型
    protected $view;
    // 插件配置
    protected $addon_config;
    // 插件信息
    protected $addon_info;
    // 错误信息
    protected $error = '';

    /**
     * 插件构造函数
     * Addons constructor.
     * @param \think\App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $app->request;
        $this->name = $this->getName();
        $this->addon_path = $app->addons->getAddonsPath() . $this->name . DIRECTORY_SEPARATOR;
        $this->addon_config = "addon_{$this->name}_config";
        $this->addon_info = "addon_{$this->name}_info";
        // $this->view = clone View::engine('Think');
        $this->view = new Think($app, config('view'));
        $this->view->config([
            'view_path' => $this->addon_path . 'view' . DIRECTORY_SEPARATOR
        ]);

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {}

    /**
     * 获取插件标识
     * @return mixed|null
     */
    final protected function getName()
    {
        $class = get_class($this);
        list(, $name, ) = explode('\\', $class);
        $this->request->addon = $name;

        return $name;
    }

    /**
     * 加载模板输出
     * @param string $template
     * @param array $vars           模板文件名
     * @return false|mixed|string   模板输出变量
     * @throws \think\Exception
     */
    protected function fetch($template = '', $vars = [])
    {
        return $this->view->fetch($template, $vars);
    }

    /**
     * 渲染内容输出
     * @access protected
     * @param  string $content 模板内容
     * @param  array  $vars    模板输出变量
     * @return mixed
     */
    protected function display($content = '', $vars = [])
    {
        return $this->view->display($content, $vars);
    }

    /**
     * 模板变量赋值
     * @access protected
     * @param  mixed $name  要显示的模板变量
     * @param  mixed $value 变量的值
     * @return $this
     */
    protected function assign($name, $value = '')
    {
        $this->view->assign([$name => $value]);

        return $this;
    }

    /**
     * 初始化模板引擎
     * @access protected
     * @param  array|string $engine 引擎参数
     * @return $this
     */
    protected function engine($engine)
    {
        $this->view->engine($engine);

        return $this;
    }

    /**
     * 插件基础信息
     * @return array
     */
    final public function getInfo()
    {
        $info = app()->cache->get($this->addon_info);
        if (!app()->isDebug() && $info) {
            return $info;
        }

        // 文件属性
        $info = $this->info ?? [];
        // 文件配置
        $info_file = $this->addon_path . 'info.ini';
        if (is_file($info_file)) {
            $_info = parse_ini_file($info_file, true, INI_SCANNER_TYPED) ?: [];
            $_info['url'] = (string) addons_url();
            $info = array_merge($_info, $info);
        }

        $one = \think\facade\Db::name('app')->field('name,type,title,description,author,version,status')->where(['name'=>$this->name])->find();
        if (!empty($one)) {
            $info = $one + $info;
        } else {
            $info['status'] = 0;
        }

        if (!app()->isDebug()) {
            app()->cache->tag('addons')->set($this->addon_info, $info);
        }

        return isset($info) ? $info : [];
    }

    /**
     * 获取配置信息
     * @param bool $type 是否获取完整配置
     * @return array|mixed
     */
    final public function getConfig($type = false)
    {
        $config = Config::get($this->addon_config, []);
        if ($config) {
            return $config;
        }

        $arr1 = $arr2 = [];

        $temp_arr = \app\admin\model\App::where(['name'=>$this->name])->value('config');
        if (!empty($temp_arr)) {
            $arr1 = json_decode($temp_arr, true);
        }
        $config_file = $this->addon_path . 'config.php';
        if (is_file($config_file)) {
            $arr2 = (array)include $config_file;
        }

        $temp_arr = $arr1+$arr2;
        if (!empty($temp_arr)) {
            if ($type) {
                return $temp_arr;
            }
            foreach ($temp_arr as $key => $value) {
                if (!empty($value['item'])) {
                    foreach ($value['item'] as $kk=>$v) {
                        if (in_array($v['type'], ['checkbox','selects'])) {
                            $config[$key][$kk] = explode(',', $v['value']);
                        } else {
                            $config[$key][$kk] = $v['value'];
                        }
                    }
                } else {
                    if (in_array($value['type'], ['checkbox','selects'])) {
                        $config[$key] = explode(',', $value['value']);
                    } else {
                        $config[$key] = $value['value'];
                    }
                }
            }
            unset($temp_arr);
        }
        Config::set($config, $this->addon_config);

        return $config;
    }

    /**
     * 自动注册第三方类库
     * @param $namespace
     */
    public function addNamespace($namespace)
    {
        $path = $this->addon_path.'library'.DIRECTORY_SEPARATOR;
        spl_autoload_register(function ($class) use ($namespace, $path){
            // 完整命名空间
            $class = ltrim($class, '\\');

            if (strpos($class, $namespace) === 0) {
                $php = $path.$class.'.php';
                if (file_exists($php)) {
                    include_once $php;
                    return true;
                }
            }
            return false;
        });
    }

    /**
     * 获取错误信息
     * @return string
     */
    final public function getError()
    {
        return $this->error;
    }

    //必须实现安装
    abstract public function install();

    //必须卸载插件方法
    abstract public function uninstall();
}
