<?php
// +----------------------------------------------------------------------
// | HkCms api 首页
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace app\api\controller;

class Index extends BaseController
{
    public function index()
    {
        return $this->success('api success');
    }
}