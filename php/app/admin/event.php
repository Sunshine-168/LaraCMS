<?php
// 事件定义文件
return [
    'bind'      => [
    ],

    'listen'    => [
        'HttpRun'  => [],
        'HttpEnd'  => [app\admin\event\AdminLog::class],
        'LogLevel' => [],
        'LogWrite' => [],
    ],

    'subscribe' => [
    ],
];
