<?php
// +----------------------------------------------------------------------
// | HkCms 后台用户服务
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\library;

use app\admin\model\auth\Admin;
use app\admin\model\auth\AuthGroup;
use app\admin\model\auth\AuthGroupAccess;
use app\admin\model\auth\AuthRule;
use libs\Auth;
use libs\Tree;
use think\facade\Cache;
use think\facade\Session;

class User extends Auth
{
    public function __construct()
    {
        parent::__construct();
    }

    public function __get($name)
    {
        return Session::get('User.'.$name, '');
    }

    /**
     * 是否已经登录
     * @return bool
     */
    public function checkLogin()
    {
        return Session::get('User', '') ? true : false;
    }

    /**
     * 登录
     * @param $username
     * @param $password
     * @return bool
     */
    public function login($username, $password)
    {
        if (empty($username) || empty($password)) {
            return false;
        }

        $user = $this->getUserInfo($username, $password);
        if (empty($user)) {
            // 登录失败
            return false;
        }
        if ($user->status=='hidden') {
            return 0;
        }

        $lasttime = $user->logintime;
        $lastip = $user->loginip;
        $user->logintime = time();
        $user->loginip = request()->ip();
        $user->save();
        $user->lasttime = $lasttime;
        $user->lastip = $lastip;
        $user->group_names = $this->getGroupField($user->id);
        Session::set('User', $user->toArray());
        return true;
    }

    /**
     * 退出登录
     * @return bool
     */
    public function logout()
    {
        Session::delete('User');
        return true;
    }

    /**
     * 判断当前用户是否是超级管理员
     * @return bool
     */
    public function hasSuperAdmin()
    {
        $rules = $this->getRuleIds($this->id);
        return in_array('*', $rules);
    }

    /**
     * 获取用户信息
     * @param string | int $identifier 用户名/ID
     * @param string $password 明文
     * @return array|bool|null|\think\Model
     */
    public function getUserInfo($identifier=null, $password='')
    {
        $identifier = !is_null($identifier) ? $identifier : $this->id;
        if (empty($identifier)) {
            return false;
        }

        if (is_int($identifier)) {
            $where['id'] = $identifier;
        } else {
            $where['username'] = $identifier;
        }

        $user = Admin::where($where)->find();
        if (empty($user)) {
            return false;
        }
        if (!empty($password) && $this->hashPassword($password, $user->salt) != $user->password) {
            $count = Cache::get('login_fail_'.$identifier);
            Cache::set('login_fail_'.$identifier, $count+1, 3600);
            return false;
        }
        return $user;
    }

    /**
     * 获取当前用户拥有的所有角色组ID,包含下级
     * @param  $status int 1-正常 0-禁用 -1-包含所有
     * @return array
     */
    public function getUserGroupId($status=1): array
    {
        $groupArr = $this->getGroups($this->id, $status);

        $groupId = [];
        $group = $status==-1 ? AuthGroup::select()->toArray():AuthGroup::where(['status'=>$status==1?'normal':'hidden'])->select()->toArray();
        foreach ($groupArr as $key=>$value) {
            $idArr = Tree::instance()->init($group)->getChildIds($value['id']);
            if (!empty($idArr)) {
                $groupId = array_merge($groupId, $idArr,[$value['id']]);
            } else {
                $groupId = array_merge($groupId,[$value['id']]);
            }
        }

        $groupId = array_unique($groupId);
        return $groupId;
    }

    /**
     * 获取下级用户
     * @param bool $withself true-包含本身
     * @return array
     */
    public function getChildrenUserIds($withself=true): array
    {
        if ($this->hasSuperAdmin()) {
            $userIds = Admin::column('id');
        } else {
            $groups = $this->getUserGroupId();
            $userIds = AuthGroupAccess::whereIn('group_id',$groups)->column('admin_id');
        }
        $userIds = array_unique($userIds);

        if (!$withself) {
            $userIds = array_diff($userIds,[$this->id]);
        }

        return $userIds;
    }

    /**
     * 获取用户所拥有的规则,多级数组
     * @param  $where
     * @return array
     */
    public function getUserRules($where = []): array
    {
        $model = AuthRule::where(['status'=>'normal']);
        if (!$this->hasSuperAdmin()) {
            $ids = $this->getRuleIds($this->id);
            $model = $model->whereIn('id', $ids);
        }
        if (!empty($where)) {
            $model = $model->where($where);
        }
        return $model->order(['weigh'=>'asc','id'=>'asc'])->select()->toArray();
    }

    /**
     * 返回用户拥有的栏目分类权限
     * @param boolean $all true-获取所有字段,false-只返回栏目ID
     * @return array
     */
    public function getUserCategory($all=true)
    {
        $model = (new \app\admin\model\cms\Category)->where(['status'=>'normal']);
        $data = [];
        if (!$this->hasSuperAdmin()) { // 判断是否是超级管理员
            $group = $this->getUserGroupId();
            $categoryIdArr = \think\facade\Db::name('category_priv')->whereIn('auth_group_id', $group)->column('category_id');
            if (!empty($categoryIdArr)) {
                $data = $model->whereIn('id', $categoryIdArr)->order(['weigh'=>'asc','id'=>'asc'])->select()->toArray();
            }
        } else {
            $data = $model->order(['weigh'=>'asc','id'=>'asc'])->select()->toArray();
        }
        if (!$all) {
            $ids = [];
            foreach ($data as $key=>$value) {
                $ids[] = $value['id'];
            }
            return $ids;
        }
        return $data;
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
}