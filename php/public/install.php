<?php
// +----------------------------------------------------------------------
// | HkCms 安装入口
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

if(version_compare(PHP_VERSION,'8.0.0','<')) {
    die('HkCms开源内容管理系统要求PHP版本 >= 8.0.0，当前PHP版本为：'.PHP_VERSION.'，请更换PHP版本后再试！');
}

if (file_exists(__DIR__ . '/../app/install/') && file_exists(__DIR__ . '/../app/install/install.lock')) {
    echo 'HkCms已安装！如果需要重新安装，请先删除/app/install/install.lock文件，然后再试。';
    exit;
}

define("ROOT_PATH", dirname(__DIR__).DIRECTORY_SEPARATOR);
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/install/Index.php';




