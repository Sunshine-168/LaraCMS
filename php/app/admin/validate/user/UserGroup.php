<?php
// +----------------------------------------------------------------------
// | HkCms 角色管理验证器
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: HkCms team <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace app\admin\validate\user;

use think\Validate;

class UserGroup extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
	    'parent_id'=>'require|number|checkPid',
        'name'=>'require|chsDash|length:2,20',
        //'rules'=>'require'
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
            'parent_id' => lang('Parent role'),
            'name' => lang('Name'),
            'rules' => lang('Rules'),
        ];
        parent::__construct($rules, $message, $field);
    }

    public function checkPid($value, $rule, $data=[])
    {
        // 获取当前角色组的下级，判断选中的角色数据是否是他的下级。
        $current_id = $this->request->param('id');
        if ($current_id) {
            $sonGroup = \app\index\library\User::instance()->getChildGroup($current_id);
            if (in_array($value, $sonGroup)) {
                return lang('Cannot choose oneself or one\'s own subordinate as the parent');
            }
        }
        return true;
    }
}
