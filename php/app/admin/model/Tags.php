<?php
// +----------------------------------------------------------------------
// | HkCms
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\admin\model;

use app\admin\model\cms\Archives;
use app\common\services\lang\LangService;
use think\facade\Db;
use think\Model;

class Tags extends Model
{
    /**
     * url
     * @param $value
     * @param $data
     * @return string|string[]
     */
    public function getUrlAttr($value, $data)
    {
        return $this->buildUrl($value, $data);
    }

    /**
     * 获取完整url地址
     * @param $value
     * @param $data
     * @return int|mixed|string|string[]
     */
    public function getFullurlAttr($value, $data)
    {
        return $this->buildUrl($value, $data, true);
    }

    /**
     * 生成url地址
     * @param $value
     * @param $data
     * @param bool $domain
     */
    protected function buildUrl($value, $data, $domain = false)
    {
        $param = site('content_lang_on') != 1 ? ['tag'=>$data['title']] : ['tag'=>$data['title'],'lang'=>$data['lang']];
        return index_url('/tags/lists', $param,true, $domain,'', ['tag'=>'tag']);
    }

    /**
     * 获取所有标签
     * @param $tag
     * @param $page
     * @return array|mixed|object|\think\App
     */
    public function getList($tag, &$page)
    {
        if (!empty($tag['arcid']) && empty($tag['model'])) {
            return [];
        }

        // 多语言
        $tag['lang'] = site('content_lang_on')==1?app()->lang->getLangset():app()->make(LangService::class)->getDefaultLang('content');

        // 缓存设置
        $cacheTime = !empty($tag['cache']) && is_numeric($tag['cache']) ? intval($tag['cache']) : 3600;
        $cacheID = to_guid_string($tag);
        $cacheData = cache($cacheID);
        if (!app()->isDebug() && $cacheData && $tag['page']!=1) {
            return $cacheData;
        }

        $tag['order'] = empty($tag['order']) ? 'views desc' : $tag['order'];

        $offset = 0;
        $length = null;
        if (!empty($tag['num']) && is_numeric($tag['num']) && $tag['num']>0) { // 指定分页
            $offset = intval($tag['num']);
        } else if (!empty($tag['num']) && strpos($tag['num'], ',') !== false) {
            $temp = explode(',', $tag['num']);
            if (count($temp)==2 && is_numeric($temp[0]) && is_numeric($temp[1])) {
                $offset = (int)$temp[0];
                $length = (int)$temp[1];
            }
        }

        $map = [['lang','=', $tag['lang']]];
        if ($tag['tid']) {
            $map[] = ['id','=', $tag['tid']];
        }

        if ($tag['page']==1) { // 分页
            if ($tag['arcid']) {
                $obj = self::hasWhere('tagsLists',['content_id'=>$tag['arcid'],'model_id'=>$tag['model']])->where($tag['where'])->order($tag['order'])->limit($offset,$length);
            } else if (isset($tag['model']) && $tag['model']>0) {
                $obj = self::hasWhere('tagsLists',['model_id'=>$tag['model']])->where($tag['where'])->order($tag['order'])->limit($offset,$length);
            } else if (isset($tag['catid']) && $tag['catid']>0) {
                $obj = self::hasWhere('tagsLists',['category_id'=>$tag['catid']])->where($tag['where'])->order($tag['order'])->limit($offset,$length);
            } else {
                $obj = self::where($map)->where($tag['where'])->order($tag['order']);
            }

            //$obj = $obj->paginate($tag['num']);

            $obj = $obj->paginate([
                'list_rows'=> $tag['num'],
                'var_page' => 'page',
                'path'=>'/tags/index' // 使用分页标签，需要自定义链接地址
            ], false);

            if ($obj->isEmpty()) {
                $page = $obj;
                return [];
            }
            $array = $obj->append(['url'])->toArray()['data'];
            $page = $obj;
        } else {
            if ($tag['arcid']) {
                $obj = self::hasWhere('tagsLists',['content_id'=>$tag['arcid'],'model_id'=>$tag['model']])->where($tag['where'])->order($tag['order'])->limit($offset,$length)->select();
            } else if (isset($tag['model']) && $tag['model']>0) {
                $obj = self::hasWhere('tagsLists',['model_id'=>$tag['model']])->where($tag['where'])->order($tag['order'])->limit($offset,$length)->select();
            } else if (isset($tag['catid']) && $tag['catid']>0) {
                $obj = self::hasWhere('tagsLists',['category_id'=>$tag['catid']])->where($tag['where'])->order($tag['order'])->limit($offset,$length)->select();
            } else {
                $obj = self::where($map)->where($tag['where'])->order($tag['order'])->limit($offset,$length)->select();
            }
            if ($obj->isEmpty()) {
                return [];
            }
            $array = $obj->append(['url'])->toArray();
        }

        // 结果进行缓存
        if (!app()->isDebug() && $tag['page']!=1) {
            cache($cacheID, $array, $cacheTime, 'taglist_tag');
        }
        return $array;
    }

