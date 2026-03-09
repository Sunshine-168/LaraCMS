<?php
// +----------------------------------------------------------------------
// | 文档模型
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\model\cms;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class Archives extends BaseModel
{
    use SoftDelete;

    /**
     * @var string 表名
     */
    protected $name = 'archives';

    /**
     * @var string 主键
     */
    protected $pk = 'id';

    /**
     * 软删除字段
     * @var string
     */
    protected $deleteTime = 'delete_time';

    /**
     * 栏目关联
     * @return \think\model\relation\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }
}