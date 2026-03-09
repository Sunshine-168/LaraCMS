<?php
// +----------------------------------------------------------------------
// | HkCms 栏目管理模型
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types = 1);

namespace app\admin\model\cms;

use app\common\services\lang\LangBindService;
use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;

class Category extends Model
{
    use SoftDelete;

    /**
     * 格式化url
     * @param $value
     * @param $data
     * @return string
     */
    public function getUrlAttr($value, $data)
    {
        if (empty($value) && !empty($data['model_id'])) {
            $site = site(); // 获取伪静态规则
            if ($site['url_mode']==1 && !empty($site['url_rewrite'])) {
                $param = $data;
            } else {
                $param = ['catname'=>$data['name']];
                if ($site['content_lang_on']==1) {
                    $param['lang'] = $data['lang'];
                }
            }
            return index_url('/index/lists', $param);
        } else if ($data['type']=='link' && is_numeric($value)) {
            $info = $this->where(['id'=>$value])->find();
            if ($info) {
                return $info['url'];
            }
        }
        return $value;
    }

    /**
     * 追加属性
     * @param $value
     * @param $data
     * @return string
     */
    public function getTypeTextAttr($value,$data)
    {
        $option = ['category'=>__('Category home'),'list'=>__('Lists'),'link'=>__('Other links')];
        return $option[$data['type']];
    }

    /**
     * 设置多语言
     * @param $value
     * @param $data
     * @return mixed|string
     */
    public function setLangAttr($value, $data)
    {
        // 写入前，必须存入当前编辑模式的缓存
        return $value?$value:app()->cache->get('admin_content_lang'.app('user')->id);
    }

    /**
     * 获取对应模型的ID，二维数组
     * @param $model_id
     * @return array
     */
    public function getModelCategory($model_id)
    {
        $lang = app('cache')->get('admin_content_lang'.app('user')->id);
        
        $category = $this->where(['status'=>'normal','lang'=>$lang])->order(['weigh'=>'asc','id'=>'asc'])->select()->toArray();
        $category = \libs\Tree::instance()->init($category)->getTreeArray(0);
        $category = model_field_screen($model_id, $category);
        return \libs\Tree::instance()->getTreeList($category);
    }

    /**
     * 新增后处理
     * @param Model $model
     */
    public static function onAfterInsert($model)
    {
        if (site('content_lang_on')==1) {
            $data = $model->getData();
            $data['update_time'] = time();
            $data['create_time'] = time();
            $idArr = lang_content_add('category', $data, ['title']);
            if (!app('user')->hasSuperAdmin()) {
                if (!empty($idArr)) {
                    foreach ($idArr as $key=>$value) {
                        Db::name('category_priv')->insert(['category_id'=>$value,'auth_group_id'=>app('user')->getUserGroupId()[0]]);
                    }
                }
            }
            // 多语言情况下自动识别其他链接对应的栏目
            if ($data['type']=='link' && is_numeric($data['url'])) {
                $category = Db::name('category')->whereIn('id', $idArr)->select()->toArray();
                foreach ($category as $key=>$value) {
                    $curId = app()->make(LangBindService::class)->getBindValue($value['url'],'category',$value['lang']);
                    Db::name('category')->where(['id'=>$value['id']])->update(['url'=>$curId]);
                }
            }
            // 单页自动生成单页文章
            if ($data['type']=='link' && !empty($data['model_id'])) {
                $category = Db::name('category')->whereIn('id', $idArr)->select()->toArray();
                $single = Db::name('model')->find($data['model_id']);
                foreach ($category as $key=>$value) {
                    if ($single['is_search']!=-1) {  // 不支持搜索的通常不是一个主表
                        $id = Db::name('archives')->insertGetId(['category_id'=>$value['id'],'model_id'=>$value['model_id'],'title'=>$value['title'],'show_tpl'=>$value['show_tpl'],'create_time'=>time(),'lang'=>$value['lang']]);
                        Db::name($single['tablename'])->insert(['id'=>$id,'content'=>'']);
                    }
                }
            }

        }
    }

    /**
     * 更新前
     * @param Model $model
     * @return mixed|void
     */
    public static function onBeforeUpdate($model)
    {
        $data = $model->getData();
        $odata = $model->getOrigin();
        if (empty($odata['model_id']) && !empty($data['model_id']) && $data['type']="link") {
            $model->setAttr('url', '');
        }
    }

    /**
     * 删除前的处理
     * @param Model $model
     * @return mixed|void
     * @throws \think\db\exception\DbException
     */
    public static function onBeforeDelete($model)
    {
        $data = $model->getData();

        if ($data['model_id'] && empty($data['delete_time'])) { // 放入回收站
            $ids = lang_content_del('category', $data['id'], false);

            $ids = Db::name('category')->whereIn('id',$ids)->select();
            foreach ($ids as $key=>$value) {
                $modelInfo = \app\admin\model\cms\Model::where(['id'=>$value['model_id']])->find();
                if (!empty($modelInfo)) {
                    $c = '\app\admin\model\cms\\'.$modelInfo->controller;
                    (new $c)->handleDel($modelInfo, $value['id']);
                }
            }
        } else if ($data['model_id'] && !empty($data['delete_time'])) { // 永久删除
            $ids = lang_content_del('category', $data['id'], false);
            $ids[] = $data['id'];
            $idArr = Db::name('category')->whereIn('id',$ids)->select()->toArray();
            foreach ($idArr as $key=>$value) {
                $modelInfo = \app\admin\model\cms\Model::where(['id'=>$value['model_id']])->find();
                if (!empty($modelInfo)) {
                    $c = '\app\admin\model\cms\\'.$modelInfo->controller;
                    (new $c)->handleDel($modelInfo, $value['id'], 3);
                }
            }
            // 删除栏目权限与多语言
            lang_content_del('category', $data['id']);
            Db::name('category_priv')->whereIn('category_id',$ids)->delete();
            Db::name('model_field_bind')->whereIn('category_id',$ids)->delete();
        }
    }

    /**
     * 恢复后的处理
     * @param Model $model
     */
    public static function onAfterRestore($model)
    {
        $data = $model->getData();

        // 文档数据恢复
        if ($data['model_id']) {
            $idArr = lang_content_get('category',$data['id']);
            Db::name('category')->whereIn('id', $idArr)->update(['delete_time'=>null]);
            $idArr = Db::name('category')->whereIn('id', $idArr)->select();

            foreach ($idArr as $key=>$value) {
                $modelInfo = \app\admin\model\cms\Model::where(['id'=>$value['model_id']])->find();
                if (!empty($modelInfo)) {
                    $c = '\app\admin\model\cms\\'.$modelInfo->controller;
                    (new $c)->handleDel($modelInfo, $value['id'], 2);
                }
            }
        }
    }
}
