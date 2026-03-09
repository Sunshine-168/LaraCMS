<?php

return [
    'base' => [
        'title' => '基础配置',
        'item' => [
            'map_type' => [
                'title' => '地图平台',
                'type' => 'radio',
                'tips' => '',
                'rules' => '',
                'error_tips' => '',
                'options' => [
                    1 => '百度',
                    '高德',
                ],
                'value' => '1',
            ],
            'map_ak' => [
                'title' => '访问（AK）',
                'type' => 'text',
                'tips' => '前往对应平台获取访问AK码',
                'rules' => 'required',
                'error_tips' => '值必须',
                'value' => 'uUvLWdeGhSwc2OLw2NPkkGrBWYf0lgG8',
            ],
        ],
    ],
];
