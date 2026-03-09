<?php
// +----------------------------------------------------------------------
// | HkCms 路由
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

use \think\facade\Route;
use app\api\middleware\AllowCrossMiddleware;
use app\api\middleware\SiteStatusMiddleware;
use app\api\middleware\ApiLoginMiddleware;
use think\Response;

Route::group(function () {
    Route::get('/', 'Index/index');
    Route::post('auth/login', 'user.Login/login')->option([
        '_title'=>'账号密码登录'
    ]);
    // 需要登录的API
    Route::group(function () {
        Route::get('user/details', 'user.User/details')->option([
            '_title'=>'获取用户详情'
        ]);
    })->middleware(ApiLoginMiddleware::class);

    // 栏目管理
    Route::post('category/listPage', 'cms.Category/listPage')->option([
        '_title'=>'获取栏目分页列表'
    ]);
    Route::get('category/detail', 'cms.Category/detail')->option([
        '_title'=>'获取栏目详情'
    ]);
    Route::get('category/tree', 'cms.Category/tree')->option([
        '_title'=>'获取栏目树形结构'
    ]);
    // 内容管理
    Route::post('content/listPage', 'cms.Content/listPage')->option([
        '_title'=>'获取内容分页列表'
    ]);
    Route::get('content/details', 'cms.Content/details')->option([
        '_title'=>'获取单条内容详情'
    ]);
    // 站点配置
    Route::get('siteConfig/getAll', 'cms.SiteConfig/getAll')->option([
        '_title'=>'获取站点配置'
    ]);
    // 站点模块
    Route::get('recommend/contentList', 'cms.Recommend/contentList')->option([
        '_title'=>'获取站点模块内容'
    ]);
})->middleware(AllowCrossMiddleware::class)
    ->middleware(SiteStatusMiddleware::class);

Route::miss(function () {
}, 'options')->middleware(AllowCrossMiddleware::class);

// 触发路由标签位
hook('apiRoute');