<?php
// +----------------------------------------------------------------------
// | HkCms 应用开始事件
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types = 1);

namespace app\common\event;

class HttpRun
{
    public function handle($handle)
    {
        // url生成服务绑定
        $app = app();
        $app->bind([
            'think\route\Url' => \app\common\library\Url::class,
        ]);

        // 增加验证码规则验证
        \think\facade\Validate::maker(function ($validate) {
            $validate->extend('captcha', function ($value) {
                return captcha_check($value);
            }, ':attribute错误!');
        });
    }
}
