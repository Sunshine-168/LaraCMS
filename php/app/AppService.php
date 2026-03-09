<?php
declare (strict_types = 1);

namespace app;

use app\admin\library\User;
use think\Service;

class AppService extends Service
{
    public function register()
    {
        // 服务注册

        // admin 应用服务注册
        if (app('http')->getName()=='admin') {
            $this->app->bind('user', User::class);
        }
    }

    public function boot()
    {
        // 服务启动
    }
}
