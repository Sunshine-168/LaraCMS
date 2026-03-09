<?php
// +----------------------------------------------------------------------
// | HkCms 网站前台
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

namespace app\index\controller;

use app\admin\model\cms\Archives;
use app\admin\model\cms\Model;
use app\index\model\cms\Category;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Validate;

class Index extends BaseController
{
    public function initialize()
    {
        parent::initialize();

        // 非开发者模式下,屏蔽非致命错误
        if (!$this->app->isDebug()) {
            // 屏蔽掉notice错误
            //error_reporting(E_ERROR & ~E_WARNING & ~E_NOTICE);
            error_reporting(E_ERROR);
        }
    }

    public function index()
    {
        return $this->view->fetch();
    }

    /**
     * 内容列表
     * @return string|void
     */
    public function lists()
    {
        // 栏目英文目录
        $catname = $this->request->param('catname','');
        // 栏目ID
        $catId = $this->request->param('catid','');

        $cateInfo = (new Category)->getCateInfo(empty($catId)?$catname:$catId);
        if (empty($cateInfo)) {
            $this->error(lang('Column doesn\'t exist.'));
        }

        $model = Model::where(['id'=>$cateInfo['model_id']])->find();
        if (empty($model)) {
            $this->error(lang('Model doesn\'t exist.'));
        }

        if ('category'==$cateInfo['type']) {
            // 栏目首页模板
            $template = 'category/'.(!empty($cateInfo['category_tpl']) ? $cateInfo['category_tpl']:'category');
        } else if ('list'==$cateInfo['type']) {
            $template = 'list/'.(!empty($cateInfo['list_tpl']) ? $cateInfo['list_tpl']:'list');
        } else if ('link'==$cateInfo['type'] && $cateInfo['model_id']) {
            if ($model['controller']!='Guestbook') {
                $info = Archives::with(['category','model'])->where(['category_id'=>$cateInfo['id'],'status'=>'normal'])->find();
                if (empty($info)) {
                    $this->error(lang('The document doesn\'t exist.'));
                }
                $info = $info->moreInfo();
                $this->view->assign('__page__', $info['__page__']??null);
                $this->view->assign('Info', $info);
                if (!empty($info['show_tpl'])) {
                    $cateInfo['show_tpl'] = $info['show_tpl'];
                }
                Db::name('archives')->where(['id'=>$info['id']])->inc('views')->update();
            }
            $template = 'page/'.(!empty($cateInfo['show_tpl']) ? $cateInfo['show_tpl']:'page');
        } else {
            $this->error(lang('The page doesn\'t exist.'));
        }

        // seo 标题
        $seo_title = empty($cateInfo['seo_title'])?$cateInfo['title']:$cateInfo['seo_title'];
        $page = $this->request->param("page","","intval");
        $page = $page==0?1:$page;
        $seo_title = str_replace(['$name','$page','$site'], [$seo_title,$page,site("title")], site('category_format'));

        $template = explode(".", $template, 2);
        $this->view->assign('Cate', $cateInfo);
        $this->view->assign('seo_title', $seo_title);
        $this->view->assign('seo_keywords', $cateInfo['seo_keywords']);
        $this->view->assign('seo_desc', $cateInfo['seo_desc']);
        return $this->view->fetch($template[0]);
    }

