<?php
// +----------------------------------------------------------------------
// |HkCms 模型管理
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace app\admin\model\cms;

use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;
use think\Model as BaseModel;

class ModelField extends BaseModel
{
    /**
     * 追加属性
     * @param $value
     * @param $data
     * @return string
     */
    public function getIscoreTextAttr($value,$data)
    {
        $option = [1=>__('Primary table'),0=>__('Auxiliary table')];
        return $option[$data['iscore']];
    }

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
     * 格式化默认字段
     * @param $value
     * @param $data
     * @return string
     */
    public function getDefaultFieldTextAttr($value, $data)
    {
        $option = [1=>__('default'),0=>__('Is not')];
        return $option[$data['default_field']];
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
     * 新增前的处理
     * @param BaseModel $model
     * @return mixed|void
     */
    public static function onBeforeInsert($model)
    {
        $data = $model->getData();
        $info = Model::find($data['model_id']);
        $prefix = $model->getConfig('prefix');
        $table = $prefix.$info->tablename;

        $op = \libs\table\TableOperate::instance();
        if (!$op->isTableExists($table)) {
            throw new DbException("[{$table}]".__('Data table does not exist'));
        }

        $fields = Db::getTableFields($table);
        $field_name = $data['field_name'];
        if (in_array($field_name, $fields)) {
            throw new DbException("[{$field_name}]".__('Field already exists'));
        }

        $bl = \libs\table\TableOperate::instance()
            ->setTable($table)
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
     * 修改前的处理
     * @param BaseModel $model
     * @return mixed|void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
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

            $prefix = $model->getConfig('prefix');
            if ($model->iscore==1) {
                $table = $prefix.'archives';
            } else {
                $info = Model::find($model->getAttr('model_id'));
                $table = $prefix.$info->tablename;
            }

            $op = \libs\table\TableOperate::instance();
            if (!$op->isTableExists($table)) {
                throw new DbException("[{$table}]".__('Data table does not exist'));
            }

            if ($model->getAttr('field_name')!=$model->getOrigin('field_name')) {
                $fields = Db::getTableFields($table);
                $field_name = $model->getAttr('field_name');
                if (in_array($field_name, $fields)) {
                    throw new DbException("[{$field_name}]".__('Field already exists'));
                }
            }

            $form_type = $model->getAttr('form_type');
            if ($data['field_name']=='publish_time') {
                $form_type = 'number';
            }

            $bl = \libs\table\TableOperate::instance()
                ->setTable($table)
                ->setField($model->getAttr('field_name'))
                ->setOldField($model->getOrigin('field_name'))
                ->setType($form_type)
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
     * 删除后的处理
     * @param BaseModel $model
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function onAfterDelete($model)
    {
        if ($model->getAttr('default_field')==1) {
            throw new DbException(__('Default field is not operational'));
        }
        $info = Model::find($model->getAttr('model_id'));
        $prefix = $model->getConfig('prefix');
        $table = $prefix.$info->tablename;

        $op = \libs\table\TableOperate::instance();
        if (!$op->isTableExists($table)) {
            throw new DbException("[{$table}]".__('Data table does not exist'));
        }

        $bl = \libs\table\TableOperate::instance()
            ->setTable($table)
            ->setField($model->getAttr('field_name'))
            ->deleteField();
        if (is_string($bl)) {
            throw new DbException("$bl");
        }

        // 删除字段绑定
        ModelFieldBind::where(['model_field_id'=>$model->getAttr('id')])->delete();
    }
}