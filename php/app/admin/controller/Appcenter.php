<?php
// +----------------------------------------------------------------------
// | HkCms 应用中心
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\controller;

use app\admin\model\App;
use app\admin\model\routine\Config;
use app\common\services\cache\CacheService;
use app\common\services\config\ConfigService;
use app\common\services\lang\LangService;
use think\addons\AddonsException;
use think\addons\Cloud;
use think\facade\Db;
use think\facade\Log;
use think\facade\Validate;

class Appcenter extends BaseController
{
    /**
     * 权限中间件
     * @var array
     */
    protected $middleware = [
        'login',
        'auth' => ['only'=>['index','online']]
    ];

    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 本地列表
     * @return string|\think\response\Json|void
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $type = $this->request->param('type');

            // 获取安装信息
            $model = App::where(['type'=>$type]);
            if ($type=='template') {
                $module = $this->request->param('module');
                $model = $model->where(['module'=>$module]);
            }
            $data = $model->order('createtime','desc')->select()->toArray();

            // 查出未安装的插件。
            $all = get_addons_info_all($type);
            if (!empty($all)) {
                if (!empty($module)) {
                    $all = $all[$module];
                }
                $newData = [];
                foreach ($all as $key=>$value) {
                    $bl = true;
                    foreach ($data as $k=>&$v) {
                        if ($key==$v['name']) {
                            $bl = false;
                            $v['website'] = $value['website']??'';
                            break;
                        }
                    }
                    if ($bl) {
                        $value['db_install'] = false;
                        $newData[] = $value;
                    }
                }
                $data = array_merge($newData, $data);
            }

            if (!empty($data)) {
                // 根据本地的插件获取线上插件的信息
                $nameArr = [];
                foreach ($data as $key=>$value) {
                    if (!isset($value['name'])) {
                        continue;
                    }
                    if ($value['name']!='default') {
                        $nameArr[] = $value['name'];
                    }

                    // 获取预览图
                    if (empty($value['image']) && $value['type']=='template') {
                        $previewPath = config('cms.tpl_static').$value['module'].DIRECTORY_SEPARATOR.$value['name'].DIRECTORY_SEPARATOR.'preview.jpg';
                        if (is_file($previewPath)) {
                            $data[$key]['image'] = str_replace('\\', '/', '/' . str_replace(public_path(), "", $previewPath));
                        } else {
                            $data[$key]['image'] = '/static/common/image/nopic.png';
                        }
                    } else if (empty($value['image'])) {
                        $data[$key]['image'] = '/static/common/image/nopic.png';
                    }

                    if (empty($data[$key]['config'])) {
                        // 获取配置
                        $data[$key]['config'] = get_addons_config($type, $value['name'], $value['module']??'');
                    }
                }

                $nameArr = implode(',', $nameArr);
                $this->view->assign('applist', $nameArr);
            } else {
                $data = $all;
            }

