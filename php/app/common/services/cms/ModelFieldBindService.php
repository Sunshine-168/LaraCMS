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

use app\common\dao\cms\ModelFieldBindDao;
use app\common\services\BaseService;

/**
 * @mixin ModelFieldBindDao
 */
class ModelFieldBindService extends BaseService
{
    /**
     * 初始化
     * @param ModelFieldBindDao $dao
     */
    public function __construct(ModelFieldBindDao $dao)
    {
        $this->dao = $dao;
    }
}