<?php
declare(strict_types=1);

use think\facade\Event;
use think\facade\Route;
use think\helper\{
    Str, Arr
};

// 插件类库自动载入
spl_autoload_register(function ($class) {

    $class = ltrim($class, '\\');

    $dir = app()->getRootPath();
    $namespace = 'addons';

    if (strpos($class, $namespace) === 0) {
        $class = substr($class, strlen($namespace));
        $path = '';
        if (($pos = strripos($class, '\\')) !== false) {
            $path = str_replace('\\', '/', substr($class, 0, $pos)) . '/';
            $class = substr($class, $pos + 1);
        }
        $path .= str_replace('_', '/', $class) . '.php';
        $dir .= $namespace . $path;
        if (file_exists($dir)) {
            include $dir;
            return true;
        }

        return false;
    }

    return false;

});

if (!function_exists('hook')) {
    /**
     * 处理插件钩子
     * @param string $event 钩子名称
     * @param array|null $params 传入参数
     * @param bool $once 是否只返回一个结果
     * @param bool $original true - 返回tp trigger原样的数据，false - 返回字符串，如果原样返回数据是数组即会转换成字符串
     * @return mixed
     */
    function hook($event, $params = null, bool $once = false, bool $original = false)
    {
        // 兼容旧版,show_map 模板在使用，upload_after 百度编辑器在使用，index_head、index_footer模板
        $oldEvn = ['index_head','index_footer','upload_after','show_map'];
        if (strpos($event,'_')!==false && in_array($event, $oldEvn)) {
            $tmpArr = explode('_', $event);
            foreach ($tmpArr as $key=>$value) {
                if ($key==0) continue;
                $tmpArr[$key] = ucfirst($value);
            }
            $event = implode('',$tmpArr);
        }

        $result = Event::trigger($event, $params, $once);

        if ($original) {
            return $result;
        } else {
            return join('', $result);
        }
    }
}

if (!function_exists('get_addons_instance')) {
    /**
     * 获取插件的单例
     * @param string $name 插件名
     * @return mixed|null
     */
    function get_addons_instance($name)
    {
        static $_addons = [];
        if (isset($_addons[$name])) {
            return $_addons[$name];
        }
        $class = get_addons_class($name);
        if (class_exists($class)) {
            $_addons[$name] = new $class(app());

            return $_addons[$name];
        } else {
            return null;
        }
    }
}

if (!function_exists('get_addons_class')) {
    /**
     * 获取插件类的类名
     * @param string $name 插件名
     * @param string $type 返回命名空间类型
     * @param string $class 当前类名
     * @return string
     */
    function get_addons_class($name, $type = 'hook', $class = null)
    {
        $name = trim($name);
        // 处理多级控制器情况
        if (!is_null($class) && strpos($class, '.')) {
            $class = explode('.', $class);

            $class[count($class) - 1] = Str::studly(end($class));
            $class = implode('\\', $class);
        } else {
            $class = Str::studly(is_null($class) ? $name : $class);
        }
        switch ($type) {
            case 'controller':
                $namespace = '\\addons\\' . $name . '\\controller\\' . $class;
                break;
            default:
                $namespace = '\\addons\\' . $name . '\\' . $class;
        }
        return class_exists($namespace) ? $namespace : '';
    }
}

if (!function_exists('addons_url')) {
    /**
     * 插件显示内容里生成访问插件的url
     * @param $url
     * @param array $param
     * @param bool|string $suffix 生成的URL后缀
     * @param bool|string $domain 域名
     * @return bool|string
     */
    function addons_url($url = '', $param = [], $suffix = true, $domain = false)
    {
        $request = app('request');
        if (empty($url)) {
            // 生成 url 模板变量
            $addons = $request->addon;
            $controller = $request->controller();
            $controller = str_replace('/', '.', $controller);
            $action = $request->action();
        } else {
            $url = Str::studly($url);
            $url = parse_url($url);
            if (isset($url['scheme'])) {
                $addons = strtolower($url['scheme']);
                $controller = $url['host'];
                $action = trim($url['path'], '/');
            } else {
                $route = explode('/', $url['path']);
                $addons = $request->addon;
                $action = array_pop($route);
                $controller = array_pop($route) ?: $request->controller();
            }
            $controller = Str::snake((string)$controller);

            /* 解析URL带的参数 */
            if (isset($url['query'])) {
                parse_str($url['query'], $query);
                $param = array_merge($query, $param);
            }
        }

        return Route::buildUrl("@addons/{$addons}/{$controller}/{$action}", $param)->suffix($suffix)->domain($domain);
    }
}

