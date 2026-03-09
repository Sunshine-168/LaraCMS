<?php
// +----------------------------------------------------------------------
// | HkCms
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\admin\controller\user;

use app\admin\controller\BaseController;
use app\admin\model\user\UserRule;
use libs\Tree;

class Rule extends BaseController
{
    protected $model;

    protected $enableValidate = true;

    protected $data = true;

    /**
     * 允许批量修改的字段
     * @var array
     */
    protected $allowFields = ['status','type','weigh'];

    public function initialize()
    {
        parent::initialize();

        $this->model = new UserRule();

        $data = UserRule::order(['weigh'=>'desc','id'=>'asc'])->select()->append(['title_lan'])->toArray();
        $arr = Tree::instance()->getTreeList(Tree::instance()->init($data)->getTreeArray(0),'title_lan');
        $this->data = $arr;
        $parent_id = [0=>lang('As a first-level menu')];
        foreach ($arr as $key=>$value) {
            $parent_id[$value['id']] = $value['title_lan'];
        }
        $this->view->assign('option', $parent_id);
    }

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

    public function del($ids = '')
    {
        if ($ids) {
            $list = $this->model->where('id','in',$ids)->select();
            if ($list->isEmpty()) {
                $this->error(lang('Record does not exist'));
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
            $this->error(lang('%s not null',['ids']));
        }
    }
}