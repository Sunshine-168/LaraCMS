<?php
// +----------------------------------------------------------------------
// | 站点配置
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\dao\config;

use app\common\dao\BaseDao;
use app\common\model\config\Config;

class ConfigDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return Config::class;
    }

    /**
     * 获取应用中心的账号信息
     * @return array
     */
    public function getCloudInfo(): array
    {
        return $this->getModel()->where(function ($query){
            $query->where(['name'=>'cloud_username'])->whereOr(['name'=>'cloud_password']);
        })->field('name,value,data_list')->select()->toArray();
    }
}