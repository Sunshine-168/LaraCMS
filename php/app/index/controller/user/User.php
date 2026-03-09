<?php
// +----------------------------------------------------------------------
// | HkCms 会员中心
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\index\controller\user;

use app\index\middleware\Auth;
use app\index\middleware\Login;
use app\common\library\Upload;
use app\index\controller\BaseController;
use libs\Email;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Event;
use think\facade\Session;
use think\facade\Validate;

class User extends BaseController
{
    protected $middleware = [
        Login::class=>['except'=>['login','register','resetPwd','verify','send','sendems']],
        //Auth::class=>['only'=>[]]
    ];

    /**
     * 用户服务操作类
     * @var \app\index\library\User
     */
    protected $user = null;

    public function initialize()
    {
        parent::initialize();

        $this->view->config(['view_path'=>app_path().'view'.DIRECTORY_SEPARATOR]);
        if (site('user_on')!=1) {
            $this->error(__('Member Center has closed'));
        }

        $this->user = \app\index\library\User::instance();
        $user = $this->user->getUser();
        if ($user) {
            $user['group'] = $this->user->getGroups($user['id']);
        }
        $this->view->assign('user', $user);
    }

    public function index()
    {
        return $this->view->fetch();
    }

    /**
     * 个人资料
     * @return string|void
     */
    public function profile()
    {
        if ($this->request->isPost()) {
            $row = $this->request->post('row');
            $row['__token__'] = $this->request->param('__token__');
            $validate = validate([
                'nickname|'.lang('Nickname')=>'require|length:2,30',
                'avatar|'.lang('Avatar')=>'max:255',
                'gender|'.lang('Gender')=>'in:0,1,2',
                'introduction|'.lang('Introduction')=>'max:255',
                '__token__'=>'require|token',
            ], [], false, false);
            if (!$validate->check($row)) {
                $this->error($validate->getError(),'',[],0,['__token__'=>$this->request->buildToken()]);
            }

            $u = $this->user->getModel();
            if (\app\index\model\User::where(['nickname'=>$row['nickname']])->where('id','<>', $u->id)->find()) {
                $this->error(lang('Nickname already exists'),'',[],0,['__token__'=>$this->request->buildToken()]);
            }

            $u->nickname = $row['nickname']??$u->nickname;
            $u->avatar = $row['avatar']??$u->avatar;
            $u->gender = $row['gender']??$u->gender;
            $u->introduction = $row['introduction']??$u->introduction;
            $u->save();
            $this->success(lang('Save success'));
        }
        $info = Db::name('user')->where(['id'=>$this->user->id])->find();
        $this->view->assign('profile', $info);
        return $this->view->fetch();
    }

    /**
     * 修改密码
     * @return string|void
     * @throws \Exception
     */
    public function changePwd()
    {
        if ($this->request->isPost()) {
            $row = $this->request->post('row');
            $row['__token__'] = $this->request->param('__token__');
            $validate = validate([
                'old_password|'.lang('Old password')=>'require|length:6,30',
                'password|'.lang('New password')=>'require|length:6,30',
                'cfm_password|'.lang('Confirm password')=>'require|length:6,30|confirm:password',
                '__token__'=>'require|token',
            ], [], false, false);
            if (!$validate->check($row)) {
                $this->error($validate->getError(),'',[],0,['__token__'=>$this->request->buildToken()]);
            }

            if ($this->user->changePassword($row['old_password'], $row['password'])) {
                $this->success();
            } else {
                $this->error($this->user->getError(), '', [], 0, ['__token__'=>$this->request->buildToken()]);
            }
        }
        return $this->view->fetch();
    }

    /**
     * 绑定
     * @return string|void
     * @throws \Exception
     */
    public function bind()
    {
        if ($this->request->isPost()) {
            $row = $this->request->post('row');
            if (!empty($row['mobile'])) {
                if (empty($row['code'])) {
                    $this->error(lang('Please fill in the verification code'));
                }
                if (!Validate::is($row['mobile'], 'mobile')) {
                    $this->error(lang('Phone number format is not correct'));
                }
                $info = \app\index\model\User::where(['mobile'=>$row['mobile']])->find();
                if ($info) {
                    $this->error(lang('Phone number already exists'));
                }
                $result = hook('userMobileCheck', ['mobile'=>$row['code'], 'code'=>$row['code'], 'event'=>'change_mobile'], true, true);
                if (!$result) {
                    $this->result(lang('Verification failed'), [],-1001);
                }
                \app\index\model\User::where('id', $this->user->id)->save(['mobile'=>$row['mobile']]);
                $this->success(lang('Success'));
            } else if (!empty($row['email'])) {
                if (empty($row['code'])) {
                    $this->error(lang('Please fill in the verification code'));
                }
                if (!Validate::is($row['email'], 'email')) {
                    $this->error(lang('Incorrect email address format'));
                }
                $info = \app\index\model\User::where(['email'=>$row['email']])->find();
                if ($info) {
                    $this->error(lang('Email has been used'));
                }

                $result = Email::instance()->check($row['email'],'change_email',$row['code']??'');
                if (!$result) {
                    $this->result(lang('Verification failed'), [],-1001);
                }

                \app\index\model\User::where('id', $this->user->id)->save(['email'=>$row['email']]);
                $this->success(lang('Success'));
            } else {
                $this->error(lang('Please fill in the complete'));
            }
        }
        return $this->view->fetch();
    }

