<?php
// +----------------------------------------------------------------------
// | HkCms 角色组管理
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: HkCms team <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\controller\auth;

use app\admin\controller\BaseController;
use app\admin\model\auth\AuthGroup;
use app\admin\model\auth\AuthRule;
use libs\Tree;
use think\db\exception\DbException;
use think\exception\ValidateException;
use think\facade\Db;

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
     * 角色模型
     * @var \app\admin\model\auth\AuthGroup
     */
    protected $model;

    /**
     * 开启验证
     * @var bool
     */
    protected $enableValidate = true;

    /**
     * 当前用户拥有的角色组数据
     * @var
     */
    private $data;

    /**
     * 拥有的角色ID
     * @var
     */
    private $userGroup;

    public function initialize()
    {
        parent::initialize();

        $this->model = new AuthGroup();

        // 获取拥有的角色组ID
        $this->userGroup = $this->user->getUserGroupId(-1);

        $searchValue = $this->request->param('searchValue');
        if (!empty($searchValue)) {
            $userGroup = array_intersect($this->userGroup,explode(',',$searchValue));
        } else {
            $userGroup = $this->userGroup;
        }

        $data = AuthGroup::whereIn('id',$userGroup)->select()->toArray();
        $arr = Tree::instance()->getTreeList(Tree::instance()->init($data)->getTreeArray($data[0]['parent_id']),'name');
        $this->data = $arr;
        $parent_id = [];
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
     * 添加
     * @return mixed|string|void
     */
    public function add()
    {
        if ($this->request->isPost()) {

            $data = $this->request->post("row/a");
            if (!in_array($data['parent_id'], $this->userGroup)) {
                $this->error(__('No results were found'));
            }

            // 规则校验
            if (empty($data['rules'])) {
                $this->error(__('Permission rules cannot be empty'));
            }
            $data['rules'] = $this->user->getGroupAuthIn($data['parent_id'], $data['rules']);

            $params = $this->preExcludeFields($data);

            $result = false;
            Db::startTrans();
            try {
                // 是否开启验证器验证，默认无验证。
                if ($this->enableValidate) {
                    $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                    $validate = validate($name);
                    if ($this->enableScene) { // 开启场景验证
                        $validate = $validate->scene('add');
                    }
                    $validate->check($params);
                }
                $result = $this->model->save($params);

                if (!empty($params['category'])) {
                    // 处理栏目
                    $categoryIdArr = explode(',', $params['category']);
                    $data = [];
                    foreach ($categoryIdArr as $key=>$value) {
                        $temp['category_id'] = $value;
                        $temp['auth_group_id'] = $this->model->getAttr('id');
                        $data[] = $temp;
                    }
                    Db::name('category_priv')->insertAll($data);
                }

                Db::commit();
            } catch (ValidateException $e) {
                Db::rollback();
                $this->error($e->getError());
            } catch (DbException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }

            if ($result !== false) {
                $this->success();
            } else {
                $this->error(__('No rows added'));
            }
        }
        return $this->view->fetch();
    }

    /**
     * 修改
     * @param null $id
     * @return mixed|string|void
     */
    public function edit($id = null)
    {
        $row = $this->model->find($id);
        if (!$row) {
            $this->error(__('No results were found'));
        }
        if ($row->rules=='*') {
            $this->error(__('Super administrator cannot operate'));
        }

        if ($this->request->isPost()) {

            $data = $this->request->post("row/a");

            // 父级校验
            if (!in_array($data['parent_id'], $this->userGroup)) {
                $this->error(__('No results were found'));
            }
            // 获取当前角色组的下级，判断选中的角色数据是否是他的下级。
            $sonGroup = $this->user->getChildGroup($row['id']);
            if (in_array($data['parent_id'], $sonGroup)) {
                $this->error(__('Cannot choose oneself or one\'s own subordinate as the parent'));
            }

            // 规则校验
            if (empty($data['rules'])) {
                $this->error(__('Permission rules cannot be empty'));
            }

            $data['rules'] = $this->user->getGroupAuthIn($data['parent_id'], $data['rules']);
            $params = $this->preExcludeFields($data);

            $result = false;
            Db::startTrans();
            try {
                // 是否开启验证器验证，默认无验证。
                if ($this->enableValidate) {
                    $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                    $validate = validate($name);
                    if ($this->enableScene) { // 开启场景验证
                        $validate = $validate->scene('edit');
                    }
                    $validate->check($params);
                }
                $result = $row->save($params);

                // 栏目处理，是否已经有添加
                if (empty($params['category'])) {
                    Db::name('category_priv')->where(['auth_group_id'=>$row['id']])->delete();
                }

                $res = Db::name('category_priv')->where(['auth_group_id'=>$row['id']])->column('category_id');
                $res = implode(',', $res);
                if ($res != $params['category']) {
                    Db::name('category_priv')->where(['auth_group_id'=>$row['id']])->delete();

                    $categoryIdArr = explode(',', $params['category']);
                    $data = [];
                    foreach ($categoryIdArr as $key=>$value) {
                        $temp['category_id'] = $value;
                        $temp['auth_group_id'] = $row['id'];
                        $data[] = $temp;
                    }
                    Db::name('category_priv')->insertAll($data);
                }

                Db::commit();
            } catch (ValidateException $e) {
                Db::rollback();
                $this->error($e->getError());
            } catch (DbException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }

            if ($result !== false) {
                $this->success();
            } else {
                $this->error(__('No changes'));
            }

            parent::edit($id);
        }

        return $this->view->fetch('', compact('row'));
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
                if ($value->rules=='*') {
                    $this->error(__('Super administrator cannot operate'));
                }
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

    /**
     * 获取对应组的权限
     */
    public function groupRule()
    {
        if ($this->request->isAjax()) {
            $id = $this->request->param('id');
            $current_id = $this->request->param('current_id');
            if (!is_numeric($id)) {
                $this->error(__('Illegal request'));
            }
            if (!in_array($id, $this->userGroup)) {
                $this->error(__('No results were found'));
            }
            $info = $this->model->where(['status'=>'normal'])->find($id);
            if (!$info) {
                $this->error(__('No results were found'));
            }

            // 设置已选中的项
            $selectIdArr = [];
            if ($current_id) {
                $currentInfo = $this->model->find($current_id);
                if ($currentInfo) {
                    // 获取当前角色组的下级，判断选中的角色数据是否是他的下级。
                    $sonGroup = $this->user->getChildGroup($current_id);
                    if (in_array($id, $sonGroup)) {
                        $this->error(__('Cannot choose oneself or one\'s own subordinate as the parent'));
                    }
                    $selectIdArr = explode(',', $currentInfo['rules']);
                }
            }

            // 获取栏目信息
            $category = $this->user->getUserCategory();
            foreach ($category as $key=>&$value) {
                if ($value['type'] == 'list') {
                    $value['icon'] = 'fas fa-file';
                } else if ($value['type'] == 'link') {
                    $value['icon'] = 'fas fa-link';
                    $value['popup'] = '1';
                } else {
                    $value['icon'] = 'fas fa-folder';
                }
                // if ($value['model_id']==0) { 作废，不限制不是模型的权限设置
                    // $sonIds = Db::name('category')->where(['parent_id'=>$value['id'],'status'=>'normal'])->where('model_id','<>',0)->find();
                    // if (empty($sonIds)) {
                    //     unset($category[$key]);
                    // }
                // }
            }
            $categoryIdArr = [];
            if ($current_id) {
                $categoryIdArr = Db::name('category_priv')->where(['auth_group_id'=>$current_id])->column('category_id');
            }
            $cate = Tree::instance()->init($category)->getJsTree(0,'title', $categoryIdArr);

            $rules = explode(',', $info['rules']);
            if (in_array('*', $rules) && $info['parent_id']===0) {
                $auth = AuthRule::where(['status'=>'normal'])->order('weigh','asc')->select()->append(['title_lan'])->toArray();
                $auth = Tree::instance()->init($auth)->getJsTree(0,'title_lan',$selectIdArr);
                $this->success('','',['auth'=>$auth,'cate'=>$cate]);
            } else {
                $auth = AuthRule::whereIn('id',$info['rules'])->where(['status'=>'normal'])->order('weigh','asc')->select()->append(['title_lan'])->toArray();
                $auth = Tree::instance()->init($auth)->getJsTree(0,'title_lan',$selectIdArr);
                $this->success('','',['auth'=>$auth,'cate'=>$cate]);
            }
        }
    }
}