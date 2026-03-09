<?php
// +----------------------------------------------------------------------
// | 用户角色组关联性中间表
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\model\user;

use app\common\model\BaseModel;

class UserGroupAccess extends BaseModel
{
    /**
     * @var string 表名
     */
    protected $name = 'user_group_access';

    /**
     * @var string 主键
     */
    protected $pk = 'user_id';
}