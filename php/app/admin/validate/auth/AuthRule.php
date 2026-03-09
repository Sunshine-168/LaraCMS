<?php
// +----------------------------------------------------------------------
// | HkCms 权限管理验证器
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: HkCms team <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types = 1);

namespace app\admin\validate\auth;

use app\admin\validate\BaseValidate;
use think\facade\Db;

class AuthRule extends BaseValidate
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
	    'name' => 'require|format|checkUnique:AuthRule',
        'parent_id'=>'require|number|checkPid'
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
    ];

    public function __construct(array $rules = [], $message = [], $field = [])
    {
        $this->field = [
            'title' => __('Title'),
            'name' => __('Rule'),
            'parent_id' => __('Superior'),
        ];
        $this->message['name.format'] = __("Rules can only be composed of lowercase letters, numbers, underscores and / or characters");

        parent::__construct();
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
                return __('Can\'t choose oneself as parent');
            }

            $arr = Db::name('category')->where(['parent_id'=>$id])->column('id');
            if (in_array($value, $arr)) {
                return __('Can\'t choose one\'s own subordinate as the parent');
            }
        }
        return true;
    }
}