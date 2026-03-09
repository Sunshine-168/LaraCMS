<?php
// +----------------------------------------------------------------------
// | HkCms 快速导航
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: HkCms team <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\controller\auth;

use app\admin\controller\BaseController;
use think\facade\Db;

class AdminPanel extends BaseController
{
    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [
        'login'
    ];

    /**
     * 用户模型
     * @var \app\admin\model\auth\AdminPanel
     */
    protected $model;

    protected $enableValidate = true;

    /**
     * 初始化操作
     */
    public function initialize()
    {
        parent::initialize();

        $this->model = new \app\admin\model\auth\AdminPanel();
    }

    /**
     * 数据添加
     * @return mixed|string|void
     * @throws \Exception
     */
    public function add()
    {
        $admin_id = session('User.id');

        if ($this->request->isPost()) {
            $auth_rule_id = $this->request->post('auth_rule_id');
            $data = [];
            if (!empty($auth_rule_id)) {
                foreach ($auth_rule_id as $v) {
                    $data[] = [
                        'admin_id' => $admin_id,
                        'auth_rule_id' => $v
                    ];
                }
            }
            $this->model->where('admin_id',$admin_id)->delete();
            if (!empty($data)) {
                $bl = $this->model->insertAll($data);
                if ($bl===false) {
                    $this->error(__('Operation failed'));
                }
            }
            $this->success();
        }

        if ($this->user->hasSuperAdmin()) {
            $rule_list = Db::name('auth_rule')->where(['status'=>'normal','is_nav'=>1])->column('title','id');
        } else {
            $rId = $this->user->getUserRules();
            $ids = [];
            foreach ($rId as $key=>$value) {
                $ids[] = $value['id'];
            }
            $rule_list = Db::name('auth_rule')->where(['status'=>'normal','is_nav'=>1])->whereIn('id', $ids)->column('title','id');
        }

        $admin_rule_list = $this->model->where('admin_id',$admin_id)->column('auth_rule_id');
        $this->view->assign([
            'rule_list' => $rule_list,
            'admin_rule_list' => $admin_rule_list
        ]);
        return $this->view->fetch();
    }
}