<?php
// +----------------------------------------------------------------------
// | HkCms 模型
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

namespace app\common\model;

use think\Model;

class BaseModel extends Model
{
    /**
     * 默认关闭自动写入
     * @var bool
     */
    protected $autoWriteTimestamp = false;
}