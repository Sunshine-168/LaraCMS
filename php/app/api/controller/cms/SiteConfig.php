<?php
// +----------------------------------------------------------------------
// | 站点配置控制器
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
namespace app\api\controller\cms;

use app\api\controller\BaseController;
use think\App;
use app\common\services\config\ConfigService as Service;

class SiteConfig extends BaseController
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
     * 获取所有配置
     * @return \think\response\Json
     */
    public function getAll()
    {
        $lang = $this->request->param('lang', '');
        if (empty($lang)) {
            $lang = app()->lang->getLangset();
        }
        return $this->success('获取成功', $this->service->getAllByApi($lang));
    }
}