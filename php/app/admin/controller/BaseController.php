<?php
// +----------------------------------------------------------------------
// | HkCms 后台总控制器
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types = 1);

namespace app\admin\controller;

use app\common\services\config\ConfigService;
use app\common\services\lang\LangService;
use think\App;
use think\exception\ValidateException;
use think\facade\Db;
use think\Validate;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    use \app\common\library\Crud, \app\common\library\Jump;

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
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [
        'login', // 登录中间件
        'auth',  // 权限认证中间件
        //'form_token', // 表单令牌
    ];

    /**
     * 模板布局
     * @var string
     */
    protected $layout = 'default';

    /**
     * 视图
     * @var \think\View | \think\Template
     */
    protected $view;

    /**
     * 写入时需要排除的字段数据.
     * @var string | array
     */
    protected $excludeFields = '';

    /**
     * 允许批量修改的字段
     * @var array
     */
    protected $allowFields = ['status','weigh'];

    /**
     * 是否开启Validate验证
     * @var bool
     */
    protected $enableValidate = false;

    /**
     * 是否开启场景验证
     * @var bool
     */
    protected $enableScene = false;

    /**
     * 是否启用多语言写入
     * @var bool
     */
    protected $enableLang = false;

    /**
     * 缓存
     * @var \think\Cache
     */
    protected $cache;

    /**
     * 用户服务
     * @var \app\admin\library\User
     */
    protected $user;

    /**
     * 配置
     * @var \think\Config
     */
    protected $config;

    /**
     * 默认快速搜索的字段(|,&) 例如：name|title 或 name&title
     * @var string
     */
    protected $searchField = 'id';

    /**
     * 内容多语言模式
     * @var string
     */
    protected $contentLang = 'zh-cn';

    /**
     * 模型
     * @var \think\Model
     */
    protected $model;

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;
        $this->view = $this->app->view;
        $this->user = $this->app->user;
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

        $langService = app()->make(LangService::class);
        // 内容多语言编辑模式，后台用户唯一，互不影响
        $this->contentLang = $langService->contentMode((int)$this->user->id);
        $this->cache->set('admin_content_lang'.$this->user->id, $this->contentLang);
        $site = app(ConfigService::class)->site('', '', $this->contentLang);

        $site = array_merge([
            'root_domain' => str_replace(['http:','https:'],'',$this->request->baseFile(true)), // 域名地址
            'root_host' => str_replace(['http:','https:'],'',$this->request->domain()), // 域名地址
            'root_file' => trim($this->request->baseFile(), '/'), // 入口文件
            'controller' => $this->request->controller(), // 控制器
            'action' => $this->request->action(), // 方法
            'static_path' => $site['cdn'].'/static/module/admin/'.$site['admin_theme'], // 模板静态目录
            'app_debug' => app()->isDebug(),
            'content_lang_mode' => $this->contentLang,
            'admin_lang'=>$langService->getLang('admin'),
            'content_lang_list'=>$site['content_lang_on']==1?$langService->getSearchList(['status'=>1,'module'=>3]):[],// 内容语言列表
        ], $site);

        // 获取后台语言配置
        $lang = get_curlang();
        $this->config->set($site, 'admin'.$lang.'_site');
        hook('configInit', $site);

        // 加载当前控制器语言包
        loadlang($this->request->controller());
        // 加载模板语言包
        load_template_lang($this->request->controller());

        // 定位模板位置
        $this->view->config(['view_path'=>$this->config->get('cms.tpl_path').'admin'.DIRECTORY_SEPARATOR.$site['admin_theme'].DIRECTORY_SEPARATOR]);
        if ($this->layout) {
            $this->view->layout('common/'.$this->layout);
        }

        // 设置输出替换
        $this->view->config([
            'tpl_replace_string'=> array_merge(
                config('view.tpl_replace_string'), [
                '__static__'=>$site['static_path'],
                '__libs__'=>$site['cdn'].'/static/libs'
            ])
        ]);
        $this->view->filter(function ($content) {
            $style = '';
            $script = '';
            $result = preg_replace_callback("/{block:(script|style)}[\s\S]*?{\/block:(script|style)}/i", function ($match) use (&$style, &$script) {
                if (isset($match[1]) && in_array($match[1], ['style', 'script'])) {
                    ${$match[1]} .= str_replace(['{block:style}','{/block:style}','{block:script}','{/block:script}'], '', $match[0]);
                }
                return '';
            }, $content);

            $content = preg_replace_callback('/^\s+(\{__STYLE__\}|\{__SCRIPT__\})\s+$/m', function ($matches) use ($style, $script) {
                return $matches[1] == '{__STYLE__}' ? $style : $script;
            }, $result ? $result : $content);
            return $content;
        });

        // 再次获取，以防止config_init发生变更
        $this->view->assign('site', array_diff_key(site(),['mail_password'=>'','mail_user'=>'','mail_from'=>'','mail_server'=>'','mail_port'=>'']));
        $this->view->assign('tempLang', $this->app->lang->get(null));
        // 新页面与弹出框标识
        $this->view->assign('popup', $this->request->get('popup','0'));
        $this->view->assign('User', $this->app->session->get('User'));
        $this->view->assign('Tpl', get_addons_config('template', $site['admin_theme'],'admin'));
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
     * 通用筛选
     * @param string $searchField
     * @param string $joinName
     * @return array
     */
    protected function buildparams($searchField=null, $joinName='')
    {
        $joinName = !empty($joinName) ? $joinName.'.':'';
        // 分页
        $offset = $this->request->param('offset',0,'intval');
        $limit = $this->request->param('limit',10,'intval');

        // 排序
        $order = $this->request->param('order', 'desc');
        $sort = $joinName.$this->request->param('sort','id');
        $sortArr = explode(',',$sort);
        foreach ($sortArr as &$item) {
            $item = stripos($item, ".")===false ? $joinName.trim($item) : $item;
        }
        $sort = implode(',', $sortArr);

        // 快速搜索
        $search = $this->request->param('search','');

        // 获取筛选的数据
        $filter = $this->request->param('filter', '');
        parse_str($filter, $filterArr);

        // 筛选数据的查询表达式
        $exp = $this->request->param('op', '',null);
        $expArr = json_decode($exp, true);

        $map = [];
        if ($search) { // 快速搜索
            $searchField = $searchField ?? $this->searchField;
            $map[] = [$joinName.$searchField, 'like', "%{$search}%"];
        }

        // 是否支持多语言字段
        if ($this->enableLang) {
            $lang = $this->request->param('clang');
            $map[] = ['lang', '=', $lang];
        }

        foreach ($filterArr as $key=>$value) {
            if (empty($value)) {
                continue;
            }

            // =、<>、>、>=、<、<=、LIKE、NOT LIKE、BETWEEN、NOT BETWEEN、IN、NOT IN、EXISTS、NOT EXISTS
            // 其中设定字段null/not null 直接$value 值为 null 和 not null 即可
            $op = !empty($expArr[$key]) ? strtoupper($expArr[$key]) : '=';
            switch ($op) {
                case '=':
                case '<>':
                case 'IN':
                case 'NOT IN':
                case '> TIME':
                case '< TIME':
                case '>= TIME':
                case '<= TIME':
                    $map[] = [$joinName.$key, $op, $value];
                    break;
                case '>':
                case '>=':
                case '<':
                case '<=':
                    $map[] = [$joinName.$key, $op, intval($value)];
                    break;
                case 'LIKE':
                case 'NOT LIKE':
                    $value = trim($value);
                    $map[] = [$joinName.$key, $op, "%{$value}%"];
                    break;
                case 'LIKE %...%':
                case 'NOT LIKE %...%':
                case 'LIKE ...%':
                case 'NOT LIKE ...%':
                case 'LIKE %...':
                case 'NOT LIKE %...':
                    $op_temp = trim(str_replace(['%...%','...%','%...'],'',$op));
                    $value = trim($value);
                    $v = trim(str_replace([$op_temp,'...'],['',$value],$op));
                    $map[] = [$joinName.$key, $op_temp, $v];
                    break;
                case 'BETWEEN':
                case 'NOT BETWEEN':
                case 'BETWEEN TIME':
                case 'NOT BETWEEN TIME':
                    $arr = explode(' - ', $value);
                    if (count($arr)==2) {
                        $map[] = [$joinName.$key, $op, $arr];
                    }
                    break;
                default: break;
            }
        }

        return [$map, $limit, $offset, $order, $sort];
    }

    /**
     * 默认的cms生成表单与字段信息, 可重载
     * @param $categoryInfo
     * @param $row
     */
    protected function buildPage($categoryInfo, $row=null)
    {
        $fieldGroup = Db::name('model_field')->where(['status'=>'normal','admin_auth'=>1,'model_id'=>$categoryInfo['model_id']])->group('field_group')->column('field_group');
        // 获取栏目
        $category_list = (new \app\admin\model\cms\Category)->getModelCategory($categoryInfo['model_id']);
        // 获取字段绑定的栏目
        $modelField = (new \app\admin\model\cms\ModelFieldBind)->getAllowField($categoryInfo['id'], $categoryInfo['model_id']);
        $modelFieldArr = [];
        foreach ($modelField as $key=>$value) {
            if ($value['field_name']=='show_tpl' && !empty($categoryInfo->show_tpl)) {
                $value['default_value'] = $categoryInfo->show_tpl;
            }
            foreach ($fieldGroup as $k=>$v) {
                if ($value['field_group'] == $v) { // 字段分组
                    $modelFieldArr[$v][] = $value;
                }
            }
        }

        // 生成表单
        $this->view->layout(false);
        foreach ($modelFieldArr as $key=>$value) {
            $modelFieldArr[$key] = $this->view->fetch('cms/content/field', ['data'=>$value,'row'=>$row]);
        }
        $this->view->assign(compact('modelFieldArr','category_list','categoryInfo','fieldGroup','row'));
        $this->view->layout(true);
    }

    /**
     * 通用的动态下拉
     * @return \think\response\Json
     */
    protected function selectPage()
    {
        // 模糊查询的字段
        $searchField = $this->request->param('searchField','');
        // 查询字段的值
        $searchFieldValue = implode('', $this->request->param('q_word',[]));
        $page = $this->request->param('pageNumber',1, 'intval');
        $pageSize = $this->request->param('pageSize',10, 'intval');
        // 扩展查询字段
        $custom = $this->request->param('custom',[]);
        $andOr = $this->request->param('andOr','and', 'strtolower');
        $orderBy = $this->request->param('orderBy',[]);

        // 初始化接收的值
        $searchKey = $this->request->param("searchKey", '');
        $searchValue = $this->request->param("searchValue", '');

        $map = [];
        // 初始化查询
        if (!empty($searchKey) && !empty($searchValue)) {
            $map[] = [$searchKey, 'in', $searchValue];
        }
        // 是否支持多语言字段
        if ($this->enableLang) {
            $lang = $this->request->param('lang', $this->contentLang);
            $map[] = ['lang', '=', $lang];
        }

        if (!empty($searchField) && is_array($searchField) && !empty($searchFieldValue)) {  // 模糊查询
            $rep = $andOr=='and' ? '&' : '|';
            $searchStr = implode(',', $searchField);
            $searchStr = str_replace(',', $rep, $searchStr);
            $map[] = [$searchStr, 'like', "%{$searchFieldValue}%"];
        }

        if (is_array($custom) && !empty($custom)) { // 扩展查询字段
            foreach ($custom as $key=>$value) {
                if (!empty($value)) {
                    $map[] = [$key, '=', $value];
                }
            }
        }

        $model = $this->model->where($map);

        // 排序处理
        if (!empty($orderBy)) {
            $order = [];
            foreach ($orderBy as $key=>$value) {
                if (!empty($value[0]) && !empty($value[1])) {
                    $value[0] = strtolower($value[0]);
                    $value[1] = strtolower($value[1]);
                    $value[1] = $value[1]=='asc' ? 'asc' : 'desc';
                    $order[$value[0]] = $value[1];
                }
            }
            if (!empty($order)) {
                $model = $model->order($order);
            }
        }

        $data = $model->page($page,$pageSize)->select();
        $total = $model->count();
        return json(['rows'=>$data->toArray(),'total'=>$total]);
    }
}
