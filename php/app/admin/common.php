<?php

if (!function_exists('extend_options')) {
    /**
     * 字段的扩展字段与html属性覆盖
     * @param $options
     * @param $extend
     * @param bool $isHtml
     * @return array
     */
    function extend_options($options, $extend, $isHtml=false)
    {
        if (!empty($extend)) {
            $extend = explode(',', $extend);
            $arr = [];
            foreach ($extend as $key=>$val) {
                $temp = explode('="', $val, 2);
                if (count($temp)==2) {
                    $arr[trim($temp[0], '"')] = trim($temp[1], '"');
                }
            }
            if (!empty($arr)) {
                $options = array_merge($options, $arr);
            }
        }
        if ($isHtml) return \libs\form\Form::attributes($options);
        return $options;
    }
}

if (!function_exists('build_select')) {
    /**
     * 生成select
     * @param $name
     * @param array $list
     * @param null $selected
     * @param array $options
     * @return string
     */
    function build_select($name, $list = array(), $selected = null, $options = array())
    {
        return \libs\form\Form::select($name, $list, $selected, $options);
    }
}

if (!function_exists('build_input')) {
    /**
     * 生成input文本框
     * @param $type
     * @param $name
     * @param null $value 即value值
     * @param array $options html标签属性
     * @param null $extend 补充选择，重复覆盖options
     * @return string
     */
    function build_input($type, $name, $value = null, $options = array(), $extend = null)
    {
        return \libs\form\Form::input($type, $name, $value, $options);
    }
}

if (!function_exists('textarea')) {
    /**
     * 生产textarea
     * @param $name
     * @param null $value
     * @param array $options
     * @return string
     */
    function build_textarea($name, $value = null, $options = array())
    {
        return \libs\form\Form::textarea($name, $value, $options);
    }
}

if (!function_exists('build_radios')) {
    /**
     * 生成radio标签
     * @param $name
     * @param array $values
     * @param null $checked
     * @param array $options
     * @return string
     */
    function build_radios($name, $values = [], $checked = null, $options = [])
    {
        return \libs\form\Form::radios($name, $values, $checked, $options);
    }
}

if (!function_exists('build_toolbar')) {
    /**
     * 生成表格工具栏按钮
     * @param string|array $operate 默认、刷新、添加、修改、删除按钮
     * @return string
     */
    function build_toolbar($operate='refresh,add,del')
    {
        // 操作按钮、以逗号分隔的形式
        $operate = is_array($operate) ? $operate : explode(',', $operate);
        if (empty($operate)) {
            return '';
        }

        $c = \think\facade\Request::controller();
        $controller = strtolower($c);

        $url = str_replace('.','/',$controller.'/');
        $html = '';
        foreach ($operate as $key=>$value) {
            // 提供参数的处理
            $param = [];
            if (is_array($value)) {
                $param = $value;
                $value = $key;
            }

            //if ('refresh'==$value) {
            //    $html .= '<button type="button" class="btn btn-secondary btn-refresh" data-toggle="tooltip" data-placement="top" title="'.__('Refresh').'"><i class="fas fa-redo-alt"></i></button>';
            //}

            // 权限判断
            if (!app('user')->check($url.$value, app('user')->id)) {
                continue;
            }
            if ('add'==$value) {
                $html .= '<button type="button" class="btn btn-primary btn-add " data-url="'.url('/'.$c.'/add', $param).'"><i class="fas fa-plus"></i> '.__('Add').'</button>';
            } else if ('edit'==$value) {
                $html .= '<button type="button" class="btn btn-disabled btn-primary  disabled btn-edit " data-url="'.url('/'.$c.'/edit', $param).'"><i class="fas fa-pen"></i> '.__('Edit').'</button>';
            } else if ('del'==$value) {
                $html .= '<button type="button" class="btn btn-disabled btn-danger disabled btn-del " data-url="'.url('/'.$c.'/del', $param).'"><i class="fas fa-trash-alt"></i> '.__('Delete').'</button>';
            }
        }
        return $html;
    }
}

if (!function_exists('has_rule')) {
    /**
     * 规则判断
     * @param string $name 规则
     * @return mixed
     */
    function has_rule($name)
    {
        if (strstr($name,'.') || strstr($name, '/')) {
            $name = strstr($name,'.') ? str_replace('.','/', $name):$name;
            $name = ltrim($name, '/');
        } else {
            $controller = strtolower(\think\facade\Request::controller());
            $name = str_replace('.','/',$controller.'/').$name;
        }
        return app('user')->check($name, app('user')->id);
    }
}

if (!function_exists('model_field_screen')) {
    /**
     * 对多级栏目，筛选出给定的model_id对应的栏目
     * @param int $model_id 模型ID
     * @param array $category 多级栏目数组
     * @return mixed
     */
    function model_field_screen($model_id, $category)
    {
        foreach ($category as $key=>$value) {
            if ($value['model_id'] != $model_id && empty($value['child'])) {
                unset($category[$key]);
            }
            if (!empty($value['child'])) {
                $child = model_field_screen($model_id, $value['child']);
                if (empty($child) && $value['model_id'] != $model_id) {
                    unset($category[$key]);
                } else {
                    $category[$key]['child'] = $child;
                }
            }
        }
        return $category;
    }
}

if (!function_exists('lang_content_add')) {
    /**
     * 多语言内容关联添加，助手函数
     * @param string $table 表格
     * @param array $data 表数据数组
     * @param array $lanField 追加初次新增的语言标识字段
     * @param bool $isBind 是否往语言绑定表里添加数据
     * @return array 返回新增参数给定的table表的ID数组
     */
    function lang_content_add(string $table, array $data, array $lanField = [], bool $isBind = true)
    {
        return app()->make(\app\common\services\lang\LangBindService::class)->contentAdd($table, $data, $lanField, $isBind);
    }
}

if (!function_exists('lang_content_del')) {
    /**
     * 内容多语言关联删除，助手函数
     * @param string $table 表格
     * @param integer $curId 当前操作的ID
     * @param bool $bl true-直接删除，false-回收站
     * @return array 返回删除的表主键
     */
    function lang_content_del($table, $curId, $bl = true)
    {
        return app()->make(\app\common\services\lang\LangBindService::class)->contentDel($table, (int)$curId, $bl);
    }
}

if (!function_exists('lang_content_get')) {
    /**
     * 获取关联的语言ID
     * @param $table
     * @param $curId
     * @return array
     */
    function lang_content_get($table, $curId)
    {
        return app()->make(\app\common\services\lang\LangBindService::class)->contentGet($table, (int)$curId);
    }
}