            $this->view->assign('data', $data);
            $this->view->assign('type', $type);
            $this->view->assign('module', $module??'');
            $this->view->layout(false);
            $html = view('appcenter/local')->getContent();
            $this->success('','',['html'=>$html]);
        }

        // 应用中心登录信息检测
        $configSer = app()->make(ConfigService::class);
        [$cloud_username, $cloud_password] = $configSer->getCloudInfo();
        if (!empty($cloud_username) && !empty($cloud_password) && empty($this->cache->get('cloud_token'))) {
            try {
                Cloud::getInstance()->chekcUser($cloud_username, $cloud_password);
            } catch (AddonsException $exception) {
                Log::error("应用中心自动登录异常：".$exception->getMessage());
            }
        }

        return $this->view->fetch('',['module'=>[],'active'=>'index']);
    }

    // 检测升级
    public function check()
    {
        $applist = $this->request->post('applist','');
        $type = $this->request->post('type','');
        if (empty($applist)||empty($type)) {
            $this->error(__('Parameter %s can not be empty',['']));
        }

        $Info = $this->cache->get('online_data'.$applist.$type);
        if (is_null($Info)) {
            try {
                $Info = Cloud::getInstance()->getInfos(['name'=>$applist,'type'=>$type]);
                $this->cache->set('online_data'.$applist.$type, $Info,3600);
            } catch (\Exception $exception) {
                $Info = [];
            }
        }

        if ($Info) {
            $applist = App::whereIn('name', $applist)->select();
            foreach ($applist as $key=>$v) {
                $version_info = isset($Info[$v['name']]['version_info'])?$Info[$v['name']]['version_info']:'';
                $Info[$v['name']]['upgradeData'] = get_upgrade($v['version'], $version_info);
            }
        }
        $this->success('','',$Info);
    }

    /**
     * 线上账号登录
     */
    public function login()
    {
        // 应用中心登录信息检测
        $username = $this->request->post('username','');
        $password = $this->request->post('password','');
        if (empty($username) || empty($password)) {
            $this->error(__('Please fill in completely'));
        }

        Cloud::getInstance()->chekcUser($username, $password);
        $this->success();
    }

    /**
     * 主题切换
     */
    public function setTheme()
    {
        $name = $this->request->param('name');
        $module = $this->request->param('module');
        if (empty($name) || empty($module)) {
            $this->error(__('Parameter %s can not be empty',['']));
        }

        Db::name('config')->where(['name'=>$module.'_theme'])->update(['value'=>$name]);
        $this->cache->tag('site')->clear();
        $this->cache->delete('get_addons_info_all_template');
        $this->cache->delete('get_addons_info_all_addon');
        hook('themeChange', $module.'_theme');
        $this->success();
    }

    /**
     * 修改主题文件
     * @return string|void
     * @throws \Exception
     */
    public function editTheme()
    {
        $name = $this->request->param('name');
        // $module = $this->request->param('module'); 暂时只支持前台
        $type = $this->request->param('t');
        if (empty($name)) {
            $this->error(__('Parameter %s can not be empty',['name']));
        }
        if (!Validate::is($name, '/^[a-zA-Z][a-zA-Z0-9_]*$/')) {
            $this->error(__('Illegal request'));
        }

        // 修改文件
        if ($this->request->isPost()) {
            // 路径
            $path = $this->request->post('path','');
            $old_path = $this->request->post('old_path','');
            $path = !empty($path) ? str_replace(['.','//',"\\\\",'/','\\','\/'],'/', trim($path) . '/') : '/';
            $old_path = !empty($old_path) ? str_replace(['.','//',"\\\\",'/','\\','\/'],'/', trim($old_path) . '/') : '/';
            $fun = function ($path){
                if (empty($path) || $path=='/') {
                    return false;
                }
                $pathArr = explode('/', rtrim(ltrim($path,'/'),'/'));
                foreach ($pathArr as $key=>$value) {
                    if (!Validate::is($value, 'alphaDash')) {
                        $this->error(__('Illegal request'));
                    }
                }
            };
            $fun($path);
            $fun($old_path);

            // 文件名
            $filename = $this->request->post('filename');
            $filename = !empty($filename) ? basename(trim($filename)) : '';
            if (empty($filename)) {
                $this->error(__('Parameter %s can not be empty',['']));
            }
            $pathinfo = pathinfo($path.$filename);
            $tmp_filename = $pathinfo['filename'];

            // 旧文件名
            $old = $this->request->post('old','');
            $old = basename($old);
            if (!Validate::is($tmp_filename, '/^[A-Za-z0-9\-\_\.]+$/') || (!empty($old) && !Validate::is(pathinfo($old_path.$old)['filename'], '/^[A-Za-z0-9\-\_\.]+$/'))) {
                $this->error(__('Incorrect file name format'));
            }
            // 内容
            $content = $this->request->post('content','',null);

            list($root, $static) = Cloud::getInstance()->getTemplatePath();
            $root = $type=='tpl'?$root.$name:$static.$name;
            if (!preg_match('#^'.(str_replace('\\','/',$root.DIRECTORY_SEPARATOR)).'#i', str_replace('\\','/', $root.$pathinfo['dirname'].DIRECTORY_SEPARATOR.$pathinfo['basename']))) {
                $this->error(__('Permission denied'));
            }
            if (empty($pathinfo['extension']) || !in_array($pathinfo['extension'],['ini','html','json','js','css'])) {
                $this->error(__('Permission denied'));
            }
            if (!empty($content) && $pathinfo['extension']=='html') {
                // 限制html里面的php相关代码提交
                if (preg_match('#<([^?]*)\?php#i', $content) || (preg_match('#<\?#i', $content) && preg_match('#\?>#i', $content))
                    || preg_match('#\{php#i', $content)
                    || preg_match('#\{:phpinfo#i', $content)
                ) {
                    $this->error(__('Warning: The template has PHP syntax. For safety, please upload it after modifying it in the local editing tool'));
                }
            }

            $adapter = new \League\Flysystem\Adapter\Local($root.DIRECTORY_SEPARATOR);
            $filesystem = new \League\Flysystem\Filesystem($adapter);

            try {
                $file = $path.$pathinfo['basename'];
                if (!empty($old_path) && !empty($old)) { // 修改文件

                    if (!$filesystem->has($old_path.$old)) {
                        throw new \Exception(__('%s not exist',[$old_path.$old]));
                    }

                    if ($old==$filename && $old_path==$path) {
                        $filesystem->update($file, $content);
                    } else if ($old!=$filename && $old_path==$path) {
                        if ($filesystem->has($file)) {
                            throw new \Exception(__('%s existed',[$file]));
                        }
                        $filesystem->write($file, $content);
                        $filesystem->delete($old_path.$old);
                    } else {
                        if ($filesystem->has($file)) {
                            throw new \Exception(__('%s existed',[$file]));
                        }
                        $filesystem->write($file, $content);
                        $filesystem->delete($old_path.$old);
                    }
                } else {
                    if ($filesystem->has($file)) {
                        throw new \Exception(__('%s existed',[$file]));
                    }
                    // 新建
                    $filesystem->write($file, $content);
                }
            } catch (\Exception $exception) {
                Log::error("修改模板文件异常：".$exception->getMessage());
                $this->error($exception->getMessage());
            }

            $this->success('','');
        }

        $langs = [];
        $langArr = [];
        $lf = request()->param('lf','');
        if ($type=='lang') {
            list($path, $static) = Cloud::getInstance()->getTemplatePath();
            $langDir = $static.$name.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR;
            $dataList = app()->make(LangService::class)->getListByModule('index');
            if (is_dir($langDir)) {
                foreach ($dataList as $value) {
                    if (!is_file($langDir.$value['mark'].'.json')) {
                        file_put_contents($langDir.$value['mark'].'.json', "{}");
                    }
                    $langs[] = $value['mark'].'.json';
                }
            }
            $langArr = !empty($langs) ? json_decode(file_get_contents($langDir.($lf && in_array($lf,$langs)?$lf:$langs[0])),true) : [];
        }

        $this->view->assign('name',$name);
        $this->view->assign('type',$type);
        $this->view->assign('langs',$langs);
        $this->view->assign('langArr',$langArr);
        $this->view->assign('curLf',$lf);
        $this->view->assign('template','/template/index/'.$name.'/');
        return $this->view->fetch();
    }

    /**
     * 删除主题文件
     */
    public function delThemeFiles()
    {
        $name = $this->request->param('name');
        $type = $this->request->param('t');
        if (empty($name) || empty($type)) {
            $this->error(__('Parameter %s can not be empty',['']));
        }
        if (!Validate::is($name, '/^[a-zA-Z][a-zA-Z0-9_]*$/')) {
            $this->error(__('Addon identification can only be letters, numbers, underscores'));
        }

        list($path, $static) = Cloud::getInstance()->getTemplatePath();

        if ($this->request->isPost()) { // 获取文件内容
            $path = $type=='tpl'?$path:$static;
            $fn = $this->request->post('file');
            $dir = $this->request->post('dir');
            if (empty($fn)) {
                $this->error(__('Parameter %s can not be empty',['']));
            }
            $fn = basename($fn);
            if ($fn=='info.ini') {
                $this->error(__('info.ini file must'));
            }
            if (!empty($dir) && is_array($dir)) {
                foreach ($dir as $key=>$value) {
                    if (!Validate::is($value, 'alphaDash')) {
                        $this->error(__('Illegal request'));
                    }
                }
                $read = implode(DIRECTORY_SEPARATOR, $dir).DIRECTORY_SEPARATOR.$fn;
                $file = $path.$name.DIRECTORY_SEPARATOR.$read;
            } else {
                $file = $path.$name.DIRECTORY_SEPARATOR.$fn;
                $read = $fn;
            }
            $file = realpath($file);
            if ($file===false) {
                $this->error(__('%s not exist',[$file]));
            }
            if (!preg_match('#^'.(str_replace('\\','/',$path.$name.DIRECTORY_SEPARATOR)).'#i', str_replace('\\','/',$file))) {
                $this->error(__('Illegal request'));
            }
            $fileInfo = pathinfo($file);
            if (!in_array($fileInfo['extension'],['ini','html','json','js','css'])) {
                $this->error(__('Unsupported file suffix'));
            }
            // 限制读取范围
            $adapter = new \League\Flysystem\Adapter\Local($path.$name.DIRECTORY_SEPARATOR);
            $filesystem = new \League\Flysystem\Filesystem($adapter);
            try {
                $filesystem->delete($read);
            } catch (\Exception $exception) {
                Log::error("删除模板文件异常：".$exception->getMessage());
                $this->error($exception->getMessage());
            }
            $this->success();
        }
    }

    /**
     * 获取主题文件
     */
    public function getThemeFiles()
    {
        $name = $this->request->param('name');
        $type = $this->request->param('t');
        if (empty($name) || empty($type)) {
            $this->error(__('Parameter %s can not be empty',['']));
        }
        if (!Validate::is($name, '/^[a-zA-Z][a-zA-Z0-9_]*$/')) {
            $this->error(__('Addon identification can only be letters, numbers, underscores'));
        }

        list($path, $static) = Cloud::getInstance()->getTemplatePath();

        if ($this->request->isPost()) { // 获取文件内容
            $path = $type=='tpl'?$path:$static;
            $fn = $this->request->post('fn');
            $dir = $this->request->post('dir');
            if (empty($fn)) {
                $this->error(__('Parameter %s can not be empty',['']));
            }
            $fn = basename($fn);
            if (!empty($dir) && is_array($dir)) {
                foreach ($dir as $key=>$value) {
                    if (!Validate::is($value, 'alphaDash')) {
                        $this->error(__('Illegal request'));
                    }
                }
                $read = implode(DIRECTORY_SEPARATOR, $dir).DIRECTORY_SEPARATOR.$fn;
                $file = $path.$name.DIRECTORY_SEPARATOR.$read;
            } else {
                $file = $path.$name.DIRECTORY_SEPARATOR.$fn;
                $read = $fn;
            }

            $file = realpath($file);
            if ($file===false) {
                $this->error(__('%s not exist',[$file]));
            }
            if (!preg_match('#^'.(str_replace('\\','/',$path.$name.DIRECTORY_SEPARATOR)).'#i', str_replace('\\','/',$file))) {
                $this->error(__('Permission denied'));
            }
            $fileInfo = pathinfo($file);
            if (!in_array($fileInfo['extension'],['ini','html','json','css','js'])) {
                $this->error(__('Permission denied'));
            }

            // 限制读取范围
            $adapter = new \League\Flysystem\Adapter\Local($path.$name.DIRECTORY_SEPARATOR);
            $filesystem = new \League\Flysystem\Filesystem($adapter);
            try {
                $response = $filesystem->read($read);
            } catch (\Exception $exception) {
                $this->error($exception->getMessage());
            }
            $this->success('','',['content'=>$response]);
        }

        if ($type=='tpl') {
            $path = $path.$name.DIRECTORY_SEPARATOR;
            $arr = \think\addons\Dir::instance()->getJsTreeTpl($path,'ini,html,json,css,js');
            $this->success('', '', $arr);
        } else{
            $path = $static.$name.DIRECTORY_SEPARATOR;
            $arr = \think\addons\Dir::instance()->getJsTreeTpl($path,'json,css,js');
            $this->success('', '', $arr);
        }
    }

    /**
     * 修改语言包
     */
    public function editLang()
    {
        $row = $this->request->post('row');
        $file = $this->request->param('file');
        $name = $this->request->param('name');
        if (empty($name)) {
            $this->error(__('Parameter %s can not be empty',['name']));
        }
        if (!Validate::is($name, '/^[a-zA-Z][a-zA-Z0-9_]*$/')) {
            $this->error(__('Addon identification can only be letters, numbers, underscores'));
        }

        list($path, $static) = Cloud::getInstance()->getTemplatePath();
        $langDir = $static.$name.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR;
        $file = basename(trim($file));
        if (!is_file($langDir.$file)) {
            $this->error(__('%s not exist',[$langDir.$file]));
        }
        $tmp = explode('.', $file);
        if (empty($tmp) || count($tmp)!=2 || $tmp[1]!='json') {
            $this->error(__('Incorrect file name format'));
        }

        $json = [];
        $keys = [];
        foreach ($row as $key=>$value) {
            $keys[] = $value;
            if (($key+1)%2==0) {
                $json[$keys[$key-1]] = $value;
            } else {
                $json[$value] = '';
            }
        }

        $content = empty($json) ? '' : json_encode($json, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

        $adapter = new \League\Flysystem\Adapter\Local($langDir);
        $filesystem = new \League\Flysystem\Filesystem($adapter);
        try {
            $filesystem->update($file, $content);
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
        $this->success();
    }

    /**
     * 新增语言包
     */
    public function addLang()
    {
        $name = $this->request->param('name');
        if (empty($name)) {
            $this->error(__('Parameter %s can not be empty',['name']));
        }
        if (!Validate::is($name, '/^[a-zA-Z][a-zA-Z0-9_]*$/')) {
            $this->error(__('Addon identification can only be letters, numbers, underscores'));
        }

        list($path, $static) = Cloud::getInstance()->getTemplatePath();
        $langDir = $static.$name.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR;

        $dataList = Db::name('config')->where(['name'=>'index_lang'])->value('data_list');
        $dataList = json_decode($dataList, true);

        $adapter = new \League\Flysystem\Adapter\Local($langDir);
        $filesystem = new \League\Flysystem\Filesystem($adapter);
        try {
            foreach ($dataList as $key=>$value) {
                if (!file_exists($langDir.$key.'.json')) {
                    $filesystem->write($key.'.json','');
                }
            }
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
        $this->success();
    }

    /**
     * 插件配置
     * @return string|void
     */
    public function setConfig()
    {
        $name = $this->request->param('name');
        $type = $this->request->param('type','');
        $module = $this->request->param('module','');
        if (empty($name)) {
            $this->error(__('Parameter %s can not be empty',['name']));
        }
        if (!Validate::is($name, '/^[a-zA-Z][a-zA-Z0-9_]*$/')) {
            $this->error(__('Addon identification can only be letters, numbers, underscores'));
        }

        $addonInfo = App::where(['name'=>$name,'type'=>$type])->find();
        if (empty($addonInfo)) {
            $this->error(__('Addon %s not installed', [$name]));
        }

        $data = get_addons_config($type, $name, $module, true); // 获取整个配置文件
        if (empty($data)) {
            die(__('The configuration file returned an empty value, please check if your configuration file is normal!'));
        }

        $this->view->assign('sdata', $data); // 写入源数据
        $this->view->assign('addonInfo', $addonInfo);

        if ($this->request->isPost()) {
            $post = $this->request->post('row');
            if (empty($post)) {
                $this->error(__('Parameter %s can not be empty',['']));
            }

            foreach ($data as $key=>&$value) {
                if (!empty($value['item'])) { // 分组写法
                    if (isset($post[$key])) {
                        foreach ($value['item'] as $k=>&$v) {
                            if (isset($post[$key][$k])) {
                                if ($v['type']=='array') {
                                    $v['value'] = !empty($post[$key][$k]) ? json_decode($post[$key][$k], true) : '';
                                } else if (is_array($post[$key][$k])) {
                                    $v['value'] = implode(',', $post[$key][$k]);
                                } else {
                                    $v['value'] = $post[$key][$k];
                                }
                            }
                        }
                    }
                } else {
                    if (isset($post[$key])) {
                        if ($value['type']=='array') {
                            $value['value'] = !empty($post[$key]) ? json_decode($post[$key], true) : '';
                        } else if (is_array($post[$key])) {
                            $value['value'] = implode(',', $post[$key]);
                        } else {
                            $value['value'] = $post[$key];
                        }
                    }
                }
            }

            App::where(['name'=>$name,'type'=>$type])->save(['config'=>json_encode($data)]);
            if ($type=='template') {
                $k = "template_{$name}_config".$module;
            } else {
                $k = "addon_{$name}_config";
            }
            $this->app->cache->delete($k);

            hook($name.'ConfigSave', $data);
            $this->success();
        }

        // 生成表单
        $this->view->layout(false);
        foreach ($data as $key=>$value) {
            if (empty($value['item'])) { // 一级
                $data = $this->view->fetch('appcenter/field', ['data'=>$data,'group'=>'']);
                break;
            } else {
                $data[$key]['item'] = $this->view->fetch('appcenter/field', ['data'=>$value['item'],'group'=>$key]);
            }
        }

        $this->view->layout(true);
        $this->view->assign('data', $data);
        $this->view->assign('module', $module);

        $path = $type=='template'?config('cms.tpl_path'). $module . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . 'config.html':app('addons')->getAddonsPath().$name.DIRECTORY_SEPARATOR.'config.html';
        return is_file($path) ? $this->view->fetch($path) : $this->view->fetch();
    }

    /**
     * 安装本地插件，非上传的形式
     */
    public function installDb()
    {
        $name = $this->request->param('name');
        $type = $this->request->param('type');
        $module = $this->request->param('module');
        if (empty($name)) {
            $this->error(__('Parameter %s can not be empty',['name']));
        }
        if (!Validate::is($name, '/^[a-zA-Z][a-zA-Z0-9_]*$/')) {
            $this->error(__('Addon identification can only be letters, numbers, underscores'));
        }
        if (empty($type)) {
            $this->error(__('Parameter %s can not be empty',['type']));
        }
        if ($type=='template' && empty($module)) {
            $this->error(__('Parameter %s can not be empty',['module']));
        }

        // 获取信息
        $info = get_addons_info($name, $type, $module);
        if (empty($info)) {
            $this->error(__('The content of the info.ini file is not in the correct format'));
        }

        // 检查是否已经安装
        $one = App::where(['name'=>$name])->find();
        if (!empty($one)) {
            $this->error(__('Addon %s is installed',[$name]));
        }

        if ($this->request->isPost()) { // 安装确认页
            // 录入数据库
            $info['status'] = 1;
            $info['type'] = $type;

            $demodata = $this->request->param('demodata','','intval');
            $force = $this->request->param('force',0);
            Db::startTrans();
            try {
                (new App)->addInstall($info);
                if ($type!='template') {
                    $obj = get_addons_instance($name);
                    if (!empty($obj)) { // 调用插件安装
                        if (isset($obj->menu)) {
                            // 自动导入菜单
                            create_menu($obj->menu,$info['name']);
                        }
                        $obj->install();
                    }
                    Cloud::getInstance()->importSql($name);
                    Cloud::getInstance()->enable($name, $force==1);
                    $demodataFile = app()->addons->getAddonsPath().$name.DIRECTORY_SEPARATOR.'demodata.sql';
                } else {
                    // 模板安装
                    list($templatePath, $staticPath) = Cloud::getInstance()->getTemplatePath($module);
                    if (is_dir($templatePath.$name.DIRECTORY_SEPARATOR.'static'.DIRECTORY_SEPARATOR)) { // 有模板静态资源的情况移动到public/static/module
                        $staticAppPath = $staticPath . $info['name'] . DIRECTORY_SEPARATOR;  // 模板静态安装路径
                        $dir = \think\addons\Dir::instance();
                        $bl = $dir->movedFile($templatePath.$name.DIRECTORY_SEPARATOR.'static'.DIRECTORY_SEPARATOR, $staticAppPath);
                        if ($bl===false) {
                            throw new AddonsException($dir->error);
                        }
                    }
                    $demodataFile = $templatePath.$name.DIRECTORY_SEPARATOR.'demodata.sql';
                }
                if ($demodata==1) { // 导入演示数据
                    if (is_file($demodataFile)) {
                        create_sql($demodataFile);
                    }
                }
                Db::commit();
            } catch (AddonsException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }

            app()->cache->clear();
            $this->success(__('Installation successful'));
        } else if (!$this->request->isAjax() && $this->request->isGet()) {
            $info = Cloud::getInstance()->checkInstall($info,$type,$module);
            $this->view->assign('info', $info);
            return $this->view->fetch('install');
        }

        $this->success('','',[
            'url'=>(string)url('/Appcenter/installDb',compact('name','type','module')),
            'title'=>$info['title']
        ]);
    }

    /**
     * 上传安装包，离线安装
     */
    public function local()
    {
        $type = $this->request->param('type');
        $file = $this->request->param('file');
        if (config('cms.appcenter_local_open')!=true) {
            $this->error(__('Locally installed plugins are not enabled, go to config/cms.php and enable `appcenter_local_open=true`'));
        }

        if ($file) {
            $file = session($this->user->id.'_'.$file);
            if ($this->request->isPost()) {
                $demodata = $this->request->param('demodata','','intval');
                $force = $this->request->param('force',0);
                $info = Cloud::getInstance()->installLocal($type, $file, $demodata, $force==1);
                $info['type'] = $type;
                (new App)->addInstall($info);
                app()->cache->clear();
                $this->success(__('Installation successful'),'', []);
            } else {
                // 读取info文件
                $info = Cloud::getInstance()->checkIni($type, $file);
                $info = Cloud::getInstance()->checkInstall($info, $type,'',$file);
                $this->view->assign('info', $info);
                return $this->view->fetch('install');
            }
        } else {
            $files = $this->request->file('files');
            if (empty($type)) {
                $this->error(__('Parameter %s can not be empty',['type']));
            }
            if (empty($files) || empty($files[0])) {
                $this->error(__('No files uploaded'));
            }
            $value = $files[0];

            try {
                validate(
                    [
                        'files' => [
                            // 限制文件大小(单位b)
                            'fileSize' => site('file_size'),
                            // 限制文件后缀，多个后缀以英文逗号分割
                            'fileExt'  => 'zip'
                        ]
                    ], [
                        'files.fileSize' => __('File cannot exceed %s',[(site('file_size')/1024/1024).'MB']),
                        'files.fileExt' => __('Only supports zip files'),
                    ]
                )->check(['files'=>$value]);

                // 保存文件
                $file = md5(date('Y-m')).'.zip';
                $dir = app()->getRuntimePath() . 'storage';
                if (!file_exists($dir)) {
                    @mkdir($dir);
                }
                $value->move(app()->getRuntimePath() . 'storage', $file);
                $file = $dir.DIRECTORY_SEPARATOR.$file;
            } catch (\think\exception\ValidateException $e) {
                $this->error($e->getMessage());
            }

            // 解压
            $filename = basename($file, '.zip');
            $path = dirname($file).DIRECTORY_SEPARATOR.$filename . DIRECTORY_SEPARATOR;
            @mkdir($path);
            (new \PhpZip\ZipFile())->openFile($file)->extractTo($path);
            // 读取info文件
            $info = Cloud::getInstance()->checkIni($type, $path);

            // 检查是否已经安装
            $one = App::where(['name'=>$info['name']])->find();
            if (!empty($one)) {
                $this->error(__('Addon %s is installed',[$info['name']]));
            }

            session($this->user->id.'_'.$filename, $path);
            $this->success('','', [
                'url'=>(string)url('/Appcenter/local',['type'=>$type,'file'=>$filename]),
                'title'=>$info['title']
            ]);
        }
    }

    /**
     * 插件打包
     * @throws \think\addons\AddonsException
     */
    public function pack()
    {
        $type = $this->request->param('type');
        $name = $this->request->param('name');
        if (empty($name)) {
            $this->error(__('Parameter %s can not be empty',['name']));
        }
        if (!Validate::is($name, '/^[a-zA-Z][a-zA-Z0-9_]*$/')) {
            $this->error(__('Addon identification can only be letters, numbers, underscores'));
        }

        if ('addon'==$type) {
            $info = get_addons_info($name);
            if (empty($info)) {
                $this->error(__('%s not exist',['info.ini']));
            }
            Cloud::getInstance()->pack($this->app->addons->getAddonsPath().$name.DIRECTORY_SEPARATOR);
        } else if ('template'==$type) {
            $module = $this->request->param('module');
            $info = get_addons_info($name,'template',$module);
            if (empty($info)) {
                $this->error(__('%s not exist',['info.ini']));
            }

            list($addonsPath, $staticPath) = Cloud::getInstance()->getTemplatePath($module);
            $addonsPath = $addonsPath.$name.DIRECTORY_SEPARATOR;
            $staticPath = $staticPath.$name.DIRECTORY_SEPARATOR;

            $zipFile = new \PhpZip\ZipFile();
            $zipFile = $zipFile->addDirRecursive($addonsPath); // 包含下级，递归
            if (is_dir($staticPath)) {
                $zipFile = $zipFile->addEmptyDir('static')->addDirRecursive($staticPath, 'static');
            }
            $zipFile->outputAsAttachment($name.'.zip'); // 直接输出到浏览器
        }
    }

    /**
     * 导出整站源码
     */
    public function packCode()
    {
        $name = $this->request->param('name');
        $newname = $this->request->param('newname');
        if (empty($name)) {
            $this->error(__('Parameter %s can not be empty',['name']));
        }
        if (empty($newname)) {
            $this->error(__('Parameter %s can not be empty',['newname']));
        }
        if (!Validate::is($newname, '/^[a-zA-Z][a-zA-Z0-9_]*$/')) {
            $this->error(__('Addon identification can only be letters, numbers, underscores'));
        }
        $tInfo = get_addons_info($name, 'template', 'index');
        if (empty($tInfo)) {
            $this->error(__('%s not exist',['info.ini']));
        }
        $tInfo['name'] = $newname;
        $tInfo['type'] = 'module';
        write_addons_info(runtime_path().'info.ini', $tInfo);

        // 导出数据库保存位置
        $filename = runtime_path().'install.sql';
        @unlink($filename);
        // 数据库配置
        $dbConfig = Db::getConfig('connections.mysql');

        // 导出前的重置数据
        $eTable = [$dbConfig['prefix'].'admin_log',$dbConfig['prefix'].'admin', $dbConfig['prefix'].'ems', $dbConfig['prefix'].'admin_panel']; // 排除的表
        $data = Db::name('model')->where(['controller'=>'Guestbook'])->select();
        foreach ($data as $key=>$value) {
            $eTable[] = $dbConfig['prefix'].$value['tablename'];
        }

        // 导出操作
        $list = Db::query('SHOW TABLE STATUS');
        $fp = @fopen($filename, 'w');
        foreach ($list as $key=>$value) {
            $result = Db::query("SHOW CREATE TABLE `{$value['Name']}`");
            $sql = "\n\nDROP TABLE IF EXISTS `{$value['Name']}`;\n";
            $sql .= trim($result[0]['Create Table']) . ";\n\n";
            if (false === @fwrite($fp, $sql)) {
                continue;
            }

            //备份数据记录
            if (in_array($value['Name'], $eTable)) {
                continue;
            }
            $result = Db::query("SELECT * FROM `{$value['Name']}`");
            foreach ($result as $row) {
                if ($value['Name'] == $dbConfig['prefix'].'app' && ($row['type']=='template' && $row['module']=='index' && $name!=$row['name'])) {
                    continue;
                }
                //if ($value['Name'] == $dbConfig['prefix'].'app' && $row['type']=='template' && $name==$row['name']) {
                //    $row['name'] = $newname;
                //}
                if ($value['Name'] == $dbConfig['prefix'].'config' && $row['name']=='cloud_username') {
                    $row['value'] = '';
                }
                if ($value['Name'] == $dbConfig['prefix'].'config' && $row['name']=='cloud_password') {
                    $row['value'] = '';
                }
                //if ($value['Name'] == $dbConfig['prefix'].'config' && $row['name']=='index_theme') {
                //    $row['value'] = $newname;
                //}

                foreach($row as &$v){
                    //将数据中的单引号转义，否则还原时会出错
                    if (is_null($v)) {
                        $v = '--null--';
                    } else if(is_string($v)) {
                        $v = addslashes($v);
                    }
                }

                $sql = "INSERT INTO `{$value['Name']}` VALUES ('" . str_replace(array("\r","\n"),array('\r','\n'),implode("', '", $row)) . "');\n";
                $sql = str_replace("'--null--'",'null', $sql);
                if (false === @fwrite($fp, $sql)) {
                    continue;
                }
            }
        }
        @fclose($fp);

        $zf = new \PhpZip\ZipFile();
        $zf->addEmptyDir('runtime');
        // 添加数据库文件
        $zf->addFile($filename);
        // 添加.env文件
        $env = runtime_path().'.env';
        @unlink($env);
        $fp = @fopen(runtime_path().'.env', 'w');
        @fwrite($fp, 'APP_DEBUG = false');
        @fclose($fp);
        $zf->addFile($env);
        // 获取项目根目录文件
        $lists = scandir(root_path());
        foreach ($lists as $key=>$value) {
            if ($value=='.' || $value=='..' || $value=='.git' || $value=='.idea' || $value=='runtime' || $value=='.env' || $value=='.gitignore' || $value=='.user.ini') {
                continue;
            }
            // 模板排除
            if ($value=='template') {
                $zf->addEmptyDir('template');
                $templates = scandir(root_path('template'));
                $tplPath = root_path('template');
                foreach ($templates as $k=>$v) {
                    if ($v=='.' || $v=='..') {
                        continue;
                    }
                    if ($v!='index' && is_dir($tplPath.$v)) {
                        $zf->addDirRecursive($tplPath.$v,'/template/'.$v);
                    } else {
                        //$zf->addDirRecursive($tplPath.$v.'/'.$name,'/template/'.$v.'/'.$newname);
                        $zf->addDirRecursive($tplPath.$v.'/'.$name,'/template/'.$v.'/'.$name);
                    }
                }
                continue;
            }
            // 模板静态文件排除
            if ($value=='public') {
                $zf->addEmptyDir('public');
                $templates = scandir(root_path('public'));
                $tplPath = root_path('public');
                foreach ($templates as $k=>$v) {
                    if ($v=='.' || $v=='..') {
                        continue;
                    }

                    if ($v=='static') {
                        $statics = scandir($tplPath.'static');
                        $staticPath = $tplPath.'static'.'/';
                        foreach ($statics as $kk=>$vv) {
                            if ($vv=='.' || $vv=='..') {
                                continue;
                            }

                            if ($vv=='module') {
                                $modules = scandir($staticPath.'module');
                                $modulePath = $staticPath.'module'.'/';
                                foreach ($modules as $kkk=>$vvv) {
                                    if ($kkk=='.' || $vvv=='..') {
                                        continue;
                                    }

                                    if ($vvv!='index' && is_dir($modulePath.$vvv)) {
                                        $zf->addDirRecursive($modulePath.$vvv,'/public/static/module/'.$vvv);
                                    } else {
                                        //$zf->addDirRecursive($modulePath.$vvv.'/'.$name,'/public/static/module/index/'.$newname);
                                        $zf->addDirRecursive($modulePath.$vvv.'/'.$name,'/public/static/module/index/'.$name);
                                    }
                                }
                                continue;
                            }

                            if (is_dir($staticPath.$vv)) {
                                $zf->addDirRecursive($staticPath.$vv,'/public/static/'.$vv);
                            } else {
                                $zf->addFile($staticPath.$vv,'/public/static/'.$vv);
                            }
                        }
                        continue;
                    }
                    if ($v=='.user.ini') {
                        continue;
                    }
                    if (is_dir($tplPath.$v)) {
                        $zf->addDirRecursive($tplPath.$v,'/public/'.$v);
                    } else {
                        $zf->addFile($tplPath.$v,'/public/'.$v);
                    }
                }
                continue;
            }
            if (is_dir(root_path().$value)) {
                $zf->addDirRecursive(root_path($value),'/'.$value);
            } else {
                $zf->addFile(root_path().$value);
            }
        }

        // 覆盖ini文件
        $zf->addFile(runtime_path().'info.ini','/info.ini');

        // 添加安装文件
        $zf->addFile($this->app->getBasePath().'common/tpl/install.php','public/install.php');
        // 删除安装标识
        if (is_file(root_path().'app/install/install.lock')) {
            $zf->deleteFromName('app/install/install.lock');
        }
        $zf->outputAsAttachment('整站源码_应用标识_'.$newname.'.zip');
    }

    /**
     * 在线应用列表
     */
    public function online()
    {
        if (!$this->config->get('cms.appcenter')) { // 是否开启应用中心(/config/cms.php)
            $this->error(__('Application center is closed'),'','',0);
        }

        if ($this->request->isAjax()) {
            $param = $this->request->param('param');
            parse_str($param, $arr);
            if (empty($arr['type'])) {
                $this->error(__('Parameter %s can not be empty',['type']));
            }
            $list = Cloud::getInstance()->getList($arr);
            if (!empty($list['row'])) {
                foreach ($list['row'] as $key=>$value) {
                    $one = App::where(['name'=>$value['name']])->find();
                    if (!empty($one)) {
                        // 已安装
                        $list['row'][$key]['is_install'] = 1;
                        $list['row'][$key]['installVersion'] = $one['version'];
                        $list['row'][$key]['status'] = $one['status'];
                        $list['row'][$key]['upgradeData'] = get_upgrade($one['version'], $value['version']);
                    } else {
                        // 未安装
                        $list['row'][$key]['is_install'] = 0;
                    }
                }
            }

            $html = $this->fetch('online_list',['data'=>$list,'param'=>$arr],false);
            $this->success('','',['html'=>$html,'total'=>$list['total']??0,'page'=>$list['page']??'']);
        } else if ($this->request->isPost()) { // 登录
            $username = $this->request->param('username');
            $password = $this->request->param('password');
            $remember = $this->request->param('remember','');
            if (empty($username) || empty($password)) {
                $this->error('请填写完整');
            }

            try {
                Cloud::getInstance()->chekcUser($username, $password);
            } catch (AddonsException $exception) {
                $this->error($exception->getMessage());
            }

            if ($remember) {    // 记住账号密码
                $configSer = app()->make(ConfigService::class);
                $configSer->saveCloudInfo($username, $password);
            }
            return redirect((string)url('/appcenter/online'));
        }

        // 应用中心登录信息检测
        $configSer = app()->make(ConfigService::class);
        [$cloud_username, $cloud_password] = $configSer->getCloudInfo();
        if ((empty($cloud_username) || empty($cloud_password)) && empty($this->cache->get('cloud_token'))) {
            return $this->fetch('login', [], false);
        } else if ((!empty($cloud_username) && !empty($cloud_password)) && empty($this->cache->get('cloud_token'))) {
            try {
                Cloud::getInstance()->chekcUser($cloud_username, $cloud_password);
            } catch (AddonsException $exception) {
                return $this->fetch('login',['msg'=>'错误信息：'.$exception->getMessage()], false);
            }
        }
        $this->view->assign('ac_user', $this->cache->get('cloud_token'));

        return $this->fetch('online');
    }

    /**
     * 在线安装
     * @throws \think\addons\AddonsException
     */
    public function install()
    {
        if ($this->request->isPost()) {
            $name = $this->request->param('name');
            $type = $this->request->param('type');
            $demodata = $this->request->param('demodata','','intval');
            $force = $this->request->param('force',0);

            $dir = Cloud::getInstance()->getCloudTmp().$name.DIRECTORY_SEPARATOR;
            $info = Cloud::getInstance()->checkIni($type, $dir);

            Cloud::getInstance()->install($info, $dir, $demodata, $force==1);
            (new App)->addInstall($info);
            $this->app->cache->tag('addons')->clear();
            \think\facade\Cache::delete('hooks');
            $this->success(__('Installation successful'),'', []);
        } else if ($this->request->isAjax()) {
            $name = $this->request->param('name');
            $version = $this->request->param('version','');
            if (empty($name)) {
                $this->error(__('Parameter %s can not be empty',['name']));
            }
            if (!Validate::is($name, '/^[a-zA-Z][a-zA-Z0-9_]*$/')) {
                $this->error(__('Addon identification can only be letters, numbers, underscores'));
            }

            $info = Cloud::getInstance()->getInfo(['name'=>$name, 'version'=>$version]);
            // 版本信息判断
            if (!version_compare($this->config->get('ver.cms_version'),$info['version']['cms_version'], $info['version']['cms_version_op'])) {
                $this->error(__('The HkCms supported by this app are %s',["{$info['version']['cms_version_op']} {$info['version']['cms_version']}"]));
            }

            // 下载文件解压
            Cloud::getInstance()->download($name, $version);
            Cloud::getInstance()->unzip($name);

            $this->success('','',[
                'url'=>(string)url('/Appcenter/install',['name'=>$name,'type'=>$info['type']]),
                'title'=>$info['title']
            ]);
        } else {
            $name = $this->request->param('name');
            $type = $this->request->param('type');

            $dir = Cloud::getInstance()->getCloudTmp().$name.DIRECTORY_SEPARATOR;
            $info = Cloud::getInstance()->checkIni($type, $dir);
            $info = Cloud::getInstance()->checkInstall($info, $type,'',$dir);
            $this->view->assign('info', $info);
            return $this->view->fetch('install');
        }
    }

    /**
     * 版本在线/本地更新
     * @throws \think\addons\AddonsException
     */
    public function upgrade()
    {
        if ($this->request->isPost()) {
            $name = $this->request->param('name');
            $type = $this->request->param('type');
            $demodata = $this->request->param('demodata','','intval');
            $version = $this->request->param('version','');

            // 读取ini文件
            $dir = Cloud::getInstance()->getCloudTmp().$name.DIRECTORY_SEPARATOR;
            $info = Cloud::getInstance()->checkIni($type, $dir);
            // 获取线上信息
            $_info = Cloud::getInstance()->getInfo(['name'=>$name, 'version'=>$version]);
            // 校验下载的版本与线上的是否一致
            if (!isset($_info['version']['version']) || $info['version'] != $_info['version']['version']) {
                $this->error(__('The downloaded version information is inconsistent with the online version'));
            }

            $addonInfo = App::where(['name'=>$info['name']])->find();
            if (empty($addonInfo)) {
                $this->error(__('Addon %s not installed', [$info['name']]));
            }
            $info['status'] = $addonInfo['status'];

            Cloud::getInstance()->upgrade($info,$dir,$demodata);
            (new App)->editInstall($_info);
            $this->app->cache->tag('addons')->clear();
            \think\facade\Cache::delete('hooks');
            $this->success(__('Installation successful'));
        } else if ($this->request->isAjax()) {
            $name = $this->request->param('name');
            $version = $this->request->param('version','');
            if (empty($name)) {
                $this->error(__('Parameter %s can not be empty',['name']));
            }
            if (!Validate::is($name, '/^[a-zA-Z][a-zA-Z0-9_]*$/')) {
                $this->error(__('Addon identification can only be letters, numbers, underscores'));
            }

            $info = Cloud::getInstance()->getInfo(['name'=>$name, 'version'=>$version]);

            $addonInfo = App::where(['name'=>$info['name']])->find();
            if (empty($addonInfo)) {
                $this->error(__('Addon %s not installed', [$info['name']]));
            }
            $info['status'] = $addonInfo['status'];

            // 版本信息判断
            if (!version_compare($this->config->get('ver.cms_version'),$info['version']['cms_version'], $info['version']['cms_version_op'])) {
                $this->error(__('The HkCms supported by this app are %s',["{$info['version']['cms_version_op']} {$info['version']['cms_version']}"]));
            }
            if (empty($addonInfo['version'])) {
                $this->error(__('Failed to get version information'));
            }
            // 插件版本信息判断
            if (!version_compare($info['version']['version'], $addonInfo['version'],'>')) {
                $this->error(__('Note that the installed version: %s',[$addonInfo['version']]));
            }

            // 下载文件解压
            Cloud::getInstance()->download($name, $version);
            Cloud::getInstance()->unzip($name);

            $this->success('','',[
                'url'=>(string)url('/Appcenter/upgrade',['name'=>$name,'type'=>$info['type'],'version'=>$version]),
                'title'=>$info['title']
            ]);
        } else {
            $name = $this->request->param('name');
            $type = $this->request->param('type');
            $addonInfo = App::where(['name'=>$name])->find();
            if (empty($addonInfo)) {
                $this->error(__('Addon %s not installed', [$name]));
            }

            $dir = Cloud::getInstance()->getCloudTmp().$name.DIRECTORY_SEPARATOR;
            $info = Cloud::getInstance()->checkIni($type, $dir);
            $info = Cloud::getInstance()->checkInstall($info, $type,'',$dir);
            $this->view->assign('info', $info);
            $this->view->assign('addonInfo', $addonInfo);
            $this->view->assign('update', 1);
            return $this->view->fetch('install');
        }
    }

    /**
     * 在线启用禁用
     * @throws \think\addons\AddonsException
     */
    public function enable()
    {
        if ($this->request->isAjax()) {
            $name = $this->request->param('name');
            $value = $this->request->param('value');
            if (empty($name)) {
                $this->error(__('Parameter %s can not be empty',['name']));
            }
            if (!Validate::is($name, '/^[a-zA-Z][a-zA-Z0-9_]*$/')) {
                $this->error(__('Addon identification can only be letters, numbers, underscores'));
            }
            if ($value!=-1 && $value!=1) {
                $this->error(__('Illegal request'));
            }

            $addonInfo = App::where(['name'=>$name])->find();
            if (empty($addonInfo)) {
                $this->error(__('Addon %s not installed', [$addonInfo['name']]));
            }

            if ($addonInfo['status']==$value) {
                // 状态一致无需更改
                $this->success();
            }

            if ($value==1) { // 启用
                Cloud::getInstance()->enable($name);
                App::where(['name'=>$name])->save(['status'=>1]);
                status_menu(1, $name); // 启用菜单
                $this->app->cache->tag('addons')->clear();
                \think\facade\Cache::delete('hooks');
            } else { // 禁用
                Cloud::getInstance()->disable($name);
                App::where(['name'=>$name])->save(['status'=>-1]);
                status_menu(0, $name); // 禁用菜单
                $this->app->cache->tag('addons')->clear();
                \think\facade\Cache::delete('hooks');
            }

            $this->success();
        }
        $this->error(__('Illegal request'));
    }

    /**
     * 在线/本地卸载
     */
    public function uninstall()
    {
        if ($this->request->isAjax()) {
            $name = $this->request->param('name','');
            $type = $this->request->param('type','');
            $module = $this->request->param('module','');

            if (!Validate::is($name, '/^[a-zA-Z][a-zA-Z0-9_]*$/')) {
                $this->error(__('Addon identification can only be letters, numbers, underscores'));
            }
            if (empty($name) || ($type!='template' && $type!='addon' && $type!='module') || ($type=='template' && empty($module))) {
                $this->error(__('Parameter %s can not be empty',['']));
            }

            // 获取已安装的插件信息
            $addonInfo = App::where(['type'=>$type,'name'=>$name])->find();
            if (empty($addonInfo)) {
                $this->error(__('Addon %s not installed', [$name]));
            }

            if ('template'==$type) { // 卸载模板的情况，检测是否已经设置为主题了
                $config = site();
                $theme = $config[$module.'_theme'] ?? '';
                if ($theme==$name) {
                    $this->error(__('The current template has been set as the theme! Please cancel and try again~'));
                }
            } else {
                if ($addonInfo['status']==1) {
                    $this->error(__('Please disable the plugin and try again'));
                }
            }

            Cloud::getInstance()->uninstall(['name'=>$name,'type'=>$type,'module'=>$module]);
            if ($type!='template') {
                // 删除菜单
                del_menu($name);
            }
            App::where(['name'=>$name])->delete();
            $this->app->cache->tag('addons')->clear();
            \think\facade\Cache::delete('hooks');
            $this->success(__('Successfully uninstalled'));
        }
        $this->error(__('Illegal request'));
    }

    /**
     * 获取筛选
     */
    public function getFilter()
    {
        $type = $this->request->get('type');
        $data = Cloud::getInstance()->getFilter($type);
        $this->success('', '', $data);
    }

    /**
     * 渲染应用中心页面
     * @param string $page 页面名称
     * @param array $data 传入模板数据
     * @param bool $layout false-关闭模板布局
     * @return string|void
     * @throws AddonsException
     */
    public function fetch(string $page, array $data = [], $layout = true)
    {
        $path = runtime_path().'online'.DIRECTORY_SEPARATOR;
        if ($layout === false) {
            $this->view->layout(false);
        }

        $file = $path.$page.'.html';
        if (!file_exists($file)) {
            try {
                Cloud::getInstance()->getRequest(['method'=>'post','url'=>'appcenter/getPage','option'=>[
                    'form_params'=>['page'=>$page,'cms_build'=>$this->config->get('ver.cms_build')]
                ]], function ($res) use ($path, $page) {
                    if ($res['page']) {
                        if (!file_exists($path)) {
                            mkdir($path, 0777, true);
                        }
                        $f = fopen($path.$page.'.html','w');
                        fwrite($f, $res['page']);
                        fclose($f);
                    }
                });
            } catch (AddonsException $exception) {
                abort(500, '['.$page.']'.$exception->getMessage());
            }
        }

        return $this->view->fetch($file, $data);
    }
}