    /**
     * 登录
     * @return false|mixed|string
     */
    public function login()
    {
        if ($this->request->isPost()) {
            $row = $this->request->param('row');
            $row['__token__'] = $this->request->param('__token__');

            $validate = validate([
                'username|'.lang('Username')=>'require|length:3,30',
                'password|'.lang('Password')=>'require|length:6,30',
                '__token__'=>'require|token',
            ], [], false, false);
            if (!$validate->check($row)) {
                $this->error($validate->getError(),'',[],0,['__token__'=>$this->request->buildToken()]);
            }

            // 开启验证码
            if (site('login_captcha')==1) {
                if (empty($row['code'])) {
                    $this->error(lang('Please fill in the verification code'),'',[],0,['__token__'=>$this->request->buildToken()]);
                }
                $result = captcha_check($row['code']);
                if (!$result) {
                    $this->result(lang('Verification failed'),[],-1001,'json',['__token__'=>$this->request->buildToken()]);
                }
            }

            $user = \app\index\library\User::instance();
            if ($user->login($row['username'], $row['password'])) {
                $this->success(lang('Login successful'),'',['url'=>(string)url('/user.user/index',[],false)]);
            } else {
                $this->result($user->getError(),[],-1001,'json',['__token__'=>$this->request->buildToken()]);
            }
        }
        $bl = Session::get('Member');
        if ($bl) {
            $this->success(lang('You are logged in'), (string)url('/user.user/index', [], false));
        }
        return $this->view->fetch();
    }

    /**
     * 注册
     * @return false|mixed|string
     */
    public function register()
    {
        if ($this->request->isPost()) {
            $row = $this->request->param('row');

            // 验证
            $validate = validate([
                'username|'.lang('Username')=>'require|length:3,30|chsDash',
                'password|'.lang('Password')=>'require|length:6,30',
                'cfm_password|'.lang('Confirm password')=>'require|length:6,30|confirm:password',
                'mobile|'.lang('Mobile')=>'mobile',
                'email|'.lang('Email')=>'email',
            ], [], false, false);
            if (!$validate->check($row)) {
                $this->error($validate->getError());
            }

            // 验证表单token
            $check = $this->request->checkToken('__token__', $this->request->param());
            if(false === $check) {
                $this->error(lang('Invalid token'),'',[],0,['__token__'=>$this->request->buildToken()]);
            }
            // 判断用户名是否已存在
            if (\app\index\model\User::getByUsername($row['username'])) {
                $this->error(lang('Username already exists'),'',[],0,['__token__'=>$this->request->buildToken()]);
            }
            // 验证码验证
            if (site('register_captcha')==2) { // 邮箱
                if (site('mail_on')!=1) {
                    $this->error(__('Mailbox is closed'),'',[],0,['__token__'=>$this->request->buildToken()]);
                }
                if (empty($row['email'])) {
                    $this->error(lang('Please fill in the email address'),'',[],0,['__token__'=>$this->request->buildToken()]);
                }
                if (\app\index\model\User::getByEmail($row['email'])) {
                    $this->error(lang('Email already exists'),'',[],0,['__token__'=>$this->request->buildToken()]);
                }
                if (empty($row['email_code'])) {
                    $this->error(lang('Please fill in the verification code'),'',[],0,['__token__'=>$this->request->buildToken()]);
                }
                $result = Email::instance()->check($row['email'],'register',$row['email_code']??'');
            } else if (site('register_captcha')==3) { // 手机
                if (empty($row['mobile'])) {
                    $this->error(lang('Please fill in your phone number'),'',[],0,['__token__'=>$this->request->buildToken()]);
                }
                if (\app\index\model\User::getByMobile($row['mobile'])) {
                    $this->error(lang('Phone number already exists'),'',[],0,['__token__'=>$this->request->buildToken()]);
                }
                if (empty($row['mobile_code'])) {
                    $this->error(lang('Please fill in the verification code'),'',[],0,['__token__'=>$this->request->buildToken()]);
                }
                $result = hook('userMobileCheck', ['mobile'=>$row['mobile'], 'code'=>$row['mobile_code'], 'event'=>'register'], true, true);
            } else { // 文字
                if (empty($row['code'])) {
                    $this->error('Please fill in the verification code');
                }
                // $result = Validate::is($row['code']??'','captcha');
                $result = captcha_check($row['code']);
            }
            if (!$result) {
                $this->result(lang('Verification failed'),[],-1001,'json',['__token__'=>$this->request->buildToken()]);
            }

            $user = \app\index\library\User::instance();
            if ($user->register($row['username'], $row['password'], $row['email']??'', $row['mobile']??'')) {
                $this->success(lang('Registered successfully'),'',['url'=>(string)url('/user.user/login')]);
            } else {
                $this->result($user->getError(),[],-1001,'json',['__token__'=>$this->request->buildToken()]);
            }
        }
        return $this->view->fetch();
    }