    /**
     * 详情页
     * @return string|void
     */
    public function show()
    {
        // 栏目ID
        $catId = $this->request->param('catid','', 'intval');
        // 栏目英文
        $list = $this->request->param('catname','');
        // 父级栏目
        $catdir = $this->request->param('catdir','');
        // 文章ID、或者别名
        $id = $this->request->param('id', '', '');
        // 模型
        $model = $this->request->param('model', 0);

        $key = $this->request->param('key','');

        if (!is_numeric($catId) && empty($list) && !empty($catdir)) {
            $catId = $catdir;
        } else if (!is_numeric($catId) && !empty($list) && empty($catdir)) {
            $catId = $list;
        } else if (!is_numeric($catId) && !empty($list) && !empty($catdir)) {
            $catId = $list;
        }

        if (empty($model) && !empty($catId)) {
            $cateInfo = (new Category)->getCateInfo($catId);
            if (empty($cateInfo)) {
                $this->error(lang('The page doesn\'t exist.'));
            }
            $model = Model::where(['id'=>$cateInfo['model_id'],'status'=>'normal'])->find();
            if (empty($model)) {
                $this->error(lang('Model doesn\'t exist.'));
            }
        } else {
            $model = Model::where(['status'=>'normal'])->where(function ($query) use ($model){
                $query->where(['diyname'=>$model])->whereOr(['tablename'=>$model]);
            })->cache(app()->isDebug()?false:'model')->find();
            if (empty($model)) {
                $this->error(lang('Model doesn\'t exist.'));
            }
        }

        // 文章ID、别名
        if (is_numeric($id)) {
            $where = ['id'=>$id];
        } else {
            $where = ['diyname'=>$id];
        }

        $archives = new Archives();
        if (!empty($key) && md5(app('session')->getId())==$key) { // 授权临时访问禁用的文章
            $info = $archives->with(['category','model'])->where($where)->append(['publish_time_text','fullurl'])->find();
        } else {
            $info = $archives->with(['category','model'])->where($where)->where(['status'=>'normal'])->append(['publish_time_text','fullurl'])->find();
        }
        if (empty($info)) {
            $this->error(lang('The document doesn\'t exist.'));
        }
        if (site('user_on') == 1 && isset($info['islogin']) && $info['islogin'] && !session('Member')) {
            $this->error(__('Please log in and operate'), (string)url('/user.user/login'));
        }
        $info = $info->moreInfo();
        $this->view->assign('__page__', $info['__page__']??null);

        // 父级栏目矫正
        if (!isset($cateInfo) || $cateInfo['id']!=$info['category_id']) {
            $cateInfo = (new Category)->getCateInfo($info['category_id']);
        }

        Db::name('archives')->where(['id'=>$info['id']])->inc('views')->update();
        $this->view->assign('Cate', $cateInfo);
        $this->view->assign('Info', $info);

        // seo 模型固定的默认字段 keywords description
        $seo_title = empty($info['seotitle'])?$info['title']:$info['seotitle'];
        $seo_title = str_replace(['$title','$name','$site'], [$seo_title,$cateInfo['title'],site("title")], site('content_format'));

        $this->view->assign('seo_title', $seo_title);
        $this->view->assign('seo_keywords', isset($info['keywords'])?$info['keywords']:$cateInfo['seo_keywords']);
        $this->view->assign('seo_desc', isset($info['description'])?$info['description']:$cateInfo['seo_desc']);
        $template = explode(".", $info['show_tpl'], 2);
        return $this->view->fetch('show/'.$template[0]);
    }

    /**
     * 文件下载
     * @return \think\response\File
     */
    public function download()
    {
        // 资源ID
        $id = $this->request->param('id','', 'intval');
        // 文档ID
        $aid = $this->request->param('aid','','intval');
        // 模型ID
        $model = $this->request->param('m', '', 'intval');
        // 字段名称
        $field = $this->request->param('fd', '');

        if (empty($id)) {
            $this->error(lang('Illegal access'));
        }

        // 获取资源ID
        $info = Db::name('attachment')->find($id);
        if (empty($info)) {
            $this->error(lang('Resource does not exist'));
        }

        if (is_numeric($aid) && is_numeric($model) && !empty($field) && Validate::is($field, 'alphaDash')) { // 统计下载
            $key = md5(app('session')->getId()).$field.$aid.$model;
            if (!cache($key)) {
                $this->error(lang('Illegal access'));
            }

            $model_field = Db::name('model_field')->where(['field_name'=>$field,'model_id'=>$model])->find();
            if (empty($model_field)) {
                $this->error(lang('Field does not exist'));
            }

            if ($model_field['iscore']==1) {
                // 主表
                controller($model,function ($obj, $model, $category) use($field,$aid) {
                    $obj->where(['id'=>$aid])->inc($field)->save();
                });
            } else {
                $tablename = Db::name('model')->where(['id'=>$model])->value('tablename');
                Db::name($tablename)->where(['id'=>$aid])->inc($field)->update();
            }
        }

        $base = public_path();
        $path = str_replace('\\','/', $base.$info['path']);
        $path = str_replace('//','/', $path);
        $path = realpath($path);
        if (!file_exists($path)) {
            $this->error(lang('File does not exist'));
        }

        // 禁止下载脚本文件
        $sExt = explode(',',config('cms.script_ext'));
        $info = pathinfo($path);
        if (strpos($path, $base) !== 0 || in_array($info['extension'], $sExt)) {
            $this->error(lang('File does not exist'));
        } else {
            return download($path,md5(time()));
        }
    }
}
