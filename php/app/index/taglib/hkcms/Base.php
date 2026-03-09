<?php
// +----------------------------------------------------------------------
// | HkCms 标签服务基类
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\index\taglib\hkcms;

class Base
{
    /**
     * 构造方法
     * @access public
     */
    public function __construct()
    {
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
    }
}