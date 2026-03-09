<?php
// +----------------------------------------------------------------------
// | HkCms 后台用户验证器
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: HkCms team <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types = 1);

namespace app\admin\validate\user;

use app\admin\validate\BaseValidate;

class User extends BaseValidate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'username' => 'require|length:3,30|chsDash|checkUnique:user,username,id',
        'nickname' => 'require|length:2,30',
        'email' => 'email|checkUnique:user,email,id',
        'mobile' => 'mobile|checkUnique:user,mobile,id',
        'money'=>'float',
        'score'=>'integer',
        'level'=>'integer',
        'exp'=>'integer',
        'avatar'=>'max:255',
        'birthday'=>'date',
        'introduction'=>'max:255',
        'password' => 'require|length:6,30',
        'status' => 'require|in:normal,hidden',
        'remark' => 'max:250',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'username.checkUnique' => '用户名已存在',
    ];


    /**
     * 修改场景的处理
     * @return User
     */
    public function sceneEdit()
    {
        return $this->only(['group_id','username','nickname','email','remark','password','status'])
            ->remove('password','require');
    }
}
