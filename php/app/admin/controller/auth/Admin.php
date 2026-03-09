<?php
// +----------------------------------------------------------------------
// | HkCms 后台用户管理
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\controller\auth;

use app\admin\controller\BaseController;
use app\admin\model\auth\AuthGroup;
use app\admin\model\auth\AuthGroupAccess;
use libs\Tree;
use think\facade\Db;

class Admin extends BaseController
{
    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [
        'login',
        'auth'=>['except'=>'getGroup']
    ];

    /**
     * 用户模型
     * @var \app\admin\model\auth\Admin
     */
    protected $model;

    /**
     * 当前用户拥有的所有的下级
     * @var
     */
    private $userChildrenId;

    protected $enableValidate = true;

    /**
     * 初始化操作
     */
    public function initialize()
    {
        parent::initialize();

        $this->model = new \app\admin\model\auth\Admin();

        // 获取拥有的userid
        $this->userChildrenId = $this->user->getChildrenUserIds(false);
        $this->view->assign('allowUserId', $this->userChildrenId);
    }

    public function index()
    {
        if ($this->request->isAjax()) {
            if ($this->request->param('searchTable')) {
                return $this->selectPage(); // 判断请求。如果是动态下拉组件请求，则交接给selectPage方法
            }

            list($map, $limit, $offset, $order, $sort) = $this->buildparams();
            $map[] = ['id', 'in', $this->user->getChildrenUserIds()];
            $data = $this->model->where($map)->order($sort, $order)->limit($offset, $limit)->select()->toArray();
            $total = $this->model->where($map)->order($sort, $order)->count();

            foreach ($data as &$value) {
                $value['group_names'] = $this->user->getGroupField($value['id']);
            }
            return json(['total' => $total, 'rows' => $data]);
        }
        return $this->view->fetch();
    }

    /**
     * 数据添加
     * @return mixed|string|void
     * @throws \Exception
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            validate('\\app\\admin\\validate\\auth\\Admin')->check($params);

            $accessGroup = explode(',', $params['group_id']);

            // 密码处理
            $params['salt'] = get_random_str();
            $params['password'] = app('user')->hashPassword($params['password'], $params['salt']);

            $bl = $this->model->save($params);
            if ($bl === false) {
                $this->error(__('Operation failed'));
            }

            $addAccess = [];
            $userId = $this->model->id;
            foreach ($accessGroup as $key => $value) {
                $addAccess[] = ['group_id' => $value, 'admin_id' => $userId];
            }
            (new AuthGroupAccess)->saveAll($addAccess);
            $this->success();
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
        if (!in_array($id, $this->userChildrenId)) {
            $this->error(__('No permission'));
        }

        if ($this->request->isPost()) {
            // 提交
            $params = $this->request->post("row/a");
            validate('\\app\\admin\\validate\\auth\\Admin')->scene('edit')->check($params);

            // 密码处理
            if (!empty($params['password'])) {
                $params['salt'] = get_random_str();
                $params['password'] = app('user')->hashPassword($params['password'], $params['salt']);
            } else {
                unset($params['password']);
            }

            $accessGroup = explode(',', $params['group_id']);
            foreach ($accessGroup as $key => $value) {
                $addAccess[] = ['group_id' => $value, 'admin_id' => 1];
            }
            Db::startTrans();
            try {
                $row->save($params);

                $addAccess = [];
                $userId = $row->id;
                foreach ($accessGroup as $key => $value) {
                    $addAccess[] = ['group_id' => $value, 'admin_id' => $userId, 'create_time' => time(), 'update_time' => time()];
                }
                AuthGroupAccess::where(['admin_id' => $userId])->delete();
                (new AuthGroupAccess)->insertAll($addAccess);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            $this->success();
        }

        $row->group_id = implode(',', AuthGroupAccess::where(['admin_id' => $row->id])->column('group_id'));
        return $this->view->fetch('', compact('row'));
    }

    /**
     * 删除
     * @param string $ids
     * @throws \think\db\exception\DbException
     */
    public function del($ids = '')
    {
        if ($ids) {
            $idsArr = explode(',', $ids);
            $idsArr = array_intersect($this->userChildrenId, $idsArr);
            parent::del(implode(',', $idsArr));
        }
        $this->error(__('Parameter %s can not be empty', ['ids']));
    }

    /**
     * 批量修改
     */
    public function batches()
    {
        $data = $this->request->only(['ids' => '', 'field' => '', 'value' => '', 'params' => '']);
        if (empty($data['ids']) || empty($data['params'])) {
            $this->error(__('Parameter %s can not be empty',['']));
        }
        $idsArr = explode(',', $data['ids']);
        $data['ids'] = array_intersect($this->userChildrenId, $idsArr);
        $this->postData = $data;

        parent::batches();
    }

    /**
     * 获取当前用户拥有的角色组
     * @return \think\response\Json
     */
    public function getGroup()
    {
        // 获取拥有的角色组ID
        $userGroup = $this->user->getUserGroupId();

        $searchValue = $this->request->param('searchValue');
        if (!empty($searchValue)) {
            $userGroup = array_intersect($userGroup,explode(',',$searchValue));
        }

        $data = AuthGroup::whereIn('id',$userGroup)->where(['status'=>'normal'])->select()->toArray();
        if (empty($data)) {
            return json(['total'=>0,'rows'=>[]]);
        }
        $arr = Tree::instance()->getTreeList(Tree::instance()->init($data)->getTreeArray($data[0]['parent_id']),'name');
        return json(['total'=>count($arr),'rows'=>$arr]);
    }
}