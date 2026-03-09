<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: luofei614 <weibo.com/luofei614>　
// +----------------------------------------------------------------------
// | 修改者: holuo (本权限类在原3.2.3的基础上修改过来的)
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace libs;

use think\facade\Db;

/**
 * 权限认证类
 * 功能特性：
 * 1，是对规则进行认证，不是对节点进行认证。用户可以把节点当作规则名称实现对节点进行认证。
 *      $auth=new Auth();  $auth->check('规则名称','用户id')
 * 2，可以同时对多条规则进行认证，并设置多条规则的关系（or或者and）
 *      $auth=new Auth();  $auth->check('规则1,规则2','用户id','and')
 *      第三个参数为and时表示，用户需要同时具有规则1和规则2的权限。 当第三个参数为or时，表示用户值需要具备其中一个条件即可。默认为or
 * 3，一个用户可以属于多个用户组(think_auth_group_access表 定义了用户所属用户组)。我们需要设置每个用户组拥有哪些规则(think_auth_group 定义了用户组权限)
 *
 * 4，支持规则表达式。
 *      在think_auth_rule 表中定义一条规则时，如果type为1， condition字段就可以定义规则表达式。 如定义{score}>5  and {score}<100  表示用户的分数在5-100之间时这条规则才会通过。
 * @category ORG
 * @package ORG
 * @subpackage Util
 * @author luofei614<weibo.com/luofei614>
 */


class Auth
{
    /**
     * 定义单例模式的变量
     * @var null
     */
    private static $_instance = null;

    public static function getInstance($option = [])
    {
        if(empty(self::$_instance)) {
            self::$_instance = new self($option);
        }
        return self::$_instance;
    }

    /**
     * 默认配置
     * @var array
     */
    protected $_config = [
        'auth_on' => true,  //认证开关
        'auth_type' => 1,   // 认证方式，1为时时认证；2为登录认证。
        'auth_group' => 'auth_group',   //用户组数据表名
        'auth_group_access' => 'auth_group_access', //用户组明细表
        'auth_rule' => 'auth_rule', //权限规则表
        'auth_user' => 'admin'  //用户信息表
    ];

    public function __construct($option = [])
    {
        // 配置文件路径: 应用目录/config/auth.php
        // if ($auth = config('auth')) {
        //     //可设置配置项 AUTH_CONFIG, 此配置项为数组。
        //     $this->_config = array_merge($this->_config, $auth);
        // }

        $auth = config('auth');
        $this->_config = array_merge($this->_config, is_array($auth)?$auth:[], $option);
    }

