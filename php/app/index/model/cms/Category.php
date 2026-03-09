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

use app\common\model\LangBind;
use app\common\services\lang\LangBindService;
use app\common\services\lang\LangService;
use think\Model;
use think\model\concern\SoftDelete;

class Category extends Model
{
    use SoftDelete;

    /**
     * 格式化url
     * @param $value
     * @param $data
     * @return string
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
        if (empty($value) && $data['model_id']>0) {
            $site = site(); // 获取伪静态规则
            if ($site['url_mode']==1 && !empty($site['url_rewrite'])) {
                $param = $data;
            } else {
                $param = ['catname'=>$data['name']];
                if ($site['content_lang_on']==1) {
                    $param['lang'] = $data['lang'];
                }
            }

            return index_url('/index/lists', $param,'', $domain);
        } else if ($data['type']=='link' && is_numeric($value)) {
            $info = $this->where(['id'=>$value])->find();
            if ($info) {
                return $info['url'];
            }
        }
        return $value;
    }

    /**
     * 上级URL地址
     * @param $value
     * @param $data
     * @return string
     */
    public function getParentUrlAttr($value, $data)
    {
        if ($data['parent_id']) {
            $info = self::where(['id'=>$data['parent_id']])->find();
            if (empty($info)) {
                return '';
            }
            if (empty($info['url'])) {
                return $this->getUrlAttr('', $info);
            }
            return $info['url'];
        } else {
            return '';
        }
    }

    /**
     * 获取栏目列表
     * @param $tag
     * @return array|mixed|object|\think\App
     */
    public function getList($tag)
    {
        $tag['order'] = !empty($tag['order']) ? $tag['order'] : 'weigh asc';

        // 缓存设置
        $cacheTime = !empty($tag['cache']) && is_numeric($tag['cache']) ? intval($tag['cache']) : 3600;
        // 语言切换
        if (site('content_lang_on')==1) {
            $tag['lang'] = !empty($tag['lang']) ? $tag['lang'] : app()->make(LangService::class)->getLang('index');
            if ($tag['name'] && is_numeric($tag['name']) && $tag['name']>0) {
                $tag['name'] = app()->make(LangBindService::class)->getBindValue($tag['name'], 'category', $tag['lang']);
            } else if ($tag['name']) {
                $tmpName = explode(',', $tag['name']);
                $newArr = [];
                foreach ($tmpName as $value) {
                    $newArr[] = app()->make(LangBindService::class)->getBindValue($value, 'category', $tag['lang']);
                }
                $tag['name'] = implode(',', $newArr);
            }
        } else {
            // 非内容语言，使用默认的内容语言标识
            $tag['lang'] = app()->make(LangService::class)->getDefaultLang('content');
        }

        $cacheID = to_guid_string($tag);
        if (!app()->isDebug() && $cacheData = cache($cacheID)) {
            return $cacheData;
        }

        $offset = 0;
        $length = null;
        if (!empty($tag['num']) && is_numeric($tag['num']) && $tag['num']>0) { // 指定分页
            $offset = intval($tag['num']);
        } else if (!empty($tag['num']) && strpos($tag['num'], ',') !== false) {
            $temp = explode(',', $tag['num']);
            if (count($temp)==2 && is_numeric($temp[0]) && is_numeric($temp[1])) {
                $offset = (int)$temp[0]-1;
                $length = (int)$temp[1];
            }
        }

        $map = [['status','=','normal'],['lang','=',$tag['lang']]];
        if (!empty($tag['type'])) { // 根据type属性获取不同上下级栏目
            if ($tag['type']=='top') { // 获取顶级栏目
                $map[] = ['parent_id', '=', 0];
            } else if ($tag['type']=='peer') { // 获取同级栏目
                if ($tag['name'] && is_numeric($tag['name']) && $tag['name']>0) {
                    $id = $this->where(['id'=>$tag['name']])->value('parent_id');
                    $map[] = ['parent_id', '=', $id];
                } else {
                    trace('获取同级栏目时name属性不能为空', 'view');
                    return [];
                }
            } else if ($tag['type']=='son') { // 获取下级栏目
                if ($tag['name'] && is_numeric($tag['name']) && $tag['name']>0) {
                    $map[] = ['parent_id', '=', $tag['name']];
                } else {
                    trace('获取下级栏目时name属性不能为空', 'view');
                    return [];
                }
            } else if ($tag['type']=='selftop') { // 获取顶级栏目的下的第一级全部栏目
                if ($tag['name'] && is_numeric($tag['name']) && $tag['name']>0) {
                    $info = get_category_top($tag['name']);
                    if (empty($info)) {
                        trace('获取顶级栏目的下的第一级全部栏目失败，'.$tag['name'].'不存在', 'view');
                        return [];
                    }
                    $map[] = ['parent_id', '=', $info->id];
                } else {
                    trace('获取顶级栏目的下的第一级全部栏目时name属性不能为空', 'view');
                    return [];
                }
            } else if ($tag['type']=='selfson') { // 获取当前栏目的下级栏目，若下级栏目不存在依然返回当前同级栏目
                if (empty($tag['name']) || !is_numeric($tag['name']) || $tag['name']<=0) {
                    trace('获取下级栏目时name属性不能为空', 'view');
                    return [];
                }
                if ($this->where(['parent_id'=>$tag['name']])->count()) {
                    $map[] = ['parent_id', '=', $tag['name']];
                } else {
                    $id = $this->where(['id'=>$tag['name']])->value('parent_id');
                    $map[] = ['parent_id', '=', $id];
                }
            }
        } else {
            if ($tag['name'] && is_numeric($tag['name']) && $tag['name']>0) {
                $map[] = ['id', '=', $tag['name']];
            } else if ($tag['name']) {
                $map[] = ['id', 'in', $tag['name']];
            }
        }
        if (!empty($tag['model']) && is_numeric($tag['model'])) { // 根据模型获取栏目
            $map[] = ['model_id', '=', $tag['model']];
        }
        if ($tag['ismenu']) { // 是否只显示导航
            $map[] = ['ismenu','=',1];
        }

        $obj = $this->where($map)->where($tag['where'])->order($tag['order'])->limit($offset,$length)->append(['parent_url','fullurl'])->select();
        if ($obj->isEmpty()) {
            return [];
        }

        $array = $obj->hidden(['delete_time'])->toArray();

        if (!empty($array)) {
            $map = [['status','=','normal']];
            if ($tag['ismenu']) { // 是否只显示导航
                $map[] = ['ismenu','=',1];
            }

            // 获取扩展字段
            $fields = \app\admin\model\cms\Fields::where(['status'=>'normal','source'=>'category'])->order('weigh', 'desc')->select()->toArray();
            foreach ($array as $key=>$value) {
                // 格式化
                foreach ($fields as $k=>$v) {
                    field_format($v, $value);
                    $array[$key] = $value;
                }

                $sonIds = $this->where($tag['where'])->where($map)->where(['parent_id'=>$value['id']])->column('id');
                $array[$key]['has_child'] = $sonIds ? true : false;
                $array[$key]['son_child'] = $sonIds;
            }
        }

        // 结果进行缓存
        if (!app()->isDebug()) {
            cache($cacheID, $array, $cacheTime, 'category_tag');
        }
        return $array;
    }

