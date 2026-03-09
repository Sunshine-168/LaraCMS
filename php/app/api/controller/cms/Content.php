<?php
// +----------------------------------------------------------------------
// | 内容管理控制器
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\api\controller\cms;

use app\api\controller\BaseController;
use app\common\services\cms\ArchivesService as Service;
use think\App;

class Content extends BaseController
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
     * 列表分页
     * @return \think\response\Json
     */
    public function listPage()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->param();
        if (empty($where['model_id']) && empty($where['catid'])) {
            return $this->error(__('Parameter %s can not be empty'));
        }
        if (!empty($where['model_id'])) {
            $where['model_id'] = intval($where['model_id']);
        }
        if (!empty($where['catid']) && !is_array($where['catid']) && !is_numeric($where['catid'])) {
            $where['catid'] = explode(',', $where['catid']);
        }
        if (empty($where['status'])) {
            $where['status'] = 'normal';
        }
        if (empty($where['lang'])) {
            $where['lang'] = app()->lang->getLangset();
        }
        return $this->success($this->service->listPage($where, $page, $limit));
    }

    /**
     * 详情
     * @return \think\response\Json
     * @throws \app\common\exception\ServiceException
     */
    public function details()
    {
        $id = $this->request->param('id', '', 'intval');
        if (empty($id)) {
            return $this->error(__('Parameter %s can not be empty', ['id']));
        }
        // 内容分页
        [$page, $limit] = $this->getPage();
        return $this->success($this->service->details($id, $page));
    }
}