<?php
// +----------------------------------------------------------------------
// | 栏目管理控制器
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\api\controller\cms;

use app\api\controller\BaseController;
use think\App;
use app\common\services\cms\CategoryService as Service;
use think\response\Json;

class Category extends BaseController
{
    /**
     * @var Service
     */
    protected $service;

    /**
     * 初始化
     * @param App $app
     * @param Service $userService
     */
    public function __construct(App $app, Service $userService)
    {
        $this->service = $userService;
        parent::__construct($app);
    }

    /**
     * 获取列表
     * @return Json
     */
    public function listPage()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->param();
        if (empty($where['status'])) {
            $where['status'] = 'normal';
        }
        if (empty($where['lang'])) {
            $where['lang'] = app()->lang->getLangset();
        }
        return $this->success($this->service->listPage($where, $page, $limit));
    }

    /**
     * 获取详情
     * @return Json
     */
    public function detail()
    {
        $where = $this->request->param(['id', 'lang', 'name']);
        if (empty($where['id']) && empty($where['name'])) {
            return $this->error(__('Parameter %s can not be empty', ['']));
        }
        if (empty($where['lang'])) {
            $where['lang'] = app()->lang->getLangset();
        }
        return $this->success($this->service->detail($where));
    }

    /**
     * 获取栏目树形结构
     * @return Json
     */
    public function tree()
    {
        $where = $this->request->param(['lang']);
        if (empty($where['lang'])) {
            $where['lang'] = app()->lang->getLangset();
        }
        return $this->success($this->service->tree($where));
    }
}