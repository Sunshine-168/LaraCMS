<?php
// +----------------------------------------------------------------------
// | HkCms 系统初始化
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types = 1);

namespace app\common\event;

class AppInit
{
    public function handle($handle)
    {
        // 文件优先级高于后台控制调试模式，防止无法进入后台，而无法手动开启调试模式
        if (app()->isDebug()!=true) {
            $path = app_path('install').'install.lock';
            if ((file_exists(app_path('install')) && file_exists($path)) || !file_exists(app_path('install'))) { // 已安装
                if (\think\facade\Db::name('config')->where('name','=','dev')->cache('devstatus',3600,'system')->value('value')=='enable') {
                    // 重置为调试模式
                    app()->debug();
                    app()->env->set('app_debug','true');
                }
            }
        }
    }
}