    /**
     * 获得权限$name 可以是字符串或数组或逗号分割， uid为 认证的用户id， $or 是否为or关系，为true是， name为数组，只要数组中有一个条件通过则通过，如果为false需要全部条件通过。
     * @param $name
     * @param $uid
     * @param string $relation
     * @return bool
     */
    public function check($name, $uid, $relation='or')
    {
        if (!$this->_config['auth_on'])
            return true;
        $authList = $this->getAuthList($uid);
        if ($authList===true) {  // 超级管理员
            return true;
        }
        if (is_string($name)) {
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $name = array($name);
            }
        }
        $list = array(); //有权限的name
        foreach ($authList as $val) {
            if (in_array($val, $name))
                $list[] = $val;
        }
        if ($relation=='or' and !empty($list)) {
            return true;
        }
        $diff = array_diff($name, $list);
        if ($relation=='and' and empty($diff)) {
            return true;
        }
        return false;
    }

    /**
     * 获取指定ID的权限规则
     * @param $uid
     * @param $status int 1-正常 0-禁用 -1 包含所有
     * @return array
     */
    public function getRuleIds($uid, $status=1)
    {
        $groups = $this->getGroups($uid, $status);
        $ids = [];
        foreach ($groups as $g) {
            $ids = array_merge($ids, explode(',', trim($g['rules'], ',')));
        }
        $ids = array_unique($ids);
        return $ids;
    }

    /**
     * 获得用户组，外部也可以调用
     * @param int $uid 用户ID
     * @param  $status int 1-正常 0-禁用 -1 包含所有
     * @return mixed
     */
    public function getGroups($uid, $status=1)
    {
        static $groups = [];
        if (isset($groups[$uid]))
            return $groups[$uid];
        $user_groups = Db::name($this->_config['auth_group_access'])
            ->alias('a')
            ->join($this->_config['auth_group']." g",'a.group_id=g.id','LEFT')
            ->where("a.{$this->_config['auth_user']}_id='{$uid}' ".($status==-1?'':"and g.status='".($status==1?'normal':'hidden')."'"))
            ->select()->toArray();
        $groups[$uid]=$user_groups?$user_groups:[];
        return $groups[$uid];
    }

    /**
     * 获取指定ID的角色组，都会分隔。
     * @param $uid
     * @param string $field
     * @param int $status
     * @return mixed
     */
    public function getGroupField($uid, $field='name', $status=1)
    {
        static $groups = [];
        if (isset($groups[$uid]))
            return $groups[$uid];

        $user_groups = Db::name($this->_config['auth_group_access'])
            ->alias('a')
            ->join($this->_config['auth_group']." g",'a.group_id=g.id','LEFT')
            ->where("a.{$this->_config['auth_user']}_id='{$uid}' ".($status==-1?'':"and g.status='".($status==1?'normal':'hidden')."'"))
            ->column($field);
        $groups[$uid]=$user_groups?implode(',',$user_groups):'';
        return $groups[$uid];
    }

    /**
     * 获取指定角色的所有下级
     * @param $groupId
     * @param boolean $bl true-包含本身，false 下级
     * @return array
     */
    public function getChildGroup($groupId, $bl = true)
    {
        $groupData = Db::name($this->_config['auth_group'])->where(['status'=>'normal'])->select()->toArray();
        if ($bl) {
            return array_merge(Tree::instance()->init($groupData)->getChildIds($groupId),[(int)$groupId]);
        } else {
            return Tree::instance()->init($groupData)->getChildIds($groupId);
        }
    }

    /**
     * 对提供的规则筛选，只获取存在组ID的规则
     * @param int $groupId 组ID
     * @param array|string $rules 规则
     * @return string
     */
    public function getGroupAuthIn($groupId, $rules)
    {
        $info = Db::name($this->_config['auth_group'])->where(['status'=>'normal'])->find($groupId);
        $infoArr = explode(',', $info['rules']);
        if ($info['rules'] == '*' || in_array('*', $infoArr)) {
            // 超级管理员的情况下
            $data = Db::name($this->_config['auth_rule'])->where(['status'=>'normal'])->select()->toArray();
            $tempArr = [];
            foreach ($data as $key=>$value) {
                $tempArr[] = $value['id'];
            }
            $infoArr = $tempArr;
        }

        $rules = is_string($rules) ? explode(',', $rules) : $rules;
        $newRules = [];
        foreach ($rules as $key=>$value) {
            if (is_numeric($value) && in_array($value, $infoArr)) {
                $newRules[] = $value;
            }
        }
        return implode(',', $newRules);
    }

    /**
     * 获得权限列表用于权限验证
     * @param $uid
     * @return array|mixed
     */
    protected function getAuthList($uid)
    {
        static $_authList = array();
        if (isset($_authList[$uid])) {
            return $_authList[$uid];
        }
        if($this->_config['auth_type']==2 && session('?_AUTH_LIST_'.$uid)){
            return session('_AUTH_LIST_'.$uid);
        }

        //读取用户所属用户组
        $groups = $this->getGroups($uid);
        $ids = array();
        foreach ($groups as $g) {
            $ids = array_merge($ids, explode(',', trim($g['rules'], ',')));
        }
        $ids = array_unique($ids);
        if (empty($ids)) {
            $_authList[$uid] = [];
            return [];
        }
        if (in_array('*', $ids)) {  // 超级管理员
            return true;
        }

        //读取用户组所有权限规则
        $map = [
            ['id','in',$ids],
            ['status','=','normal']
        ];
        $rules = Db::name($this->_config['auth_rule'])->where($map)->select();

        //循环规则，判断结果。
        $authList = array();
        foreach ($rules as $r) {
            if (!empty($r['condition'])) {
                //条件验证、废弃
                //$user = $this->getUserInfo($uid);
                //$command = preg_replace('/\{(\w*?)\}/', '$user[\'\\1\']', $r['condition']);
                //@(eval('$condition=(' . $command . ');'));
                //if ($condition) {
                //    $authList[] = $r['name'];
                //}
            } else {
                //存在就通过
                $authList[] = $r['name'];
            }
        }
        $_authList[$uid] = $authList;
        if($this->_config['auth_type']==2){
            //session结果
            session('_AUTH_LIST_'.$uid,$authList);
        }
        return $authList;
    }

    /**
     * 获取用户信息
     * @param $uid
     * @return mixed
     */
    protected function getUserInfo($uid)
    {
        static $userinfo=array();
        if(!isset($userinfo[$uid])){
            $userinfo[$uid] = Db::name($this->_config['auth_user'])->find($uid);
        }
        return $userinfo[$uid];
    }
}