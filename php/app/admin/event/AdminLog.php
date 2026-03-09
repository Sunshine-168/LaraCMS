<?php
// +----------------------------------------------------------------------
// | HkCms 管理员操作日志
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: HkCms team <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\admin\event;

class AdminLog
{
    public function handle($handle)
    {
        if (request()->isPost()) {
            \app\admin\model\auth\AdminLog::logs();
        }
    }
}