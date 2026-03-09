<?php
// +----------------------------------------------------------------------
// | HkCms 模型验证
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

namespace app\admin\validate\cms;

use libs\table\TableOperate;
use app\admin\validate\BaseValidate;

class Model extends BaseValidate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'type' => 'in:single,more',
        'name' => 'require|checkUnique:Model,name',
        'tablename' => 'require|alphaDash|checkUnique:Model,tablename|checkTable',
        'controller' => 'alphaDash',
        'diyname' => 'alphaDash|checkUnique:Model,diyname',
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
     * 验证表是否存在
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     */
    protected function checkTable($value, $rule, $data=[])
    {
        $id = $this->request->param('id');
        $prefix = \app\admin\model\cms\Model::getConfig('prefix');
        if (empty($id)) {
            return TableOperate::instance()->isTableExists($prefix.$value) ? __('Table already exists') : true;
        } else {
            $tablename = \app\admin\model\cms\Model::where('id', $id)->value('tablename');
            if ($tablename==$value) {
                return true;
            }
            return TableOperate::instance()->isTableExists($prefix.$value) ? __('Table already exists') : true;
        }
    }

    /**
     * 导入场景的处理
     * @return Model
     */
    public function sceneImport()
    {
        return $this->only(['name','tablename','status'])->remove('type','controller');
    }
}