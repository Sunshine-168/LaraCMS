<?php
// +----------------------------------------------------------------------
// | 站点模块控制器
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
namespace app\api\controller\cms;

use app\api\controller\BaseController;
use think\App;
use app\common\services\cms\RecommendService as Service;

class Recommend extends BaseController
{
    /**
     * @var Service
     */
    protected $service;

    /**
     * 初始化
     * @param App $app
     * @param Service $service
     */
    public function __construct(App $app, Service $service)
    {
        $this->service = $service;
        parent::__construct($app);
    }

    /**
     * 获取站点模块内容数据
     * @return \think\response\Json
     */
    public function contentList()
    {
        $where = $this->request->param(['name', 'itemid', 'num']);
        if (empty($where['name'])) {
            return $this->error(__('Parameter %s can not be empty', ['name']));
        }
        $where['lang'] = $this->app->lang->getLangset();
        $data = $this->service->contentList($where);
        return $this->success($data);
    }
}