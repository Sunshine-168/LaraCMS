<?php
// +----------------------------------------------------------------------
// | HkCms 排序标签
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

namespace app\index\taglib\hkcms;

use app\admin\model\cms\ModelField;

class TagOrder extends Base
{
    public function initialize()
    {
        parent::initialize();
    }

    /**
     * 获取排序列表
     * @param int $modelId
     * @param array $tag
     * @return array
     */
    public function lists($modelId, $tag)
    {
        $param = app()->request->get();

        // 获取允许的排序字段
        $data = ModelField::where(['model_id'=>$modelId,'is_order'=>1])->order('weigh','desc')->select()->toArray();
        if (empty($data)) {
            return [];
        }

        // 设定当前排序字段
        $sort = isset($param['sort']) && in_array($param['sort'],array_map(function ($vo){return $vo['field_name'];},$data)) ? $param['sort'] : '_default';
        // 设定默认排序
        $order = isset($param['order']) && in_array($param['order'],['desc','asc']) ? $param['order'] : '';
        $orderlist = [
            [
                'title' => '默认',
                'field' => '_default',
                'iscore' => '1',
                'url' => '?'.http_build_query(array_merge($param,['sort'=>'_default'])),
                'currentstyle' => '_default'==$sort ? $tag['currentstyle']:'',
                'order' => $order
            ]
        ];
        foreach ($data as $key=>$value) {
            $tmpOrder = $value['field_name']==$sort?($order=='desc'?'asc':'desc'):'desc';
            $temp['title'] = $value['field_title'];
            $temp['field'] = $value['field_name'];
            $temp['iscore'] = $value['iscore'];
            $temp['order'] = $tmpOrder;
            $temp['url'] = '?'.http_build_query(array_merge($param,['sort'=>$value['field_name'],'order'=>$tmpOrder]));
            $temp['currentstyle'] = $value['field_name']==$sort ? $tag['currentstyle']:'';
            $orderlist[] = $temp;
        }

        return $orderlist;
    }
}