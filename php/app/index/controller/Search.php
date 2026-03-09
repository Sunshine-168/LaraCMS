<?php
// +----------------------------------------------------------------------
// | HkCms 搜索页
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\index\controller;

use app\admin\model\cms\Archives;
use app\admin\model\cms\Model;
use app\index\model\cms\Category;

class Search extends BaseController
{
    /**
     * 搜索结果列表
     * @return string|void
     */
    public function index()
    {
        // 搜索关键字
        $param['keyword'] = $this->request->param('keyword','', 'strip_tags,htmlspecialchars,trim');
        // 模型ID
        $param['model_id'] = $this->request->param('model_id','', 'intval');
        $param['model_id'] = $param['model_id'] ? $param['model_id'] : '';
        // 栏目ID
        $param['category_id'] = $this->request->param('category_id','', 'intval');
        // 排序字段
        $param['orderby'] = $this->request->param('orderby','', 'strip_tags,htmlspecialchars,trim');
        // 排序类型
        $param['ordermode'] = $this->request->param('ordermode','', 'strip_tags,htmlspecialchars,trim');
        $__param__ = $param;
        $param['orderby'] = $param['orderby'] ? $param['orderby'] : 'weigh';
        //$param['ordermode'] = in_array($param['ordermode'], ['asc','desc']) ? $param['ordermode'] : '';
        // 默认排序列表
        $orderList = [
            ['field'=>'weigh','ordermode'=>'asc','title'=>__('Default'),'url'=>'','currentstyle'=>''],
            ['field'=>'publish_time','ordermode'=>'desc','title'=>__('Release date'),'url'=>'','currentstyle'=>''],
            ['field'=>'views','ordermode'=>'desc','title'=>__('Pageviews'),'url'=>'','currentstyle'=>'']
        ];

        // 定位当前排序
        foreach ($orderList as $key=>$value) {
            if ($value['field']==$param['orderby']) {
                $orderList[$key]['currentstyle'] = 'active';
                $param['ordermode'] = $param['ordermode'] ? ($param['ordermode']=='asc'?'desc':'asc') : $value['ordermode'];
                $orderList[$key]['url'] = (string) url('/search/index',array_merge($param,['orderby'=>$value['field'],'ordermode'=>$param['ordermode']]));
            } else {
                $orderList[$key]['url'] = (string) url('/search/index',array_merge($param,['orderby'=>$value['field'],'ordermode'=>$value['ordermode']]));
            }
        }

        // 当前语言
        $lang = get_curlang();

        if (empty($param['keyword'])) {
            $this->error(lang('Please input keywords'));
        }

        // 初始条件
        $map = [['a.status','=','normal'],['lang','=',$lang]];
        // 栏目ID
        if ($param['category_id']) {
            //$map[] = ['category_id','=',$param['category_id']];
            if (!$param['model_id']) {
                $category = Category::where(['status'=>'normal'])->find($param['category_id']);
                $param['model_id'] = $category ? $category['model_id'] : $param['category_id'];
            }
        }
        // 模型ID
        if ($param['model_id']) {
            $map[] = ['model_id','=',$param['model_id']];
            $model = Model::where(['status'=>'normal','is_search'=>1])->find($param['model_id']);
        }

        $archives = (new Archives())->alias('a')->with(['category']);
        // 模型字段
        $modelField = "model.name,model.alias,model.tablename,model.remark,model.controller,model.type,model.allow_single,model.is_search,model.status,model.config";
        if (!empty($model)) { // 模型存在的情况，获取副表字段
            // 获取扩展字段
            $fields = \think\facade\Db::name('model_field')->where(['status'=>'normal','model_id'=>$model->id])->select()->toArray();
            $extField = [];
            foreach ($fields as $key=>$value) {
                if ($value['iscore']==0) {
                    $extField[] = $value['field_name'];
                }
            }
            $extField = implode(',',$extField);
            $archives->join($model->tablename.' x','a.id=x.id','left')->field('a.*,x.id as xid,'.$extField.','.$modelField);
        } else {
            $archives->field('a.*,'.$modelField);
        }

        $archives = $archives
            ->join('model model', 'a.model_id=model.id')
            ->where($map)
            ->where('model.status','=','normal')
            ->where('model.is_search','=',1)
            ->where(function ($query) use($param) {
                $q = explode(' ', $param['keyword']);
                foreach ($q as $key=>$value) {
                    if (empty($value)) {
                        continue;
                    }
                    $query->whereOr('title','like', "%$value%");
                }
            })
            ->where(function ($query) use($param) {
                if (!empty($param['category_id'])) {
                    $query->where(['category_id'=>$param['category_id']])->whereOr('find_in_set("'.$param['category_id'].'",category_ids)');
                }
            })
            ->order($param['orderby'],$param['ordermode'])
            ->append(['publish_time_text','fullurl'])
            ->paginate([
                'path'=>'/search/index',
                'list_rows'=> 10,
                'var_page' => 'page',
                'query' => $param
            ], false);

        $array = $archives->toArray()['data'];
        $page = $archives;

        $modelInfo = Model::where(['status'=>'normal','is_search'=>1])->order('controller')->select()->toArray();
        foreach ($modelInfo as $key=>$value) {
            $modelInfo[$key]['url'] = (string) url('/search/index',['model_id'=>$value['id'],'keyword'=>$__param__['keyword']??'']);
            $modelInfo[$key]['currentstyle'] = $__param__['model_id']==$value['id'] ? 'active' : '';
        }

        $this->view->assign('seo_title', __('Search for `%s` related content',[$param['keyword']]).' - '.site('title'));
        $this->view->assign('seo_keywords', '');
        $this->view->assign('seo_desc', '');
        return $this->view->fetch('index/search', [
            '__SDATA__'=>$array,
            '__SPAGE__'=>$page,
            '__modelInfo__'=>$modelInfo,
            '__param__'=>$__param__,
            '__order__'=>$orderList,
        ]);
    }
}