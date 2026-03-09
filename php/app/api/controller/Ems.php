<?php
// +----------------------------------------------------------------------
// | HkCms 邮箱验证码接口
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\api\controller;

use libs\Email;
use think\Exception;
use think\exception\ValidateException;
use think\facade\Db;

class Ems extends BaseController
{
    /**
     * 邮箱验证码模型
     * @var \app\common\model\Ems
     */
    protected $model;

    public function initialize()
    {
        parent::initialize();

        $this->model = new \app\common\model\Ems;
    }

    /**
     * 发送邮箱验证码
     */
    public function send()
    {
        if (site('mail_on')!=1) {
            $this->error(lang('Mailbox is closed'));
        }

        $data = $this->request->only(['email', 'event']);
        try {
            $this->validate($data,['email|'.lang('Email')=>'require|email','event|'.lang('Event')=>'require|alphaDash']);
        } catch (ValidateException $exception) {
            $this->error($exception->getError());
        }

        // 查询是否短时间内有发送过验证码
        $create_time = $this->model->where(['email'=>$data['email'],'event'=>$data['event']])->order('create_time','desc')->value('create_time');
        if ($create_time && time() - $create_time < 60) {
            $this->error(lang('Send frequently.'));
        }

        // 获取用信息
        $user = Db::name('user')->where(['email'=>$data['email']])->find();
        if ('reset_pwd'==$data['event'] && empty($user)) { // 重置密码
            $this->error(lang('Email does not exist'));
        } else if ('register'==$data['event'] && !empty($user)) { // 用户注册
            $this->error(lang('Email already exists'));
        } else if ('change_email'==$data['event'] && !empty($user)) { // 绑定新邮箱
            $this->error(lang('Email has been used'));
        }

        $random = get_random_str();
        $html = $this->app->view->fetch('/email',['random'=>$random]);

        Db::startTrans();
        $email = Email::instance();
        try {
            $bl = \app\common\model\Ems::insert([
                'event'=>$data['event'],
                'email'=>$data['email'],
                'code'=>$random,
                'ip'=>$this->request->ip(),
                'create_time'=>time(),
            ]);

            if ($bl) {
                $bl = $email->email($data['email'])->subject('申请邮箱验证码')->message($html)->send();
                if (!$bl) {
                    throw new Exception($email->getError());
                }
            }

            Db::commit();
        } catch (Exception $exception) {
            Db::rollback();
            $this->error($exception->getMessage());
        }

        $this->success(lang('Operation succeeded'));
    }

    /**
     * 验证
     */
    public function check()
    {
        $data = $this->request->only(['email', 'event', 'code']);

        try {
            $this->validate($data,['email|'.lang('Email')=>'require|email','event|'.lang('Event')=>'require|alphaDash','code|'.lang('Verification Code')=>'require|alphaDash']);
        } catch (ValidateException $exception) {
            $this->error($exception->getError());
        }

        // 限制事件类型，防止频繁调用。
        // if (!in_array($data['event'], ['register','change_email','change_pwd','reset_pwd','default'])) {
        //     $this->error(lang('Unsupported events.'));
        // }

        $result = Email::instance()->check($data['email'], $data['event'], $data['code']);
        if ($result) {
            $this->success(__('Operation succeeded'));
        } else {
            $this->error(lang('Incorrect verification code'));
        }
    }
}