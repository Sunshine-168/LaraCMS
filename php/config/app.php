<?php
// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------

return [
    // 应用地址
    'app_host'         => env('app.host', ''),
    // 密钥
    'app_key'         => env('app.app_key', 'HkCms_Default'),
    // 应用的命名空间
    'app_namespace'    => '',
    // 是否启用路由
    'with_route'       => true,
    // 是否启用事件
    'with_event'       => true,
    // 默认应用
    'default_app'      => 'index',
    // 默认时区
    'default_timezone' => env('app.default_timezone', 'Asia/Shanghai'),

    // 应用映射（自动多应用模式有效）
    'app_map'          => [
    ],
    // 域名绑定（自动多应用模式有效）
    'domain_bind'      => [
        'api' => 'api',
        '*' => 'index',
    ],
    // 入口文件绑定,无需写index
    'app_file'         => [
        // 应用名称 => 文件名,无需写后缀
        'admin' => 'admin',
        'api'   => 'api',
    ],

    // 禁止URL访问的应用列表（自动多应用模式有效）
    'deny_app_list'    => ['common','admin'],

    // 异常页面的模板文件
    'exception_tmpl'   => app()->getBasePath() . 'common'. DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR .'think_exception.tpl',
    // 部署模式下启用（非调试模式）
    'http_exception_template'    =>  [
        404 =>  app()->getBasePath() . 'common'. DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . '404.html',
    ],

    // 错误显示信息,非调试模式有效
    'error_message'    => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'   => true,
];