    /**
     * 获取栏目基本信息
     * @param $catId
     * @param bool $clear
     * @return array|bool|mixed|object|\think\App
     */
    public function getCateInfo($catId, $clear=false)
    {
        // 语言切换
        $lang = app()->make(LangService::class)->getDefaultLang('content');
        if (site('content_lang_on')==1) {
            $lang = app()->lang->getLangSet();
        }

        $cacheID = $lang.'getCateInfo_' . $catId;

        //强制刷新缓存
        if ($clear) {
            cache($cacheID, NULL);
        }
        if (!app()->isDebug() && $cacheData = cache($cacheID)) {
            return $cacheData;
        }

        if (is_numeric($catId)) {
            $catId = app()->make(LangBindService::class)->getBindValue($catId, 'category', $lang);
            $cateInfo = Category::where(['status'=>'normal','id'=>$catId])->find();
        } else if (!empty($catId) && \think\facade\Validate::is($catId,'alphaDash')) {
            $cateInfo = Category::where(['status'=>'normal','name'=>$catId,'lang'=>$lang])->find();
        } else {
            return false;
        }

        if (empty($cateInfo)) {
            return false;
        }

        $cateInfo = $cateInfo->append(['parent_url','fullurl'])->toArray();
        // 获取扩展字段
        $fields = \app\admin\model\cms\Fields::where(['status'=>'normal','source'=>'category'])->order('weigh', 'desc')->select()->toArray();
        foreach ($fields as $field) {
            field_format($field, $cateInfo);
        }
        if (!app()->isDebug()) {
            cache($cacheID, $cateInfo, 3600, 'category_tag');
        }
        return $cateInfo;
    }

    /**
     * 面包屑导航
     * @param $tag array 标签
     * @param $cate array 栏目信息
     * @return string
     */
    public function getBreadcrumb($tag, $cate = [])
    {
        $home_url = '/';
        $home_title = lang("Home");

        $catid = !empty($tag['catid']) ? $tag['catid'] : ($cate['id']??'');
        if (empty($catid)) {
            return '';
        }

        $cacheID = to_guid_string($tag);
        if (!app()->isDebug() && $cacheData = cache($cacheID)) {
            return $cacheData;
        }

        $str = '<a href="'.$home_url.'" class="'.$tag['class'].'">'.$home_title.'</a>';
        if (!is_numeric($catid)) {
            return $str;
        }

        $tempArr = [];
        $arr = $this->where(['id'=>$catid,'status'=>'normal'])->find();
        if (empty($arr)) {
            return $str;
        }

        $tempArr[] = $arr->toArray();
        while (true) { // 循环获取上级
            $arr = $this->where(['id'=>$arr['parent_id'],'status'=>'normal'])->find();
            if (empty($arr)) {
                break;
            }
            $tempArr[] = $arr->toArray();
        }

        for ($i = count($tempArr)-1; $i >= 0; $i--)
        {
            if ($catid!=$tempArr[$i]['id']) {
                $str .= $tag['symbol'].'<a href="'.$tempArr[$i]['url'].'" class="'.$tag['class'].'">'.$tempArr[$i]['title'].'</a>';
            } else {
                if ($tag['isclick']==1) {
                    $str .= $tag['symbol'].'<a href="'.$tempArr[$i]['url'].'" class="'.$tag['currentstyle'].'">'.$tempArr[$i]['title'].'</a>';
                } else {
                    $str .= $tag['symbol'].'<span class="'.$tag['currentstyle'].'">'.$tempArr[$i]['title'].'</span>';
                }
            }
        }
        // 结果进行缓存
        if (!app()->isDebug()) {
            cache($cacheID, $str, 86400, 'category_tag');
        }
        return $str;
    }
}