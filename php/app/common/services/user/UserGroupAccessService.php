<?php
// +----------------------------------------------------------------------
// | 用户与角色组
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\common\services\user;

use app\common\dao\user\UserGroupAccessDao;
use app\common\services\BaseService;

/**
 * @mixin UserGroupAccessDao
 */
class UserGroupAccessService extends BaseService
{
    /**
     * 初始化
     * @param UserGroupAccessDao $userGroupAccessDao
     */
    public function __construct(UserGroupAccessDao $userGroupAccessDao)
    {
        $this->dao = $userGroupAccessDao;
    }
}