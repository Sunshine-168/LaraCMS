<?php
// +----------------------------------------------------------------------
// | HkCms 轮播、广告模型
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: HkCms team <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types = 1);

namespace app\admin\model\cms;

use think\Model;

class ModelFieldBind extends Model
{
    /**
     * 获取允许的字段并格式返回
     * @param $category_id
     * @param $model_id
     * @param array $data
     * @return array
     */
    public function getAllowField($category_id, $model_id, &$data=[])
    {
        $fieldIdArr = ModelField::where(['status'=>'normal','admin_auth'=>1,'model_id'=>$model_id])->column('id');
        $fieldBind = ModelFieldBind::where('model_field_id','in', $fieldIdArr)->select()->toArray();
        $fieldBindArr = [];
        foreach ($fieldBind as $key=>$value) {
            $fieldBindArr[$value['model_field_id']][] = $value['category_id'];
        }

        $modelField = ModelField::where(['status'=>'normal','admin_auth'=>1,'model_id'=>$model_id])->order('weigh', 'asc')->select()->toArray();
        foreach ($modelField as $key=>$value) {
            if (isset($fieldBindArr[$value['id']]) && !in_array($category_id, $fieldBindArr[$value['id']])) {
                unset($modelField[$key]);   // 排除不属于该栏目的字段
            }
        }

        $model = \app\admin\model\cms\Model::where(['id'=>$model_id])->cache()->find();

        // 对传入的data，去除不合法的字段；
        if (is_array($data)) {
            $mainData = [];
            $viceData = [];
            foreach ($data as $key=>$value) {
                foreach ($modelField as $k=>$v) {
                    if ($key == $v['field_name']) {
                        // 格式化
                        switch ($v['form_type']) {
                            case 'datetime':
                            case 'date':
                                $value = $value ?: null;
                                break;
                            case 'number':
                                $value = $v['decimals']>0 ? number_format(floatval($value), $v['decimals'], '.','') : intval($value);
                                break;
                            case 'checkbox':
                            case 'selects':
                                $value = implode(',', $value);
                                break;
                            case 'select':
                                $value = isset($v['data_list'][$value]) ? $value : null;
                                break;
                            default:
                                $value = trim($value);
                                break;
                        }

                        // 主副表
                        if ($model['controller']=='Guestbook') {
                            $mainData[$key] = $value;
                        } else {
                            if ($v['iscore']==1) {
                                $mainData[$key] = $value;
                            } else {
                                $viceData[$key] = $value;
                            }
                        }
                    }
                }
            }
            $data = $model['controller']!='Guestbook' ? ['main'=>$mainData, 'vice'=>$viceData] : $mainData;
        }
        return $modelField;
    }
}
