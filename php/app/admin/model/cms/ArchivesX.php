<?php
// +----------------------------------------------------------------------
// | HkCms 文章模型附表
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\model\cms;

use think\Model;

class ArchivesX extends Model
{
    public static $tablename = '';

    public function __construct(array $data = [])
    {
        $this->name = self::$tablename;
        parent::__construct($data);
    }
}