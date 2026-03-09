<?php
// +----------------------------------------------------------------------
// | HkCms cms配置
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

return [
    // 模板路径
    'tpl_path' => root_path().'template'.DIRECTORY_SEPARATOR,
    // 模板静态路径
    'tpl_static'=> public_path('static'.DIRECTORY_SEPARATOR.'module'),
    // 强制不允许脚本上传
    'script_ext'=>'asp,aspx,php,jsp,cgi,asa,cer,cdx,hta,exe,sh',

    // 应用中心
    'appcenter' => true,
    // 启用本地安装插件, false = 关闭，true = 开启，默认关闭，为了你的安全，建议部署到线上时不要开启
    'appcenter_local_open' => false,
    // 应用中心API
    'api_url' => 'http://api.hkcms.cn/',
    // 应用中心地址
    'app_url' => 'http://www.hkcms.cn/',

    // admin模块配置
    'admin'=>[
        // 登录失败次数，超过五次锁定账号一天，使用缓存机制
        'login_fail'=>5
    ],

    // 验证规则
    'rule_lists' => [
        'required'=>'必须',
        'checkbox'=>'checkbox/radio必选',
        'digits'=>'数字',
        'integer'=>'整数',
        'letters'=>'字母',
        'alphanum'=>'字母数字',
        'date'=>'日期',
        'time'=>'时间',
        'email'=>'邮箱',
        'url'=>'网址',
        'qq'=>'QQ号',
        'IDcard'=>'身份证号码',
        'tel'=>'电话号码',
        'mobile'=>'手机号',
        'zipcode'=>'邮政编码',
        'chinese'=>'中文字符',
        'username'=>'3-12位数字、字母、下划线',
        'password' =>'6-16位字符，不包含空格'
    ],
    'rule_lists_tp' => [
        'required'=>'require',
        'checkbox'=>'require',
        'digits'=>'number',
        'integer'=>'integer',
        'letters'=>'alpha',
        'alphanum'=>'alphaNum',
        'date'=>'date',
        'time'=>['regex'=>'/^([01]\d|2[0-3])(:[0-5]\d){1,2}$/'],
        'email'=>'email',
        'url'=>'url',
        'qq'=>'number',
        'IDcard'=>'idCard',
        'tel'=>['regex'=>'/^(?:(?:0\d{2,3}[\- ]?[1-9]\d{6,7})|(?:[48]00[\- ]?[1-9]\d{6}))$/'],
        'mobile'=>'mobile',
        'zipcode'=>'zip',
        'chinese'=>'chs',
        'username'=>['regex'=>'/^\w{3,12}$/'],
        'password' =>['regex'=>'/^[\S]{6,16}$/']
    ],
    'rule_lists_msg' => [
        'time.regex'=>'时间格式不正确',
        'tel.regex'=>'电话号码格式不正确',
        'username.regex'=>'只能是3-12位数字、字母、下划线',
        'password.regex'=>'只能是6-16位字符，不包含空格',
    ]
];