<?php
// +----------------------------------------------------------------------
// | HkCms 内容管理
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: HkCms team <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\controller\cms;

use app\admin\controller\BaseController;

class Content extends BaseController
{
    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [
        'login',
        'auth' => ['except'=>['show']]
    ];

    public function index()
    {
        return $this->view->fetch();
    }

    public function show()
    {
        return __('Please select the left column');
    }
}