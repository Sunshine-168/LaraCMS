<?php
// +----------------------------------------------------------------------
// | HkCms simditor 编辑器
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: HkCms team <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace addons\simditor;

use think\Addons;

class Simditor extends Addons
{

    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }
}