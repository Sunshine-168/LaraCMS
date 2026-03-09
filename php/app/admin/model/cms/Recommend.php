<?php
// +----------------------------------------------------------------------
// | HkCms 推荐位模型
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types = 1);

namespace app\admin\model\cms;

use think\Model;

class Recommend extends Model
{
    /**
     * 设置多语言
     * @param $value
     * @param $data
     * @return mixed|string
     */
    public function setLangAttr($value, $data)
    {
        return $value?$value:app()->cache->get('admin_content_lang'.app('user')->id);
    }

    /**
     * 新增事件
     * @param Model $model
     */
    public static function onAfterInsert($model)
    {
        if (site('content_lang_on')==1) {
            $data = $model->getData();
            $data['update_time'] = time();
            $data['create_time'] = time();
            lang_content_add('recommend', $data, ['remark']);
        }
    }

    /**
     * 删除的操作
     * @param Model $model
     */
    public static function onAfterDelete($model)
    {
        // 获取当前删除的ID
        $curId = $model->getAttr('id');
        $data = lang_content_del('recommend', $curId);
        foreach ($data as $key=>$value) {
            Banner::where(['recommend_id'=>$value])->delete();
        }
        Banner::where(['recommend_id'=>$curId])->delete();
    }
}
