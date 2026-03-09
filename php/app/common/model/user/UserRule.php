<?php
// +----------------------------------------------------------------------
// | 用户权限
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\model\user;

use app\common\model\BaseModel;

class UserRule extends BaseModel
{
    /**
     * @var string 表名
     */
    protected $name = 'user_rule';

    /**
     * @var string 主键
     */
    protected $pk = 'id';

    /**
     * id搜索器
     * @param $query
     * @param $value
     * @param $data
     * @return void
     */
    public function searchIdAttr($query, $value, $data)
    {
        if ($value) {
            $rules = explode(',', $value);
            if (count($rules)>1) {
                $rules = array_unique($rules);
                $query->whereIn('id', $rules);
            } else {
                $query->where('id', $value);
            }
        }
    }
}