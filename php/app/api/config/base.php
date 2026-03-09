<?php

return [
    // 开启API模块
    'api_enable'=>false,
    // 跨域请求域名，默认空值，不允许跨域
    'cors_domain'=>[
        // '*' // 允许所有域名
        // 'localhost'
        // 'local.demo.com'
        // '127.0.0.1'
    ],
    // 默认分页一页承载的记录行数
    'default_limit'=>20,
    // 最大分页一页承载的记录行数
    'limit_max'=>100,
];