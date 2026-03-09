<?php
// +----------------------------------------------------------------------
// | HkCms
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\index\model\cms;

use app\common\services\lang\LangService;
use think\Model;

class Recommend extends Model
{
    /**
     * 获取站点模块列表
     * @param $tag
     * @return array|mixed|object|\think\App
     */
    public function getList($tag)
    {
        if (empty($tag) || empty($tag['name'])) {
            return [];
        }

        $where = ['name'=>$tag['name'],'status'=>'normal'];
        if (site('content_lang_on')==1) {
            $tag['lang'] = app()->make(LangService::class)->getLang('index');
            $where['lang'] = $tag['lang'];
        } else {
            // 非内容语言，使用默认的内容语言标识
            $tag['lang'] = app()->make(LangService::class)->getDefaultLang('content');
            $where['lang'] = $tag['lang'];
        }

        // 缓存设置
        $cacheTime = !empty($tag['cache']) && is_numeric($tag['cache']) ? intval($tag['cache']) : 3600;
        $cacheID = to_guid_string($tag);
        if (!app()->isDebug() && $cacheData = cache($cacheID)) {
            return $cacheData;
        }

        $info = $this->where($where)->find();
        if (empty($info)) {
            return [];
        }

        $banner = [];
        if ($info['type']==4) {
            $jsonArray = json_decode($info['value_id'], true);
            try {
                $banner = controller($jsonArray['model'], function ($model) use ($jsonArray, $where) {
                    $model = $model->where(['status'=>'normal','lang'=>$where['lang']]);
                    if (!empty($jsonArray['column'])) {
                        $model = $model->whereIn('category_id', $jsonArray['column']);
                    }
                    if (!empty($jsonArray['order'])) {
                        $model = $model->order($jsonArray['order']);
                    }
                    $array = $model->limit((int)$jsonArray['limit'])->select()->toArray();
                    // 获取扩展字段
                    $fields = \think\facade\Db::name('model_field')->where(['status'=>'normal','model_id'=>$jsonArray['model']])->select()->toArray();
                    foreach ($array as $key=>&$value) {
                        // 格式化
                        foreach ($fields as $k=>$v) {
                            field_format($v, $value);
                        }
                    }
                    return $array;
                });
            } catch (\Exception $exception) {}
        } else {
            if (!empty($tag['itemid']) && is_numeric($tag['itemid'])) {
                //$banner = Banner::where(['recommend_id'=>$info->getAttr('id')])->where(['id'=>$tag['itemid']])->order('weigh','asc')->select()->toArray();
                $bannerTmp = Banner::where(['recommend_id'=>$info->getAttr('id')])->order('weigh','asc')->select()->toArray();
                $banner = isset($bannerTmp[$tag['itemid']-1])?[$bannerTmp[$tag['itemid']-1]]:[];
            } else {
                $banner = Banner::where(['recommend_id'=>$info->getAttr('id')])->order('weigh','asc');

                $offset = 0;
                $length = null;
                if (!empty($tag['num']) && is_numeric($tag['num']) && $tag['num']>0) {
                    $offset = intval($tag['num']);
                } else if (!empty($tag['num']) && strpos($tag['num'], ',') !== false) {
                    $temp = explode(',', $tag['num']);
                    if (count($temp)==2 && is_numeric($temp[0]) && is_numeric($temp[1])) {
                        $offset = (int)$temp[0]-1;
                        $length = (int)$temp[1];
                    }
                }

                if ($offset) {
                    $banner->limit($offset, $length);
                }
                $banner = $banner->select()->toArray();
            }
        }

        $banner = ['recommend'=>$info->toArray(),'banner'=>$banner];
        if (!app()->isDebug()) {
            cache($cacheID, $banner, $cacheTime, 'recommend_tag');
        }
        return $banner;
    }
}