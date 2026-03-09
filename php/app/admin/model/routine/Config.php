<?php
// +----------------------------------------------------------------------
// | HkCms 基本配置模型
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types = 1);

namespace app\admin\model\routine;

use app\admin\library\Html;
use app\common\services\config\ConfigService;
use app\common\services\lang\LangService;
use think\facade\Cache;
use think\facade\Validate;
use think\Model;

class Config extends Model
{
    /**
     * 配置写入缓存初始化
     * @param bool $clear true-清空，重新初始化
     * @param string $module 指定模块
     * @deprecated 后续版本不在支持，请使用ConfigService里面的方法site()
     * @return array
     */
    public static function initConfig($clear = false, $module = ''): array
    {
        $app = app();
        $curName = empty($module) ? $app->http->getName() : $module;
        if (true == $clear) {
            Cache::tag(ConfigService::CACHE_TAG)->clear();
            //$app->cache->tag('hk_site')->clear();
        }

        $lang = get_curlang();
        $Site = $app->cache->get($curName.$lang.'_site');
        if (empty($Site)) {
            $Site = [];

            $list = self::whereIn('lang',[$lang,-1])->select()->toArray();
            foreach ($list as $k=>$v) {
                // 移除应用中心账号
                if ($v['name']=='cloud_username') {
                    cache('cloud_username', $v['value']);
                    continue;
                }
                if ($v['name']=='cloud_password') {
                    cache('cloud_password', $v['value']);
                    continue;
                }
                if ($v['group']=='group') {
                    continue;
                }

                if ($v['name']=='index_lang' || $v['name']=='admin_lang' || $v['name']=='content_lang') { // 前台/后台语言列表
                    $Site[$v['name'].'_list'] = json_decode($v['data_list'], true);
                }

                if ($v['type'] == 'radio' || $v['type'] == 'select') {
                    $Site[$v['name']] = $v['value'];
                } else if ($v['type'] == 'array') {
                    $Site[$v['name']] = json_decode($v['value'], true);
                } else if ($v['type'] == 'checkbox' || $v['type'] == 'selects') {
                    $value = explode('|',$v['value']);
                    if (empty($value)) {
                        $Site[$v['name']] = [];
                    } else {
                        $Site[$v['name']] = $value;
                    }
                } else {
                    $Site[$v['name']] = $v['value'];
                }

                if ($v['name'] == 'file_size' || $v['name'] == 'chunk_size') {
                    $Site[$v['name']] = $v['value']*1024*1024;
                }
                if ($v['name'] == 'file_type') {
                    $Site[$v['name']] = str_replace('|',',',$v['value']);
                }
            }

            config($Site,$curName.$lang.'_site');
            $app->cache->tag('hk_site')->set($curName.$lang.'_site', $Site);
        }

        // 上传地址不写入缓存
        if (isset($Site['upload_url']) && !Validate::is($Site['upload_url'],'url')) {
            $httpStr = request()->baseFile(true);
            $httpStr = str_replace(['http:','https:'],'',$httpStr);
            $Site['upload_url'] = $httpStr.$Site['upload_url'];
        }

        return $Site;
    }

    /**
     * 获取语言包列表
     * @param string $module
     * @deprecated 后续版本不再支持
     * @return array
     */
    public static function language($module='index')
    {
        return app()->make(LangService::class)->getListByModule($module);
        $cacheID = 'hk_language_' . $module;
        if (!env('APP_DEBUG') && $cacheData = cache($cacheID)) {
            return $cacheData;
        }

        $info = self::where('name','=', $module.'_lang')->find();
        if (empty($info)) {
            return [];
        }
        $info = $info->toArray();
        $info['data_list'] = json_decode($info['data_list'], true);
        $newArr = [];
        $site = site();
        foreach ($info['data_list'] as $key=>$value) {
            $newArr[$key]['value'] = $value;
            $url = '/?lang='.$key;
            if ($site['url_mode'] == 1 && isset($site['url_rewrite']['/'])) {
                if (strstr($site['url_rewrite']['/'],':lang')) {
                    $url = '/'.$key.'/';
                }
            } else if ($site['url_mode'] == 2) {
                $url = Html::getRootPath().$key.'/';
            }
            $newArr[$key]['url'] = $url;
        }
        $info['data_list'] = $newArr;

        if (!env('APP_DEBUG')) { // 写入缓存
            cache($cacheID, $info, 3600);
        }
        return $info;
    }

    /**
     * 新增事件
     * @param Model $model
     */
    public static function onAfterInsert($model)
    {
        if (site('content_lang_on')==1) {
            $data = $model->getData();
            lang_content_add('config', $data);
        }
    }

    /**
     * 删除后的处理
     * @param Config $model
     * @throws \think\Exception
     */
    public static function onAfterDelete($model)
    {

        // 获取当前删除的ID
        $curId = $model->getAttr('id');
        lang_content_del('config', $curId);

        Cache::tag(ConfigService::CACHE_TAG)->clear();
    }
}
