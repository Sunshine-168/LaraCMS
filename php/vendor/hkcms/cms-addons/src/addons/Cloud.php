<?php

declare (strict_types=1);

namespace think\addons;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use PhpZip\Util\Iterator\IgnoreFilesRecursiveFilterIterator;
use think\Exception;
use think\facade\Cache;
use think\facade\Config;
use think\helper\Str;

class Cloud
{
    /**
     * 定义单例模式的变量
     * @var null
     */
    private static $_instance = null;

    public static function getInstance()
    {
        if(empty(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 检测更新
     * @return mixed
     * @throws AddonsException
     */
    public function checkUpgrade($v, $p)
    {
        return $this->getRequest(['url'=>'cms/upgrade', 'method'=>'GET', 'option'=>[
            'query'=>['v'=>$v,'p'=>$p, 'type'=>2, 'domain'=>request()->host(), 'ip'=>request()->ip(), 'version'=>$v]
        ]]);
    }

    /**
     * 联盟授权检测
     * @param $domain
     * @return mixed
     */
    public function checkAuthorize($domain)
    {
        return $this->getRequest(['url'=>'cms/authorize', 'method'=>'GET', 'option'=>[
            'query'=>['domain'=>$domain]
        ]]);
    }

    /**
     * 验证用户信息
     * @param $user
     * @param $pass
     * @throws AddonsException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function chekcUser($user, $pass)
    {
        $this->getRequest(['url'=>'user/login', 'method'=>'POST', 'option'=>[
            'form_params' => [
                'username' => $user,
                'password' => $pass
            ]
        ]], function ($data) {
            Cache::set('cloud_token', $data['userinfo'],86400);
        });
    }

    /**
     * 获取列表
     * @param $filter
     * @return mixed
     */
    public function getList($filter)
    {
        return $this->getRequest(['url'=>'appcenter/getlist', 'method'=>'get', 'option'=>[
            'query'=>$filter
        ]]);
    }

    /**
     * 获取单个应用的详细信息
     * @param $filter
     * @return mixed
     * @throws AddonsException
     */
    public function getInfo($filter)
    {
        return $this->getRequest(['url'=>'appcenter/getinfo', 'method'=>'get', 'option'=>[
            'query' => $filter
        ]]);
    }

    /**
     * 获取多个应用信息
     * @param $names
     * @return mixed
     * @throws AddonsException
     */
    public function getInfos($names)
    {
        return $this->getRequest(['url'=>'appcenter/getinfos', 'method'=>'get', 'option'=>[
            'query' => $names
        ]]);
    }

    /**
     * 获取筛选
     * @param $type
     * @return mixed
     */
    public function getFilter($type)
    {
        if (empty($type)) {
            throw new AddonsException(__('Parameter %s can not be empty',['type']));
        }
        return $this->getRequest(['url'=>'appcenter/getfilter?type='.$type, 'method'=>'get']);
    }

    /**
     * 更新CMS
     * @param $v
     * @param string $p
     * @param string $path
     * @return bool
     */
    public function upgradeCms($v, $p = '', $path = '')
    {
        if (empty($path)) {
            try {
                $client = $this->getClient();
                $response = $client->request('get', 'cms/download', ['query' => ['v'=>$v, 'p'=>$p]]);
                $content = $response->getBody()->getContents();
            }  catch (ClientException $exception) {
                throw new AddonsException($exception->getMessage());
            }

            if (substr($content, 0, 1) === '{') {
                // json 错误信息
                $json = json_decode($content, true);
                throw new AddonsException($json['msg']??__('Server returns abnormal data'));
            }
            // 保存路径
            $name = $v.'_'.$p;
            $zip = $this->getCloudTmp().$name.'.zip';
            if (file_exists($zip)) {
                @unlink($zip);
            }
            $w = fopen($zip, 'w');
            fwrite($w, $content);
            fclose($w);
        } else {
            // 保存路径
            $name = $v.'_'.$p;
            $zip = $this->getCloudTmp().$name.'.zip';
            if (file_exists($zip)) {
                @unlink($zip);
            }
            $path = str_replace('\\','/',public_path().ltrim($path,'/'));
            rename($path,$zip);
        }

        $dir = Dir::instance();
        try {
            // 解压
            $unzipPath = $this->unzip($name);

            // 备份保存路径
            $backup = runtime_path().'backup'.DIRECTORY_SEPARATOR;
            @mkdir($backup);
            // 命名保存文件
            $backupZip = $backup.'HkCms_v'.config('ver.cms_version').'.'.config('ver.cms_build').'.zip';

            // 实例化
            $zf = new \PhpZip\ZipFile();
            $zf->addEmptyDir('runtime');
            // 获取项目根目录文件
            $lists = scandir(root_path());
            foreach ($lists as $key=>$value) {
                if ($value=='.' || $value=='..' || $value=='.git' || $value=='.idea' || $value=='runtime') {
                    continue;
                }
                if (is_dir(root_path().$value)) {
                    $zf->addDirRecursive(root_path($value),'/'.$value);
                } else {
                    $zf->addFile(root_path().$value);
                }
            }
            $zf->saveAsFile($backupZip)->close();

            // 执行更新SQL
            if (file_exists($unzipPath.'upgrade.sql')) {
                $this->exportSql($backup.config('ver.cms_version').'.sql');
                create_sql($unzipPath.'upgrade.sql');
            }
            // 执行更新脚本
            if (file_exists($unzipPath.'upgrade.php')) {
                include_once $unzipPath.'upgrade.php';
            }
            // 移动文件，解压目录移动到addons
            $dir->movedFile($unzipPath, root_path());
            // 清理
            $this->clearInstallDir([],[$zip]);
        } catch (\Exception $exception) {
            $this->clearInstallDir([$this->getCloudTmp().$name.DIRECTORY_SEPARATOR],[$zip]);
            throw new AddonsException($exception->getMessage());
        }
        return true;
    }

    /**
     * 给定目录路径打包下载
     * @param $path
     * @param string $savePath 为空-直接输出到浏览器，路径-保存到路径
     * @throws AddonsException
     */
    public function pack($path, $savePath = '')
    {
        $zipFile = new \PhpZip\ZipFile();
        try{
            if ($savePath) {
                $dir = runtime_path().'backup'.DIRECTORY_SEPARATOR;
                @mkdir($dir);
                $zipFile
                    ->addDirRecursive($path) // 包含下级，递归
                    ->saveAsFile($savePath)
                    ->close();
            } else {
                $zipFile
                    ->addDirRecursive($path) // 包含下级，递归
                    ->outputAsAttachment(basename($path).'.zip'); // 直接输出到浏览器
            }
        } catch(Exception $e){
            $zipFile->close();
            throw new AddonsException($e->getMessage());
        } catch(\PhpZip\Exception\ZipException $e){
            $zipFile->close();
            throw new AddonsException($exception->getMessage());
        }
    }

    /**
     * 插件安装
     * 【name：插件标识，version：插件版本信息(array)，type: 应用类型,module:所属模块】
     * ['name'=>'demo','version'=>['verison'=>'1.0.0'],'type'=>'template','module'=>'index']
     * @param array $info 安装的插件信息
     * @param string $unzipPath 解压的目录
     * @param string $demodata 1=安装演示数据
     * @param bool $force true-覆盖安装
     * @return bool
     * @throws AddonsException
     */
    public function install($info, $unzipPath = '', $demodata = '', bool $force=false)
    {
        // 目录权限检测
        $this->competence($info['type'], $info['name'], $info['module']??'',false,$force);

        // 需要删除目录
        $installDirArr = [];
        try {

            if (!is_dir($unzipPath)) {
                $unzipPath = $this->unzip($info['name']);
            }

            $dir = Dir::instance();
            if ($info['type']=='template') {
                list($templatePath, $staticPath) = $this->getTemplatePath($info['module']);
                $staticAppPath = $staticPath . $info['name'] . DIRECTORY_SEPARATOR;  // 模板静态安装路径
                $templatePath = $templatePath . $info['name'] . DIRECTORY_SEPARATOR; // 模板路径

                // 创建安装路径
                @mkdir($staticAppPath, 0755, true);
                @mkdir($templatePath, 0755, true);
                // 记录需要删除目录
                $installDirArr[] = $staticAppPath;
                $installDirArr[] = $templatePath;

                if (is_dir($unzipPath . 'static' . DIRECTORY_SEPARATOR)) { // 有模板静态资源的情况移动到public/static/module
                    $bl = $dir->movedFile($unzipPath . 'static' . DIRECTORY_SEPARATOR, $staticAppPath);
                    if ($bl===false) {
                        throw new AddonsException($dir->error);
                    }
                }
                $bl = $dir->movedFile($unzipPath, $templatePath); // 移动到模板目录下
                if ($bl===false) {
                    throw new AddonsException($dir->error);
                }
                $demodataFile = $templatePath;
            } else { // 插件、模块
                $addonsPath = app()->addons->getAddonsPath(); // 插件根目录

                // 创建目录
                @mkdir($addonsPath . $info['name'], 0755, true);
                $installDirArr[] = $addonsPath . $info['name'] . DIRECTORY_SEPARATOR;

                // 移动文件，解压目录移动到addons
                $dir->movedFile($unzipPath,$addonsPath . $info['name'] . DIRECTORY_SEPARATOR);

                $obj = get_addons_instance($info['name']);
                if (!empty($obj)) { // 调用插件安装
                    if (isset($obj->menu)) {
                        // 自动导入菜单
                        create_menu($obj->menu,$info['name']);
                    }
                    $obj->install();
                }

                // 导入数据库
                $this->importSql($info['name']);
                // 调用插件启用方法
                $this->enable($info['name'], $force);

                $demodataFile = $addonsPath . $info['name'] . DIRECTORY_SEPARATOR;
            }

            // 导入演示数据
            if ($demodata==1) {
                $demodataFile = $demodataFile.'demodata.sql';
                if (is_file($demodataFile)) {
                    create_sql($demodataFile);
                }
            }

        } catch(AddonsException $e) {
            $this->clearInstallDir($installDirArr);
            throw new AddonsException($e->getMessage());
        } catch (Exception $e) {
            $this->clearInstallDir($installDirArr);
            throw new AddonsException($e->getMessage());
        }
        return true;
    }

    /**
     * 插件更新
     * @param $info
     * @return bool
     */
    public function upgrade($info, $unzipPath = '', $demodata = '')
    {
        // 目录权限检测
        $this->competence($info['type'], $info['name'], $info['module']??'', true);

        try {
            if (!is_dir($unzipPath)) {
                $unzipPath = $this->unzip($info['name']);
            }

            $dir = Dir::instance();

            if ($info['type']=='template') {
                list($templatePath, $staticPath) = $this->getTemplatePath($info['module']);
                $staticAppPath = $staticPath . $info['name'] . DIRECTORY_SEPARATOR;  // 模板静态安装路径
                $templatePath = $templatePath . $info['name'] . DIRECTORY_SEPARATOR; // 模板路径

                if (is_dir($unzipPath . 'static' . DIRECTORY_SEPARATOR)) { // 有模板静态资源的情况移动到public/static/module
                    $bl = $dir->movedFile($unzipPath . 'static' . DIRECTORY_SEPARATOR, $staticAppPath);
                    if ($bl===false) {
                        throw new AddonsException($dir->error);
                    }
                }
                $bl = $dir->movedFile($unzipPath, $templatePath); // 移动到模板目录下
                if ($bl===false) {
                    throw new AddonsException($dir->error);
                }

                $demodataFile = $templatePath;
            } else {
                $addonsPath = app()->addons->getAddonsPath(); // 插件根目录

                if ($info['status']==1) { // 判断是否已经启用，先禁用
                    $this->disable($info['name']);
                }

                // 移动文件，解压目录移动到addons
                $dir->movedFile($unzipPath,$addonsPath . $info['name'] . DIRECTORY_SEPARATOR);

                $obj = get_addons_instance($info['name']);
                if (!empty($obj) && method_exists($obj,'upgrade')) { // 调用插件更新
                    if (isset($obj->menu)) {
                        // 自动导入菜单，自动判断是否升级
                        create_menu($obj->menu,$info['name']);
                    }
                    $obj->upgrade();
                }

                // 导入数据库
                $this->importSql($info['name'], true);
                // 调用插件启用方法
                $this->enable($info['name']);

                $demodataFile = $addonsPath . $info['name'] . DIRECTORY_SEPARATOR;
            }

            // 导入演示数据
            if ($demodata==1) {
                $demodataFile = $demodataFile.'demodata.sql';
                if (is_file($demodataFile)) {
                    create_sql($demodataFile);
                }
            }
        } catch(AddonsException $e) {
            throw new AddonsException($e->getMessage());
        } catch (Exception $e) {
            throw new AddonsException($e->getMessage());
        }
        return true;
    }

    /**
     * 本地安装
     * @param string $type 应用类型
     * @param string $file zip压缩位置
     * @param string $file 是否导入演示数据
     * @param bool $force true-强制覆盖
     * @return array|false
     * @throws AddonsException
     */
    public function installLocal($type, $file, $demodata = '', bool $force=false)
    {
        $path = dirname($file).DIRECTORY_SEPARATOR;
        $filename = basename($file).'.zip';
        try {
            $unzipPath = $file;

            // 检查info.ini文件
            $_info = $this->checkIni($type, $unzipPath);
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $_info['name'])) {
                throw new AddonsException(__('Addon identification can only be letters, numbers, underscores'));
            }
            $all = get_addons_info_all($type);
            if (isset($all[$_info['name']])) {
                throw new AddonsException(__('Addon %s already exists', [$_info['name']]));
            }

            $this->competence($type,$_info['name'],$_info['module']??'',false, $force);

            // 模板情况下的处理
            if ('template'==$type) {
                list($templatePath, $staticPath) = $this->getTemplatePath($_info['module']);
                $staticAppPath = $staticPath . $_info['name'] . DIRECTORY_SEPARATOR;
                $addonsAppPath = $templatePath . $_info['name'] . DIRECTORY_SEPARATOR;

                @mkdir($addonsAppPath, 0755, true);

                // 移动对应的模板目录
                $dir = Dir::instance();
                if (is_dir($unzipPath . 'static' . DIRECTORY_SEPARATOR)) { // 静态文件目录
                    $bl = $dir->movedFile($unzipPath . 'static' . DIRECTORY_SEPARATOR, $staticAppPath, $file);
                    if ($bl===false) {
                        throw new AddonsException($dir->error);
                    }
                    @mkdir($staticAppPath, 0755, true);
                }
                $bl = $dir->movedFile($unzipPath, $addonsAppPath);
                if ($bl===false) {
                    throw new AddonsException($dir->error);
                }

                $demodataFile = $addonsAppPath;
            } else {
                // 创建插件目录
                $addonsPath = app()->addons->getAddonsPath(); // 插件根目录
                @mkdir($addonsPath . $_info['name'], 0755, true);
                // 移动对应的模板目录
                $dir = Dir::instance();
                $bl = $dir->movedFile($unzipPath, $addonsPath . $_info['name'] . DIRECTORY_SEPARATOR);
                if ($bl===false) {
                    throw new AddonsException($dir->error);
                }

                $obj = get_addons_instance($_info['name']);
                if (!empty($obj)) { // 调用插件安装
                    if (isset($obj->menu)) {
                        // 自动导入菜单
                        create_menu($obj->menu,$_info['name']);
                    }
                    $obj->install();
                }

                // 导入数据库
                $this->importSql($_info['name']);
                // 调用插件启用方法
                $this->enable($_info['name'], $force);

                $demodataFile = $addonsPath . $_info['name'] . DIRECTORY_SEPARATOR;
            }

            // 导入演示数据
            if ($demodata==1) {
                $demodataFile = $demodataFile.'demodata.sql';
                if (is_file($demodataFile)) {
                    create_sql($demodataFile);
                }
            }
            @unlink($path.$filename);
        } catch (AddonsException $e) {
            throw new AddonsException($e->getMessage());
        } catch (\PhpZip\Exception\ZipException $e) {
            throw new AddonsException($e->getMessage());
        } catch (Exception $e) {
            throw new AddonsException($e->getMessage());
        }
        return $_info;
    }

    /**
     * 插件卸载
     * @param $info
     * @return bool
     */
    public function uninstall($info)
    {
        if ('template' == $info['type']) { // 模板卸载方式
            $addonsPath = config('cms.tpl_path').$info['module'].DIRECTORY_SEPARATOR;
            $staticPath = config('cms.tpl_static').$info['module'].DIRECTORY_SEPARATOR;
            // 卸载演示数据
            if (is_file($addonsPath.$info['name'].'/undemodata.sql')) {
                create_sql($addonsPath.$info['name'].'/undemodata.sql');
            }
            Dir::instance()->delDir($addonsPath.$info['name']);
            Dir::instance()->delDir($staticPath.$info['name']);
            return true;
        } else {
            // 插件卸载
            $obj = get_addons_instance($info['name']);
            if (!empty($obj)) { // 调用插件卸载
                $obj->uninstall();
            }
            // 卸载演示数据
            if (is_file(app()->addons->getAddonsPath().$info['name'].'/undemodata.sql')) {
                create_sql(app()->addons->getAddonsPath().$info['name'].'/undemodata.sql');
            }
            Dir::instance()->delDir(app()->addons->getAddonsPath().$info['name']);
            return true;
        }
    }

    /**
     * 插件启用
     * @param $name string 插件标识
     * @param $force bool true-强制覆盖
     * @return bool
     * @throws AddonsException
     */
    public function enable($name, bool $force=false)
    {
        // 插件install文件夹路径
        $installPath = app()->addons->getAddonsPath().$name.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR;

        // 检查安装目录是否有覆盖的文件，并复制文件
        if (is_dir($installPath)) {
            $installFile = [];
            $installDir = [];
            try {
                $list = ['app','public','template','static'];
                foreach ($list as $key=>$value) {
                    // 当前插件文件夹/install/app(template、static)
                    $installPathDir = $installPath. $value . DIRECTORY_SEPARATOR;
                    if (!is_dir($installPathDir)) {
                        continue;
                    }

                    if ('app'==$value || 'public'==$value) { // php 代码复制

                        // 获取安装的目录文件是否存在
                        $listArr = Dir::instance()->rglob($installPathDir . '*', GLOB_BRACE);
                        if (empty($listArr)) {
                            continue;
                        }

                        // 判断是否已经存在该文件了，存在就报错
                        if ($force===false) {
                            $tmpFiles = [];
                            foreach ($listArr as $k=>$v) {
                                $newFile = str_replace($installPathDir, base_path(), $v);
                                if (is_file($newFile)) {
                                    $tmpFiles[] = $newFile;
                                }
                            }
                            if (!empty($tmpFiles)) {
                                $tmpFiles = implode(',', $tmpFiles);
                                throw new AddonsException(__('%s existed',[$tmpFiles]));
                            }
                        }

                        // 复制目录
                        $bl = Dir::instance()->copyDir($installPathDir, base_path());
                        if ($bl===false) {
                            throw new AddonsException(__('%s copy to %s fails',[$installPathDir,base_path()]));
                        }
                    } else if ('template'==$value) { // 复制到模板
                        $listArr = Dir::instance()->getList($installPathDir);
                        $site = site();

                        foreach ($listArr as $k=>$v) {
                            if (in_array($v,['.','..'])) {
                                continue;
                            }
                            if (!isset($site[$v.'_theme']) && $v==$name) { // 检测是否是插件主题化
                                $tempArr = Dir::instance()->getList($installPathDir.$v.DIRECTORY_SEPARATOR);
                                foreach ($tempArr as $item) {
                                    if (in_array($v,['.','..'])) {
                                        continue;
                                    }
                                    if (file_exists($installPathDir.$v.DIRECTORY_SEPARATOR.$item.DIRECTORY_SEPARATOR.'info.ini')) {
                                        $themeName = $item;
                                        break;
                                    }
                                }
                            }

                            // 获取当前主题
                            $curTheme = isset($themeName) ? '' : $site[$v.'_theme'] . DIRECTORY_SEPARATOR;

                            $themePath = config('cms.tpl_path').$v.DIRECTORY_SEPARATOR . $curTheme;

                            // 获取插件安装文件模块目录下的所有文件
                            $temp_installPathDir = $installPathDir . $v . DIRECTORY_SEPARATOR;
                            $temp = Dir::instance()->rglob( $temp_installPathDir . '*', GLOB_BRACE);
                            if (empty($temp)) {
                                continue;
                            }

                            // 判断是否已经存在该文件了，存在就报错
                            if ($force===false) {
                                $tmpFiles = [];
                                foreach ($temp as $item) {
                                    $newFile = str_replace($temp_installPathDir, $themePath, $item);
                                    if (is_file($newFile)) {
                                        $tmpFiles[] = $newFile; // 记录已存在的文件
                                    }
                                }
                                if (!empty($tmpFiles)) {
                                    $tmpFiles = implode(',', $tmpFiles);// 报错已存在的文件
                                    throw new AddonsException(__('%s existed',[$tmpFiles]));
                                }
                            }

                            // 复制目录
                            $bl = Dir::instance()->copyDir($temp_installPathDir, $themePath);
                            if ($bl===false) {
                                throw new AddonsException(__('%s copy to %s fails',[$temp_installPathDir,$themePath]));
                            }
                        }
                    } else if ('static'==$value) { // 静态文件 代码复制
                        $listArr = Dir::instance()->rglob($installPathDir . '*', GLOB_BRACE);
                        if (empty($listArr)) {
                            continue;
                        }
                        $addonsStatic = public_path('static'.DIRECTORY_SEPARATOR.'addons');
                        if (is_dir($addonsStatic.$name.DIRECTORY_SEPARATOR) && $force===false) {
                            throw new AddonsException(__('%s existed', [$addonsStatic.$name.DIRECTORY_SEPARATOR]));
                        }
                        if (!@mkdir($addonsStatic.$name.DIRECTORY_SEPARATOR) && $force===false) {
                            throw new AddonsException(__('Failed to create "%s" folder',[$addonsStatic.$name.DIRECTORY_SEPARATOR]));
                        }
                        $installDir[] = $addonsStatic.$name.DIRECTORY_SEPARATOR; // 记录安装的文件，出错回滚
                        $bl = Dir::instance()->copyDir($installPathDir, $addonsStatic.$name.DIRECTORY_SEPARATOR);
                        if ($bl===false) {
                            throw new AddonsException(__('%s copy to %s fails',[$installPathDir,$addonsStatic.$name.DIRECTORY_SEPARATOR]));
                        }
                    }
                }
            } catch (AddonsException $exception) {
                $this->clearInstallDir($installDir,$installFile);
                throw new AddonsException($exception->getMessage());
            } catch (\think\Exception $exception) {
                $this->clearInstallDir($installDir,$installFile);
                throw new AddonsException($exception->getMessage());
            }
        }

        // 执行插件启用方法
        $obj = get_addons_instance($name);
        if (!empty($obj) && method_exists($obj,'enable')) {
            $obj->enable();
        }
        // 插件主题化，将插件默认主题写入数据库
        if (isset($themeName)) {
            $info = get_addons_info($themeName,'template',$name);
            \think\facade\Db::name('app')->insert([
                'name'=>$info['name'],
                'title'=>$info['title']??'',
                'image'=>$info['image']??'',
                'price'=>$info['price']??0,
                'module'=>$info['module'],
                'type'=>'template',
                'description'=>$info['description']??'',
                'author'=>$info['author']??'',
                'version'=>$info['version']['version']??$info['version'],
                'status'=>1,
                'createtime'=>time(),
            ]);
            if (!\think\facade\Db::name('config')->where(['name'=>$name.'_theme'])->find()) {
                $ini = get_addons_info($name,'addon');
                \think\facade\Db::name('config')->insert([
                    'group'=>'more',
                    'name'=>$name.'_theme',
                    'title'=>($ini['title']??$name).'主题',
                    'value'=>$themeName,
                    'type'=>'text',
                    'max_number'=>'0',
                    'is_default'=>'0',
                    'lang'=>'-1',
                    'weigh'=>'1',
                ]);
            }
        }

        return true;
    }

    /**
     * 插件禁用
     * @param $name string 标识名称
     * @return bool
     * @throws AddonsException
     */
    public function disable($name)
    {
        $installPath = app()->addons->getAddonsPath().$name.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR;

        $dirArr = []; // 文件夹
        $fileArr = []; // 文件列表
        $static = []; // 静态资源
        if (is_dir($installPath)) { // 找出已安装的
            $list = ['app','public','template','static'];
            foreach ($list as $key=>$value) {
                $installPathDir = $installPath. $value . DIRECTORY_SEPARATOR;
                if (!is_dir($installPathDir)) {
                    continue;
                }

                if ('app'==$value || 'public'==$value) { // php 代码复制
                    $listArr = Dir::instance()->rglob($installPathDir . '*', GLOB_BRACE);
                    if (empty($listArr)) {
                        continue;
                    }
                    foreach ($listArr as $k=>$v) { // 找出已经存在的文件
                        if (is_file($v)) {
                            $newFile = str_replace($installPathDir,base_path(),$v);
                            if (!is_file($newFile)) {
                                continue;
                            }
                            if (!is_writable($newFile)) {
                                throw new AddonsException(__('%s no permission to write',[$newFile]));
                            }
                            $fileArr[] = $newFile;
                        } else if (is_dir($v)) {
                            if (!is_writable($v)) {
                                throw new AddonsException(__('%s no permission to write',[$v]));
                            }
                            $dirArr[] = str_replace($installPathDir,base_path(),$v);
                        }
                    }
                } else if ('template'==$value) { // 静态文件 代码复制
                    $listArr = Dir::instance()->getList($installPathDir);
                    $site = site();

                    foreach ($listArr as $k=>$v) {
                        if (in_array($v,['.','..'])) {
                            continue;
                        }

                        if (isset($site[$v.'_theme']) && $v==$name) { // 检测是否是插件主题化
                            $tempArr = Dir::instance()->getList($installPathDir.$v.DIRECTORY_SEPARATOR);
                            foreach ($tempArr as $item) {
                                if (in_array($v,['.','..'])) {
                                    continue;
                                }
                                if (file_exists($installPathDir.$v.DIRECTORY_SEPARATOR.$item.DIRECTORY_SEPARATOR.'info.ini')) {
                                    $themeName = config('cms.tpl_path').$v.DIRECTORY_SEPARATOR;
                                    break 2;
                                }
                            }
                        }

                        if (!isset($site[$v.'_theme'])) {
                            continue;
                        }

                        // 模板主题路径
                        $themePath = config('cms.tpl_path').$v.DIRECTORY_SEPARATOR . $site[$v.'_theme'] . DIRECTORY_SEPARATOR;

                        // 获取插件安装文件模块目录下的所有文件
                        $temp_installPathDir = $installPathDir . $v . DIRECTORY_SEPARATOR;
                        $temp = Dir::instance()->rglob( $temp_installPathDir . '*', GLOB_BRACE);
                        if (empty($temp)) {
                            continue;
                        }

                        foreach ($temp as $item) {
                            if (is_file($item)) {
                                $newFile = str_replace($temp_installPathDir, $themePath, $item);
                                if (!is_file($newFile)) {
                                    continue;
                                }
                                if (!is_writable($newFile)) {
                                    throw new AddonsException(__('%s no permission to write',[$newFile]));
                                }
                                $fileArr[] = $newFile;
                            } else if (is_dir($item)) {
                                $newFile = str_replace($temp_installPathDir, $themePath, $item);
                                if (!is_dir($newFile)) {
                                    continue;
                                }
                                if (!is_writable($item)) {
                                    throw new AddonsException(__('%s no permission to write',[$item]));
                                }
                                $dirArr[] = $newFile;
                            }
                        }
                    }
                } else if ('static'==$value) { // 静态文件 代码复制
                    $addonsStatic = public_path('static'.DIRECTORY_SEPARATOR.'addons');
                    if (is_dir($addonsStatic)) {
                        if (!is_writable($addonsStatic)) {
                            throw new AddonsException(__('%s no permission to write', [$addonsStatic]));
                        }
                        $static[] = $addonsStatic.$name.DIRECTORY_SEPARATOR;
                    }
                }
            }
        }

        // 文件删除
        if (!empty($fileArr)) {
            foreach ($fileArr as $key=>$value) {
                @unlink($value);
            }
        }
        // 文件夹删除
        if (!empty($dirArr)) {
            $dirArr = array_reverse($dirArr); // 倒序
            foreach ($dirArr as $key=>$value) {
                @rmdir($value); // 只删除空的目录
            }
        }
        // 插件静态资源删除
        if (!empty($static)) {
            $this->clearInstallDir($static,[]);
        }

        // 执行插件禁用方法
        $obj = get_addons_instance($name);
        if (!empty($obj) && method_exists($obj,'disable')) {
            $obj->disable();
        }

        // 清除插件主题文件
        if (isset($themeName)) {
            \think\facade\Db::name('app')->where(['module'=>$name])->delete();
            \think\facade\Db::name('config')->where(['name'=>$name.'_theme'])->delete();
            $this->clearInstallDir([$themeName],[]);

            $staticPath = config('cms.tpl_static').$name.DIRECTORY_SEPARATOR;
            if (is_dir($staticPath)) {
                $this->clearInstallDir([$staticPath],[]);
            }
        }
    }


    /**
     * 安装、更新前的检查
     * @param string $type 应用类型
     * @param string $name 应用标识
     * @param string $module 应用模块
     * @param bool $update 场景：更新、安装
     * @param bool $force true-覆盖安装
     * @throws AddonsException
     */
    public function competence($type, $name, $module, $update=false, bool $force=false)
    {
        if ('template'==$type) {
            // 模板的情况
            list($templatePath, $staticPath) = $this->getTemplatePath($module);

            if (!is_dir($templatePath)) { // 模板安装目录不存在
                throw new AddonsException(__('%s not exist',[$templatePath]));
            }
            if (!is_dir($staticPath)) { // 静态资源安装目录不存在
                throw new AddonsException(__('%s not exist',[$staticPath]));
            }
            if (is_dir($templatePath.$name)  && $update===false && $force===false) { // 不是更新的时候，已经有对应目录抛出异常
                throw new AddonsException(__('%s existed',[$templatePath.$name]));
            }
            if (is_dir($staticPath.$name) && $update===false && $force===false) {
                throw new AddonsException(__('%s existed',[$staticPath.$name]));
            }
        } else {
            $addonsPath = app()->addons->getAddonsPath();
            if ($update===false) {
                $dirArr = $this->getAddonsDir($addonsPath); // 获取插件目录下的所有插件目录名称
                if (in_array($name, $dirArr)  && $update===false) { // 检查插件目录，如果已存在抛出异常
                    throw new AddonsException(__('%s existed',[$name]));
                }
            }
        }
    }

    /**
     * 获取模板路径与模板静态路径
     * @param string $module
     * @return string[] 返回模板路径与静态路径
     */
    public function getTemplatePath($module = 'index')
    {
        $addonsPath = config('cms.tpl_path').$module.DIRECTORY_SEPARATOR;
        $staticPath = config('cms.tpl_static').$module.DIRECTORY_SEPARATOR;
        return [$addonsPath, $staticPath];
    }

    /**
     * 获取插件目录
     * @param $dir
     * @return array
     */
    private function getAddonsDir($dir)
    {
        $dirArray = [];
        if (false != ($handle = opendir ( $dir ))) {
            while ( false !== ($file=readdir($handle)) ) {
                if ($file != "." && $file != ".." && strpos($file,".")===false) {
                    $dirArray[] = $file;
                }
            }
            closedir($handle);
        }
        return $dirArray;
    }

    /**
     * 获取下载的应用临时位置[runtime文件夹]
     * @return string
     */
    public function getCloudTmp()
    {
        $dir = runtime_path().'cloud'.DIRECTORY_SEPARATOR;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * 清理安装目录或文件
     * @param array $dirArr 清理的路径
     * @param array $fileArr 清理的文件
     */
    private function clearInstallDir($dirArr, $fileArr = [])
    {
        foreach ($dirArr as $value) {
            if (empty($value)) {
                continue;
            }
            Dir::instance()->delDir($value);
        }
        foreach ($fileArr as $value) {
            if (empty($value)) {
                continue;
            }
            @unlink($value);
        }
    }

    /**
     * 下载插件
     * @param string $name 应用标识
     * @param string $version 下载的版本号
     * @return string 返回zip保存路径
     * @throws AddonsException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function download($name, $version)
    {
        try {
            $client = $this->getClient();
            $response = $client->request('get', 'appcenter/download', ['query' => ['name'=>$name, 'version'=>$version, 'cms_version'=>config('ver.cms_version')]]);
            $content = $response->getBody()->getContents();
        }  catch (ClientException $exception) {
            throw new AddonsException($exception->getMessage());
        }

        if (substr($content, 0, 1) === '{') {
            // json 错误信息
            $json = json_decode($content, true);
            throw new AddonsException($json['msg']??__('Server returns abnormal data'));
        }

        // 保存路径
        $zip = $this->getCloudTmp().$name.'.zip';
        if (file_exists($zip)) {
            @unlink($zip);
        }
        $w = fopen($zip, 'w');
        fwrite($w, $content);
        fclose($w);
        return $zip;
    }

    /**
     * 解压缩
     * @param $name
     * @return string
     * @throws \PhpZip\Exception\ZipException
     */
    public function unzip($name)
    {
        $cloudPath = $this->getCloudTmp();
        // 创建解压路径
        $unzipPath = $cloudPath . $name . DIRECTORY_SEPARATOR;
        $zip = $cloudPath . $name .'.zip';

        try {
            @mkdir($unzipPath);
            $zipFile = new \PhpZip\ZipFile();
            $zipFile->openFile($zip);
            $zipFile->extractTo($unzipPath);
        } catch (\PhpZip\Exception\ZipException $e) {
            $zipFile->close();
            $this->clearInstallDir([$unzipPath]);
            throw new AddonsException($e->getMessage());
        } catch (\Exception $e) {
            $zipFile->close();
            $this->clearInstallDir([$unzipPath]);
            throw new AddonsException($e->getMessage());
        }
        return $unzipPath;
    }

    /**
     * 验证info
     * @param $type
     * @param $path
     * @return array info.ini信息
     * @throws AddonsException
     */
    public function checkIni($type, $path)
    {
        // 检查info.ini文件
        $info_file = $path . 'info.ini';
        if (!is_file($info_file)) {
            throw new AddonsException(__('%s not exist',['info.ini']));
        }
        $_info = parse_ini_file($info_file, true, INI_SCANNER_TYPED) ?: [];
        if (empty($_info)) {
            throw new AddonsException(__('The content of the info.ini file is not in the correct format'));
        }

        if ('template'==$type) {
            $arr = ['type','module','name','title','author','version'];
        } else {
            $arr = ['type','name','title','author','version','status'];
        }

        foreach ($arr as $key=>$value) {
            if (!array_key_exists($value, $_info)) {
                throw new AddonsException(__('The content of the info.ini file is not in the correct format'));
            }
        }
        
        return $_info;
    }

    /**
     * 安装/更新时的检测，用于提示用户
     * @param array $info
     * @param string $type
     * @param string $module
     * @param string $diypath
     * @return array
     */
    public function checkInstall(array $info, $type='', $module = '', $diypath = '')
    {
        $type = $type ?: $info['type'];
        $module = $module ?: ($info['module']??'');
        $name = $info['name'];

        // 依赖的插件检测
        if (!empty($info['addon']) && is_array($info['addon'])) {
            $addon = [];
            foreach ($info['addon'] as $key=>&$v) {
                if (\think\facade\Validate::is($key, '/^[a-zA-Z][a-zA-Z0-9_]*$/')) {
                    $bl = \think\facade\Db::name('app')->where(['name'=>$key])->find();
                    if (!$bl) {
                        $addon[] = ['name'=>$key,'version'=>__('Not installed'),'need_ver'=>$v,'status'=>0];
                        continue;
                    }
                    $addon[] = ['name'=>$key,'version'=>$bl['version'],'need_ver'=>$v,'status'=>$bl['status']];
                }
            }
            $info['addon'] = $addon;
        }

        // 检测数据库依赖
        if (!empty($info['database']) && is_string($info['database'])) {
            $info['database'] = explode(',', $info['database']);
            $result = \think\facade\Db::query("SHOW TABLE STATUS");
            $tables = [];
            foreach ($result as $key=>$value) {
                $tables[] = $value['Name'];
            }
            $temp = [];
            $prefix = config('database.connections.mysql.prefix');
            foreach ($info['database'] as $key=>$value) {
                $temp[] = ['table'=>$prefix.$value,'status'=>in_array($prefix.$value, $tables)?-1:1];
            }
            $info['database'] = $temp;
        }

        // 检测目录权限
        $info['dir'] = isset($info['dir']) && is_string($info['dir'])?explode(',', $info['dir']):[];
        $newDir = [];
        foreach ($info['dir'] as $vv) {
            $path = str_replace('//','/',str_replace('\\','/', root_path().$vv));
            if (!is_writable($path)) {
                $newDir[] = ['path'=>$path,'status'=>0];
            }
        }
        $info['dir'] = $newDir;

        if ('template'==$type) {
            list($templatePath, $staticPath) = Cloud::getInstance()->getTemplatePath($module);
            // 模板目录与静态文件目录检测, 为空，为本地插件开发的情况
            if (!empty($diypath) && is_dir($templatePath.$name.DIRECTORY_SEPARATOR)) {
                $info['dir'][] = ['path'=>$templatePath.$name.DIRECTORY_SEPARATOR,'status'=>-1];
            }
            if (!is_writable($templatePath)) {
                $info['dir'][] = ['path'=>$templatePath,'status'=>0];
            }
            $diypath = $diypath?:$templatePath.$name.DIRECTORY_SEPARATOR;
            // 演示数据检测
            if (is_file($diypath.'demodata.sql')) { // 演示数据检测
                $info['demodata'] = 1;
            }
            // 有静态文件，安装时移动到public目录下，检测是否已存在，是否有写入权限
            if (is_dir($diypath.'static'.DIRECTORY_SEPARATOR)) {
                if (is_dir($staticPath.$name)) {
                    // 重复
                    $info['dir'][] = ['path'=>$staticPath.$name,'status'=>-1];
                } else if (!is_writable($staticPath)) {
                    $info['dir'][] = ['path'=>$staticPath,'status'=>0];
                }
            }
        } else {
            $addonPath = app()->addons->getAddonsPath().$name.DIRECTORY_SEPARATOR;
            // 插件目录是否存在, 为空，为本地插件开发的情况
            if (!empty($diypath) && is_dir($addonPath)) {
                $info['dir'][] = ['path'=>$addonPath,'status'=>-1];
            }
            $diypath = $diypath?:$addonPath;
            // 演示数据检测
            if (is_file($diypath.'demodata.sql')) {
                $info['demodata'] = 1;
            }
            // 插件目录是否可写
            if (!is_writable(app()->addons->getAddonsPath())) {
                $info['dir'][] = ['path'=>app()->addons->getAddonsPath(),'status'=>0];
            }
            $installPath = $diypath.'install'.DIRECTORY_SEPARATOR;
            if (is_dir($installPath)) {
                $list = ['app','public','template','static'];
                foreach ($list as $key=>$value) {
                    $installPathDir = $installPath. $value . DIRECTORY_SEPARATOR;
                    if (!is_dir($installPathDir)) {
                        continue;
                    }
                    if ('app'==$value || 'public'==$value) {
                        // 获取安装的目录文件是否存在
                        $listArr = Dir::instance()->rglob($installPathDir . '*', GLOB_BRACE);
                        if (empty($listArr)) {
                            continue;
                        }
                        foreach ($listArr as $k=>$v) {
                            $newFile = str_replace($installPathDir, base_path(), $v);
                            if (is_dir($newFile) && !is_writable($newFile)) {
                                $info['dir'][] = ['path'=>$newFile,'status'=>0];
                            }
                            if (is_file($newFile)) {
                                $info['dir'][] = ['path'=>$newFile,'status'=>-1];
                            }
                        }
                    } else if ('template'==$value) { // 复制到模板
                        $listArr = Dir::instance()->getList($installPathDir);
                        $site = site();
                        foreach ($listArr as $k=>$v) {
                            if (in_array($v, ['.', '..']) || !isset($site[$v . '_theme'])) { // 必须是模块文件夹
                                continue;
                            }
                            $themePath = config('cms.tpl_path').$v.DIRECTORY_SEPARATOR . $site[$v.'_theme'] . DIRECTORY_SEPARATOR;
                            // 获取插件安装文件模块目录下的所有文件
                            $temp_installPathDir = $installPathDir . $v . DIRECTORY_SEPARATOR;

                            // 判断是否已经存在该文件
                            $temp =  Dir::instance()->rglob( $temp_installPathDir . '*', GLOB_BRACE);
                            if (empty($temp)) {
                                continue;
                            }
                            foreach ($temp as $item) {
                                $newFile = str_replace($temp_installPathDir, $themePath, $item);
                                if (is_dir($newFile) && !is_writable($newFile)) {
                                    $info['dir'][] = ['path'=>$newFile,'status'=>0];
                                }
                                if (is_file($newFile)) {
                                    $info['dir'][] = ['path'=>$newFile,'status'=>-1];
                                }
                            }
                        }
                    } else if ('static'==$value) { // 静态文件
                        $listArr = Dir::instance()->rglob($installPathDir . '*', GLOB_BRACE);
                        if (empty($listArr)) {
                            continue;
                        }
                        $addonsStatic = public_path('static'.DIRECTORY_SEPARATOR.'addons');
                        if (is_dir($addonsStatic.$name)) {
                            $info['dir'][] = ['path'=>$addonsStatic.$name,'status'=>-1];
                        }
                        if (!is_writable($addonsStatic)) {
                            $info['dir'][] = ['path'=>$addonsStatic,'status'=>0];
                        }
                    }
                }
            }
        }
        return $info;
    }

    /**
     * 导入数据库
     * @param string $name 应用标识
     * @param string $upgrade true-使用upgrade.sql,false-使用install.sql
     */
    public function importSql($name, $upgrade=false)
    {
        $sql = $upgrade?app()->addons->getAddonsPath().$name.DIRECTORY_SEPARATOR.'upgrade.sql':app()->addons->getAddonsPath().$name.DIRECTORY_SEPARATOR.'install.sql';
        if (!file_exists($sql)) {
            return false;
        }

        // 导入数据库
        create_sql($sql);
        return true;
    }

    /**
     * 数据库备份
     * @param $filename
     * @return bool
     */
    public function exportSql($filename)
    {
        $db = app('db');
        $list = $db->query('SHOW TABLE STATUS');

        $fp = @fopen($filename, 'w');
        foreach ($list as $key=>$value) {
            $result = $db->query("SHOW CREATE TABLE `{$value['Name']}`");
            $sql = "\n\nDROP TABLE IF EXISTS `{$value['Name']}`;\n";
            $sql .= trim($result[0]['Create Table']) . ";\n\n";
            if (false === @fwrite($fp, $sql)) {
                return false;
            }
            //备份数据记录
            $result = $db->query("SELECT * FROM `{$value['Name']}`");
            foreach ($result as $row) {

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
                    return false;
                }
            }
        }
        @fclose($fp);
        return true;
    }

    /**
     * 获取Client对象
     * @return Client
     */
    protected function getClient()
    {
        static $client;
        if (empty($client)) {
            $token = Cache::get('cloud_token');
            $token = !empty($token) && !empty($token['token']) ? $token['token'] : null;
            $client = new Client([
                'base_uri' => Config::get('cms.api_url'),
                'headers' => [
                    'token' => $token
                ]
            ]);
        }
        return $client;
    }

    /**
     * 通用请求
     * @param $option
     * @param callable $success
     * @return mixed
     */
    public function getRequest($option, callable $success=null)
    {
        try {
            $client = $this->getClient();
            $response = $client->request($option['method']??'post', $option['url'], $option['option']??[]);
            $content = $response->getBody()->getContents();
        }  catch (\think\exception\ErrorException $exception) {
            throw new AddonsException($exception->getMessage(),500);
        }  catch (ClientException $exception) {
            throw new AddonsException($exception->getCode()==404 ? '404 Not Found' : $exception->getMessage(), 500);
        }

        $json = json_decode($content, true);
        if (!empty($json) && isset($json['code'])) {
            if ($json['code']==200) {
                if (empty($success)) {
                    return $json['data'];
                }
                return call_user_func($success, $json['data']);
            } else {
                throw new AddonsException($json['msg']);
            }
        } else {
            throw new AddonsException(__('Server returns abnormal data'));
        }
    }
}