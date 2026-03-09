<?php
// +----------------------------------------------------------------------
// | 站点配置
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\services\config;

use app\common\dao\config\ConfigDao;
use app\common\services\BaseService;
use app\common\services\cache\CacheService;
use libs\crypto\Crypto;
use libs\crypto\Exception\WrongKeyOrModifiedCiphertextException;
use think\facade\Cache;
use think\facade\Lang;
use think\facade\Log;
use think\facade\Validate;

/**
 * @mixin ConfigDao
 */
class ConfigService extends BaseService
{
    // 缓存标签
    const CACHE_TAG = [
        'site',
        'cloud'
    ];

    /**
     * 初始化
     * @param ConfigDao $dao
     */
    public function __construct(ConfigDao $dao, CacheService $cacheService)
    {
        $this->dao = $dao;
        // 加入统一缓存管理
        $cacheService->bucket( self::CACHE_TAG);
    }

    /**
     * 获取站点配置
     * @param string $name
     * @param string $module
     * @return array|string
     */
    public function site(string $name = '', string $module = '', string $lang = '')
    {
        // 获取模块
        $app = app();
        $module = empty($module) ? $app->http->getName() : $module;
        // 获取语言
        $lang = $lang ? : Lang::getLangset();
        //$value = $name ? config($module.$lang.'_site.') : config($module.$lang.'_site');
        $config = config($module.$lang.'_site');
        if (empty($config)) { // 为空的情况获取缓存
            $config = app()->isDebug() ? [] : Cache::get($module.$lang.'_site');
            if (empty($config)) {
                $configList = $this->dao->search()->whereIn('lang',[$lang,-1])->select();
                foreach ($configList as $v) {
                    // 移除应用中心账号
                    if ($v['name']=='cloud_username') {
                        continue;
                    }
                    if ($v['name']=='cloud_password') {
                        continue;
                    }
                    if ($v['group']=='group') {
                        continue;
                    }
                    if ($v['type'] == 'radio' || $v['type'] == 'select') {
                        $config[$v['name']] = $v['value'];
                    } else if ($v['type'] == 'array') {
                        $config[$v['name']] = json_decode($v['value'], true);
                    } else if ($v['type'] == 'checkbox' || $v['type'] == 'selects') {
                        $tmpValue = explode('|',$v['value']);
                        if (empty($tmpValue)) {
                            $config[$v['name']] = [];
                        } else {
                            $config[$v['name']] = $tmpValue;
                        }
                    } else {
                        $config[$v['name']] = $v['value'];
                    }

                    if ($v['name'] == 'file_size' || $v['name'] == 'chunk_size') {
                        $config[$v['name']] = $v['value']*1024*1024;
                    }
                    if ($v['name'] == 'file_type') {
                        $config[$v['name']] = str_replace('|',',',$v['value']);
                    }
                }

                config($config,$module.$lang.'_site');
                Cache::tag(self::CACHE_TAG[0])->set($module.$lang.'_site', $config);
            }
        }
        $value = $name ? ($config[$name] ?? '') : $config;
        // 上传地址不写入缓存
        if (empty($name) && isset($value['upload_url']) && !Validate::is($value['upload_url'],'url')) {
            $httpStr = request()->baseFile(true);
            //$httpStr = str_replace(['http:','https:'],'',$httpStr);
            $value['upload_url'] = $httpStr.$value['upload_url'];
            return $value;
        } else if (!empty($name) && $name=='upload_url' && !Validate::is($value,'url')) {
            $httpStr = request()->baseFile(true);
            //$httpStr = str_replace(['http:','https:'],'',$httpStr);
            return $httpStr.$value;
        }
        return $value;
    }

    /**
     * 获取应用中心保存的用户信息
     * @return string[]
     */
    public function getCloudInfo(): array
    {
        if ($arr = Cache::get(self::CACHE_TAG[1])) {
            return $arr;
        }
        $data = $this->dao->getCloudInfo();
        $name = "";
        $pass = "";
        try {
            foreach ($data as $item) {
                if ($item['value'] && $item['name']=='cloud_username') {
                    $name = Crypto::decryptWithPassword($item['value'], $item['data_list']==null?"":$item['data_list']);
                } else if ($item['value'] && $item['name']=='cloud_password') {
                    $pass = Crypto::decryptWithPassword($item['value'], $item['data_list']==null?"":$item['data_list']);
                }
            }
        } catch (WrongKeyOrModifiedCiphertextException $wrongKeyOrModifiedCiphertextException) {
            $name = $pass = "";
            Log::error("[应用中心账号密码解析失败]：".$wrongKeyOrModifiedCiphertextException->getMessage());
        }

        $arr = [$name, $pass];
        Cache::tag(self::CACHE_TAG[1])->set('cloud_user', $arr);
        return $arr;
    }

    /**
     * 保存应用中心信息
     * @param string $name
     * @param string $pass
     * @return void
     */
    public function saveCloudInfo(string $name, string $pass)
    {
        $grs = get_random_str(6);
        $name = \libs\crypto\Crypto::encryptWithPassword($name, $grs,false);
        $this->dao->update(['name'=>'cloud_username'],['value'=>$name,'data_list'=>$grs]);
        $grs = get_random_str(6);
        $pass = \libs\crypto\Crypto::encryptWithPassword($pass, $grs,false);
        $this->dao->update(['name'=>'cloud_password'],['value'=>$pass,'data_list'=>$grs]);
        Cache::tag(self::CACHE_TAG[1])->set('cloud_user', [$name, $pass]);
    }

    /**
     * 刷新版本
     * @return void
     */
    public function refreshVer(): void
    {
        $info = $this->dao->getOne(['name'=>'version']);
        if (!empty($info)) {
            if ($info['value']) {
                $tmpArr = explode('.',$info['value']);
                $in = end($tmpArr) + 1;
                $tmpArr[count($tmpArr)-1] = $in;
                $info->save(['value'=>implode('.', $tmpArr)]);
            } else {
                $info->save(['value'=>'1.0.0']);
            }
        }
    }

    /**
     * 获取所有配置，给到api调用
     * @return mixed
     */
    public function getAllByApi($lang)
    {
        $site = site('','',$lang);
        // 剔除账号
        unset($site['mail_server']);
        unset($site['mail_port']);
        unset($site['mail_from']);
        unset($site['mail_fname']);
        unset($site['mail_auth']);
        unset($site['mail_user']);
        unset($site['mail_password']);
        unset($site['url_rewrite']);
        unset($site['login_fail_count']);
        return $site;
    }
}