<?php
// +----------------------------------------------------------------------
// | HkCms 文章模型
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\model\cms;

use app\admin\model\Tags;
use app\common\services\lang\LangService;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Validate;
use think\Model;
use think\model\concern\SoftDelete;
use think\Paginator;

class Archives extends Model
{
    use SoftDelete;

    /**
     * 软删除字段
     * @var string
     */
    protected $deleteTime = 'delete_time';

    public static $tablename;

    /**
     * 指定自动时间戳类型
     * @var string
     */
    protected $autoWriteTimestamp = 'int';

    /**
     * 格式化发布日期
     * @param $value
     * @param $data
     * @return false|string
     */
    public function getPublishTimeTextAttr($value, $data)
    {
        return empty($data['publish_time']) ? '-' : date('Y-m-d H:i:s', $data['publish_time']);
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
        return $this->buildUrl($data['url'], $data, true);
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
            if (!empty($data['jumplink'])) {
                return $data['jumplink'];
            }
            $cateInfo = Category::where(['id'=>$data['category_id']])->cache(60)->find();
            if (!$cateInfo) {
                return '';
            }
            $cateInfo = $cateInfo->toArray();
            $site = site(); // 获取伪静态规则
            if ($data['status']!='normal') {
                // 去到预览页
                $param = ['category_id'=>$data['category_id'],'id'=>$data['id']];
                if ($site['content_lang_on']==1) {
                    $param['lang'] = $data['lang'];
                }
                return (string) url('/cms.archives/preview',$param);
            }
            if ($site['url_mode']==1 && !empty($site['url_rewrite'])) {
                $cateInfo['aid'] = $data['id'];
                $cateInfo['diyname'] = !empty($data['diyname']) ? $data['diyname']:'';
                $param = $cateInfo;
            } else {
                $param = ['id'=>$data['id'],'catname'=>$cateInfo['name']];
                if ($site['content_lang_on']==1) {
                    $param['lang'] = $data['lang'];
                }
            }
            return index_url($cateInfo['type']=='link'?'/index/lists':'/index/show', $param, '', $domain);
        } else if ($domain) {
            if (!\think\facade\Validate::regex($value, '/^https?:\/\/(([a-zA-Z0-9_-])+(\.)?)*(:\d+)?\//i')) {
                $value = app('request')->domain() . $value;
            }
        }
        return $value;
    }

    /**
     * 格式化
     * @param $value
     * @param $data
     * @return false|string
     */
    public function getDeleteTimeTextAttr($value, $data)
    {
        return empty($data['delete_time']) ? '-' : date('Y-m-d H:i:s', $data['delete_time']);
    }

    /**
     * save 方法下，新增，修改都会触发
     * @param Model $model
     * @return mixed|void
     */
    public static function onBeforeWrite($model)
    {
        //$publishTime = $model->getData('publish_time');

        $data = $model->getData();

        if (empty($data['publish_time'])) {
            $model->setAttr('publish_time', time());
        } else if (!is_numeric($data['publish_time'])) {
            $model->setAttr('publish_time', strtotime($data['publish_time']));
        }

        //$data = $model->getData();
        if (isset($data['flags']) && $data['flags']!=$model->getOrigin('flags')) {
            $flags = explode(',', $data['flags']);
            if (in_array('top', $flags)) {
                $model->setAttr('weigh', 1);
            } else if ($data['weigh']==1) {
                $model->setAttr('weigh', 100);
            }
        }
    }

    /**
     * 新增后的处理
     * @param Model $model
     */
    public static function onAfterInsert($model)
    {
        $data = $model->getData();

        // 新增后标签处理
        if (!empty($data['tags'])) {
            $oArr = explode(',', $data['tags']);
            foreach ($oArr as $key=>$value) {
                $info = Db::name('tags')->where(['title'=>$value])->find();
                if (empty($info)) {
                    $id = Db::name('tags')->insertGetId([
                        'title'=>$value,
                        'total'=>1,
                        'lang'=>$data['lang']??'',
                        'create_time'=>time(),
                        'update_time'=>time()
                    ]);
                } else {
                    $id = $info['id'];
                    Db::name('tags')->where(['title'=>$value])->inc('total')->update();
                }
                Db::name('tags_list')->insert([
                    'tags_id'=>$id,
                    'model_id'=>$data['model_id'],
                    'category_id'=>$data['category_id'],
                    'content_id'=>$data['id'],
                    'content_title'=>$data['title'],
                    'lang'=>$data['lang']??'',
                    'create_time'=>time()
                ]);
            }
        }
    }

    /**
     * 更新后的处理
     * @param Model $model
     * @return bool|void
     */
    public static function onAfterUpdate($model)
    {
        $origin = $model->getOrigin();
        $data = $model->getData();
        // 标签处理。源数据为空，更改的也是空则不处理
        if ((empty($data['tags']) && empty($origin['tags'])) || !isset($data['tags'])) {
            return true;
        } else if (empty($origin['tags']) && !empty($data['tags'])) { // 源数据为空，更改的有数据，则新增
            $oArr = explode(',', $data['tags']);
            foreach ($oArr as $key=>$value) {
                $info = Db::name('tags')->where(['title'=>$value])->find();
                if (empty($info)) {
                    $id = Db::name('tags')->insertGetId([
                        'title'=>$value,
                        'total'=>1,
                        'lang'=>$data['lang']??'',
                        'create_time'=>time(),
                        'update_time'=>time()
                    ]);
                } else {
                    $id = $info['id'];
                    Db::name('tags')->where(['title'=>$value])->inc('total')->update();
                }
                Db::name('tags_list')->insert([
                    'tags_id'=>$id,
                    'model_id'=>$data['model_id'],
                    'category_id'=>$data['category_id'],
                    'content_id'=>$data['id'],
                    'content_title'=>$data['title'],
                    'lang'=>$data['lang']??'',
                    'create_time'=>time()
                ]);
            }
        } else if (!empty($origin['tags']) && empty($data['tags'])) { // 源数据不为空，但是提交上来的空(清空)
            $oArr = explode(',', $origin['tags']);
            foreach ($oArr as $key=>$value) {
                $info = Db::name('tags')->where(['title'=>$value])->find();
                if (empty($info)) {
                    continue;
                } else {
                    if ($info['total']>0) {
                        Db::name('tags')->where(['title'=>$value])->dec('total')->update();
                        Db::name('tags_list')->where(['tags_id'=>$info['id'],'content_id'=>$data['id']])->delete();
                    }
                }
            }
        } else if (!empty($origin['tags']) && !empty($data['tags'])) { // 源数据不为空，提交上来的也不为空
            $oArr = explode(',', $origin['tags']);
            $arr = explode(',', $data['tags']);

            foreach ($oArr as $key=>$value) {
                if (!in_array($value, $arr)) {
                    $info = Db::name('tags')->where(['title'=>$value])->find();
                    if (empty($info)) {
                        continue;
                    } else {
                        if ($info['total']>0) {
                            Db::name('tags')->where(['title'=>$value])->dec('total')->update();
                            Db::name('tags_list')->where(['tags_id'=>$info['id'],'content_id'=>$data['id']])->delete();
                        }
                    }
                }
            }

            foreach ($arr as $key=>$value) {
                if (!in_array($value, $oArr)) {
                    $info = Db::name('tags')->where(['title'=>$value])->find();
                    if (empty($info)) {
                        $id = Db::name('tags')->insertGetId([
                            'title'=>$value,
                            'total'=>1,
                            'lang'=>$data['lang']??'',
                            'create_time'=>time(),
                            'update_time'=>time()
                        ]);
                    } else {
                        $id = $info['id'];
                        Db::name('tags')->where(['title'=>$value])->inc('total')->update();
                    }
                    Db::name('tags_list')->insert([
                        'tags_id'=>$id,
                        'model_id'=>$data['model_id'],
                        'category_id'=>$data['category_id'],
                        'content_id'=>$data['id'],
                        'content_title'=>$data['title'],
                        'lang'=>$data['lang']??'',
                        'create_time'=>time()
                    ]);
                }
            }
        }
    }

    /**
     * 文档模型删除事件
     * @param Model $model
     */
    public static function onAfterDelete($model)
    {
        $data = $model->getData();
        if (!empty($data['tags'])) {
            $oArr = explode(',', $data['tags']);
            foreach ($oArr as $key=>$value) {
                $info = Db::name('tags')->where(['title'=>$value])->find();
                if (empty($info)) {
                    continue;
                } else {
                    if ($info['total']>0) {
                        Db::name('tags')->where(['title'=>$value])->dec('total')->update();
                        Db::name('tags_list')->where(['tags_id'=>$info['id'],'content_id'=>$data['id']])->delete();
                    }
                }
            }
        }
    }

    /**
     * 文档模型恢复事件
     * @param Model $model
     */
    public static function onAfterRestore($model)
    {
        $data = $model->getData();
        if (!empty($data['tags'])) {
            $oArr = explode(',', $data['tags']);
            foreach ($oArr as $key=>$value) {
                $info = Db::name('tags')->where(['title'=>$value])->find();
                if (empty($info)) {
                    $id = Db::name('tags')->insertGetId([
                        'title'=>$value,
                        'total'=>1,
                        'lang'=>$data['lang']??'',
                        'create_time'=>time(),
                        'update_time'=>time()
                    ]);
                } else {
                    $id = $info['id'];
                    Db::name('tags')->where(['title'=>$value])->inc('total')->update();
                }
                Db::name('tags_list')->insert([
                    'tags_id'=>$id,
                    'model_id'=>$data['model_id'],
                    'category_id'=>$data['category_id'],
                    'content_id'=>$data['id'],
                    'content_title'=>$data['title'],
                    'lang'=>$data['lang']??'',
                    'create_time'=>time()
                ]);
            }
        }
    }

    /**
     * 查询后，使用与模型->select()/find()后执行save，保证表名称保持一致。
     * @param Model $model
     */
    public static function onAfterRead(Model $model)
    {
        if (!empty(self::$tablename)) {
            $model->name = $model->getName() != self::$tablename ? self::$tablename:$model->getName();
        }
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
            abort(404, lang('%s not null',['category_id']));
        }
        $categoryInfo = Category::where(['id'=>$categoryId,'status'=>'normal'])->find();
        if (empty($categoryInfo)) {
            abort(404, lang('Column information does not exist'));
        }
        $modelInfo = \app\admin\model\cms\Model::where(['status'=>'normal', 'id'=>$categoryInfo['model_id']])->find();
        if (empty($modelInfo)) {
            abort(404, lang('Model information does not exist'));
        }
        return $modelInfo;
    }

    /**
     * 获取附表内容并格式化
     * @return array|bool
     */
    public function moreInfo()
    {
        $fields = Db::name('model_field')->where(['status'=>'normal','model_id'=>$this->model->id])->select()->toArray();

        $obj = Db::name($this->model->tablename)->where(['id'=>$this->getAttr('id')])->find();
        if (empty($obj)) {
            return $this->toArray();
        }

        if (isset($obj['content'])) {
            $content = htmlspecialchars_decode($obj['content']);

            // 内容分页
            $page = request()->param('page');
            $page = max(1, $page);
            $arr = explode('#page#', $content);
            $content = $arr[$page-1] ?? $arr[0];
            $this->__page__ = \app\common\library\Bootstrap::make([],1,(int)$page,count($arr),false,['path'=>'/index/show','query'=>['page'=>'','id'=>$this->getAttr('id')]]);

            // *内容标签内链*
            if (app()->isDebug()) {
                $tags = (new Tags)->where(['autolink'=>1])->select();
            } else {
                $tags = (new Tags)->where(['autolink'=>1])->cache("autolink_cache", 7200, 'taglist_tag')->select();
            }
            $box = [];
            // 取出所有标签防止被替换
            $content = preg_replace_callback('/(<(?!\d+).*?>)/i', function ($match) use (&$box){
                return '<'.array_push($box, $match[1]).'>';
            }, $content);

            // 先全部替换成代号，防止已替换的词跟标签重导致多次替换
            foreach ($tags as $key=>$value) {
                $content = preg_replace_callback('/('.preg_quote($value['title']).')/i', function ($match) use($value, &$box) {
                    return '<'.array_push($box, [$match[0],(string)$value->getUrlAttr($match[0], $value->toArray())]).'>';
                },$content,3); // 最多替换3次
            }

            $obj['content'] = preg_replace_callback('/<(\d+)>/', function ($match) use($box) {
                $val = $box[$match[1]-1];
                if (!is_array($val)) {
                    return $val;
                }
                return '<a href="'.$val[1].'" class="autolink" data-type="tags">'.$val[0].'</a>';
            },$content); // 最多替换3次
        }

        $info = array_merge($this->toArray(), $obj);
        foreach ($fields as $k=>$v) {
            field_format($v, $info);
        }

        // 兼容旧版模板
        foreach ($fields as $k=>$v) {
            field_format($v, $obj);
        }
        $info['more'] = $obj;

        $newFields = [];
        foreach ($fields as $v) {
            $newFields[$v['field_name']] = $v;
        }
        $info['fields'] = $newFields;


        return $info;
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
     * 固定方法，用于栏目删除后的处理
     * @param $modelInfo
     * @param $categoryId
     * @param int $force 1=分类放入回收站，2=还原，3=销毁
     */
    public function handleDel($modelInfo, $categoryId, $force=1)
    {
        if ($force==3) {
            $ids = \think\facade\Db::name('archives')->where(['category_id'=>$categoryId])->where('delete_time','not null')->column('id');
            \think\facade\Db::name('archives')->where(['category_id'=>$categoryId])->where('delete_time','not null')->delete();
            \think\facade\Db::name($modelInfo['tablename'])->whereIn('id',$ids)->delete();
        } else if ($force == 2) {
            //(new Archives)->onlyTrashed()->where(['category_id'=>$categoryId])->update(['delete_time'=>null]);
            $data = (new Archives)->onlyTrashed()->where(['category_id'=>$categoryId])->select();
            foreach ($data as $key=>$value) {
                $value->restore();
            }
        } else {
            //(new Archives)->where(['category_id'=>$categoryId])->update(['delete_time'=>time()]);

            $data = (new Archives)->where(['category_id'=>$categoryId])->select();
            foreach ($data as $key=>$value) {
                $value->delete();
            }
        }
    }

    /**
     * 获取列表，用于内容标签
     * @param $tag
     * @param $catData
     * @param $modelData
     * @param $page \think\Paginator|mixed paginate 分页对象
     * @return array|mixed|object|\think\App
     */
    public function tagContent($tag, $catData, $modelData, &$page)
    {
        $tag['order'] = !empty($tag['order']) ? $tag['order'] : 'weigh asc,id desc';
        $tag['flag'] = !empty($tag['flag']) ? $tag['flag'] : '';

        // 语言切换
        $tag['lang'] = app()->make(LangService::class)->getDefaultLang('content');
        if (site('content_lang_on')==1) {
            $tag['lang'] = app()->lang->getLangSet();
        }

        // 增加缓存非分页数据
        if (!$tag['page']) {
            $cacheID = to_guid_string($tag);
            if (!env('APP_DEBUG') && $cacheData = Cache::get($cacheID)) {
                return $cacheData;
            }
        }

        // 默认条件
        $map = [['status','=','normal'],['lang','=',$tag['lang']]];

        // 检查条件字段是否加了id
        if (empty($tag['aid']) && empty($tag['aids'])) {
            if (is_string($tag['where'])) {
                $sortArr = explode(' ',$tag['where']);
                foreach ($sortArr as $key=>$item) {
                    $sortArr[$key] = stripos($item, "id")===0 ? 'a.'.$item : $item;
                }
                $tag['where'] = implode(' ', $sortArr);
            } else if (is_array($tag['where'])) {
                foreach ($tag['where'] as $key=>$value) {
                    if ($key=='id') {
                        $tag['where']['a.id'] = $value;
                        unset($tag['where']['id']);
                        break;
                    }
                }
            }
        }

        $obj = $this->alias('a')->with(['category','model'])->where($tag['where']);
        // 文档属性
        if (!empty($tag['flag'])) {
            $sql = [];
            if (stripos($tag['flag'], ' and ')) {
                $flag = explode(' and ', $tag['flag']);
                foreach ($flag as $key=>$val) {
                    $sql[] = "find_in_set('{$val}',flags)";
                }
                if ($sql) {
                    $sql = implode(' and ', $sql);
                }
            } else if (stripos($tag['flag'], ' or ')) {
                $flag = explode(' or ', $tag['flag']);
                foreach ($flag as $key=>$val) {
                    $sql[] = "find_in_set('{$val}',flags)";
                }
                if ($sql) {
                    $sql = "(".implode(' or ', $sql).")";
                }
            } else {
                $sql = "find_in_set('{$tag['flag']}',flags)";
            }
            $obj = $obj->where($sql);
        }

        if ($tag['aid']) { // 指定单条文档
            $obj = $obj
                ->where($map)
                ->where('id','=',$tag['aid'])
                ->order($tag['order'])
                ->append(['publish_time_text','fullurl'])
                ->field($tag['field'])
                ->select()
                ->toArray();
            if (empty($obj)) {
                return [];
            }
            $array = $obj[0];
            $modelInfo = \app\admin\model\cms\Model::where(['status'=>'normal','id'=>$array['model']['id']])->cache(app()->isDebug()?false:300)->find();
            if (empty($modelInfo)) {
                return [];
            }
            $info = Db::name($modelInfo['tablename'])->find($array['id']);
            $array = array_merge($array, empty($info)?[]:$info);
            // 获取扩展字段
            if (app()->isDebug()) {
                $fields = \think\facade\Db::name('model_field')->where(['status'=>'normal','model_id'=>$array['model']['id']])->select()->toArray();
            } else {
                $fields = \think\facade\Db::name('model_field')->where(['status'=>'normal','model_id'=>$array['model']['id']])->cache('model_field'.$array['model']['id'], 3600, 'model_field')->select()->toArray();
            }
            $array = [$array];
        } else if ($tag['aids']) { // 指定多条文档
            $obj = $obj
                ->where($map)
                ->whereIn('id', $tag['aids'])
                ->order($tag['order'])
                ->append(['publish_time_text','fullurl'])
                ->field($tag['field'])
                ->select()
                ->toArray();
            if (empty($obj)) {
                return [];
            }

            $array = $obj[0];
            $modelInfo = \app\admin\model\cms\Model::where(['status'=>'normal','id'=>$array['model']['id']])->cache(app()->isDebug()?false:300)->find();
            if (empty($modelInfo)) {
                return [];
            }

            $newArray = [];
            foreach ($obj as $key=>$value) {
                $info = Db::name($modelInfo['tablename'])->find($value['id']);
                $array = array_merge($value, empty($info)?[]:$info);
                $newArray[] = $array;
            }

            // 获取扩展字段
            if (app()->isDebug()) {
                $fields = \think\facade\Db::name('model_field')->where(['status'=>'normal','model_id'=>$array['model']['id']])->select()->toArray();
            } else {
                $fields = \think\facade\Db::name('model_field')->where(['status'=>'normal','model_id'=>$array['model']['id']])->cache('normal_model_field'.$array['model']['id'], 3600, 'model_field')->select()->toArray();
            }
            $array = $newArray;
        } else {
            // 查出模型数据
            $tag['model'] = empty($tag['model']) ? $modelData['id']??'':$tag['model'];
            $tag['catid'] = empty($tag['catid']) ? $catData['id']??'':$tag['catid'];
            $map[] = ['model_id','=',$modelData['id']];

            // 获取请求的排序、数据筛选
            $sort = app()->request->get('sort');
            $order = app()->request->get('order');
            if ($sort!='_default' && Validate::is($sort, 'alphaDash')) {
                // 获取扩展字段
                if (app()->isDebug()) {
                    $mf = ModelField::where(['field_name'=>$sort])->find();
                } else {
                    $mf = ModelField::where(['field_name'=>$sort])->cache('field_name_model_field', 7200, 'model_field')->find();
                }
                if (!empty($mf)) {
                    $tag['order'] = [($mf->iscore==1?'':'x.').$sort=>$order=='desc'?$order:'asc'];
                }
            }
            
            $param = app()->request->get();
            // 启用筛选
            if ($tag['filter']) {
                $filter = array_diff_key($param, array_flip(['sort','order','lang']));
                // 获取扩展字段
                if (app()->isDebug()) {
                    $filterField = ModelField::where(['model_id'=>$tag['model'],'is_filter'=>1])->select()->toArray();
                } else {
                    $filterField = ModelField::where(['model_id'=>$tag['model'],'is_filter'=>1])->cache('is_filter_model_field', 3660, 'model_field')->select()->toArray();
                }
                foreach ($filterField as $value) {
                    if (isset($filter[$value['field_name']]) && $filter[$value['field_name']]!='') {
                        $tempArr = explode(',', $filter[$value['field_name']]);
                        // 匹配是否是筛选的值
                        if (!empty($value['setting'])) {
                            $setting = json_decode($value['setting'], true);
                            if (!empty($setting['filter_option'])) {
                                $option = json_decode($setting['filter_option'], true);
                            } else if (!empty($value['data_list'])) {
                                $option = $value['data_list'];
                            }
                        } else if (!empty($value['data_list'])) {
                            $option = $value['data_list'];
                        }
                        if (!empty($option)) {
                            $option = array_flip($option);
                            foreach ($tempArr as $k=>$v) {
                                if (!in_array($v,$option)) {
                                    unset($tempArr[$k]);
                                }
                            }
                            if (count($tempArr)>1) {
                                if (in_array($value['form_type'],['checkbox','selects','selectpage'])) {
                                    $obj = $obj->where(function($query) use($value,$tempArr) {
                                        foreach ($tempArr as $whereValue) {
                                            $query->whereFindInSet(($value['iscore']!=1?'x.':'').$value['field_name'], $whereValue, 'or');
                                        }
                                    });
                                } else {
                                    $map[] = [($value['iscore']!=1?'x.':'').$value['field_name'],'in',implode(',',$tempArr)];
                                }
                                $param = array_merge($param, [$value['field_name']=>implode(',',$tempArr)]);
                            } else if (count($tempArr)==1) {
                                if (in_array($value['form_type'],['checkbox','selects','selectpage'])) {
                                    $obj = $obj->whereFindInSet(($value['iscore']!=1?'x.':'').$value['field_name'],$tempArr[0]);
                                } else {
                                    $map[] = [($value['iscore']!=1?'x.':'').$value['field_name'],'=',$tempArr[0]];
                                }
                                $param = array_merge($param, [$value['field_name']=>$tempArr[0]]);
                            }
                        }
                    }
                }
            }

            // 获取栏目ID，包含下级。
            if (!empty($tag['catid']) && is_numeric($tag['catid']) && $tag['insub']==1) {
                $catIdArr = get_category_sub($tag['catid'], false, ['status'=>'normal','model_id'=>$tag['model']]);
                if (!empty($catIdArr)) {
                    $catIdArr[] = $tag['catid'];
                    $obj = $obj->where(function ($query) use($tag, $catIdArr) {
                        $query->where('category_id','in', $catIdArr)->whereOr('find_in_set("'.$tag['catid'].'",category_ids)');
                    });
                } else {
                    $obj = $obj->where('(category_id = '.$tag['catid'].' or find_in_set("'.$tag['catid'].'",category_ids))');
                }
            } else if (!empty($tag['catid']) && is_numeric($tag['catid']) && $tag['insub']==0) {
                // 栏目与副栏目
                $obj = $obj->where('(category_id = '.$tag['catid'].' or find_in_set("'.$tag['catid'].'",category_ids))');
            } else if (!empty($tag['catid'])) { // 同时获取多个栏目的数据
                $obj = $obj->where(function ($query) use($tag) {
                    $query->where('category_id','in', $tag['catid']);
                    $catIdArr = explode(',',(string)$tag['catid']);
                    foreach ($catIdArr as $key=>$value) {
                        $query->whereOr('find_in_set("'.$value.'",category_ids)');
                    }
                });
            }

            // 获取扩展字段
            if (app()->isDebug()) {
                $fields = \think\facade\Db::name('model_field')->where(['status'=>'normal','model_id'=>$modelData->id])->select()->toArray();
            } else {
                $fields = \think\facade\Db::name('model_field')->where(['status'=>'normal','model_id'=>$modelData->id])->cache('normal_model_field'.$modelData->id, 3600, 'model_field')->select()->toArray();
            }
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

            if ($tag['page'] && empty($tag['tagid'])) { // 开启分页
                $obj = $obj
                    ->join($modelData->tablename.' x','a.id=x.id','LEFT')
                    ->where($map)
                    ->order($tag['order'])
                    ->field($field)
                    ->append(['publish_time_text','fullurl'])
                    ->paginate([
                        'list_rows'=> intval($tag['num']),
                        'var_page' => 'page',
                        'query' => $param,
                        'path'=>'/index/lists'
                    ], false);

                $array = $obj->toArray()['data'];
                $page = $obj;
            } else if ($tag['page'] && !empty($tag['tagid'])) { // 瀑布流分页
                $page = Paginator::getCurrentPage('page');
                $obj = $obj->join($modelData->tablename.' x','a.id=x.id','LEFT')
                    ->where($map)
                    ->order($tag['order'])
                    ->field($field)
                    ->append(['publish_time_text','fullurl'])
                    ->page($page, intval($tag['num']))
                    ->select();
                $array = $obj->toArray();

                $tag['key'] = md5($tag["tagid"]);
                $total = cache($tag['key']);
                if (empty($total['total'])) {
                    // 数量
                    $total = $obj->count();
                } else {
                    $total = $total['total'];
                }
                $tag['total'] = $total;
                $page = $tag;
                $page['map_param'] = $param;
                cache($tag['key'],$page);
            } else { // 不分页
                // 限制结果
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

                $array = $obj
                    ->join($modelData->tablename.' x','a.id=x.id','LEFT')
                    ->where($map)
                    ->order($tag['order'])
                    ->limit($offset,$length)
                    ->field($field)
                    ->append(['publish_time_text','fullurl'])
                    ->select()
                    ->toArray();
            }
        }

        // 字段格式化
        foreach ($array as $key=>$value) {
            foreach ($fields as $k=>$v) {
                field_format($v, $array[$key]);
                // 兼容旧版模板
                $array[$key]['more'][$v['field_name']] = $array[$key][$v['field_name']];
            }
        }

        if (!$tag['page']) {
            // 结果进行缓存
            if (!env('APP_DEBUG')) {
                // 缓存设置
                $cacheTime = !empty($tag['cache']) && is_numeric($tag['cache']) ? intval($tag['cache']) : 3600;
                Cache::tag('archives_content_tag')->set($cacheID, $array, $cacheTime);
            }
        }
        return $array;
    }

    /**
     * 用于获取单条标签
     * @param $tag
     * @param $model
     * @return array
     */
    public function tagArcone($tag, $model)
    {
        if (empty($tag['aid']) || !is_numeric($tag['aid'])) {
            return [];
        }

        $cacheTime = !empty($tag['cache']) && is_numeric($tag['cache']) ? intval($tag['cache']) : 3600;

        if (!empty($tag['more']) && $tag['more']==1 && $model['type']=='more' && $model['allow_single']!=1) { // 副表
            $info = $this->alias('a')->with(['category','model'])
                ->join($model->tablename.' x','a.id=x.id','LEFT')
                ->where('x.id',$tag['aid']);
        } else {
            $info = $this->with(['category','model'])
                ->where('id',$tag['aid']);
        }

        if (!env('APP_DEBUG')) {
            $info = $info->cache($cacheTime);
        }

        $info = $info->append(['publish_time_text','url'])->find();
        if (empty($info)) {
            return [];
        }

        $array = [$info->toArray()];
        // 获取扩展字段
        if (app()->isDebug()) {
            $fields = \think\facade\Db::name('model_field')->where(['status'=>'normal','model_id'=>$model->id])->toArray();
        } else {
            $fields = \think\facade\Db::name('model_field')->where(['status'=>'normal','model_id'=>$model->id])->cache('normal_model_field', 3600, 'model_field')->select()->toArray();
        }
        // 字段格式化
        foreach ($array as $key=>$value) {
            foreach ($fields as $k=>$v) {
                field_format($v, $array[$key]);
                // 兼容旧版
                if ($v['iscore']!=1 && !empty($tag['more']) && $tag['more']==1 && $model['type']=='more' && $model['allow_single']!=1) {
                    $array[$key]['more'][$v['field_name']] = $array[$key][$v['field_name']];
                }
            }
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

    /**
     * 关联副表
     * @return \think\model\relation\HasOne
     */
//    public function moreInfo()
//    {
//        \app\admin\model\cms\ArchivesX::$tablename = self::$tablename;
//        return $this->hasOne(\app\admin\model\cms\ArchivesX::class,'id','id');
//    }
}