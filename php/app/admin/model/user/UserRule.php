<?php
// +----------------------------------------------------------------------
// | HkCms
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\admin\model\user;

use think\Model;

class UserRule extends Model
{
    public function getTitleLanAttr($value, $data)
    {
        return lang($data['title']);
    }
}