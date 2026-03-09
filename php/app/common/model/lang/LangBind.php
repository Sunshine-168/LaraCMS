<?php
// +----------------------------------------------------------------------
// | 语言与内容关系模型
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\common\model\lang;

use app\common\model\BaseModel;

class LangBind extends BaseModel
{
    /**
     * @var string 表名
     */
    protected $name = 'lang_bind';

    /**
     * @var string 主键
     */
    protected $pk = 'id';
}