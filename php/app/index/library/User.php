<?php
// +----------------------------------------------------------------------
// | HkCms 前台用户服务
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\index\library;

use app\index\model\User as UserModel;
use libs\Auth;
use think\facade\Cookie;
use think\facade\Session;
use think\facade\Validate;
use think\Service;

class User extends Auth
{
    /**
     * 默认配置
     * @var array
     */
    protected $_config = [
        'auth_on' => true,  //认证开关
        'auth_type' => 1,   // 认证方式，1为时时认证；2为登录认证。
        'auth_group' => 'user_group',   //用户组数据表名
        'auth_group_access' => 'user_group_access', //用户组明细表
        'auth_rule' => 'user_rule', //权限规则表
        'auth_user' => 'user'  //用户信息表
    ];

    protected static $instance = null;

    protected $error = '';

    /**
     * 用户模型对象
     * @var array
     */
    protected $user = null;

    protected $allowField = ['id', 'username', 'nickname', 'email', 'mobile', 'money', 'score', 'level', 'exp', 'avatar', 'gender', 'birthday', 'introduction', 'latest_time', 'login_time', 'login_ip', 'status'];

    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function __get($name)
    {
        return $this->user ? $this->user->$name : null;
    }

    /**
     * 获取模型
     * @return UserModel | array
     */
    public function getModel()
    {
        return $this->user;
    }

    /**
     * 获取用户信息
     * @param bool $all true-所有字段
     * @return array|mixed
     */
    public function getUser(bool $all = false)
    {
        if (!$this->user) {
            $Member = Session::get('Member');
            if (empty($Member)) {
                return [];
            }
            $info = UserModel::find($Member['id']);
            if (empty($info)) {
                return [];
            }
            $this->user = $info;
        }
        return array_intersect_key($this->user->toArray(), array_flip($this->allowField));
    }

    /**
     * 获取token
     * @return array|mixed|string
     */
    public function getToken()
    {
        return empty($this->user) ? app()->request->server('Authorization', app()->request->param('utoken', Cookie::get('utoken',''))) : $this->user->token;
    }

    /**
     * @return static|null
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * 验证用户token信息
     * @param $token
     * @return bool
     */
    public function checkToken($token)
    {
        if (empty($token)) {
            $this->setError('You are not logged in');
            return false;
        }
        if (!empty($this->user)) {
            return true;
        }
        $arr = app('token')->get($token);
        if (isset($arr['user_id']) && $arr['user_id']>0) {
            $info = UserModel::find($arr['user_id']);
            if (empty($info)) {
                $this->setError('User information not exist');
                return false;
            }
            if ($info->status!='normal') {
                $this->setError('Account is disabled');
                return false;
            }
            $info->token = $token;
            $this->user = $info;
            return true;
        } else {
            $this->setError('You are not logged in');
            return false;
        }
    }

    /**
     * 登录
     * @param string $username 手机号/邮箱/账号名
     * @param string $password
     * @return bool true-登录成功,false-登录失败
     */
    public function login(string $username, string $password) : bool
    {
        $field = Validate::is($username,'mobile') ? 'mobile' : (Validate::is($username,'email') ? 'email' : 'username');

        $info = UserModel::where([$field=>$username])->find();
        if (empty($info)) {
            if ($field=='mobile') {
                $info = UserModel::where(['username'=>$username])->find();
            }
            if (empty($info)) {
                $this->setError('Account not exist');
                return false;
            }
        }
        if ($info->status!='normal') {
            $this->setError('Account is disabled');
            return false;
        }
        if ($this->hashPassword($password, $info->salt) != $info->password) {
            $info->login_failed = $info->login_failed + 1;
            $info->save();
            $this->setError('Password error');
            return false;
        }

        $info->login_ip = request()->ip();
        $info->login_failed = 0;
        $info->latest_time = $info->login_time ? strtotime($info->login_time) : null;
        $info->login_time = time();
        $info->save();

        $this->user = $info;

        Session::set('Member', $info->toArray());

        event('userLoginSuccess', $info);
        return true;
    }

    /**
     * 注册账号
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $email 邮箱
     * @param string $mobile 手机
     * @param array $extend 额外字段
     * @return bool
     */
    public function register(string $username, string $password, string $email='', string $mobile='', array $extend=[]) : bool
    {
        if (UserModel::getByUsername($username)) {
            $this->setError('Username already exists');
            return false;
        }
        if ($email && UserModel::getByEmail($email)) {
            $this->setError('Email already exists');
            return false;
        }
        if ($mobile && UserModel::getByMobile($mobile)) {
            $this->setError('Phone number already exists');
            return false;
        }

        $random = get_random_str(10);

        $add = [
            'username' => $username,
            'nickname' => Validate::is($username,'mobile') ? substr_replace($username,'****',3,4) : $username,
            'email' => $email,
            'mobile' => $mobile,
            'password' => $this->hashPassword($password, $random),
            'salt' => $random,
            'money' => 0.00,
            'score' => 0,
            'level' => 0,
            'exp' => 0,
        ];

        $add = array_merge($add, $extend);
        $model = UserModel::create($add);
        hook('userRegisterSuccess', $model);
        return true;
    }

    /**
     * 修改密码
     * @param $old
     * @param $new
     * @return bool
     */
    public function changePassword($old, $new)
    {
        if ($old == $new) {
            $this->setError('The old password and new password can not be the same');
            return false;
        }
        if ($this->hashPassword($old, $this->user->salt) != $this->user->password) {
            $this->setError('Password error');
            return false;
        }
        $random = get_random_str(10);
        UserModel::where(['id'=>$this->user->id])->save(['password'=>$this->hashPassword($new, $random),'salt'=>$random]);
        return true;
    }

    /**
     * 退出登录
     * @return bool
     */
    public function loginOut()
    {
        if (empty($this->user)) {
            $this->setError('You are not logged in');
            return false;
        }
        Session::delete('Member');
        event('userLogoutSuccess', $this->user);
        $this->user = null;
        return true;
    }

    /**
     * 对明文密码，进行加密，返回加密后的密文密码
     * @param string $password 明文
     * @param string $salt 认证码
     * @return string 密文
     */
    public function hashPassword($password, $salt = ""): string
    {
        return md5(sha1($password) . md5($salt));
    }

    /**
     * 设置错误信息
     * @param string $msg
     * @return $this
     */
    public function setError(string $msg)
    {
        $this->error = $msg;
        return $this;
    }

    /**
     * 返回错误信息
     * @return mixed|string
     */
    public function getError()
    {
        return $this->error ? lang($this->error) : '';
    }
}