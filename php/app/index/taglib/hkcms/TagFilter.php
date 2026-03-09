<?php
// +----------------------------------------------------------------------
// | HkCms 筛选标签
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\index\taglib\hkcms;

use app\admin\model\cms\ModelField;

class TagFilter extends Base
{
    public function initialize()
    {
        parent::initialize();
    }

    /**
     * 获取筛选列表
     * @param $modelId
     * @param $tag
     * @return array
     */
    public function lists($modelId, $tag)
    {
        $param = app()->request->get();

        // 获取允许的筛选字段
        $data = ModelField::where(['model_id'=>$modelId,'is_filter'=>1]);
        if ($tag['field']) { // 限制字段
            $data = $data->whereIn('field_name',$tag['field']);
        }
        $data = $data->order('weigh','asc')->select()->toArray();
        $filter = [];
        $field = []; // 筛选字段
        foreach ($data as $key=>$value) {
            $temp = [];
            $temp['title'] = $value['field_title'];
            $temp['name'] = $value['field_name'];

            //if (empty($value['setting'])) {
            //    continue;
            //}

            $arr = [];
            if (!empty($value['setting'])) {
                $setting = json_decode($value['setting'], true);
                if (!empty($setting['filter_option'])) {
                    $arr = json_decode($setting['filter_option'], true);
                } else if (!empty($value['data_list'])) {
                    $arr = $value['data_list'];
                }
            } else if (!empty($value['data_list'])) {
                $arr = $value['data_list'];
            }

            $field[] = $value['field_name'];
            $tempParam = $param;
            unset($tempParam[$value['field_name']]);

            // alltxt=off 关闭默认第一个选项
            if ($tag['alltxt']!='off') {
                $temp['option'][] = ['title'=>$tag['alltxt'],'name'=>$value['field_name'],'url'=>'?'.http_build_query($tempParam),'currentstyle'=>empty($param[$value['field_name']])?$tag['currentstyle']:''];
            }
            foreach ($arr as $k=>$v) {
                $tempOption['title'] = $v;
                $tempOption['name'] = $k;

                if ($tag['multiple']==1 && !empty($param[$value['field_name']])) { // 允许多选
                    $val = explode(',', $param[$value['field_name']]);
                    if (in_array($k,$val)) {
                        $val = array_diff($val,[$k]);
                    } else {
                        $val[] = $k;
                    }

                    $cur = [$value['field_name']=>implode(',',array_unique($val))];
                    $tempOption['currentstyle'] = isset($param[$value['field_name']]) && in_array($k,explode(',', $param[$value['field_name']]))?$tag['currentstyle']:'';
                } else {
                    $cur = [$value['field_name']=>$k];
                    $tempOption['currentstyle'] = isset($param[$value['field_name']]) && $param[$value['field_name']]==$k?$tag['currentstyle']:'';
                }

                $tempOption['url'] = '?'.http_build_query(array_merge($param,$cur));
                $temp['option'][] = $tempOption;
            }
            $filter[] = $temp;
        }

        return $filter;
    }
}