<?php

declare (strict_types=1);

namespace libs\form;

use think\Facade;

/**
 * Class Form
 * @method string select($name, $list = array(), $selected = null, $options = array()) 生成select标签
 * @method string input($type, $name, $value = null, $options = array()) 生成指定类型的input 标签
 * @method string radios($name, $values, $checked=null, $options = array()) 生成 radio 组
 * @method string textarea($name, $value = null, $options = array()) 生成 textarea 标签
 * @package libs\form
 */
class Form extends Facade
{
    /**
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'libs\form\FormBuilder';
    }
}