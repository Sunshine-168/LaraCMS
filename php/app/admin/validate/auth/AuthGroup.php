<?php
// +----------------------------------------------------------------------
// | HkCms 角色管理验证器
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: HkCms team <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace app\admin\validate\auth;

use think\Validate;

class AuthGroup extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
	    'parent_id'=>'require|number',
        'name'=>'require|chsDash|length:2,20',
        'rules'=>'require'
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [];

    public function __construct(array $rules = [], $message = [], $field = [])
    {
        $this->field = [
            'parent_id' => __('Parent role'),
            'name' => __('Name'),
            'rules' => __('Rules'),
        ];
        parent::__construct();
    }
}
