<?php
// +----------------------------------------------------------------------
// | HkCms 用户模型
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: HkCms team <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types = 1);

namespace app\admin\model\auth;

use think\helper\Str;
use think\Model;

class Admin extends Model
{
    /**
     * 头像获取
     * @param $value
     * @param $data
     * @return string
     */
    public function getAvatarAttr($value, $data)
    {
        if (empty($value)) {
            $str = empty($data['nickname']) ? $data['username'] : $data['nickname'];

            // 设置背景颜色
            $total = unpack('L', hash('adler32', $str, true))[1];
            list($r, $g, $b) = ColorHSLToRGB(($total % 360)/360, 0.3, 0.9);

            $str = Str::upper(Str::substr($str, 0, 1));
            $svg = base64_encode('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="100" height="100"><rect height="100" width="100" fill="rgb('.$r.','.$g.','.$b.')"></rect><text x="50" y="50" font-size="50" fill="#fff" dominant-baseline="central" text-anchor="middle">'.$str.'</text></svg>');
            $value = 'data:image/svg+xml;base64,'.$svg;
        }
        return $value;
    }

    /**
     * 登录时间格式化
     * @param $value
     * @return false|string
     */
    public function getLogintimeAttr($value)
    {
        return !empty($value)?date('Y-m-d H:i', $value):'';
    }

    /**
     * 删除管理员后的处理
     * @param Model $model
     */
    public static function onAfterDelete($model)
    {
        AuthGroupAccess::where(['admin_id'=>$model->getAttr('id')])->delete();
    }
}
