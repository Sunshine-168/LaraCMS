<?php
// +----------------------------------------------------------------------
// | HkCms 模型管理
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace app\admin\model\cms;

use think\facade\Db;
use think\Model as BaseModel;

class Model extends BaseModel
{
    /**
     * 数据新增后的处理
     * @param BaseModel $model
     * @return mixed|void
     * @throws \think\Exception
     */
    public static function onAfterInsert($model)
    {
        $one = ModelController::where(['name'=>$model->getAttr('controller')])->find();

        $path = $one->sql_file;
        $res = lang('SQL file is empty');
        if (!empty($path)) {
            $path = explode(',', $path);
            $res = \libs\table\TableOperate::instance([
                'tablename' => $model->getAttr('tablename'),
                'prefix'    => $model->getConfig('prefix'),
                'model_id'  => $model->getAttr('id'),
                'sql_file'  => $path
            ])->createTables();
        }
        if (is_string($res)) {
            $model->where(['id'=>$model->getAttr('id')])->delete();
            throw new \think\Exception($res);
        }

        // 如果有配置文件则写入配置信息
        if (!empty($one['config'])) {
            $temp_arr = json_decode($one['config'], true);
            $config = [];
            foreach ($temp_arr as $key => $value) {
                if (in_array($value['type'], ['checkbox','selects']) || ($value['type']=='selectpage' && !empty($value['data_list']['multiple']))) {
                    $config[$key] = explode(',', $value['value']);
                } else {
                    $config[$key] = $value['value'];
                }
            }
            if ($config) {
                Db::name('model')->where(['id'=>$model->getAttr('id')])->update(['config'=>json_encode($config)]);
            }
        }
    }

    /**
     * 数据更新前的处理
     * @param BaseModel $model
     * @return mixed|void
     * @throws \think\Exception
     */
    public static function onBeforeUpdate($model)
    {
        $oldTable = $model->getOrigin('tablename');
        $newTable = $model->getAttr('tablename');

        if ($oldTable!=$newTable) {
            $prefix = $model->getConfig('prefix');
            $res = \libs\table\TableOperate::instance()->renameTable($prefix.$oldTable, $prefix.$newTable);
            if (is_string($res)) {
                throw new \think\Exception($res);
            }
        }
    }

    /**
     * 删除模型后的处理
     * @param BaseModel $model
     * @throws \think\Exception
     */
    public static function onAfterDelete($model)
    {
        $table = $model->getAttr('tablename');
        $prefix = $model->getConfig('prefix');

        // 数据表删除
        $res = \libs\table\TableOperate::instance()->dropTable($prefix.$table);
        if (is_string($res)) {
            throw new \think\Exception($res);
        }

        // 文章内容删除
        Db::name('archives')->where(['model_id'=>$model->getAttr('id')])->delete();

        // 字段表数据删除
        ModelField::where(['model_id'=>$model->getAttr('id')])->delete();
        // 分类删除
        $ids = Category::where(['model_id'=>$model->getAttr('id')])->column('id');
        Db::name('category')->where(['model_id'=>$model->getAttr('id')])->delete();
        ModelFieldBind::whereIn('category_id', $ids)->delete();
    }
}