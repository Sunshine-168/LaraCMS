<?php
// +----------------------------------------------------------------------
// | HkCms 基础验证器
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: HkCms team <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\validate;

use think\Validate;

class BaseValidate extends Validate
{

    /**
     * 验证唯一性
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool
     */
    protected function checkUnique($value, $rule, $data=[])
    {
        $arr = explode(',', $rule);
        $name = $arr[1] ?? 'name';
        $id = $arr[2] ?? 'id';
        $idValue = $this->request->get($id);
        if (!$idValue) {    // 添加
            $count = $this->db->name($arr[0])->where([$name=>$value])->count();
        } else {    // 更新
            $count = $this->db->name($arr[0])->where([$name=>$value])->where($id,'<>', $idValue)->count();
        }

        return $count>0 ? __('%s existed',[$value]):true;
    }
}