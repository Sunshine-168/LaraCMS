<?php
// +----------------------------------------------------------------------
// | HkCms 路由
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

use think\facade\Route;

Route::pattern([
    'catdir' => '[A-Za-z0-9_\-]+', // 栏目的父级英文目录
    'catname' => '[A-Za-z0-9_\-]+', // 栏目英文目录名称
    'catid' => '\d+',  // 栏目ID
    'id' => '[A-Za-z0-9_\-]+',    // 文章ID或文章别名
    'model' => '[A-Za-z0-9_\-]+',  // 模型标识
    'year' => '\d+',  // 年、月、日
    'month' => '\d+',
    'day' => '\d+',
    'page' => '\d+',
    'lang' => '[A-Za-z_\-]+'
]);

$site = site();

// 触发路由标签位
hook('indexRoute');

// 会员路由
Route::get('/u$', '/user.user/index')->ext('');
Route::rule('/u/login$', '/user.user/login');
Route::rule('/u/register$', '/user.user/register');
Route::rule('/u/loginout$', '/user.user/loginout');
Route::rule('/u/profile$', '/user.user/profile');
Route::rule('/u/bind$', '/user.user/bind');
Route::rule('/u/changePwd$', '/user.user/changePwd');
Route::rule('/u/sms/send$', '/user.user/send');
Route::rule('/u/upload$', '/user.user/upload');
Route::rule('/u/resetpwd', '/user.user/resetPwd');

if ($site['url_mode']==1 && !empty($site['url_rewrite'])) { // 伪静态

    // 系统必要路由
    Route::rule('guestbook/captcha','/guestbook/captcha')->ext('html');

    foreach ($site['url_rewrite'] as $key=>$value) {
        $value = explode(',', $value);
        foreach ($value as $k=>$v) {
            $v = explode('.', $v);
            $ext = !empty($v[1])?$v[1]:'';
            if (stripos($key, '/' ) !== 0) {
                $key = '/'.$key;
            }
            Route::rule($v[0], $key)->ext($ext);
        }
    }
}