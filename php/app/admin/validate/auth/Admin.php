<?php
// +----------------------------------------------------------------------
// | HkCms 后台用户验证器
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types = 1);

namespace app\admin\validate\auth;

use app\admin\validate\BaseValidate;

class Admin extends BaseValidate
{
    public function __construct(array $rules = [], $message = [], $field = [])
    {
        $this->field = [
            'group_id' => __('Owned role'),
            'username' => __('Username'),
            'nickname' => __('Nickname'),
            'email' => __('Email'),
            'remark' => __('Remark'),
            'password' => __('Password'),
            'status' => __('Status'),
        ];

        parent::__construct();
    }

    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'group_id' => 'require|checkGroupids',
        'username' => 'require|alphaDash|length:3,16|checkUnique:admin,username,id',
        'nickname' => 'chsDash',
        'email' => 'email|checkUnique:admin,email,id',
        'remark' => 'max:250',
        'password' => 'require|length:6,16',
        'status' => 'require|in:normal,hidden'
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [];

    /**
     * 验证提交的所属角色组
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     */
    protected function checkGroupids($value, $rule, $data=[])
    {
        $groupIds = app('user')->getUserGroupId();
        $arr = explode(',', $value);
        $newArr = array_intersect($groupIds,$arr);
        if (empty($newArr)) {
            return __('The role group to which it belongs cannot be empty');
        }
        return true;
    }

    /**
     * 修改场景的处理
     * @return Admin
     */
    public function sceneEdit()
    {
        return $this->only(['group_id','username','nickname','email','remark','password','status'])
            ->remove('password','require');
    }
}
