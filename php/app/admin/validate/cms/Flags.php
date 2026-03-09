<?php
// +----------------------------------------------------------------------
// | HkCms 文档属性验证
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

namespace app\admin\validate\cms;

use app\admin\validate\BaseValidate;

class Flags extends BaseValidate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'title' => 'require',
        'name' => 'require|alphaDash|checkUnique:flags,name',
        'status' => 'require|in:normal,hidden'
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */
    protected $message = [];

    public function __construct()
    {
        $this->field = [
            'title' => __('Title'),
            'name' => __('Name'),
            'status' => __('Status'),
        ];
        parent::__construct();
    }

    /**
     * 验证唯一性
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool
     */
    protected function checkUnique($value, $rule, $data=[])
    {
        $arr = explode(',', $rule);
        $name = $arr[1] ?? 'name';
        $id = $arr[2] ?? 'id';
        $idValue = $this->request->get($id);
        if (!$idValue) {    // 添加
            $count = $this->db->name($arr[0])->where(['lang'=>$data['lang']])->where([$name=>$value])->count();
        } else {    // 更新
            $count = $this->db->name($arr[0])->where(['lang'=>$data['lang']])->where([$name=>$value])->where($id,'<>', $idValue)->count();
        }

        return $count>0 ? __('%s existed',[$value]):true;
    }
}