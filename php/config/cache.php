<?php

// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------

return [
    // 默认缓存驱动
    'default' => env('cache.driver', 'file'),

    // 缓存连接方式配置
    'stores'  => [
        'file' => [
            // 驱动方式
            'type'       => 'File',
            // 缓存保存目录
            'path'       => '',
            // 缓存前缀
            'prefix'     => '',
            // 缓存有效期 0表示永久缓存
            'expire'     => 0,
            // 缓存标签前缀
            'tag_prefix' => 'tag:',
            // 序列化机制 例如 ['serialize', 'unserialize']
            'serialize'  => [],
        ],
        'redis' => [
            // 驱动方式
            'type'       => 'Redis',
            // 地址
            'host'       => env('redis.host', '127.0.0.1'),
            // 端口
            'port'       => env('redis.port', '6379'),
            // 密码
            'password'   => env('redis.password', ''),
            // 数据库
            'select'     => intval(env('redis.select', 0)),
            // 服务器主动关闭
            'timeout'    => 0,
            // 缓存有效期 0表示永久缓存
            'expire'     => 3600,
            // 缓存前缀
            'prefix'     => env('redis.prefix', 'hkcms:'),
            // 缓存标签前缀
            'tag_prefix' => env('redis.tag_prefix', 'tag:'),
            // 序列化机制 例如 ['serialize', 'unserialize']
            'serialize'  => [],
        ]
    ],
];
