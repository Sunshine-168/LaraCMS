<?php
// +----------------------------------------------------------------------
// | HkCms
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\index\controller;

class Tags extends BaseController
{
    public function initialize()
    {
        parent::initialize();

        // 非开发者模式下,屏蔽错误
        if (!$this->app->isDebug()) {
            // 屏蔽掉notice错误、warning
            error_reporting(E_ERROR & ~E_WARNING & ~E_NOTICE);
        }
    }

    /**
     * 标签首页
     * @return string|void
     */
    public function index()
    {
        // 获取配置
        $config = site();

        $this->view->assign('seo_title', $config['tags_seo_title']);
        $this->view->assign('seo_keywords', $config['tags_seo_keyword']);
        $this->view->assign('seo_desc', $config['tags_seo_desc']);
        return $this->view->fetch('tags/'.$config['tags_index']);
    }

    /**
     * 标签列表页
     * @return string|void
     */
    public function lists()
    {
        $config = site();

        $title = $this->request->param('tag','', 'strip_tags');
        if (empty($title)) {
            return $this->view->fetch('tags/'.$config['tags_list']);
        }
        if (is_numeric($title)) {
            $info = \app\admin\model\Tags::where(['id'=>$title])->find();
        } else {
            $info = \app\admin\model\Tags::where(['title'=>$title])->find();
        }
        if (empty($info)) {
            $this->error(lang('Non existent label'));
        }
        $info->inc('views')->save();

        $info = $info->toArray();

        $this->view->assign('Tags', $info);
        $this->view->assign('seo_title', empty($info['seo_title'])?$info['title']:$info['seo_title']);
        $this->view->assign('seo_keywords', $info['seo_keywords']);
        $this->view->assign('seo_desc', $info['seo_description']);
        return $this->view->fetch('tags/'.$config['tags_list']);
    }
}