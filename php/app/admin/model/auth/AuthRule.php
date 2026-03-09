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

class AuthRule extends Model
{
    /**
     * 生成菜单,支持三级
     * @param $menu array 数组
     * @param string $app 插件名称
     * @param integer $parent_id 父级
     * @return bool
     */
    public function createMenu($menu, $app = '', $parent_id = 0) : bool
    {
        if (empty($menu) || !is_array($menu)) {
            return false;
        }
        foreach ($menu as $key=>$value) {
            if (empty($value['title']) || empty($value['name'])) {
                continue;
            }
            $info = Db::name('auth_rule')->where(['name'=>$value['name']])->find();
            if ($info && $info['app']!=$app) {
                // 菜单冲突
                continue;
            }

            $value['app'] = $app;
            // 获取父级
            if (isset($value['parent_id']) && !is_numeric($value['parent_id'])) {
                $value['parent_id'] = $this->where(['name'=>$value['parent_id']])->value('id');
            } else if ($parent_id) {
                $value['parent_id'] = $parent_id;
            }
            // 路由
            $value['route'] = $value['route'] ?? '';
            // 图标
            $value['icon'] = isset($value['icon']) ? $value['icon'] : (empty($value['child']) ? 'far fa-circle' : 'fas fa-list-ul');
            // 排序
            $value['weigh'] = $value['weigh']??100;
            // 菜单类型
            if ((!isset($value['type']) && empty($value['child'])) || (isset($value['type']) && !in_array($value['type'],[0,1,2]) && empty($value['child']))) {
                $value['type'] = 0;
            } else if ((!isset($value['type']) && !empty($value['child'])) || (isset($value['type']) && !in_array($value['type'],[0,1,2]) && !empty($value['child']))) {
                $value['type'] = 1;
            }
            // 是否追加到快速导航
            $value['is_nav'] = isset($value['is_nav']) ? $value['is_nav'] : ($value['type']==1?1:0);

            if ($info) {
                self::update($value, ['id'=>$info['id']]);
            } else {
                $tempModel = self::create($value);
            }
            $this->createMenu($value['child']??[], $app, $info ? $info['id'] : $tempModel->id);
        }
        return true;
    }

    public function setNameAttr($value)
    {
        return strtolower($value);
    }

    public function getTitleLanAttr($value,$data)
    {
        return lang($data['title']);
    }

    /**
     * save 方法下，新增，修改都会触发
     */
    public static function onAfterWrite()
    {
        // 删除菜单缓存
        app('cache')->tag('menu')->clear();
    }

    public static function onAfterDelete()
    {
        app('cache')->tag('menu')->clear();
    }
}
