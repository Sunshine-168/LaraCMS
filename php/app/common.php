<?php

if (!function_exists('__')) {
    /**
     * 获取语言变量值，同TP lang()方法一致
     * @param string $name
     * @param array $vars
     * @param string $lang
     * @return mixed
     */
    function __(string $name, array $vars = [], string $lang = '')
    {
        return lang($name, $vars, $lang);
    }
}

if (!function_exists('get_doc_total')) {
    /**
     * 获取栏目文档数量
     * @param array $cateInfo 栏目信息
     * @param bool $is_sub true-统计包含下级，false-不含下级
     * @return int
     */
    function get_doc_total($cateInfo, $is_sub=false)
    {
        if ($is_sub) {
            $cache = cache('category_doc_total_sub');
        } else {
            $cache = cache('category_doc_total');
        }
        if (isset($cache[$cateInfo['id']])) {
            return $cache[$cateInfo['id']];
        }

        $model = \app\admin\model\cms\Model::where(['status'=>'normal','id'=>$cateInfo['model_id']])->find();
        if (empty($model)) {
            return 0;
        }

        $c = '\app\admin\model\cms\\'.$model->controller;
        if ($is_sub) {
            $tempArr = get_category_sub($cateInfo['id'], true);
            $num = (new $c)->whereIn('category_id', $tempArr)->where(['status'=>'normal'])->count();
        } else {
            $num = (new $c)->where(['status'=>'normal','category_id'=>$cateInfo['id']])->count();
        }

        $cache[$cateInfo['id']] = $num;
        if ($is_sub) {
            cache('category_doc_total_sub', $cache);
        } else {
            cache('get_doc_total', $cache);
        }
        return $num;
    }
}

