<?php
// 事件定义文件
return [
    'bind'      => [
    ],

    'listen'    => [
        'HttpRun'  => [],
        'RouteLoaded' => [app\index\event\RouteLoaded::class],
        'HttpEnd'  => [],
        'LogLevel' => [],
        'LogWrite' => [],
    ],

    'subscribe' => [
    ],
];
