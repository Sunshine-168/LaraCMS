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
use app\admin\model\user\UserGroupAccess;
use libs\Tree;
use think\facade\Db;

class User extends BaseController
{
    /**
     * 用户模型
     * @var \app\admin\model\user\User
     */
    protected $model;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [
        'login',
        'auth'=>['except'=>'getGroup']
    ];

    /**
     * 是否开启Validate验证
     * @var bool
     */
    protected $enableValidate = true;

    public function initialize()
    {
        parent::initialize();

        $this->model = new \app\admin\model\user\User;
    }

    public function index()
    {
        if ($this->request->isAjax()) {
            if ($this->request->param('searchTable')) {
                return $this->selectPage(); // 判断请求。如果是动态下拉组件请求，则交接给selectPage方法
            }

            list($map, $limit, $offset, $order, $sort) = $this->buildparams();

            $data = $this->model->where($map)->order($sort, $order)->limit($offset, $limit)->select()->toArray();
            $total = $this->model->where($map)->order($sort, $order)->count();
            foreach ($data as &$value) {
                $value['group_names'] = \app\index\library\User::instance()->getGroupField($value['id']);
            }
            return json(['total' => $total, 'rows' => $data]);
        }
        return $this->view->fetch();
    }

    public function edit($id = null)
    {
        $row = $this->model->find($id);
        if (!$row) {
            $this->error(lang('Record does not exist'));
        }

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            validate('\\app\\admin\\validate\\user\\User')->scene('edit')->check($params);
            // 密码处理
            if (!empty($params['password'])) {
                $params['salt'] = get_random_str(10);
                $params['password'] = app('user')->hashPassword($params['password'], $params['salt']);
            } else {
                unset($params['password']);
            }
            // 会员分组
            if (!empty($params['group_id'])) {
                $accessGroup = explode(',', $params['group_id']);
                foreach ($accessGroup as $key => $value) {
                    $addAccess[] = ['group_id' => $value, 'user_id' => 1];
                }
            }
            $params['birthday'] = empty($params['birthday']) ? null : $params['birthday'];
            Db::startTrans();
            try {
                $bl = $row->save($params);
                if ($bl === false) {
                    Db::rollback();
                    $this->error(lang('Operation failed'));
                }
                if (!empty($accessGroup)) {
                    $addAccess = [];
                    $userId = $row->id;
                    foreach ($accessGroup as $key => $value) {
                        $addAccess[] = ['group_id' => $value, 'user_id' => $userId, 'create_time' => time(), 'update_time' => time()];
                    }
                    UserGroupAccess::where(['user_id' => $userId])->delete();
                    (new UserGroupAccess)->insertAll($addAccess);
                } else {
                    UserGroupAccess::where(['user_id' => $row->id])->delete();
                }

                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            $this->success();
        }
        $row = $row->getOrigin();
        $row['group_id'] = implode(',', UserGroupAccess::where(['user_id' => $row['id']])->column('group_id'));
        return $this->view->fetch('', compact('row'));
    }

    /**
     * 获取会员分组
     */
    public function getGroup()
    {
        // 第一次加载搜索
        $searchValue = $this->request->param('searchValue');
        $map = [];
        if (!empty($searchValue)) {
            $map[] = ['id','in',$searchValue];
        }

        // 获取拥有的角色组ID
        $data = UserGroup::where(['status'=>'normal'])->where($map)->select()->toArray();
        if (empty($data)) {
            return json(['total'=>0,'rows'=>[]]);
        }
        $arr = Tree::instance()->getTreeList(Tree::instance()->init($data)->getTreeArray($data[0]['parent_id']),'name');
        return json(['total'=>count($arr),'rows'=>$arr]);
    }
}