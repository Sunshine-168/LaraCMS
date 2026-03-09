<?php
// +----------------------------------------------------------------------
// | 用户角色组关联Dao
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\common\dao\user;

use app\common\dao\BaseDao;
use app\common\model\user\UserGroupAccess;

class UserGroupAccessDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return UserGroupAccess::class;
    }

    /**
     * 获取关联的组列表
     * @param int $userId
     * @return mixed
     */
    public function getListByIUserId(int $userId)
    {
        return $this->getModel()->alias('UserGroupAccess')
            ->join('UserGroup UserGroup', 'UserGroupAccess.group_id = UserGroup.id')
            ->where('UserGroup.status', 'normal')
            ->where('UserGroupAccess.user_id', $userId)
            ->field('UserGroupAccess.user_id,UserGroupAccess.group_id,UserGroup.parent_id,UserGroup.name,UserGroup.rules')
            ->select();
    }
}