    /**
     * 退出登录
     */
    public function loginOut()
    {
        $bl = $this->user->loginOut();
        if (!$bl) {
            $this->error($this->user->getError());
        }
        $this->success(lang('Exit successfully'), (string) url('/user.user/login'));
    }

    /**
     * 重置密码
     * @return string|void
     */
    public function resetPwd()
    {
        if ($this->request->isPost()) {
            $status = $this->request->post('status');
            $username = $this->request->post('username');
            $code = $this->request->post('code');

            if ($status==1) {
                if (!Validate::is($code, 'captcha')) {
                    $this->error(lang('Verification failed'));
                }
                if (Validate::is($username, 'email')) {
                    $info = \app\index\model\User::where(['email'=>$username])->find();
                    if (!$info) {
                        $this->error(lang('Email does not exist'));
                    }
                    session('temp_username', $username);
                    $this->success('', (string) url('/user.user/resetPwd',['status'=>2]));
                } else if (Validate::is($username, 'mobile')) {
                    $info = \app\index\model\User::where(['mobile'=>$username])->find();
                    if (!$info) {
                        $this->error(lang('Phone number does not exist'));
                    }
                    session('temp_username', $username);
                    $this->success('', (string) url('/user.user/resetPwd', ['status'=>3]));
                } else {
                    $this->error(lang('Incorrect format'));
                }
            } else if ($status==2) {

                $password = $this->request->post('password');
                $validate = validate([
                    'password|'.lang('Password')=>'require|length:6,30',
                    'code|'.lang('Password')=>'require',
                ], [], false, false);
                if (!$validate->check(['password'=>$password,'code'=>$code])) {
                    $this->error($validate->getError(),'',[]);
                }

                // 验证码验证
                $result = false;
                $type = $this->request->post('type');
                $mark = session('temp_username');
                if ($type==2) { // 邮箱
                    if (empty($mark)) {
                        $this->error(lang('Please fill in the email address'));
                    }
                    if (empty($mark)) {
                        $this->error(lang('Please fill in the verification code'));
                    }
                    $info = \app\index\model\User::where(['email'=>$mark])->find();
                    if (!$info) {
                        $this->error(lang('Email does not exist'));
                    }
                    $result = Email::instance()->check($mark,'reset_pwd',$code);
                } else if ($type==3) { // 手机
                    if (empty($mark)) {
                        $this->error(lang('Please fill in your phone number'));
                    }
                    if (empty($mark)) {
                        $this->error(lang('Please fill in the verification code'));
                    }
                    $info = \app\index\model\User::where(['mobile'=>$mark])->find();
                    if (!$info) {
                        $this->error(lang('Phone number does not exist'));
                    }
                    $result = hook('userMobileCheck', ['mobile'=>$mark, 'code'=>$code, 'event'=>'reset_pwd'], true, true);
                }
                if (!$result) {
                    $this->result(lang('Verification failed'), [], -1000);
                }

                // 重置密码
                $random = get_random_str(10);
                $password = $this->user->hashPassword($password, $random);

                $info->password = $password;
                $info->salt = $random;
                $info->save();
                $this->success('', (string)url('/user.user/login'));
            }
        }
        if ($this->request->isGet()) {
            $status = $this->request->get('status');
            $username = session('temp_username');
            if (($status==2||$status==3) && $username) {
                $this->view->assign('temp_username', $username);
                $this->view->assign('status', $status);
                return $this->view->fetch('reset_pwd2');
            }
        }
        return $this->view->fetch();
    }

