<?php
// +----------------------------------------------------------------------
// | 系统插件服务
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\services\system;

use app\common\services\BaseService;
use app\common\services\cache\CacheService;
use think\facade\Cache;

class AddonService extends BaseService
{
    // 缓存标签
    const CACHE_TAG = "addons";

    /**
     * 初始化
     */
    public function __construct()
    {
    }

    /**
     * 加载插件JS文件
     * @return string
     */
    public function loadJs()
    {
        $adminjslist = Cache::get('adminjslist');
        $debug = app()->isDebug();
        if (empty($adminjslist) || $debug) {
            $addonsPath = public_path('static'.DIRECTORY_SEPARATOR.'addons');
            $lists = glob($addonsPath.'*');
            $str = '';
            if (!empty($lists)) {
                foreach ($lists as $value) {
                    $name = basename($value);
                    $js = $value.DIRECTORY_SEPARATOR.$name.'.js';
                    if (is_file($js)) {
                        $cache = site();
                        $str .= '<script type="text/javascript" src="'.site('cdn').'/static/addons/'.$name.'/'.$name.'.js?v='.(env('APP_DEBUG')?time():$cache['version']??'').'"></script>';
                    }
                }
            }
            $adminjslist = $str;
            if (!$debug) {
                Cache::tag(self::CACHE_TAG)->set('adminjslist', $adminjslist, 86400);
            }
        }
        return $adminjslist;
    }
}