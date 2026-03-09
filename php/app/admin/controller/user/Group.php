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
use app\admin\model\user\UserGroup;
use app\admin\model\user\UserRule;
use libs\Tree;

class Group extends BaseController
{
    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [
        'login',
        'auth'=>['except'=>['groupRule']]
    ];

    /**
     * 是否开启Validate验证
     * @var bool
     */
    protected $enableValidate = true;

    /**
     * 角色模型
     * @var UserGroup
     */
    protected $model;

    protected $data;

    public function initialize()
    {
        parent::initialize();

        $this->model = new UserGroup();

        $data = UserGroup::select()->toArray();
        $arr = Tree::instance()->getTreeList(Tree::instance()->init($data)->getTreeArray(0),'name');
        $this->data = $arr;
        $parent_id = [0=>lang('Nothing')];
        foreach ($arr as $key=>$value) {
            $parent_id[$value['id']] = $value['name'];
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

    /**
     * 获取对应组的权限
     */
    public function groupRule()
    {
        if ($this->request->isAjax()) {
            $id = $this->request->param('id');
            $current_id = $this->request->param('current_id');
            if (!is_numeric($id)) {
                $this->error(lang('Illegal format'));
            }

            // 设置已选中的项
            $selectIdArr = [];
            if ($current_id) {
                $currentInfo = $this->model->find($current_id);
                if ($currentInfo) {
                    // 获取当前角色组的下级，判断选中的角色数据是否是他的下级。
                    $sonGroup = \app\index\library\User::instance()->getChildGroup($current_id);
                    if (in_array($id, $sonGroup)) {
                        $this->error(lang('Cannot choose oneself or one\'s own subordinate as the parent'));
                    }
                    $selectIdArr = explode(',', $currentInfo['rules']);
                }
            }

            // 一级
            if ($id>0) {
                $info = $this->model->where(['status'=>'normal'])->find($id);
                if (!$info) {
                    $this->error(lang('Record does not exist'));
                }
                $auth = UserRule::whereIn('id',$info['rules'])->where(['status'=>'normal'])->order('weigh','desc')->select()->append(['title_lan'])->toArray();
                $auth = Tree::instance()->init($auth)->getJsTree(0,'title_lan',$selectIdArr);
                $this->success('','',['auth'=>$auth]);

            } else {
                $auth = UserRule::where(['status'=>'normal'])->order('weigh','desc')->select()->append(['title_lan'])->toArray();
                $auth = Tree::instance()->init($auth)->getJsTree(0,'title_lan',$selectIdArr);
                $this->success('','',['auth'=>$auth]);
            }
        }
    }
}