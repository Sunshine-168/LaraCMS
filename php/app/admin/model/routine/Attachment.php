<?php
// +----------------------------------------------------------------------
// |HkCms 附件模型
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: HkCms team <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\model\routine;

use app\admin\model\auth\Admin;
use think\Model;

class Attachment extends Model
{
    /**
     * 字节转KB
     * @param $value
     * @param $data
     * @return string
     */
    public function getSizeTextAttr($value, $data)
    {
        return !empty($data['size']) && is_numeric($data['size']) ? number_format($data['size'] / 1024, 2).'kb' : 0;
    }

    /**
     * 获取完整地址
     * @param $value
     * @param $data
     */
    public function getCdnUrlAttr($value, $data)
    {
        //return $data['storage']=='local' ? app('request')->domain().$data['path'] : cdn_url($data['path']);
        return cdn_url($data['path'], true);
    }

    public function getUserNameAttr($value, $data)
    {
        $name = '-';
        if ($data['user_type']==1) { // 管理员
            $name = Admin::where(['id'=>$data['user_id']])->value('username');
        }
        return $name;
    }

    /**
     * 删除事件
     * @param Model $model
     */
    public static function onAfterDelete($model)
    {
        // 附件删除事件
        hook('uploadDel', $model);
        @unlink(str_replace('\\', '/', root_path().'public'.$model->path));
    }
}