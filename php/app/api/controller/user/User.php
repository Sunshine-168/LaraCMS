<?php
// +----------------------------------------------------------------------
// | 会员API
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace app\api\controller\user;

use app\api\controller\BaseController;
use app\common\services\user\UserService;
use think\App;

class User extends BaseController
{
    /**
     * @var UserService
     */
    protected $service;

    public function __construct(App $app, UserService $userService)
    {
        $this->service = $userService;
        parent::__construct($app);
    }

    /**
     * 获取用户信息
     * @return \think\response\Json
     */
    public function details()
    {
        return $this->success($this->service->details($this->request->userId()));
    }
}