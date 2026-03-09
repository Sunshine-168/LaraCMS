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

class Fields extends BaseValidate
{
    public function __construct(array $rules = [], $message = [], $field = [])
    {
        $this->field = [
            'form_type' => __('Type'),
            'field_name' => __('Field'),
            'field_title' => __('Title'),
            'length' => __('Length'),
            'data_list' => __('Option list'),
            'max_number' => __('Greatest amount'),
            'decimals' => __('Decimal places'),
            'default_value' => __('Default'),
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
	    'source' => 'require',
	    'source_id' => 'require|number',
        'form_type' => 'require|alphaDash',
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
        'edit'  =>  ['source','source_id','form_type','field_name','field_title','length','data_list','max_number','decimals','default_value','rules','tips','error_tips','extend','weigh','status'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'field_name.regx'=>''
    ];

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
        if (!$idValue) {    // 添加
            $count = $this->db->name('fields')->where(['field_name'=>$value,'source'=>$data['source'],'source_id'=>$data['source_id']])->count();
        } else {    // 更新
            $count = $this->db->name('fields')->where(['field_name'=>$value,'source'=>$data['source'],'source_id'=>$data['source_id']])->where('id','<>', $idValue)->count();
        }
        return $count>0 ? __('%s existed',[$value]):true;
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
            return __('Length format is incorrect or length exceeds');
        } else if ('number' == $data['form_type'] && ($value>20 || $value<1) && !empty($data['decimals'])) {
            return __('Length format is incorrect or length exceeds');
        } else if ('text' == $data['form_type'] && ($value<1 || $value>250)) {
            return __('The length of single line text should be within 1 ~ 250');
        } else if ('textarea' == $data['form_type'] && ($value<1 || $value>16000)) {
            return __('The length of multiline text should be within 1 ~ 16000');
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
            return __("The default value must be a number");
        } else if ('datetime' == $data['form_type'] && !$this->dateFormat($value,'Y-m-d H:i:s')) {
            return __("The default value must be in date time format");
        } else if ('date' == $data['form_type'] && !$this->dateFormat($value,'Y-m-d')) {
            return __('The default value must be date');
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
