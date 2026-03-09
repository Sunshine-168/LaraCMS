<?php
// +----------------------------------------------------------------------
// | HkCms admin应用入口
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

namespace think;

if(version_compare(PHP_VERSION,'8.0.0','<')) {
    die('HkCms开源内容管理系统要求PHP版本 >= 8.0.0，当前PHP版本为：'.PHP_VERSION.'，请更换PHP版本后再试！');
}
if (file_exists(__DIR__ . '/../app/install/') && !file_exists(__DIR__ . '/../app/install/install.lock')) {
    header("location:/install.php");
    exit;
}

require __DIR__ . '/../vendor/autoload.php';

// 执行HTTP应用并响应
$http = (new App())->http;

$response = $http->name('admin')->run();

$response->send();

$http->end($response);