<?php
// +----------------------------------------------------------------------
// | HkCms APP应用安装模型
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: HkCms team <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\model;

use think\Model;

class App extends Model
{
    public function addInstall($info)
    {
        $this->save([
            'name'=>$info['name'],
            'title'=>$info['title']??'',
            'image'=>$info['image']??'',
            'price'=>$info['price']??0,
            'module'=>$info['module']??'',
            'type'=>$info['type'],
            'description'=>$info['description']??'',
            'author'=>$info['author']??'',
            'version'=>$info['version']['version']??$info['version'],
            'status'=>1,
            'createtime'=>time(),
        ]);
    }

    public function editInstall($info)
    {
        $this->where(['name'=>$info['name'],'type'=>$info['type']])->save([
            'name'=>$info['name'],
            'title'=>$info['title'],
            'image'=>$info['image'],
            'price'=>$info['price'],
            'module'=>$info['module']??'',
            'type'=>$info['type'],
            'description'=>$info['description']??'',
            'author'=>$info['author']??'',
            'version'=>$info['version']['version'],
            'status'=>1,
            'createtime'=>time(),
        ]);
    }
}