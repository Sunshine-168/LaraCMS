<?php
// +----------------------------------------------------------------------
// | 用户模型
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\model\user;

use app\common\model\BaseModel;

class User extends BaseModel
{
    /**
     * @var string 表名
     */
    protected $name = 'user';

    /**
     * @var string 主键
     */
    protected $pk = 'id';

    /**
     * 额外字段
     * @var string[]
     */
    protected $append = [
        'gender_text'
    ];

    /**
     * 格式化性别
     * @param $valur
     * @param $data
     * @return mixed|string
     */
    public function getGenderTextAttr($valur, $data)
    {
        if (isset($data['gender'])) {
            if ($data['gender']==1) {
                return __('Female');
            } else if ($data['gender']==2) {
                return __('male');
            } else {
                return __('Keep secret');
            }
        }
        return "";
    }

    /**
     * 角色组多对多
     * @return \think\model\relation\BelongsToMany
     */
    //public function group()
    //{
    //    return $this->belongsToMany(UserGroup::class, UserGroupAccess::class, 'group_id', 'user_id');
    //}
}