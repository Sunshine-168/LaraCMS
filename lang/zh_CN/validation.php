<?php

return [
    'required' => '请填写:attribute',
    'email' => ':attribute格式不对',
    'confirmed' => '两次:attribute不一致',
    'unique' => '这个:attribute已经有了',
    'numeric' => ':attribute须为数字',
    'integer' => ':attribute须为整数',
    'string' => ':attribute须为文字',
    'file' => '请选择文件',
    'in' => ':attribute不在允许范围内',
    'min' => [
        'numeric' => ':attribute不能小于 :min',
        'string' => ':attribute至少 :min 位',
        'file' => ':attribute至少 :min KB',
        'array' => ':attribute至少 :min 项',
    ],
    'max' => [
        'numeric' => ':attribute不能大于 :max',
        'string' => ':attribute最多 :max 字',
        'file' => ':attribute不能超过 :max KB',
        'array' => ':attribute最多 :max 项',
    ],
    'password' => [
        'letters' => ':attribute须包含字母',
        'mixed' => ':attribute须同时包含大小写字母',
        'numbers' => ':attribute须包含数字',
        'symbols' => ':attribute须包含符号',
        'uncompromised' => '这个:attribute出现在泄露记录中，请换一个',
    ],
];
