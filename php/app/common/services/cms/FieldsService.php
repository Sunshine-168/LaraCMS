<?php
// +----------------------------------------------------------------------
// | 字段管理
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\services\cms;

use app\common\dao\cms\FieldsDao;
use app\common\services\BaseService;
use think\facade\Cache;
use think\facade\Db;

/**
 * @mixin FieldsDao
 */
class FieldsService extends BaseService
{
    const CacheTag = 'fieldsService';

    /**
     * 初始化
     * @param FieldsDao $dao
     */
    public function __construct(FieldsDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取缓存字段一维数组
     * @param string $source
     * @return array
     */
    public function getFieldsNameCache(string $source = 'category')
    {
        $fields = Cache::get('fields_name'.$source);
        if (empty($fields)) {
            $fields = $this->dao->column(['source'=>$source, 'status'=>'normal'],'field_name');
            Cache::tag(self::CacheTag)->set('fields_name'.$source, $fields, 300);
        }
        return $fields;
    }

    /**
     * 格式化字段
     * @param $field
     * @param string $source
     * @return void
     */
    public function fieldFormat(&$field, string $source = 'category')
    {
        $fieldData = $this->dao->search([])->where(['source'=>$source, 'status'=>'normal'])->cache(60)->field('field_name,form_type,data_list')->select();
        foreach ($fieldData as $item) {
            if (isset($field[$item['field_name']])) {
                $value = $field[$item['field_name']];
                switch ($item['form_type']) {
                    case 'checkbox':
                    case 'selects':
                        $value = $value ? explode(',', $value) : [];
                        break;
                    case 'selectpage':
                        $dataList = json_decode($item['data_list'],true);
                        if (empty($dataList) || empty($dataList)) {
                            return $value;
                        }
                        // 多语言
                        $map = [];
                        if (!empty($dataList['enable-lang']) && $dataList['enable-lang']==1) {
                            $map = [['lang', '=', app()->lang->getLangSet()]];
                        }
                        // 原始值返回给前台
                        $field[$item['field_name'].'_raw'] = $value;
                        // url形式，多选转数组
                        if ($dataList['type']=='url' && !empty($dataList['multiple']) && $dataList['multiple']==1) {
                            $value = $value ? explode(',', $value) : '';
                        } else if ($dataList['type']=='table' && !empty($dataList['table']) && !empty($dataList['key-field']) && !empty($dataList['multiple']) && $dataList['multiple']==1) {
                            // 关联表，多选
                            if (empty($value)) { // 关联表值为空或0不进行关联
                                break;
                            }
                            $value = Db::name($dataList['table'])->whereIn($dataList['key-field'], $value)->where($map)->cache(60)->select()->toArray();
                        } else if ($dataList['type']=='table' && !empty($dataList['table']) && !empty($dataList['key-field']) && (empty($dataList['multiple']) || $dataList['multiple']!=1)) {
                            // 关联表，单选
                            if (empty($value)) { // 关联表值为空或0不进行关联
                                break;
                            }
                            $value = Db::name($dataList['table'])->where($dataList['key-field'], $value)->where($map)->cache(60)->find();
                        }
                        break;
                    case 'image':
                    case 'downfile':
                        $value = cdn_url($value, true);
                        break;
                    case 'images':
                    case 'downfiles':
                        if ($value) {
                            $tmpVal = json_decode($value, true);
                            if ($tmpVal) {
                                $value = array_map(function ($node){
                                    $node['file'] = cdn_url($node['file'], true);
                                    return $node;
                                }, $tmpVal);
                            } else {
                                //$value = explode(',', $value);
                                //$value = array_map(function ($node){
                                //    return cdn_url($node, true);
                                //}, $value);
                                $value = null;
                            }
                        } else {
                            $value = null;
                        }
                        break;
                    case 'editor':
                    case 'textarea':
                        $value = htmlspecialchars_decode($value);
                        break;
                    case 'array':
                        $value = json_decode(htmlspecialchars_decode($value), true);
                }
                $field[$item['field_name']] = $value;
            }
        }
    }
}