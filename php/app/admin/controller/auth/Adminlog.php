<?php
// +----------------------------------------------------------------------
// | HkCms 操作日志管理
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\controller\auth;

use app\admin\controller\BaseController;

class Adminlog extends BaseController
{
    /**
     * 管理员日志模型
     * @var \app\admin\model\auth\AdminLog
     */
    protected $model;

    /**
     * 初始化操作
     */
    public function initialize()
    {
        parent::initialize();

        $this->model = new \app\admin\model\auth\AdminLog();
    }

    public function index()
    {
        if ($this->request->isAjax()) {
            list($map, $limit, $offset, $order, $sort) = $this->buildparams();

            $data = $this->model->where($map)->order($sort, $order)->limit($offset, $limit)->select()->toArray();
            $total = $this->model->where($map)->order($sort, $order)->count();
            foreach ($data as $key=>$value) {
                $info = \think\facade\Db::name('admin')->where(['id'=>$value['admin_id']])->field('nickname')->cache()->find();
                $data[$key]['nickname'] = $info['nickname']??'-';
            }
            return json(['total'=>$total,'rows'=>$data]);
        }

        return $this->view->fetch();
    }
}