<?php
// +----------------------------------------------------------------------
// | HkCms 验证器
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types = 1);

namespace app\admin\validate\user;

use app\admin\validate\BaseValidate;
use think\facade\Db;

class UserRule extends BaseValidate
{
    protected $regex = [ 'format' => '[a-z0-9_\/]+'];

    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
	    'title' => 'require',
	    'name' => 'require|format|checkUnique:UserRule',
        'parent_id'=>'require|number|checkPid'
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'name.format' => '规则只能是小写字母、数字、下划线和/组成',
        'name.checkUnique' => '规则名称已存在',
    ];

    public function __construct(array $rules = [], $message = [], $field = [])
    {
        $this->field = [
            'title' => lang('Title'),
            'name' => lang('Rule'),
            'parent_id' => lang('Superior'),
        ];
        $this->message['name.format'] = lang("Rules can only be composed of lowercase letters, numbers, underscores and / or characters");
        $this->message['name.checkUnique'] = lang("Rule name already exists");
        parent::__construct($rules, $message, $field);
    }

    /**
     * 验证父级合法性
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     */
    public function checkPid($value, $rule, $data=[])
    {
        $id = $this->request->param('id');
        if ($id) {
            if ($id == $value) {
                return lang('Can\'t choose oneself as parent');
            }

            $arr = Db::name('category')->where(['parent_id'=>$id])->column('id');
            if (in_array($value, $arr)) {
                return lang('Can\'t choose one\'s own subordinate as the parent');
            }
        }
        return true;
    }
}