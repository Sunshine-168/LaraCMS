<?php
// +----------------------------------------------------------------------
// | HkCms 字段验证
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace app\admin\validate\cms;

use app\admin\validate\BaseValidate;

class ModelField extends BaseValidate
{
    public function __construct(array $rules = [], $message = [], $field = [])
    {
        $this->field = [
            'form_type' => __('Field type'),
            'field_name' => __('Field name'),
            'field_title' => __('Title'),
            'length' => __('Length'),
            'data_list' => __('Option list'),
            'max_number' => __('Greatest amount'),
            'decimals' => __('Decimal places'),
            'default_value' => __('Defaults'),
            'rules' => __('Rule'),
            'tips' => __('Tips'),
            'error_tips' => __('Error tips'),
            'extend' => __('HTML attr'),
            'weigh' => __('Weigh'),
            'status' => __('Status')
        ];

        $this->message['field_name.regx'] = __("Starts with a letter, only supports letters, numbers, underscores");
        parent::__construct();
    }

    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'model_id' => 'require|number',
        'form_type' => 'require|alphaDash',
        'iscore' => 'require|in:1,0',
        'field_name' => 'require|alphaDash|regx:^[a-zA-z].+|max:20|checkField',
        'field_title' => 'require',
        'length' => 'requireCallback:requireLength|number|checkLength',
        'data_list' => 'checkRules',
        'max_number' => 'requireCallback:requireMaxNumber|number',
        'decimals' => 'requireIf:form_type,number|number|max:1',
        'default_value' => 'checkDefault',
        'rules' => 'max:255',
        'tips' => 'max:255',
        'error_tips' => 'max:255',
        'extend' => 'max:500',
        'weigh' => 'number',
        'status' => 'require|in:normal,hidden'
    ];

    protected $scene = [
        'edit'  =>  ['model_id','form_type','field_name','field_title','length','data_list','max_number','decimals','default_value','rules','tips','error_tips','extend','weigh','status'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [];

    /**
     * 验证字段是否重复
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     */
    protected function checkField($value, $rule, $data=[])
    {
        $idValue = $this->request->param('id');
        if (in_array($value, ['category','model','id','tags','style','category_ids','lang'])) {
            return __('%s existed', [$value]);
        }
        if (!$idValue) {    // 添加
            // 取消区分主副表重复检测，改成在模型内字段唯一。
            $count = $this->db->name('model_field')->where(['field_name'=>$value,'model_id'=>$data['model_id']])->count();
        } else {    // 更新
            $count = $this->db->name('model_field')->where(['field_name'=>$value,'model_id'=>$data['model_id']])->where('id','<>', $idValue)->count();
        }
        return $count>0 ? __('%s existed', [$value]):true;
    }

    /**
     * 验证规则格式
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     */
    protected function checkRules($value, $rule, $data=[])
    {
        if (in_array($data['form_type'], ['radio','checkbox','select'])) {
            $data_list = explode("\r\n", $value);

            foreach ($data_list as $k=>$v) {
                $arr = explode('|',$v);
                if (count($arr) != 2) {
                    return __('Data list format error');
                }
            }
        }
        return true;
    }

    /**
     * 验证数字
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     */
    protected function checkLength($value, $rule, $data=[])
    {
        if ('number' == $data['form_type'] && ($value>11 || $value<1) && empty($data['decimals'])) {
            return __('Incorrect length format or length exceeds');
        } else if ('number' == $data['form_type'] && ($value>20 || $value<1) && !empty($data['decimals'])) {
            return __('Incorrect length format or length exceeds');
        } else if ('text' == $data['form_type'] && ($value<1 || $value>250)) {
            return __('The length of a single line should be within 1~250');
        } else if ('textarea' == $data['form_type'] && ($value<1 || $value>16000)) {
            return __('The length of multi-line text should be 1~16000');
        }
        return true;
    }

    /**
     * 验证默认值
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     */
    protected function checkDefault($value, $rule, $data=[])
    {
        if ('number' == $data['form_type'] && !is_numeric($value)) {
            return __('The default value must be numeric');
        } else if ('datetime' == $data['form_type'] && !$this->dateFormat($value,'Y-m-d H:i:s')) {
            return __('The default value should be the format of the date and time (Y-m-d H:i:s)');
        } else if ('date' == $data['form_type'] && !$this->dateFormat($value,'Y-m-d')) {
            return __('The default value must be the date format');
        } else if ('array' == $data['form_type'] && $value) {
            $json = json_decode($value, true);
            if (empty($json)) {
                return __('Default value must be JSON characters');
            }
        }
        return true;
    }

    /**
     * 长度在以下情况下必须验证
     * @param $value
     * @param $data
     * @return bool
     */
    public function requireLength($value, $data)
    {
        if (in_array($data['form_type'], ['text', 'textarea', 'image', 'number', 'images', 'downfile', 'downfiles'])) {
            return true;
        }
        return false;
    }

    /**
     * 最大数量
     * @param $value
     * @param $data
     * @return bool
     */
    public function requireMaxNumber($value, $data)
    {
        if (in_array($data['form_type'], ['images','downfiles'])) {
            return true;
        }
        return false;
    }
}
