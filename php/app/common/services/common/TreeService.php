<?php
// +----------------------------------------------------------------------
// | tree 服务层
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\common\services\common;

class TreeService
{
    /**
     * 生成树形结构
     * @param array $options 数据源
     * @param string $mainFiled 主键字段，与父级字段对应关系的字段
     * @param string $labelField 标题字段，Cascader级联组件的label字段
     * @param string $valueField 值字段，Cascader级联组件的值字段
     * @param array $extendField 额外需要展示的字段
     * @param string $pidName 父级字段
     * @param int $pid 父级ID
     * @param int $level 级别
     * @return array
     */
    public static function optionTree(array &$options, string $mainFiled = 'id', string $labelField = 'title', string $valueField = 'id', array $extendField = [], string $pidName = 'parent_id', int $pid = 0, int $level = 0): array
    {
        $_options = $options;
        $data = [];
        foreach ($_options as $key=>$option) {
            if ($option[$pidName] == $pid) {
                $item = ['value'=>$option[$valueField], 'label'=>$option[$labelField]];
                $extendValue = [];
                foreach ($extendField as $value) {
                    if (isset($option[$value])) {
                        $extendValue[$value] = $option[$value];
                    }
                }
                $item = array_merge($item, $extendValue);
                unset($options[$key]);
                $item['children'] = self::optionTree($options, $mainFiled, $labelField, $valueField, $extendField, $pidName, $option[$mainFiled], $level + 1);
                if (empty($item['children'])) {
                    unset($item['children']);
                }
                $data[] = $item;
            }
        }
        return $data;
    }
}