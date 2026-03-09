<?php
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\services\cms;

use app\common\dao\cms\CategoryPrivDao;
use app\common\services\BaseService;

/**
 * @mixin CategoryPrivDao
 */
class CategoryPrivService extends BaseService
{
    /**
     * 初始化
     * @param CategoryPrivDao $dao
     */
    public function __construct(CategoryPrivDao $dao)
    {
        $this->dao = $dao;
    }
}