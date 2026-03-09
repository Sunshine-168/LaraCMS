<?php
// +----------------------------------------------------------------------
// | 用户权限
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\common\services\user;

use app\common\dao\user\UserRuleDao;
use app\common\services\BaseService;

/**
 * @mixin UserRuleDao
 */
class UserRuleService extends BaseService
{
    /**
     * 初始化
     * @param UserRuleDao $dao
     */
    public function __construct(UserRuleDao $dao)
    {
        $this->dao = $dao;
    }
}