<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
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

$response = $http->run();

$response->send();

$http->end($response);