if (!function_exists('get_addons_info')) {
    /**
     * 读取插件的基础信息
     * @param string $name 插件名
     * @param $type string 插件类型， template 或其他
     * @param $module string 所属模块
     * @return array
     */
    function get_addons_info($name, $type='addon', $module='index')
    {
        if ($type=='template') {
            $addon_info = "addon_{$name}_info_".$module;
            $info = app()->cache->get($addon_info);
            if (!app()->isDebug() && !empty($info)) {
                return $info;
            }

            // 获取模板说明
            $info_file = config('cms.tpl_path').$module.DIRECTORY_SEPARATOR.$name.DIRECTORY_SEPARATOR. 'info.ini';
            if (!is_file($info_file)) {
                return [];
            }
            $info = parse_ini_file($info_file, true, INI_SCANNER_TYPED) ?: [];

            $one = \think\facade\Db::name('app')->field('name,type,title,description,author,version,status')->where(['name'=>$name,'module'=>$module])->find();
            if (!empty($one)) {
                $info = $one + $info;
            }
        } else {
            $addon = get_addons_instance($name);
            if (!$addon) {
                return [];
            }
            $info = $addon->getInfo();
        }
        if (!app()->isDebug() && $type=='template') {
            app()->cache->tag('addons')->set($addon_info, $info);
        }
        return $info;
    }
}

if (!function_exists('get_addons_info_all')) {

    /**
     * 获取本地所有插件信息
     * @param $type string 插件类型， template 或其他
     * @return array
     */
    function get_addons_info_all($type)
    {
        $all = app()->cache->get('get_addons_info_all_'.$type);
        if (!app()->isDebug() && !empty($all)) {
            return $all;
        }
        if ($type=='template') {
            $templatePath = config('cms.tpl_path');
            $module = glob( $templatePath . '*');

            $data = [];
            foreach ($module as $key => $value) {
                if (is_dir($value) == false) {
                    continue;
                }
                $name = basename($value); // 模板下的文件夹，默认情况下只有index、admin
                $templateArr = glob( $templatePath.$name.DIRECTORY_SEPARATOR . '*');
                foreach ($templateArr as $k=>$v) {
                    if (is_dir($v) == false) {
                        continue;
                    }
                    // 获取模板说明
                    $info_file = $v . DIRECTORY_SEPARATOR . 'info.ini';
                    if (!is_file($info_file)) {
                        throw new \Exception(lang('%s,not exist',[$info_file]));
                    }

                    $tempArr = [];
                    $tempArr['name'] = basename($v);
                    $_info = parse_ini_file($info_file, true, INI_SCANNER_TYPED) ?: [];
                    if (empty($_info['name']) || !\think\facade\Validate::is($name, '/^[a-zA-Z][a-zA-Z0-9_]*$/')) {
                        throw new \Exception(lang('Incorrect plug-in ID format'));
                    }
                    if ($_info['name']!=$tempArr['name']) {
                        throw new \Exception(lang('模板标识名与文件夹不一致'));
                    }

                    // 获取预览图
                    $previewPath = config('cms.tpl_static').$name.DIRECTORY_SEPARATOR.$tempArr['name'].DIRECTORY_SEPARATOR.'preview.jpg';
                    if (is_file($previewPath)) {
                        $tempArr['image'] = str_replace('\\', '/', '/' . str_replace(public_path(), "", $previewPath));
                    } else {
                        $tempArr['image'] = '/static/common/image/nopic.png';
                    }

                    $tempArr = array_merge($tempArr, $_info);
                    $data[$name][$tempArr['name']] = $tempArr;
                }
            }
        } else {
            $dir = app()->getRootPath() . 'addons' . DIRECTORY_SEPARATOR;
            $addons = glob( $dir . '*');
            $data = [];
            foreach ($addons as $key => $value) {
                $name = basename($value);
                $info = get_addons_info($name);
                if (!isset($info['type']) || $info['name']!=$name) {
                    continue;
                }
                if (!empty($info) && $info['type']==$type) {
                    $data[$name] = $info;
                }
            }
        }
        if (!app()->isDebug()) {
            app()->cache->tag('addons')->set('get_addons_info_all_'.$type, $data);
        }
        return $data;
    }
}

