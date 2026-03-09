<?php
// +----------------------------------------------------------------------
// | HkCms 单页模型
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\model\cms;

use app\common\services\lang\LangService;
use think\facade\Cache;
use think\Model;

class Single extends Model
{
    public static $tablename;

    protected $name = 'archives';

    /**
     * 无需表前缀, 定义表名称
     * @param $name
     * @return $this
     */
    public function setTable($name)
    {
        $this->name = $name;
        self::$tablename = $name;
        return $this;
    }

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
     * save 方法下，新增，修改都会触发
     * @param Model $model
     * @return mixed|void
     */
    public static function onBeforeWrite($model)
    {
        $data = $model->getData();

        if (empty($data['publish_time'])) {
            $model->setAttr('publish_time', time());
        } else if (!is_numeric($data['publish_time'])) {
            $model->setAttr('publish_time', strtotime($data['publish_time']));
        }
    }

    /**
     * 生成url地址
     * @param $value
     * @param $data
     * @param bool $domain
     */
    protected function buildUrl($value, $data, $domain = false)
    {
        if (empty($value)) {
            $cateInfo = Category::where(['id'=>$data['category_id']])->find();
            if ($cateInfo) {
                $site = site(); // 获取伪静态规则
                if ($site['url_mode']==1 && !empty($site['url_rewrite'])) {
                    $cateInfo = $cateInfo->toArray();
                    $cateInfo['unq_id'] = 'url_single'.$data['id'].$data['model_id'];
                    $param = $cateInfo;
                } else {
                    $param = ['catname'=>$cateInfo['name']];
                    if ($site['content_lang_on']==1) {
                        $param['lang'] = $data['lang'];
                    }
                }
                return index_url('/index/lists', $param, '', $domain);
            }
        }
        return $value;
    }

    /**
     * 固定方法，用于栏目删除后的处理
     * @param $modelInfo
     * @param $categoryId
     * @param bool $force
     * @return bool
     */
    public function handleDel($modelInfo, $categoryId, $force=false)
    {
        if ($force==3) {
            $ids = \think\facade\Db::name('archives')->where(['category_id'=>$categoryId])->where('delete_time','not null')->column('id');
            \think\facade\Db::name('archives')->where(['category_id'=>$categoryId])->where('delete_time','not null')->delete();
            \think\facade\Db::name($modelInfo['tablename'])->whereIn('id',$ids)->delete();
        } else if ($force == 2) {
            (new Archives)->onlyTrashed()->where(['category_id'=>$categoryId])->update(['delete_time'=>null]);
        } else {
            (new Archives)->where(['category_id'=>$categoryId])->update(['delete_time'=>time()]);
        }
    }

    /**
     * 用于获取单条标签
     * @param $tag
     * @param $model
     * @return array
     */
    public function tagArcone($tag, $model)
    {
        return [];
    }

    /**
     * 固定方法。用于内容标签，不需要直接return [];
     * @param $tag
     * @param $catData
     * @param $modelData
     * @param $page \think\Paginator|mixed paginate 分页对象
     * @return array|mixed|object|\think\App
     */
    public function tagContent($tag, $catData, $modelData, &$page)
    {
        // 缓存设置
        $cacheTime = !empty($tag['cache']) && is_numeric($tag['cache']) ? intval($tag['cache']) : 3600;
        // 语言切换
        $tag['lang'] = app()->make(LangService::class)->getDefaultLang('content');
        if (site('content_lang_on')==1) {
            $tag['lang'] = app()->lang->getLangSet();
        }
        $cacheID = to_guid_string($tag);
        if (!env('APP_DEBUG') && $cacheData = Cache::get($cacheID)) {
            return $cacheData;
        }

        if (empty($tag['catid'])) {
            return [];
        }

        // 获取扩展字段
        $fields = \think\facade\Db::name('model_field')->where(['status'=>'normal','model_id'=>$modelData['id']])->select()->toArray();

        $extField = [];$fieldNew = [];$diyFieldArr = $tag['field']=='*' ? [] : explode(',', $tag['field']);
        foreach ($fields as $key=>$value) {
            if ($value['iscore']==0) {
                $extField[] = $value['field_name'];
            }
            if ($tag['field']!='*' && !in_array($value['field_name'],$diyFieldArr)) {
                continue;
            }
            $fieldNew[$key] = $value;
        }
        $extField = implode(',',$extField);
        $fields = $fieldNew;

        // 查看是否指定字段
        $field = $tag['field']=='*' ? 'a.*,x.id as xid,'.$extField : 'a.id,lang,style,views,url,category_id,status,'.$tag['field'];

        if (!empty($tag['inlist']) && $tag['inlist']==1) {
            $tag['order'] = empty($tag['order']) ? 'update_time desc' : $tag['order'];
            $tag['where'] = empty($tag['where']) ? [] : $tag['where'];
            $tag['num'] = intval($tag['num']);
            $categorys = get_category_sub($tag['catid']);
            if (!empty($tag['page'])) {
                $obj = $this->alias('a')->with(['category','model'])
                    ->join($modelData['tablename'].' x','a.id=x.id','LEFT')
                    ->where('category_id','in',$categorys)
                    ->where(['model_id'=>$modelData['id']])
                    ->where($tag['where'])->order($tag['order'])
                    ->append(['url','fullurl'])
                    ->field($field)
                    ->paginate(
                    [
                        'list_rows'=> $tag['num']??10,
                        'var_page' => 'page',
                        'path'=>'/index/lists'
                    ]
                );
                $array = $obj->toArray()['data'];
                $page = $obj;
            } else {
                $array = $this->alias('a')->with(['category','model'])
                    ->join($modelData['tablename'].' x','a.id=x.id','LEFT')
                    ->where('category_id','in',$categorys)
                    ->where(['model_id'=>$modelData['id']])
                    ->where($tag['where'])
                    ->limit($tag['num'])
                    ->order($tag['order'])
                    ->field($field)
                    ->select()
                    ->append(['url','fullurl'])
                    ->toArray();
            }
        } else {

            $tag['catid'] = empty($tag['catid']) ? $catData['id']??'':$tag['catid'];

            $obj = $this->alias('a')->with(['category','model'])->join($modelData['tablename'].' x','a.id=x.id','LEFT')->where(['model_id'=>$modelData['id']]);

            if (!empty($tag['catid']) && is_numeric($tag['catid'])) {
                $obj = $obj->where('category_id','=',$tag['catid']);
            } else if (!empty($tag['catid'])) { // 同时获取多个栏目的数据
                $obj = $obj->whereIn('category_id', $tag['catid']);
            }

            $array = $obj->field($field)->select()->append(['url','fullurl'])->toArray();
        }

        // 字段格式化
        foreach ($array as $key=>$value) {
            foreach ($fields as $k=>$v) {
                field_format($v, $array[$key]);
            }
        }

        // 结果进行缓存
        if (!env('APP_DEBUG') && empty($tag['page'])) {
            cache($cacheID, $array, $cacheTime, 'single_tag');
        }
        return $array;
    }

    /**
     * 栏目相对关联
     * @return \think\model\relation\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * 模型相对关联
     * @return Archives|\think\model\relation\BelongsTo
     */
    public function model()
    {
        return $this->belongsTo(\app\admin\model\cms\Model::class);
    }
}