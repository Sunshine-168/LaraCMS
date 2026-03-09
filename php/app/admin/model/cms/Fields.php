<?php
// +----------------------------------------------------------------------
// | HkCms 字段管理
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace app\admin\model\cms;

use think\db\exception\DbException;
use think\facade\Db;

class Fields extends \think\Model
{
    /**
     * 格式化规则
     * @param $value
     * @param $data
     * @return string | array
     */
    public function getDataListAttr($value, $data)
    {
        return empty($value) ? [] : json_decode($value, true);
    }

    /**
     * 数据列表格式化
     * @param $value
     * @param $data
     * @return false|string
     */
    public function setDataListAttr($value, $data)
    {
        // 键值对判断
        if ('array'==$data['form_type']) {
            return json_encode(['key'=>empty($data['key_name_alias'])?'键名':$data['key_name_alias'],'value'=>empty($data['key_value_alias'])?'键值':$data['key_value_alias']]);
        }

        // 动态下拉类型
        if ('selectpage'==$data['form_type']) {
            if (empty($data['data_list'])) {
                return '';
            }
            if (!empty($data['data_list']['param'])) {
                $param = [];
                $tmpKey = 1;
                foreach ($data['data_list']['param'] as $key=>$item) {
                    if ($tmpKey==$key) {
                        continue;
                    }
                    if (isset($data['data_list']['param'][$key+1])) {
                        $param['custom'][$item] = $data['data_list']['param'][$key+1];
                        $tmpKey = $key+1;
                    }
                }
                $data['data_list']['param'] = $param;
            }
            if (!empty($data['data_list']['search-field']) && is_array($data['data_list']['search-field'])) {
                $data['data_list']['search-field'] = implode(',',$data['data_list']['search-field']);
            }
            return json_encode($data['data_list']);
        }

        $data_list = explode("\r\n", $value);

        $list = [];
        foreach ($data_list as $k=>$v) {
            $arr = explode('|',$v);
            $list[$arr[0]] = $arr[1];
        }
        return json_encode($list);
    }

    /**
     * 格式化配置信息字段
     * @param $value
     * @param $data
     * @return false|string
     */
    public function setSettingAttr($value, $data)
    {
        $arr = config('base.form_setting');
        foreach ($arr as $key=>&$val) {
            // 筛选列表
            if ($key=='filter_option') {
                if ((isset($data['is_filter']) && $data['is_filter']!=1) || empty($value['filter_option'])) {
                    $val = '';
                    continue;
                }
                $data_list = explode("\r\n", $value['filter_option']);
                $list = [];
                foreach ($data_list as $k=>$v) {
                    $temp = explode('|',$v);
                    $list[$temp[0]] = $temp[1];
                }
                $val = json_encode($list);
            } else if (isset($value[$key])) {
                $val = is_int($value[$key]) ? $value[$key] : trim($value[$key]);
            } else {
                $setting = $this->getOrigin('setting');
                if (!$setting) {
                    continue;
                }
                $set = json_decode($setting, true);
                $val = $set[$key] ?? '';
            }
        }

        return json_encode($arr);
    }

    /**
     * 字段名称格式化
     * @param $value
     * @param $data
     * @return string
     */
    public function setFieldNameAttr($value, $data)
    {
        return strtolower($value);
    }

    /**
     * 增加、修改事件
     * @param \think\Model $model
     * @return mixed|void
     * @throws DbException
     */
    public static function onBeforeWrite($model)
    {
        $data = $model->getData();
        $table = $model->getConfig('prefix').$data['source'];
        $fields = Db::getTableFields($table);

        if (empty($model->getAttr('id')) || $model->getAttr('field_name')!=$model->getOrigin('field_name')) {
            // 判断字段是否已存在
            if (in_array($data['field_name'], $fields)) {
                throw new DbException("[{$data['field_name']}] ".lang('Field already exists'));
            }
        }
    }

    /**
     * 新增后
     * @param \think\Model $model
     */
    public static function onAfterInsert($model)
    {
        $data = $model->getData();
        $bl = \libs\table\TableOperate::instance()
            ->setTable($model->getConfig('prefix').$data['source'])
            ->setField($data['field_name'])
            ->setType($data['form_type'])
            ->setDataList($data['data_list'] ?? null)
            ->setDecimals($data['decimals'] ?? null)
            ->setLength(intval($data['length'] ?? 0))
            ->setDefault($data['default_value'] ?? '')
            ->setComment($data['field_title'])
            ->addField();

        if (is_string($bl)) {
            throw new DbException("$bl");
        }
    }

    /**
     * 修改前
     * @param \think\Model $model
     * @throws DbException
     */
    public static function onBeforeUpdate($model)
    {
        $change = $model->getChangedData();
        $sqlField = ['field_name','form_type','length','default_value','data_list','decimals'];
        $bl = false;
        foreach ($change as $key=>$value) {
            if (in_array($key, $sqlField)) {
                $bl = true;
                break;
            }
        }
        if ($bl) {
            $data = $model->getData();
            $bl = \libs\table\TableOperate::instance()
                ->setTable($model->getConfig('prefix').$data['source'])
                ->setField($model->getAttr('field_name'))
                ->setOldField($model->getOrigin('field_name'))
                ->setType($model->getAttr('form_type'))
                ->setDataList($data['data_list'] ?? null)
                ->setDecimals($data['decimals'] ?? null)
                ->setLength((int)$model->getAttr('length'))
                ->setDefault($model->getAttr('default_value'))
                ->setComment($model->getAttr('field_title'))
                ->editField();

            if (is_string($bl)) {
                throw new DbException("$bl");
            }
        }
    }

    /**
     * 删除后
     * @param \think\Model $model
     */
    public static function onAfterDelete($model)
    {
        $data = $model->getData();
        $bl = \libs\table\TableOperate::instance()
            ->setTable($model->getConfig('prefix').$data['source'])
            ->setField($data['field_name'])
            ->deleteField();
        if (is_string($bl)) {
            throw new DbException("$bl");
        }
    }

    /**
     * 获取允许的字段并格式返回
     * @param $source
     * @param $sourceId
     * @param array $data
     * @return array
     */
    public function getAllowField($source, $sourceId, &$data=[])
    {
        $field = self::where(['status'=>'normal','source'=>$source,'source_id'=>$sourceId])->order('weigh', 'desc')->select()->toArray();

        // 对传入的data，去除不合法的字段；
        if (is_array($data)) {
            $mainData = [];
            foreach ($data as $key=>$value) {
                foreach ($field as $k=>$v) {
                    if ($key == $v['field_name']) {
                        // 格式化
                        switch ($v['form_type']) {
                            case 'datetime':
                            case 'date':
                                $value = $value ?: null;
                                break;
                            case 'number':
                                $value = intval($value);
                                break;
                            case 'checkbox':
                            case 'selects':
                                $value = implode(',', $value);
                                break;
                            default:
                                $value = trim($value);
                                break;
                        }

                        $mainData[$key] = $value;
                    }
                }
            }
            $data = $mainData;
        }
        return $field;
    }
}