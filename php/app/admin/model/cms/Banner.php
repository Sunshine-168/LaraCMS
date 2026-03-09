<?php
// +----------------------------------------------------------------------
// | HkCms 轮播、广告模型
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types = 1);

namespace app\admin\model\cms;

use think\Model;

class Banner extends Model
{
    /**
     * 设置多语言
     * @param $value
     * @param $data
     * @return mixed|string
     * 新增事件
     * @param Model $model
     */
    public function setLangAttr($value, $data)
    {
        // 写入前，必须存入当前编辑模式的缓存
        return $value?$value:app()->cache->get('admin_content_lang'.app('user')->id);
    }
}
