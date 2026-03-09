<?php
declare(strict_types=1);

namespace think\addons;

use think\App;
use think\helper\Str;
use think\facade\Config;
use think\facade\View;
use think\view\driver\Think;

abstract class Controller
{
    // success、error、result
    use \app\common\library\Jump;

    /**
     * 错误模板，主题文件夹下
     * @var string
     */
    protected $error_tmpl = '/error';
    protected $success_tmpl = '/success';

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
     * 缓存
     * @var \think\Cache
     */
    protected $cache;

    // 站点配置
    public $site;

    /**
     * 插件构造函数
     * Addons constructor.
     * @param \think\App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $app->request;
        $this->cache = $app->cache;
        $this->name = $this->getName();
        $this->addon_path = $app->addons->getAddonsPath() . $this->name . DIRECTORY_SEPARATOR;
        $this->addon_config = "addon_{$this->name}_config";
        $this->addon_info = "addon_{$this->name}_info";

        // 初始化站点配置信息
        $site = \app\admin\model\routine\Config::initConfig();
        $site['root_domain'] = $this->request->domain(true); // 带域名
        $site['root_file'] = trim($this->request->baseFile(), '/');

        // 模板
        // $this->view = clone View::engine('Think');
        $this->view = new Think($app, config('view'));
        $this->view->config([
            //'view_path' => $this->addon_path . 'view' . DIRECTORY_SEPARATOR,
            'tpl_replace_string'=>[
                '__addons__'=>'/static/addons/'.$this->name,
                '__libs__'=>$site['cdn'].'/static/libs'
            ]
        ]);
        $this->site = $site;
        $this->assign('site', $site);

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
        // 加载当前插件语言包
        $this->app->lang->load($this->addon_path.'lang'.DIRECTORY_SEPARATOR.$this->app->lang->getLangset().'.php');
        // 加载当前控制器语言包
        $name = $this->request->controller();
        if (strpos($name, '.')) {
            $arr = explode('.', $name);
            if (count($arr) == 2) {
                $path = strtolower($arr[0].DIRECTORY_SEPARATOR.$arr[1]);
            } else {
                $path = strtolower($name);
            }
        } else {
            $path = strtolower($name);
        }
        $path = $this->addon_path.'lang'.DIRECTORY_SEPARATOR.$this->app->lang->getLangset().DIRECTORY_SEPARATOR.$path.'.php';
        if (is_file($path)) {
            $this->app->lang->load($path);
        }
    }

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
     * @param string $template 模板文件名
     * @param array $vars      模板输出变量
     * @return false|mixed|string
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
        $info = Config::get($this->addon_info, []);
        if ($info) {
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

        Config::set($info, $this->addon_info);

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

        $temp_arr = \app\admin\model\App::where(['name'=>$this->name])->value('config');
        if (empty($temp_arr)) {
            $config_file = $this->addon_path . 'config.php';
            if (is_file($config_file)) {
                $temp_arr = (array)include $config_file;
            }
        } else {
            $temp_arr = json_decode($temp_arr, true);
        }

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
     * 获取错误信息
     * @return string
     */
    final public function getError()
    {
        return $this->error;
    }
}
