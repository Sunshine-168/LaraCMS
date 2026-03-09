<?php
// +----------------------------------------------------------------------
// | HkCms 权限菜单模型
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: HkCms team <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types = 1);

namespace app\admin\model\auth;

use think\facade\Db;
use think\Model;

class AuthGroup extends Model
{
    /**
     * save 方法下，新增，修改都会触发
     */
    public static function onAfterWrite()
    {
        // 删除菜单缓存
        app('cache')->tag('menu')->clear();
    }

    public static function onAfterDelete($model)
    {
        app('cache')->tag('menu')->clear();
        Db::name('category_priv')->where(['auth_group_id'=>$model['id']])->delete();
    }
}