    /**
     * 获取标签的文档内容
     * @param $tag
     * @param $page
     * @return array|mixed|object|\think\App
     */
    public function getContent($tag, &$page)
    {
        // 缓存设置
        $cacheTime = !empty($tag['cache']) && is_numeric($tag['cache']) ? intval($tag['cache']) : 3600;
        $cacheID = to_guid_string($tag);
        if (!env('APP_DEBUG') && $cacheData = cache($cacheID)) {
            return $cacheData;
        }

        $tag['order'] = !empty($tag['order']) ? $tag['order'] : 'create_time desc';
        $tag['num'] = intval($tag['num']);

        if (is_numeric($tag['tid'])) {
            $info = self::where(['id'=>$tag['tid']])->find();
        } else {
            $info = self::where(['title'=>$tag['tid']])->find();
        }

        if (empty($info)) {
            return [];
        }

        if ($tag['page']) { // 开启分页
            $param = app()->request->param();
            $pageParam = [
                'list_rows'=> $tag['num'],
                'var_page' => 'page',
                'path'=>'/tags/lists', // 使用分页标签，需要自定义链接地址
                'query' => $param,
                'rule' => ['tag'=>'tag']  // 伪静态写入自己的规则，后续版本可不写，系统会自动判断
            ];
            $obj = TagsList::where(['tags_id'=>$info['id']])->where($tag['where'])->order($tag['order'])->paginate($pageParam, false);
            $array = $obj->toArray()['data'];
            $page = $obj;
        } else {
            $array = TagsList::where(['tags_id'=>$info['id']])->where($tag['where'])->order($tag['order'])->limit($tag['num'])->select()->toArray();
        }

        $newArray = [];
        foreach ($array as $key=>$value) {
            $info = Archives::alias('a')->with(['category','model'])->where(['id'=>$value['content_id'],'status'=>'normal'])->append(['publish_time_text','fullurl'])->find();
            if (!$info) {
                continue;
            }
            $info = $info->toArray();
            $info2 = Db::name($info['model']['tablename'])->find($info['id']);
            $info = array_merge($info, empty($info2)?[]:$info2);

            // 字段格式化
            $fields = \think\facade\Db::name('model_field')->where(['status'=>'normal','model_id'=>$info['model']['id']])->cache(env('APP_DEBUG')?false:3600)->select()->toArray();
            foreach ($fields as $k=>$v) {
                field_format($v, $info);
            }

            if (is_string($info['tags'])) {
                $tagsArr = explode(',', $info['tags']);
                $newTagsArr = [];
                foreach ($tagsArr as $k=>$v) {
                    $tmp['title'] = $v;
                    $tmp['url'] = $this->getUrlAttr($v, $info);

                    $newTagsArr[] = $tmp;
                }
                if ($newTagsArr) {
                    $info['tags'] = $newTagsArr;
                }
            }

            $newArray[] = $info;
        }

        // 结果进行缓存
        if (!env('APP_DEBUG')) {
            cache($cacheID, $newArray, $cacheTime, 'taglist_tag');
        }
        return $newArray;
    }

    /**
     * 一对多关联
     * @return \think\model\relation\HasMany
     */
    public function tagsLists()
    {
        return $this->hasMany(TagsList::class,'tags_id');
    }
}