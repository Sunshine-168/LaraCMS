<?php
// +----------------------------------------------------------------------
// | HkCms 留言板模型
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\model\cms;

use app\common\model\LangBind;
use app\common\services\lang\LangBindService;
use app\common\services\lang\LangService;
use think\Model;

class Guestbook extends Model
{
    public static $tablename;

    protected $autoWriteTimestamp = false;

    /**
     * 格式化
     * @param $value
     * @param $data
     * @return false|string
     */
    public function getCreateTimeAttr($value, $data)
    {
        return empty($data['create_time']) ? '-' : date('Y-m-d H:i:s', $data['create_time']);
    }

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
     * 获取栏目对应的表格信息
     * @param $categoryId
     * @param $categoryInfo
     * @return array|Model|null
     */
    public function getTableInfo($categoryId, &$categoryInfo=null)
    {
        if (empty($categoryId)) {
            abort(404, __('Parameter %s can not be empty',['category_id']));
        }
        $categoryInfo = Category::where(['id'=>$categoryId,'status'=>'normal'])->find();
        if (empty($categoryInfo)) {
            abort(404, __('Column information does not exist'));
        }
        $modelInfo = \app\admin\model\cms\Model::where(['status'=>'normal', 'id'=>$categoryInfo['model_id']])->find();
        if (empty($modelInfo)) {
            abort(404, __('Model information does not exist'));
        }
        return $modelInfo;
    }

    /**
     * 固定方法，用于栏目删除后的处理
     * @param $modelInfo
     * @param $categoryId
     * @param int $force 1=分类放入回收站，2=还原，3=销毁
     */
    public function handleDel($modelInfo, $categoryId, $force=1)
    {
        \think\facade\Db::name($modelInfo['tablename'])->where(['category_id'=>$categoryId])->delete();
    }

    /**
     * 用于模板标签调用
     * @param $tag
     * @param $catData
     * @param $modelData
     * @return array|string
     */
    public function tagContent($tag, $catData, $modelData, &$page)
    {
        return [];
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
     * 用于留言板标签
     * @return array|mixed|object|string|\think\App
     */
    public function tagGuestbook($tag)
    {
        if (empty($tag['catid'])) {
            return '';
        }

        $catid = $tag['catid'];
        // 缓存设置
        $cacheTime = !empty($tag['cache']) && is_numeric($tag['cache']) ? intval($tag['cache']) : 3600;

        if (site('content_lang_on')==1) {
            $tag['lang'] = app()->make(LangService::class)->getLang('content');
            $catid = app()->make(LangBindService::class)->getBindValue($catid, 'category');
        } else {
            // 语言切换，未开启内容语言使用默认的
            $tag['lang'] = app()->make(LangService::class)->getDefaultLang('content');
        }

        $cacheID = to_guid_string($tag);
        if (!app()->isDebug() && $cacheData = cache($cacheID)) {
            return $cacheData;
        }

        $cate = \app\admin\model\cms\Category::where(['id'=>$catid,'status'=>'normal','lang'=>$tag['lang']])->find();
        if (empty($cate)) {
            return '';
        }

        $model = \app\admin\model\cms\Model::where(['controller'=>'Guestbook','status'=>'normal'])->find($cate['model_id']);
        if (empty($model)) {
            return '';
        }

        $captcha = ['url'=>(string)index_url('/guestbook/captcha'),'field'=>'row[captcha]','type'=>'','input'=>'','btn'=>'','status'=>0];
        if (!empty($model['config'])) {
            $config = json_decode($model['config'], true);
            // 验证码判断
            if ($config['captcha']==1) { // 开启验证码
                $captcha['type'] = $config['type'];
                if ($config['type']=='mobile') {
                    $codelang = __('SMS verification code');
                    $captcha['btn'] = '<a href="#!" class="btn btn-primary" data-toggle="captcha_send" data-url="'.(string)index_url('/guestbook/send',['mid'=>$cate['model_id']]).'" data-type="mobile">'.__('Send verification code').'</a>';
                } else if ($config['type']=='email') {
                    $codelang = __('E-mail verification code');
                    $captcha['btn'] = '<a href="#!" class="btn btn-primary" data-toggle="captcha_send" data-url="'.(string)index_url('/guestbook/send',['mid'=>$cate['model_id']]).'" data-type="email">'.__('Send verification code').'</a>';
                } else {
                    $curl = $captcha['url'];
                    $codelang = __('verification code');
                    $captcha['btn'] = '<img src="'.$curl.'" style="width:100%" alt="captcha" onclick="this.src=\''.$curl.'?\'+Math.random();" />';
                }
                $captcha['input'] = '<input class="form-control form-captcha" name="row[captcha]" placeholder="'.$codelang.'" type="text">';
                $captcha['status'] = 1;
            }
        }

        // 获取表字段
        $data = [];
        $modelField = (new \app\admin\model\cms\ModelFieldBind)->getAllowField($catid, $cate['model_id'], $data);
        $field = [];
        $all = [];
        foreach ($modelField as $key=>$value) {
            $field[$value['field_name']] = "row[{$value['field_name']}]";
            $all[$value['field_name']] = $value;
        }
        $queryParam = ['catid'=>$catid];
        if (site('content_lang_on')==1) {
            $queryParam['lang'] = $tag['lang'];
        }
        $frmData = [
            'action'=>(string)url('index:/guestbook/index', $queryParam),
            'field'=>$field,
            'captcha'=>$captcha,
            'all'=>$all,
            'category'=>$cate->toArray()
        ];
        if (!app()->isDebug()) {
            cache($cacheID, $frmData, $cacheTime, 'guestbook_tag');
        }
        return $frmData;
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function model()
    {
        return $this->belongsTo(\app\admin\model\cms\Model::class);
    }
}