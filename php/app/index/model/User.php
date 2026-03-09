<?php
// +----------------------------------------------------------------------
// | HkCms 用户表
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\index\model;

use think\helper\Str;
use think\Model;

class User extends Model
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
     * 登录时间
     * @param $value
     * @param $data
     * @return false|string
     */
    public function getLoginTimeAttr($value, $data)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    /**
     * 上次登录时间
     * @param $value
     * @param $data
     * @return false|string
     */
    public function getLatestTimeAttr($value, $data)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }
}