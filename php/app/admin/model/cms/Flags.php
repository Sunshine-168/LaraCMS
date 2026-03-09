<?php
// +----------------------------------------------------------------------
// | HkCms 文档属性
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\model\cms;

use think\Model;

class Flags extends Model
{
    /**
     * 指定自动时间戳类型
     * @var string
     */
    protected $autoWriteTimestamp = 'int';

    /**
     * 新增后处理
     * @param Model $model
     */
    public static function onAfterInsert($model)
    {
        if (site('content_lang_on')==1) {
            $data = $model->getData();
            $data['update_time'] = time();
            $data['create_time'] = time();
            lang_content_add('flags', $data, ['title']);
        }
    }
}