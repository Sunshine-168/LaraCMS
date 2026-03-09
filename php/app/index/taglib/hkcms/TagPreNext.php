<?php
// +----------------------------------------------------------------------
// | HkCms 上下页标签、上一页标签、下一页标签
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\index\taglib\hkcms;

use think\facade\Cache;
use think\helper\Str;

class TagPreNext extends Base
{
    /**
     * 上篇下篇生成A标签
     * @param $tag
     * @return string
     */
    public function preNextHtml($tag)
    {
        $catinfo = (new \app\index\model\cms\Category)->getCateInfo($tag['catid']);
        if (!empty($catinfo) && !empty($catinfo["model_id"])) {
            $info = controller($catinfo, function ($obj, $model, $category) use($tag) {
                if ($tag['type']=='pre') {
                    return $obj->where(['category_id'=>$category['id'],'status'=>'normal'])->where('id','<',$tag['id'])->cache('preNextHtmlpre',86400,'tagpre_tag')->order(["id"=>"DESC"])->find();
                } else {
                    return $obj->where(['category_id'=>$category['id'],'status'=>'normal'])->where('id','>',$tag['id'])->cache('preNextHtml',86400,'tagpre_tag')->order(["id"=>"asc"])->find();
                }

            },'category');
            if (empty($info)) {
                return '';
            }
            $info = $info->append(["url"])->toArray();

            $title = $info[$tag['field']] ?? '';
            $title = $tag['len'] ? (Str::length($title)>$tag['len']?Str::substr($title,0, (int)$tag['len']).$tag['dot']:$title) : $title;

            return '<a href="'.$info['url'].'" '.((empty($tag['target']))?'':'target="'.$tag['target'].'"').' title="'.$info[$tag['field']].'">'.$title.'</a>';
        } else {
            return '';
        }
    }

    /**
     * 上篇下篇
     * @param $tag
     * @return array | string
     */
    public function preNext($tag)
    {
        $catinfo = (new \app\index\model\cms\Category)->getCateInfo($tag['catid']);
        $num = isset($tag['num']) && is_numeric($tag['num']) && $tag['num']>0 ? (int) $tag['num'] : '';
        if (empty($catinfo) || empty($catinfo["model_id"])) {
            return '';
        }

        $tag['lang'] = $catinfo['lang'];
        $tag['model_id'] = $catinfo['model_id'];
        $cacheKey = implode(',', $tag);
        if (!app()->isDebug() && $cacheData = Cache::get($cacheKey)) {
            return $cacheData;
        }

        $infos = controller($catinfo, function ($obj, $model, $category) use($tag, $num) {
            $obj = $obj->where(['category_id'=>$category['id'],'status'=>'normal']);
            if (site('content_lang_on')==1) {
                $obj = $obj->where('lang', $category['lang']);
            }
            if ($tag['type']=='pre') {
                $obj = $obj->where('id','<',$tag['aid'])->order(["id"=>"DESC"]);
                return $num ? $obj->limit($num)->select() : $obj->find();
            } else {
                $obj = $obj->where('id','>',$tag['aid'])->order(["id"=>"asc"]);
                return $num ? $obj->limit($num)->select() : $obj->find();
            }

        },'category');
        if (empty($infos)) {
            return '';
        }

        $infos = $infos->append(["url"])->toArray();

        if (!$num) {
            $infos = [$infos];
        }

        foreach ($infos as $key=>$info) {
            $title = $info[$tag['field']] ?? '';
            $title = $tag['len'] ? (Str::length($title)>$tag['len']?Str::substr($title,0, (int)$tag['len']).$tag['dot']:$title) : $title;
            $info[$tag['field'].'_old'] = $info[$tag['field']];
            $info[$tag['field']] = $title;

            $infos[$key] = $info;
        }

        if (!app()->isDebug()) {
            Cache::tag('tagpre_tag')->set($cacheKey, $infos, 7200);
        }
        return $infos;
    }
}