    /**
     * 文件上传
     */
    public function upload()
    {
        $data = $this->request->only(['chunksize'=>'','filesize'=>'','fileid'=>'', 'fileindex'=>'', 'action'=>'chunk']);

        if (!empty($data['fileid'])) {
            if ($this->config->get('site.chunk')!=1) {
                $this->error(lang('Sharding is turned off'));
            }

            $obj = Upload::instance(['user_id'=>$this->user->id,'user_type'=>1]);
            if ('clear'==$data['action']) { // 出错清理
                $obj->clear($data['fileid']);
                $this->success('','', []);
            } else {
                if (empty($data['chunksize']) || empty($data['filesize']) || empty($data['fileindex'])) {
                    $this->error(lang('Parameter is empty'));
                }
                $files = $this->request->file('files');
                if (empty($files)) {
                    $this->error(lang('No files uploaded'));
                }

                // 保存分块
                $res = ['chunk'=>1];
                $chunkInfo = $obj->chunk($files, $data['fileid'], $data['fileindex']);

                // 合并
                if ($data['chunksize']==$data['filesize']) {
                    $res = $obj->merge($data['fileid'], $data['fileindex'], $chunkInfo);
                }
                $this->success('','', $res);
            }
        } else {
            $files = $this->request->file('files');
            if (empty($files)) {
                $this->error(lang('No files uploaded'));
            }

            $res = Upload::instance(['user_id'=>$this->user->id,'user_type'=>1])->upload($files);
            $this->success('','', $res);
        }
    }

    /**
     * 验证码
     * @return \think\Response
     */
    public function verify()
    {
        return captcha();
    }

    /**
     * 短信发送
     */
    public function send()
    {
        $data = $this->request->only(['mobile', 'event']);
        try {
            $this->validate($data,['mobile|'.lang('Mobile')=>'require|mobile','event|'.lang('Event')=>'require|alphaDash']);
        } catch (ValidateException $exception) {
            $this->error($exception->getError());
        }

        // 查询是否短时间内有发送过验证码
        $create_time = Db::name('sms')->where(['mobile'=>$data['mobile'],'event'=>$data['event']])->order('create_time','desc')->value('create_time');
        if ($create_time && time() - $create_time < 60) {
            $this->error(lang('Send frequently.'));
        }
        $total = Db::name('sms')->where(['ip' => $this->request->ip()])->whereTime('create_time', '-1 hours')->count();
        if ($total >= 5) {
            $this->error(lang('Send frequently.'));
        }

        // 获取用信息
        $user = Db::name('user')->where(['mobile'=>$data['mobile']])->find();
        if ('reset_pwd'==$data['event'] && empty($user)) { // 重置密码
            $this->error(lang('Phone number does not exist'));
        } else if ('register'==$data['event'] && !empty($user)) { // 用户注册
            $this->error(lang('Phone number already exists'));
        } else if ('change_mobile'==$data['event'] && !empty($user)) { // 绑定新手机号
            $this->error(lang('Phone number already exists'));
        }

        // 是否有短信验证事件监听
        if (!Event::hasListener('user_mobile_send')) {
            $this->error(lang('Please install the SMS verification plugin first'));
        }

        // 仅支持一个发送事件的监听
        $data['code'] = mt_rand(1000, 9999);
        $result = hook('userMobileSend', $data, true, true);
        if ($result) {
            $this->success(lang('Sent successfully'));
        } else {
            $this->error(lang('Failed to send'));
        }
    }

    /**
     * 发送邮箱验证码
     */
    public function sendems()
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
        $create_time = Db::name('ems')->where(['email'=>$data['email'],'event'=>$data['event']])->order('create_time','desc')->value('create_time');
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
        $html = '<div>
                    <p>您的验证码是：</p>
                    <h2>'.$random.'</h2>
                    <p>验证码的有效期为一小时，为不影响您的正常操作，请您及时完成验证。</p>
                    <p>如非本人操作，请忽略此邮件。</p>
                </div>';

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
                $bl = $email->email($data['email'])->subject(site('title'))->message($html)->send();
                if (!$bl) {
                    throw new \Exception($email->getError());
                }
            }

            Db::commit();
        } catch (\Exception $exception) {
            Db::rollback();
            $this->error($exception->getMessage());
        }

        $this->success(lang('Sent successfully'));
    }
}