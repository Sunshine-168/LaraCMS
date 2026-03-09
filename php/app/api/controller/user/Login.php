<?php
// +----------------------------------------------------------------------
// | 登录API
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace app\api\controller\user;

use app\api\controller\BaseController;
use app\common\services\user\TokenService;
use app\common\services\user\UserService;
use think\App;
use think\facade\Cache;

class Login extends BaseController
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
     * 账号密码登录
     * @return \think\response\Json
     */
    public function login()
    {
        $post = $this->request->only(['username'=>'', 'password'=>''], 'post');
        if (empty($post['username'])) {
            return $this->error(__('Please input account'));
        }
        if (empty($post['password'])) {
            return $this->error(__('Please input password'));
        }
        if (Cache::get(md5('api_login_fail_'.$post['username']))>site('login_fail_count')) {
            return $this->error(__('Too many incorrect accounts or passwords. Please try again later'));
        }
        $user = $this->service->getOne(['username|email|mobile'=>$post['username']]);
        if (empty($user)) {
            $this->service->loginFailCheck($post['username'], (int)site('login_fail_count'));
        }
        if ($user->password!=$this->service->hashPassword($post['password'], $user->salt)) {
            $this->service->loginFailCheck($post['username'], (int)site('login_fail_count'));
        }
        if ($user['status']!='normal') {
            return $this->error(__('The account has been disabled'));
        }
        // 获取客户端
        $client = $this->request->getFormClient();
        // 创建token
        $tokenInfo = (new TokenService())->create($user->id, $client);
        return $this->success("操作成功", $this->service->loginAfter($user, $tokenInfo, $client));
    }
}