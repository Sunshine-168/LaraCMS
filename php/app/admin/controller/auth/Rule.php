<?php
// +----------------------------------------------------------------------
// | HkCms 权限管理
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: HkCms team <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\controller\auth;

use app\admin\controller\BaseController;
use app\admin\model\auth\AuthRule;
use libs\Tree;

class Rule extends BaseController
{
    /**
     * @var AuthRule
     */
    protected $model;

    /**
     * 开启验证
     * @var bool
     */
    protected $enableValidate = true;

    /**
     * 允许批量修改的字段
     * @var array
     */
    protected $allowFields = ['status','type','weigh','is_nav'];

    private $data;

    public function initialize()
    {
        parent::initialize();

        $this->model = new AuthRule();

        if (!$this->user->hasSuperAdmin()) {
            $data = AuthRule::whereIn('id', $this->user->getRuleIds($this->user->id,-1))->order(['weigh'=>'desc','id'=>'asc'])->select()->append(['title_lan'])->toArray();
        } else {
            $data = AuthRule::order(['weigh'=>'asc','id'=>'asc'])->select()->append(['title_lan'])->toArray();
        }

        $arr = Tree::instance()->getTreeList(Tree::instance()->init($data)->getTreeArray(0),'title_lan');
        $this->data = $arr;
        $parent_id = [0=>__('As a first-level menu')];
        foreach ($arr as $key=>$value) {
            $parent_id[$value['id']] = $value['title_lan'];
        }
        $this->view->assign('option', $parent_id);
    }

    /**
     * 查看
     * @return string|\think\response\Json|void
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            return json(['total'=>count($this->data),'rows'=>$this->data]);
        }
        return $this->view->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            parent::add();
        }
        $this->view->assign('parent_id', $this->request->param('parent_id','0','intval'));
        return $this->view->fetch();
    }

    /**
     * 删除
     * @param string $ids
     */
    public function del($ids = '')
    {
        if ($ids) {
            $list = $this->model->where('id','in',$ids)->select();
            if ($list->isEmpty()) {
                $this->error(__('No results were found'));
            }
            $arr = [];
            foreach ($list as $key=>$value) {
                $arr[$value->id] = $value->id;
                $temp = Tree::instance()->init($this->data)->getChildIds($value->id);
                if ($temp) {
                    $arr = $temp + $arr;
                }
            }
            parent::del(implode(',',$arr));
        } else {
            $this->error(__('Parameter %s can not be empty',['ids']));
        }
    }
}