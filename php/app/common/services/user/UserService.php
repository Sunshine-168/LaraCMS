<?php
// +----------------------------------------------------------------------
// | 用户服务
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\common\services\user;

use app\common\dao\user\UserDao;
use app\common\exception\ServiceException;
use app\common\model\user\User;
use app\common\services\BaseService;
use think\facade\Cache;
use think\facade\Config;

/**
 * @mixin UserDao
 */
class UserService extends BaseService
{
    // 返回前台的字段
    const Field = 'id,username,nickname,email,mobile,money,score,level,exp,avatar,gender,birthday,introduction,latest_time';

    /**
     * 初始化
     * @param UserDao $userDao
     */
    public function __construct(UserDao $userDao)
    {
        $this->dao = $userDao;
    }

    /**
     * 登录失败
     * @param string $username
     * @param int $count
     * @return mixed
     * @throws ServiceException
     */
    public function loginFailCheck(string $username, int $count): void
    {
        $key = md5('api_login_fail_'.$username);
        $num = Cache::get($key);
        $num = empty($num) ? 0 : $num;
        if ($num>$count) {
            throw new ServiceException("Too many incorrect accounts or passwords. Please try again later", 400);
        } else {
            Cache::set($key, ++$num, 600);
            throw new ServiceException("The account or password is incorrect", 400);
        }
    }

    /**
     * 密码加密
     * @param string $pass
     * @param string $salt
     * @return string
     */
    public function hashPassword(string $pass, string $salt): string
    {
        return md5(sha1($pass) . md5($salt));
    }

    /**
     * 获取用户信息
     * @param int|User $id
     * @return array
     */
    public function details(int $id, $field = ''): array
    {
        $info = $this->dao->getOne($id, $field ?: self::Field);
        return $this->resultFormat($info)->toArray();
    }

    /**
     * 处理返回数据
     * @param User $info
     * @return User
     */
    public function resultFormat(User $info): User
    {
        // 格式化登录时间
        $info->login_time = $info->login_time ? date('Y-m-d H:i:s', $info->login_time) : "";
        // 头像加域名
        $info->avatar = cdn_url($info['avatar'], true);
        // 获取组
        $info->group = app()->make(UserGroupAccessService::class)->getListByIUserId($info->id);
        // 获取权限
        $rules = "";
        foreach ($info->group as $item) {
            $rules .= $item['rules'];
        }
        $info->rule = app()->make(UserRuleService::class)->search(['id'=>$rules])
            ->where('status', 'normal')
            ->field('title,id,name,parent_id,route,icon,app,type,weigh')
            ->order('weigh', 'asc')
            ->select();
        return $info;
    }

    /**
     * 登录后的操作
     * @param User $user
     * @param array $tokenInfo
     * @return array
     */
    public function loginAfter(User $user, array $tokenInfo, string $client, string $mark = 'api'): array
    {
        // 隐藏字段
        $tokenInfo['user'] = $this->resultFormat($user)->hidden(['password','salt'])->toArray();
        // 获取默认配置
        $expire = Config::get('jwt.expire');
        // 写入缓存
        Cache::set(md5($tokenInfo['token']), ['client'=>$client,'mark'=>$mark,'user_id'=>$user->id], $expire);
        return $tokenInfo;
    }
}