if (!function_exists('write_addons_info')) {
    /**
     * 写入插件ini文件信息
     * @param $filename string 应用跟路径
     * @param $array array 配置字段数组
     * @return bool|string
     */
    function write_addons_info($filename, $array)
    {
        $tempArr = [];
        foreach ($array as $key=>$value) {
            if (is_array($value)) {
                if (empty($value)) {
                    continue;
                }
                $tempArr[] = "[{$key}]";
                foreach ($value as $k=>$v) {
                    $tempArr[] = is_numeric($value) ? "$k = $v" : "$k = \"$v\"";
                }
            } else {
                $tempArr[] = is_numeric($value) ? "$key = $value" : "$key = \"$value\"";
            }
        }

        // 写入配置
        if ($handle = fopen($filename, 'w')) {
            fwrite($handle, implode("\n", $tempArr)."\n");
            fclose($handle);
        } else {
            return lang('%s,File write failed', ["[$filename]"]);
        }
        return true;
    }
}

if (!function_exists('get_addons_config')) {
    /**
     * 获取插件配置
     * @param string $type 类型
     * @param string $name 标识
     * @param string $module 模块
     * @param bool $complete true-获取所有结构数组，false-获取配置值
     * @return array|mixed|null
     */
    function get_addons_config($type, $name, $module='', $complete=false)
    {
        if ($type=='template') {
            $k = "template_{$name}_config".$module;
            $config_file = config('cms.tpl_path'). $module . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . 'config.json';
        } else {
            $k = "addon_{$name}_config";
            $config_file = app()->addons->getAddonsPath() . $name . DIRECTORY_SEPARATOR . 'config.php';
        }
        
        $config = app()->isDebug() ? [] : app()->cache->get($k);
        if ($config && $complete===false) {
            return $config;
        }

        // 优先从数据库里取
        $arr1 = $arr2 = [];
        $temp_arr = \app\admin\model\App::where(['name'=>$name])->value('config');
        if (!empty($temp_arr)) {
            $temp_arr = json_decode($temp_arr, true);
            foreach ($temp_arr as $key => $value) {
                if (!empty($value['item'])) {
                    foreach ($value['item'] as $kk=>$v) {
                        $config[$key][$kk] = $v['value'];
                    }
                } else {
                    $config[$key] = $value['value'];
                }
            }
        }
        // 配置文件
        if (is_file($config_file)) {
            $arr2 = $type!='template'?(array)include $config_file:json_decode(file_get_contents($config_file),true);
            if (is_array($arr2)) {
                $temp_arr = $arr2;
                foreach ($temp_arr as $key => $value) {
                    if (!empty($value['item'])) {
                        foreach ($value['item'] as $kk=>$v) {
                            $temp_arr[$key]['item'][$kk]['value'] = $config[$key][$kk] ?? $temp_arr[$key]['item'][$kk]['value'];
                        }
                    } else {
                        $temp_arr[$key]['value'] = $config[$key] ?? $temp_arr[$key]['value'];
                    }
                }
            }
        }

        if (!empty($temp_arr)) {
            if ($complete) {
                return $temp_arr;
            }
            $config = [];
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
                    if (in_array($value['type'], ['checkbox','selects']) || ($value['type']=='selectpage' && !empty($value['data_list']['multiple']))) {
                        $config[$key] = explode(',', $value['value']);
                    } else {
                        $config[$key] = $value['value'];
                    }
                }
            }
            app()->cache->tag('addons')->set($k, $config);
        }
        return $config;
    }
}

if (!function_exists('write_addons_config')) {
    /**
     * 写入插件配置文件
     * @param $type
     * @param $name
     * @param $module
     * @param $data
     */
    function write_addons_config($type, $name, $module, $data)
    {
        if ($type=='template') {
            $config_file = config('cms.tpl_path') . $module . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . 'config.php';
        } else {
            $config_file = app()->addons->getAddonsPath() . $name . DIRECTORY_SEPARATOR . 'config.php';
        }

        if (!is_really_writable($config_file)) {
            throw new \think\addons\AddonsException(lang('%s,File cannot be written',[$config_file]));
        }

        if ($handle=fopen($config_file, 'w')) {
            fwrite($handle, "<?php\n\n"."return ".\Symfony\Component\VarExporter\VarExporter::export($data).";\n");
            fclose($handle);
        } else {
            throw new \think\addons\AddonsException(lang('%s,File has no permission to write', [$config_file]));
        }
    }
}

if (!function_exists('addons_exist')) {
    /**
     * 判断插件是否存在
     * @param $name
     * @param $type
     * @param $module
     */
    function addons_exist($name, $type='addon', $module='index')
    {
        $info = \app\admin\model\App::where(['name'=>$name,'type'=>$type])->find();
        if (empty($info) || $info['status']!=1) {
            return false;
        }
        return $info;
    }
}