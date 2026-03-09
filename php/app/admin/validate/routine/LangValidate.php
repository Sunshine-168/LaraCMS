<?php

namespace app\admin\validate\routine;

use app\admin\validate\BaseValidate;

/**
 * 多语言验证
 */
class LangValidate extends BaseValidate
{
    /**
     * 初始化
     * @param array $rules
     * @param $message
     * @param $field
     */
    public function __construct(array $rules = [], $message = [], $field = [])
    {
        // 设置多语言
        $this->field = [
            'mark' => __('mark'),
            'title' => __('title')
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
        'title' => 'require',
        'mark' => 'require|alphaDash',
        'module' => 'require',
    ];
}