if (!function_exists('to_guid_string')) {
    /**
     * 根据PHP各种类型变量生成唯一标识字符
     * @param mixed $mix 变量
     * @return string
     */
    function to_guid_string($mix)
    {
        if (is_object($mix)) {
            return spl_object_hash($mix);
        } elseif (is_resource($mix)) {
            $mix = get_resource_type($mix) . strval($mix);
        } else {
            $mix = serialize($mix);
        }
        return md5($mix);
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML special characters in a string.
     *
     * @param $value
     * @return string
     */
    function e($value)
    {
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
    }
}

if (!function_exists('get_random_str')) {
    /**
     * 产生一个指定长度的随机字符串,并返回给用户
     * @param integer $len 产生字符串的长度
     * @return string 随机字符串
     */
    function get_random_str($len = 6)
    {
        $chars = array(
            "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
            "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
            "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
            "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
            "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
            "3", "4", "5", "6", "7", "8", "9"
        );
        $charsLen = count($chars) - 1;
        // 将数组打乱
        shuffle($chars);
        $output = "";
        for ($i = 0; $i < $len; $i++) {
            $output .= $chars[mt_rand(0, $charsLen)];
        }
        return $output;
    }
}

if (!function_exists('get_upgrade')) {
    /**
     * 筛选更新包
     * @param $version string 当前安装的版本
     * @param $versionArr array 所有版本数组信息
     * @return array|bool
     */
    function get_upgrade($version, $versionArr)
    {
        $arr = [];
        if (empty($versionArr)) {
            return false;
        }
        foreach ($versionArr as $key=>$value) {
            if (version_compare($value, $version, '>')) {
                $arr[] = $value;
            }
        }
        return empty($arr) ? false : $arr;
    }
}

if (!function_exists('load_js')) {
    /**
     * 加载插件JS
     */
    function load_js()
    {
        return app()->make(\app\common\services\system\AddonService::class)->loadJs();
    }
}

if (!function_exists('is_really_writable')) {
    /**
     * 判断文件、目录是否可写
     * @param $file
     * @return bool
     */
    function is_really_writable($file)
    {
        return \think\addons\Dir::instance()->isReallyWritable($file);
    }
}

if (!function_exists('loadlang')) {
    /**
     * 加载对应控制器的语言包
     * @param $name
     */
    function loadlang($name)
    {
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
        $app = app();
        $app->lang->load($app->getAppPath().'lang'.DIRECTORY_SEPARATOR.$app->lang->getLangset().DIRECTORY_SEPARATOR.$path.'.php');
    }
}

if (!function_exists('load_template_lang')) {
    /**
     * 加载模板语言包
     * @param $name
     * @return array|mixed
     */
    function load_template_lang($name)
    {
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
        $app = app();
        $root = config('cms.tpl_static').$app->http->getName().DIRECTORY_SEPARATOR.site($app->http->getName().'_theme').DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR;
        $lang = $app->lang->getLangset();
        $loadFile = [];
        if (is_file($root.$lang.'.json')) {
            $loadFile[] = $root.$lang.'.json';
            $json = file_get_contents($root.$lang.'.json');
            $arr = json_decode($json, true);
            $more = $root.$lang.DIRECTORY_SEPARATOR;
            if (is_file($more.$path.'.json')) {
                $loadFile[] = $more.$path.'.json';
                $json = file_get_contents($more.$path.'.json');
                $temp = json_decode($json, true);
                $arr = array_merge($arr, $temp);
            }
            $app->lang->load($loadFile);
            return $arr;
        }
        return [];
    }
}

if (!function_exists('load_taglib')) {
    /**
     * 预加载插件标签库
     */
    function load_taglib()
    {
        $view = app()->config->get('view');
        $arr = explode(',', $view['taglib_pre_load']);

        $addons = \think\facade\Db::name('app')->where(['status'=>1,'type'=>'addon'])->cache('load_taglib',604800, 'system')->select();
        if ($addons) {
            $path = app('addons')->getAddonsPath();
            foreach ($addons as $addon) {
                $taglib = $path.$addon['name'].DIRECTORY_SEPARATOR.'taglib'.DIRECTORY_SEPARATOR;
                if (!is_dir($taglib)) {
                    continue;
                }
                $info = glob($taglib . '*.php');
                foreach ($info as $key=>$value) {
                    $value = basename($value,'.php');
                    if (!in_array('addons\\'.$addon['name'].'\\taglib\\'.$value, $arr)) {
                        $arr[] = 'addons\\'.$addon['name'].'\\taglib\\'.$value;
                    }
                }
            }
        }

        $view['taglib_pre_load'] = implode(',', $arr);
        app()->config->set($view,'view');
    }
}

if (!function_exists('index_url')) {
    /**
     * 在其他模块生成前台URL
     * @param $url string URL地址，伪静态URL，没有则默认跟TP url生成一样
     * @param array $param 参数
     * @param bool|string $suffix
     * @param bool|string $domain
     * @param string $baseFile 增加入口文件
     * @param array $ruleParam 规则变量，补充额外的规则变量
     * @param array $extend 在伪静态情况下，该参数追加到url地址上
     * @return string|string[]
     */
    function index_url($url, $param=[], $suffix = true, $domain = false, $baseFile='', $ruleParam = [], $extend = [])
    {
        $site = site(); // 获取伪静态规则
        if ($site['url_mode']==1 && !empty($site['url_rewrite'])) {
            $cacheId = to_guid_string(array_merge([$site['url_rewrite'],$url,['domain'=>$domain]],$param));
            $cache_url = cache($cacheId);
            if (!app()->isDebug() && $cache_url) {
                return $cache_url;
            }

            // 提取设置好的伪静态变量
            $urlRewrite = [];
            foreach ($site['url_rewrite'] as $key=>$value) {
                $value = preg_replace(['/\[\:(\w+)\]/', '/\:(\w+)/'], ['<\1?>', '<\1>'], $value);
                if (preg_match_all('/<\w+\??>/', $value, $matches)) {
                    foreach ($matches[0] as $name) {
                        if (strpos($name, '?')) {
                            $name     = substr($name, 1, -2);
                        } else {
                            $name = substr($name, 1, -1);
                        }
                        $urlRewrite[$name] = $name;
                    }
                }
            }

            // cms默认变量
            $rules = [];
            $cor = array_merge($urlRewrite,[
                'catdir'=>'parent_id', // 栏目父级英文
                'catname'=>'name',
                'catid'=>'id',
                'id'=>'aid', // 文章ID
                'model'=>'model_id',
                'year'=>'update_time',
                'month'=>'update_time',
                'day'=>'update_time',
                'page'=>'page'
            ], $ruleParam);

            if (isset($param['update_time'])) {
                $param['update_time'] = strtotime($param['update_time'])?:$param['update_time'];
            }
            foreach ($cor as $key=>$value) {
                if (!isset($param[$value])) {
                    continue;
                }
                if ($key=='year') {
                    $rules[$key] = date('Y', $param[$value]);
                    continue;
                }
                if ($key=='month') {
                    $rules[$key] = date('m', $param[$value]);
                    continue;
                }
                if ($key=='day') {
                    $rules[$key] = date('d', $param[$value]);
                    continue;
                }
                $rules[$key] = $param[$value];
            }

            // 扩展参数赋值
            foreach ($extend as $key=>$value) {
                $extend[$key] = isset($param[$key])?$param[$key]:$value;
            }

            $rule = $rules;
            $oldUrl = $url; // 不匹配时，给到动态
            $url = ltrim($url,'/');
            $rewrite = $site['url_rewrite'][$url]??'';
            if ($rewrite && $rewriteArr = explode(',', $rewrite)) {
                if (site('content_lang_on')==1) { // 开启多语言
                    $rule['lang'] = $param['lang'] ?? get_curlang();
                }

                // 匹配其中一条规则
                foreach ($rewriteArr as $value) {
                    $value = preg_replace(['/\[\:(\w+)\]/', '/\:(\w+)/'], ['<\1?>', '<\1>'], $value);

                    // 提取路由规则中的变量
                    $allTmp = [];
                    $fTmp = [];
                    if (preg_match_all('/<\w+\??>/', $value, $matches)) {
                        foreach ($matches[0] as $name) {
                            if (strpos($name, '?')) {
                                $name     = substr($name, 1, -2);
                            } else {
                                $name = substr($name, 1, -1);
                            }
                            if (isset($rule[$name])) {
                                $fTmp[] = $name;
                            }
                            $allTmp[] = $name;
                        }
                    }
                    if (count($allTmp) == count($fTmp)) {
                        $rewrite = $value;
                    }
                }

                $rewrite = stripslashes($rewrite); // 删除反斜杠
                if (site('content_lang_on')==1 && !((strpos($rewrite, '<lang>')!==false || strpos($rewrite, '<lang?>')!==false))) { // 开启多语言
                    $extend['lang'] = $rule['lang'];
                }

                // 分页页码存在的情况下必须带上
                if (isset($rule['page']) && is_numeric($rule['page']) && empty($extend['page'])) {
                    $extend['page'] = $rule['page'];
                }

                array_walk($rule, function ($val, $key) use(&$rewrite, &$extend, $param) {
                    if ((strpos($rewrite, '<catdir>')!==false || strpos($rewrite, '<catdir?>')!==false) && $key=='catdir' && isset($param['parent_id'])) {
                        $name = '';
                        if ($param['parent_id']) {
                            $name = \think\facade\Db::name('category')->where(['id'=>$param['parent_id']])->where(['model_id'=>$param['model_id']])->value('name');
                        }
                        if ((strpos($rewrite, '<catname>')!==false || strpos($rewrite, '<catname?>')!==false)) {
                            $val = $name;
                        } else {
                            $val = $name?$name:$param['name']; // 父级不存在使用当前目录
                        }
                    } else if ((strpos($rewrite, '<model>')!==false || strpos($rewrite, '<model?>')!==false) && $key=='model') {
                        $modelInfo = \app\admin\model\cms\Model::where(['id'=>$param['model_id']])->cache()->find();
                        if ($modelInfo) {
                            $val = $modelInfo && $modelInfo->diyname ? $modelInfo->diyname : $modelInfo->tablename;
                        }
                    }

                    // url文章别名
                    if ((strpos($rewrite, '<id>')!==false || strpos($rewrite, '<id?>')!==false) && $key=='id' && !empty($param['diyname'])) {
                        $val = $param['diyname'];
                    }

                    if (strpos($rewrite, '<'.$key.'>')!==false || strpos($rewrite, '<'.$key.'?>')!==false) {
                        unset($extend[$key]); // 对已匹配的参数，剔除扩展参数
                    }

                    $rewrite = str_replace(["<{$key}>","<{$key}?>"],$val, $rewrite);
                });
                $rewrite = str_replace(['$','//'],['','/'], $rewrite);
                $url = (string) url($rewrite, $extend, false,$domain);
                $url = preg_replace("/\/([\w]+)\.php\//i", "/", $url);
                if (!app()->isDebug()) {
                    cache($cacheId, $url);
                }
                return $url;
            } else {
                $url = \think\facade\Route::buildUrl($oldUrl, $param)->suffix($suffix)->domain($domain)->build();
                $file = $baseFile ?:app('request')->baseFile();
                $url = str_replace($file,'', $url);
                if (!app()->isDebug()) {
                    cache($cacheId, $url);
                }
                return $url;
            }
        } else {
            // 生成URL地址
            $url = \think\facade\Route::buildUrl($url, $param)->suffix($suffix)->domain($domain)->build();
            // 是否显示入口文件index.php文件
            $siteFile = site('url_mode')==0 ? site('url_file') : 0;
             if ($siteFile==1) { // 显示入口文件，用于兼容不支持伪静态
                 $file = $baseFile?$baseFile:'/index.php'; // 默认index_url 跳转的是首页

                 if ($domain) {
                     if (preg_match("/^(https|http):\/\/([^\/]+)/i", $url, $mat)) {
                         $url = preg_replace("/^(https|http):\/\/([^\/]+)/i",'', $url);
                         $url = $mat[0].$file.preg_replace("/\/([\w]+)\.php\//i", "/", $url);
                     }
                 } else {
                     $url = $file.preg_replace("/\/([\w]+)\.php\//i", "/", $url);
                 }
            } else { // 隐藏入口文件，必须配置URL重写
                $url = $baseFile ? preg_replace("/\/([\w]+)\.php\//i", "/$baseFile/", $url) : preg_replace("/\/([\w]+)\.php\//i", "/", $url);
            }
            return $url;
        }
    }
}

if (!function_exists('build_tp_rules')) {

    /**
     * 对前端验证生成TP验证规则
     * @param array $modelField 字段数组
     * @return array 返回tp规则与错误提示
     */
    function build_tp_rules($modelField)
    {
        // 验证
        $tp_rule = config('cms.rule_lists_tp');
        $tp_msg = config('cms.rule_lists_msg');
        $valData = [];
        $msgData = [];
        foreach ($modelField as $key=>$val) {
            if (empty($val['rules'])) {
                continue;
            }

            $tempArr = explode(',', $val['rules']);
            $rules = [];
            foreach ($tempArr as $k=>$v) {
                if (!isset($tp_rule[$v])) {
                    continue;
                }

                // 解析前端规则、配置TP验证
                if (is_array($tp_rule[$v])) {
                    $msg_k = key($tp_rule[$v]);
                    $rules[$msg_k] = $tp_rule[$v][$msg_k];
                } else {
                    $msg_k = $tp_rule[$v];
                    $rules[] = $tp_rule[$v];
                }

                // 自动追加 max 规则
                if (in_array($val['form_type'], ['text','textarea'])) {
                    $rules['max'] = $val['length'];
                }

                $rules = array_unique($rules); // 防止重复规则
                if (isset($tp_msg[$v.'.'.$msg_k])) { // 提示信息生成
                    $msgData[$val['field_name'].'.'.$msg_k] = $tp_msg[$v.'.'.$msg_k];
                }
            }
            $valData[$val['field_name'].'|'.__($val['field_title'])] = $rules;
        }
        return [$valData, $msgData];
    }
}

if (!function_exists('get_template_path')) {
    /**
     * 获取当前模板主题根路径
     * @param string $name 模块
     * @return object|string
     */
    function get_template_path($name='')
    {
        $request = app('request');
        $cache = app('cache');
        $config = app('config');

        // 获取当前应用
        $name = empty($name) ? app('http')->getName() : $name;

        // 获取当前主题
        $theme = site($name.'_theme');
        $type = $request->isMobile() ? 'mobile' : 'pc';
        $themePath = app()->isDebug() ? '' : $cache->get('path_'.$type.$theme);
        if (empty($themePath)) {
            // 定位模板位置
            $themePath = $config->get('cms.tpl_path').$name.DIRECTORY_SEPARATOR.$theme.DIRECTORY_SEPARATOR;
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

            if (count($dirArr)==2) { // 非响应式
                // 判断当前访问类型
                if ($type=='mobile') {
                    // 判断当前模板是移动跟PC分开。
                    $themePath = $themePath.'mobile'.DIRECTORY_SEPARATOR;
                } else {
                    $themePath = $themePath.'pc'.DIRECTORY_SEPARATOR;
                }
            }
            $cache->set('path_'.$type.$theme, $themePath, 7200);
        }
        return $themePath;
    }
}

if (!function_exists('get_current')) {
    /**
     * 设定当前页面的栏目亮色
     * @param $item array 栏目循环项
     * @param $categoryIdArr array 当前页面的栏目信息
     * @param $currentstyle string 选中标识
     * @return mixed
     */
    function get_current($item, $categoryIdArr, $currentstyle)
    {
        if (!empty($categoryIdArr)) {
            if (in_array($item['id'], $categoryIdArr)) {
                return $currentstyle;
            }
        } else if ('link'==$item['type'] && empty($item['model_id'])) {
            if ($item['url']==\think\facade\Request::baseUrl()) {
                return $currentstyle;
            }
        }
        return '';
    }
}

if (!function_exists('get_date_format')) {
    /**
     * 格式化日期
     * @param $tag
     * @return false|string
     */
    function get_date_format($tag)
    {
        if (!is_numeric($tag['name'])) {
            $tag['name'] = strtotime($tag['name']);
            if (empty($tag['name'])) {
                return '';
            }
        }
        if (isset($tag['api']) && $tag['api']=='human' && ((!isset($tag['lt'])) || (isset($tag['lt']) && (time() - $tag['name'])<$tag['lt']))) { // 采用语义化格式
            $time = time() - $tag['name'];

            if ($time<60) {
                return __('Now');
            } else if (($t = floor($time / (60*60*24*365)))!=0) {
                return __('%s year ago',[$t]);
            } else if (($t = floor($time / (60*60*24*30)))!=0) {
                return __('%s month ago',[$t]);
            } else if (($t = floor($time / (60*60*24*7)))!=0) {
                return __('%s week ago',[$t]);
            } else if (($t = floor($time / (60*60*24)))!=0) {
                return __('%s day ago',[$t]);
            } else if (($t = floor($time / (60*60)))!=0) {
                return __('%s hour ago',[$t]);
            } else if (($t = floor($time / 60))!=0) {
                return __('%s minute ago',[$t]);
            }
        }
        return date($tag['format'], $tag['name']);
    }
}

if (!function_exists('get_category')) {
    /**
     * 获取栏目信息， \app\index\model\cms\Category
     * @param integer | string $catId 栏目ID，或栏目英文
     * @param bool $clear true-刷新缓存，false-不刷新
     * @return array|bool|mixed|object|\think\App
     */
    function get_category($catId, $clear=false)
    {
        $cacheID = 'getCateInfo_' . $catId;
        //强制刷新缓存
        if ($clear) {
            cache($cacheID, NULL);
        }
        if (!app()->isDebug() && $cacheData = cache($cacheID)) {
            return $cacheData;
        }

        if (is_numeric($catId)) {
            $cateInfo = \app\index\model\cms\Category::where(['status'=>'normal','id'=>$catId])->find();
        } else if (!empty($catId) && \think\facade\Validate::is($catId,'alphaDash')) {
            $cateInfo = \app\index\model\cms\Category::where(['status'=>'normal','name'=>$catId])->find();
        } else {
            return false;
        }
        if (empty($cateInfo)) {
            return false;
        }

        $cateInfo = $cateInfo->append(['parent_url'])->toArray();
        if (!app()->isDebug()) {
            cache($cacheID, $cateInfo, 3600, 'category_tag');
        }
        return $cateInfo;
    }
}

if (!function_exists('get_category_sub')) {
    /**
     * 获取栏目所有下级ID
     * @param int $cateId 栏目ID
     * @param bool $is_self true-包含自己，false-不含自己
     * @param array $extend 扩展其他字段以限制查询条件
     * @return array
     */
    function get_category_sub($cateId, $is_self=false, $extend = [])
    {
        $all = \app\admin\model\cms\Category::where(['parent_id'=>$cateId])->where($extend)->column('id');
        $tempArr = $all;
        if ($is_self) {
            $tempArr[] = $cateId;
        }
        foreach ($all as $key=>$value) {
            if ($temp = get_category_sub($value)) {
                $tempArr = array_merge($tempArr, $temp);
            }
        }
        return $tempArr;
    }
}

if (!function_exists('get_category_parent')) {
    /**
     * 获取栏目的所有上级
     * @param int $cateId 栏目ID
     * @param bool $is_self  true-包含自己，false-不包含
     * @return array
     */
    function get_category_parent($cateId, $is_self=false)
    {
        $info = \app\admin\model\cms\Category::where(['id'=>$cateId])->find();

        $tempArr = [];
        if ($is_self) {
            $tempArr[] = $info['id'];
        }

        if ($info['parent_id']!=0 && $temp = get_category_parent($info['parent_id'], true)) {
            $tempArr = array_merge($tempArr, $temp);
        }
        return $tempArr;
    }
}

if (!function_exists('get_category_top')) {
    /**
     * 获取最上级的栏目信息
     * @param $cateId
     * @return array|\think\Model
     */
    function get_category_top($cateId)
    {
        $info = \app\admin\model\cms\Category::where(['id'=>$cateId])->find();
        if ($info['parent_id']) {
            return get_category_top($info['parent_id']);
        }
        return $info;
    }
}

if (!function_exists('controller')) {
    /**
     * 获取模型管理操作的控制类
     * @param array | integer $info 模型ID、或栏目ID、查询出的模型数组、栏目数组
     * @param Closure $fun 回调
     * @param string $type model-模型数据，category-栏目数据
     * @return mixed
     */
    function controller($info, Closure $fun, $type='model')
    {
        $category = [];
        if (is_numeric($info) && $type=='category') {
            $category = get_category($info);
            $info = $category['model_id'];
        } else if (is_array($info) && $type=='category') {
            $category = $info;
            $info = $info['model_id'];
        } else if (is_array($info) && $type=='model') {
            $info = $info['id'];
        }

        $model = \app\admin\model\cms\Model::where(['id'=>$info,'status'=>'normal'])->find();
        if (empty($model)) {
            return [];
        }
        $c = '\app\admin\model\cms\\'.$model->controller;
        $obj = (new $c);
        return $fun($obj, $model, $category);
    }
}

if (!function_exists('create_menu')) {
    /**
     * 创建菜单
     * @param array $menu 菜单数组
     * @param string $app 所属app(插件标识)
     * @return bool
     */
    function create_menu(array $menu, string $app = '')
    {
        try {
            $bl = (new \app\admin\model\auth\AuthRule)->createMenu($menu, $app);
        } catch (\Exception $exception) {
            trace("创建菜单失败：".$exception->getMessage(),'error');
            $bl = false;
        }
        app('cache')->tag('menu')->clear();
        return $bl;
    }
}

if (!function_exists('status_menu')) {
    /**
     * 更改状态
     * @param integer $status 状态:normal-正常,hidden-禁用
     * @param string $app
     */
    function status_menu($status = 1, string $app = '')
    {
        $status = $status ? 'normal':'hidden';
        app('cache')->tag('menu')->clear();
        return \app\admin\model\auth\AuthRule::where(['app'=>$app])->save(['status'=>$status]);
    }
}

if (!function_exists('del_menu')) {
    /**
     * 删除菜单
     * @param string $app 插件标识
     * @return bool
     */
    function del_menu(string $app)
    {
        app('cache')->tag('menu')->clear();
        $ids = \app\admin\model\auth\AuthRule::where(['app'=>$app])->column('id');
        if ($ids) {
            \think\facade\Db::name('admin_panel')->whereIn('auth_rule_id',$ids)->delete();
        }
        return \app\admin\model\auth\AuthRule::where(['app'=>$app])->delete();
    }
}

if (!function_exists('create_sql')) {

    /**
     * 执行SQL文件
     * @param string $file sql文件绝对路径
     * @return bool
     */
    function create_sql(string $file)
    {
        if (!is_file($file)) {
            trace('执行SQL失败：'.$file.',文件不存在', 'error');
            return false;
        }
        $sqlStr = file_get_contents($file);

        $config = \think\facade\Db::getConfig('connections');

        //表前缀，表名，模型id替换
        $sqlSplit = str_replace('@prefix@', $config['mysql']['prefix'], $sqlStr);
        $sqlSplit = rtrim($sqlSplit, ';');
        try {
            $sql = explode(';',$sqlSplit);
            foreach ($sql as $key=>$value) {
                \think\facade\Db::execute($value);
            }
            return true;
        } catch (\Exception $exception) {
            trace('执行SQL失败：'.$exception->getMessage(), 'error');
            return false;
        }
    }
}

if (!function_exists('cdn_url')) {
    /**
     * 获取完整URL地址
     * @param string $path 相对资源路径
     * @param string|bool $domain true-显示域名，false-不显示，或直接写完整域名
     * @return string
     */
    function cdn_url(string $path, $domain = false)
    {
        if (empty($path)) {
            return '';
        }

        // 判断是否是base64图片
        if (\think\facade\Validate::regex($path, '/^data:image\/(.*)/')) {
            return $path;
        }
        // 判断是否已经有域名了
        if (\think\facade\Validate::regex($path, '/^https?:\/\/(([a-zA-Z0-9_-])+(\.)?)*(:\d+)?\//i')) {
            return $path;
        }

        $url = site('cdn_url');
        if (empty($url) && $domain) {
            return is_bool($domain) ? app('request')->domain().$path : $domain.$path;
        } else {
            $url = rtrim($url,'/');
            return $url.$path;
        }
    }
}

if (!function_exists('site')) {
    /**
     * 获取站点配置
     * @param string $name  参数名
     * @param string $module  模块
     * @return mixed
     */
    function site(string $name = '', string $module = '')
    {
        return app(\app\common\services\config\ConfigService::class)->site($name, $module);
    }
}

if (!function_exists('field_format')) {
    /**
     * 字段格式化内容
     * @param $field array 字段信息
     * @param $value array 字段数据
     */
    function field_format($field, &$value)
    {
        if (empty($field['field_name']) || !isset($value[$field['field_name']])) {
            return false;
        }
        if ($field['form_type']=='array' && isset($value[$field['field_name']])) {
            $value[$field['field_name']] = json_decode(htmlspecialchars_decode($value[$field['field_name']]), true);
        } else if (in_array($field['form_type'], ['checkbox','selects']) && isset($value[$field['field_name']])) {
            $value[$field['field_name']] = $value[$field['field_name']] ? explode(',', $value[$field['field_name']]) : '';
        } else if (in_array($field['form_type'], ['images','downfiles']) && isset($value[$field['field_name']])) { // 多图、多文件
            // $value[$field['field_name']] = $value[$field['field_name']] ? explode(',', $value[$field['field_name']]) : '';
            if ($value[$field['field_name']]) {
                $tmpVal = json_decode($value[$field['field_name']], true);
                if ($tmpVal) {
                    $imgBox = [];
                    foreach ($tmpVal as $item) {
                        $imgBox[] = $item['file'];
                    }
                    // 兼容旧版
                    $value[$field['field_name']] = $imgBox;
                    // 获取图片备注用法,字段名字前后加下划线，例如：_images_
                    $value['_'.$field['field_name'].'_'] = $tmpVal;
                } else {
                    $value[$field['field_name']] = explode(',', $value[$field['field_name']]);
                }
            } else {
                $value[$field['field_name']] = null;
            }
        } else if (in_array($field['form_type'], ['textarea','editor']) && isset($value[$field['field_name']])) {
            $value[$field['field_name']] = htmlspecialchars_decode($value[$field['field_name']]);
        } else if (in_array($field['form_type'], ['selectpage']) && !is_array($field['data_list'])) { // 动态下拉
            $data_list = json_decode($field['data_list'],true);
            if (empty($data_list) || empty($data_list['type'])) {
                return false;
            }
            // 多语言
            $map = [];
            if (!empty($data_list['enable-lang']) && $data_list['enable-lang']==1) {
                $map = [['lang','=',app()->lang->getLangSet()]];
            }
            $value[$field['field_name'].'_raw'] = $value[$field['field_name']];
            // url形式，多选转数组
            if ($data_list['type']=='url' && !empty($data_list['multiple']) && $data_list['multiple']==1) {
                $value[$field['field_name']] = $value[$field['field_name']] ? explode(',', $value[$field['field_name']]) : '';
            } else if ($data_list['type']=='table' && !empty($data_list['table']) && !empty($data_list['key-field']) && !empty($data_list['multiple']) && $data_list['multiple']==1) {
                // 关联表，多选
                if (empty($value[$field['field_name']])) { // 关联表值为空或0不进行关联
                    return [];
                }
                $value[$field['field_name']] = \think\facade\Db::name($data_list['table'])->whereIn($data_list['key-field'], $value[$field['field_name']])->where($map)->select()->toArray();
            } else if ($data_list['type']=='table' && !empty($data_list['table']) && !empty($data_list['key-field']) && (empty($data_list['multiple']) || $data_list['multiple']!=1)) {
                // 关联表，单选
                if (empty($value[$field['field_name']])) { // 关联表值为空或0不进行关联
                    return [];
                }
                $value[$field['field_name']] = \think\facade\Db::name($data_list['table'])->where($data_list['key-field'], $value[$field['field_name']])->where($map)->find();
            }
        }
    }
}

if (!function_exists('clear_cache')) {
    /**
     * 缓存清理
     */
    function clear_cache()
    {
        $cacheService = app()->make(\app\common\services\cache\CacheService::class);
        $cacheService->clearAll();
    }
}

if (!function_exists('get_curlang')) {
    /**
     * 获取当前内容语言
     * @return mixed|string
     */
    function get_curlang()
    {
        $module = app()->http->getName();
        return app()->make(\app\common\services\lang\LangService::class)->getLang($module);
    }
}

if (!function_exists('ColorHSLToRGB')) {
    /**
     * HSL转RGB
     * @param $h
     * @param $s
     * @param $l
     * @return array
     */
    function ColorHSLToRGB($h, $s, $l)
    {
        $r = $g = $b = 0;
        $v = ($l <= 0.5) ? ($l * (1.0 + $s)) : ($l + $s - $l * $s);
        if ($v > 0){
            $m = $l + $l - $v;
            $sv = ($v - $m ) / $v;
            $h *= 6.0;
            $sextant = floor($h);
            $fract = $h - $sextant;
            $vsf = $v * $sv * $fract;
            $mid1 = $m + $vsf;
            $mid2 = $v - $vsf;

            switch ($sextant)
            {
                case 0:
                    $r = $v;
                    $g = $mid1;
                    $b = $m;
                    break;
                case 1:
                    $r = $mid2;
                    $g = $v;
                    $b = $m;
                    break;
                case 2:
                    $r = $m;
                    $g = $v;
                    $b = $mid1;
                    break;
                case 3:
                    $r = $m;
                    $g = $mid2;
                    $b = $v;
                    break;
                case 4:
                    $r = $mid1;
                    $g = $m;
                    $b = $v;
                    break;
                case 5:
                    $r = $v;
                    $g = $m;
                    $b = $mid2;
                    break;
            }
        }
        return array(floor($r * 255.0), floor($g * 255.0), floor($b * 255.0));
    }
}

if (!function_exists('captcha')) {
    /**
     * 生成验证码
     * @param string $config
     * @param integer $type
     * @return \think\Response | string
     */
    function captcha($config = null, $type = 1)
    {
        $app = app();
        return (new \libs\captcha\Captcha($app->config,$app->session))->create($config);
    }
}

if (!function_exists('captcha_check')) {
    /**
     * 验证码验证
     * @param string $value
     * @return bool
     */
    function captcha_check($value)
    {
        $app = app();
        return (new \libs\captcha\Captcha($app->config,$app->session))->check($value);
    }
}

if (!function_exists('getExtToMime')) {
    /**
     * 后缀与mime对照关系
     * @param $obj
     * @param string $type
     * @return array|mixed|string
     */
    function getExtToMime($obj, $type='ext')
    {
        $array = json_decode(file_get_contents(public_path('static/common').'filetype.json'), true);

        if ($type=='ext') {
            return isset($array[$obj]) ? $array[$obj] : '';
        } else {
            $res = [];
            foreach ($array as $key=>$value) {
                if ($value == $obj) {
                    $res[] = $key;
                }
            }
            return $res;
        }
    }
}

if (!function_exists('thumb')) {
    /**
     * 缩略图
     * @param string $imgSrc 图片地址
     * @param string $w 宽
     * @param string $h 高
     * @param string $type 生成方式
     * @param bool $bl true-不存在自动生成缩略
     * @return string
     */
    function thumb($imgSrc, $w = null, $h = null, $type = null, $bl = false)
    {
        if (empty($imgSrc)) {
            return '';
        }

        $path = public_path();
        $imgPath = str_replace('\\','/', $path.ltrim($imgSrc,'/'));
        if (!file_exists($imgPath)) {
            return $imgSrc;
        }

        $config = site();

        try {
            $image = \libs\image\Image::open($imgPath);

            // 判断是否缩略图已经存在
            $ext = pathinfo($imgPath, PATHINFO_EXTENSION);
            $src = dirname($imgSrc).'/'.basename($imgSrc,'.'.$ext).'_thumb.'.$ext;
            $srcPath = str_replace('\\','/', $path.ltrim($src,'/'));
            if (file_exists($srcPath)) {
                return $src;
            }

            if ($bl) {
                // 返回图片的宽度
                $width = $image->width();
                // 返回图片的高度
                $height = $image->height();
                // 宽度
                $w = empty($w) ? $config['thumb_width'] : $w;
                // 高度
                $h = empty($h) ? $config['thumb_height'] : $h;
                // 生成类型
                $type = empty($type) ? $config['thumb_type'] : $type;
                // 高度判断
                if ($w>$width || $h>$height) {
                    return $imgSrc;
                }

                $image->thumb($w, $h, $type)->save($srcPath);

                // 保存缩略图到数据库
                if ($value = \app\admin\model\routine\Attachment::where(['path'=>$imgSrc])->find()) {
                    $value = $value->toArray();
                    $value['md5'] = md5($srcPath);
                    $value['size'] = filesize($srcPath);
                    $value['title'] = $value['title'].'[缩略图]';
                    $value['path'] = $src;
                    unset($value['update_time']);
                    unset($value['create_time']);
                    unset($value['id']);
                    (new \app\admin\model\routine\Attachment)->save($value);
                }

                return $src;
            }
            return $imgSrc;
        } catch (\Exception $exception) {
            return '';
        }
    }
}

if (!function_exists('get_category_parent_id')) {

    /**
     * 获取栏目上级id
     * @param $cateId int 分类id
     * @param $level int 级数
     * @return mixed
     */
    function get_category_parent_id($cateId, $level)
    {
        for ($i = 0; $i < $level; $i++) {
            $cateId = \app\admin\model\cms\Category::where(['id'=>$cateId])->value('parent_id');
        }
        return $cateId;
    }
}