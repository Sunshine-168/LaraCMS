<?php
// +----------------------------------------------------------------------
// | HkCms 地图位置选取插件
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: HkCms team <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace addons\address;

use think\Addons;

class Address extends Addons
{
    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }

    /**
     * 前端调用，显示地图。
     * @param $param
     * @return false|mixed|string
     * @throws \think\Exception
     */
    public function showMapHook($param)
    {
        if (isset($param['id'])) { // 新版
            $id = $param['id'];
            $param['attr'] = '<div id="'.$id.'" '.($param['attr']??"").'></div>';
        } else {
            $temp = [];
            $id = 'dituContent';
            if (isset($param['attr'])) {
                foreach ($param['attr'] as $key=>$value) {
                    if ($key=='id') {
                        $id = $value;
                    }
                    $temp[] = $key.'="'.e($value).'"';
                }
            }
            $param['attr'] = '<div '.implode(' ', $temp).'></div>';
        }
        $this->assign('config', $this->getConfig());
        $this->assign('key_id', $id);
        return $this->fetch('/show', ['param'=>$param]);
    }
}