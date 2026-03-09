<?php
// +----------------------------------------------------------------------
// | HkCms 权限验证
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: HkCms team <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\middleware;

use think\exception\HttpResponseException;

class Auth
{
    use \app\common\library\Jump;

    /**
     * 错误模板，主题文件夹下
     * @var string
     */
    protected $error_tmpl = '/error';
    protected $success_tmpl = '/success';

    public function handle($request, \Closure $next)
    {
        $user = app('user');

        if (!$user->id) {
            return redirect((string)url('/login/index'))->remember();
        }

        $action = strtolower($request->action());
//        if ($action=='batches') {   // 批量修改默认设置与修改权限一致
//            $action = 'edit';
//        }

        $url = str_replace('.','/',$request->controller()).'/'.$action;
        $url = strtolower($url);

        $bl = $user->check($url, $user->id);
        if (!$bl) {
            if ($request->isAjax()) {
                $this->error(__('No permission'));
            } else {
                throw new HttpResponseException(response('<html><head><style>.code{font-size: 80px;margin-bottom: 20px;padding-top: 20%}*{text-align: center}</style></head><body><p class="code">403</p><p>'.__('No permission').'</p></body></html>'));
            }
        }

        return $next($request);
    }
}