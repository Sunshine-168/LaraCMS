<?php
// +----------------------------------------------------------------------
// | HkCms 全站配置管理
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\controller\routine;

use app\admin\controller\BaseController;
use app\common\services\config\ConfigService;
use app\common\services\lang\LangBindService;
use app\common\services\lang\LangService;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Validate;

class Config extends BaseController
{
    /**
     * @var \app\admin\model\routine\Config
     */
    protected $model;

    protected $middleware = [
        'login',
        'auth' => ['except'=>['fieldGroup']]
    ];

    /**
     * 初始化操作
     */
    public function initialize()
    {
        parent::initialize();

        $this->model = new \app\admin\model\routine\Config();
    }

    public function index()
    {
        $group = $this->model->where(['name'=>'group'])->value('value');
        $group = json_decode($group, true);

        $list = $this->model->where('group','<>','group')->where('lang', '=','-1')->whereOr('lang','=',$this->contentLang)->order(['weigh'=>'asc','id'=>'asc'])->select()->toArray();
        $newList = [];
        foreach ($list as $k=>$v) {
            if (in_array($v['name'],['category_format','content_format','url_file'])) {
                continue;
            }
            if ($v['name']=='cloud_username' && !empty($v['value'])) {
                $v['value'] = \libs\crypto\Crypto::decryptWithPassword($v['value'], $v['data_list']??'',false);
            }
            if ($v['name']!='cloud_username' && !empty($v['data_list'])){
                $v['data_list'] = json_decode($v['data_list'],true);
            }
            if ($v['type'] == 'array') {
                $v['value_array'] = json_decode($v['value'],true);
                $v['value_array'] = is_array($v['value_array'])?$v['value_array']:[];
            }

            $newList[$v['group']][] = $v;
        }

        $this->view->layout(false);

        foreach ($newList as $key=>&$v) {
            // 兼容旧版数据
            if ($key=='language') {
                $v = $this->view->fetch('field', ['data'=>$v]).$this->view->fetch('field_lan', ['data'=>[]]);
            } else {
                $v = $this->view->fetch('field', ['data'=>$v]);
            }
        }

        $this->view->layout(true);
        $this->view->assign('lists',$newList);
        $this->view->assign('group',$group);
        $this->view->assign('tabs_page', $this->request->param('tabs_page','basics'));

        // 其他页转过来添加返回按钮
        $ref = $this->request->get('referer','');
        $this->view->assign('backBtn', $ref?1:0);
        return $this->view->fetch();
    }

    /**
     * 添加站点配置
     * @return mixed|string|void
     * @throws \Exception
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");

            $params['is_default'] = 0;
            $this->validate($params, [
                'type|'.__('Type') => 'require|alphaDash',
                'group|'.__('Grouping') => 'require',
                'name|'.__('Field') => 'require|max:20',
                'title|'.__('Title') => 'require',
                'rules|'.__('Rule') => 'max:255',
                'tips|'.__('Tips') => 'max:255',
                'error_tips|'.__('Error tips') => 'max:255',
                'extend|'.__('HTML attr') => 'max:500',
            ]);

            // 判断是否已经存在
            if ($this->model->where(['name'=>$params['name']])->whereIn('lang',[$this->contentLang,-1])->value('id')) {
                $this->error(__('%s existed',[__('Field')]));
            }
            if (in_array($params['name'], ['root_domain','root_file','static_path','app_debug','content_lang','index_lang_list','admin_lang_list'])) {
                $this->error(__('%s existed',[__('Field')]));
            }

            // 最大数量判断
            if (in_array($params['type'], ['images','downfiles']) && !is_numeric($params['max_number'])) {
                $this->error(__('Maximum number format error'));
            }
            if ('number' == $params['type'] && !is_numeric($params['value'])) {
                $this->error(__("The default value must be a number"));
            } else if ('datetime' == $params['type'] && !Validate::dateFormat($params['value'],'Y-m-d H:i:s')) {
                $this->error(__("The default value must be in date time format"));
            } else if ('date' == $params['type'] && !Validate::dateFormat($params['value'],'Y-m-d')) {
                $this->error(__('The default value must be date'));
            }

            if (in_array($params['type'], ['radio','checkbox','select','selects'])) {
                $data_list = explode("\r\n", $params['data_list']);
                $datalist = [];
                foreach ($data_list as $k => $v) {
                    $arr = explode('|',$v);
                    if (count($arr) != 2) {
                        $this->error(__('Option list format error'));
                    }
                    $datalist[$arr[0]] = $arr[1];
                }
                $params['data_list'] = json_encode($datalist);
            }

            // 动态下拉类型
            if ('selectpage'==$params['type']) {
                if (empty($params['data_list'])) {
                    return '';
                }
                if (!empty($params['data_list']['param'])) {
                    $param = [];
                    $tmpKey = 1;
                    foreach ($params['data_list']['param'] as $key=>$item) {
                        if ($tmpKey==$key) {
                            continue;
                        }
                        if (isset($params['data_list']['param'][$key+1])) {
                            $param['custom'][$item] = $params['data_list']['param'][$key+1];
                            $tmpKey = $key+1;
                        }
                    }
                    $params['data_list']['param'] = $param;
                }
                if (!empty($params['data_list']['search-field']) && is_array($params['data_list']['search-field'])) {
                    $params['data_list']['search-field'] = implode(',',$params['data_list']['search-field']);
                }
                $params['data_list'] = json_encode($params['data_list']);
            }

            // 键值对判断
            if ('array'==$params['type']) {
                $params['data_list'] = json_encode(['key'=>empty($params['key_name_alias'])?'键名':$params['key_name_alias'],'value'=>empty($params['key_value_alias'])?'键值':$params['key_value_alias']]);
                unset($params['key_name_alias']);
                unset($params['key_value_alias']);
            }
            $params['lang'] = $this->contentLang;
            $params['setting'] = !empty($params['setting']) ? json_encode($params['setting']):null;
            // 获取排序
            $max = Db::name('config')->where(['group'=>$params['group']])->max('weigh');
            $params['weigh'] = $max+1;
            $this->model->save($params);
            Cache::tag(ConfigService::CACHE_TAG)->clear();
            $this->success();
        }

        $table = Db::getTables();
        foreach ($table as $key=>$value) {
            if (strpos($value,env('DATABASE.PREFIX'))===false) {
                unset($table[$key]);
                continue;
            }
            $table[$key] = preg_replace('/'.env('DATABASE.PREFIX').'/','',$value,1);
        }
        $this->view->assign('table', $table);
        return $this->view->fetch();
    }

    /**
     * 修改配置
     * @param null $id
     * @return mixed|void
     */
    public function edit($id = null)
    {
        $data = input('post.','',null);

        // 获取修改前的多语言开关
        $oldcontent_lang_on = $this->model->where(['name'=>'content_lang_on'])->value('value');

        //修改基础配置、邮件配置数据
        foreach ($data['row'] as $k=>$v) {
            if ($k=='cloud_password' && $v=='--password--') {
                continue;
            }
            if ($k=='cloud_username' && !empty($v)) {
                $grs = get_random_str(6);
                $v = \libs\crypto\Crypto::encryptWithPassword($v, $grs,false);
                $this->model->where('name',$k)->whereIn('lang',[$this->contentLang,-1])->update(['data_list'=>$grs]);
            }
            if ($k=='cloud_password' && !empty($v)) {
                $grs = get_random_str(6);
                $v = \libs\crypto\Crypto::encryptWithPassword($v, $grs,false);
                $this->model->where('name',$k)->whereIn('lang',[$this->contentLang,-1])->update(['data_list'=>$grs]);
            }
            if ($k=='cdn' && !empty($v) && !Validate::is($v, 'url')) {
                $this->error(__('The site URL format is incorrect'));
            }
            if (is_array($v)) {
                $v = implode('|',$v);
            }
            $this->model->where('name',$k)->whereIn('lang',[$this->contentLang,-1])->update(['value'=>$v]);
        }

        if (isset($data['group']) && isset($data['group']['key']) && isset($data['group']['value'])) {
            $keys = $data['group']['key'];
            $values = $data['group']['value'];
            $add = [];
            foreach ($keys as $key=>$value) {
                $add[$value] = $values[$key];
            }
            $this->model->where(['name'=>'group'])->save(['value'=>json_encode($add)]);
        }

        // 开启内容多语言，同步语言
        if ($data['row']['content_lang_on']==1 && $oldcontent_lang_on==2) {
            $curLang = app()->make(LangService::class)->getDefaultLang('content');
            // 同步系统配置文件
            $tmpCount = $this->model->where(['is_default'=>1])->where('lang','not in',[$curLang,-1])->count();
            if ($tmpCount==0) { // 没有其他语言数据，则新增语言
                // 获取开启多语言的单语言数据
                $refData = $this->model->where('lang','=', $curLang)->select()->toArray();
                foreach ($refData as $value) {
                    $strName = [];
                    if ($value['name']!='icp' && $value['name']!='logo' && $value['name']!='favicon') {
                        $strName = ['value'];
                    }
                    lang_content_add('config', $value, $strName,false);
                }
            }

            // 同步非系统配置，进行语言绑定
            $tmpCount = $this->model->where(['is_default'=>0])->count();
            if ($tmpCount>0) {
                $refData = $this->model->where('is_default','=',0)->where('lang','=',$curLang)->select()->toArray();
                if ($refData) {
                    $langbindArr = Db::name('lang_bind')->where(['table'=>'config'])->select()->toArray();
                    $newLangbind = [];
                    foreach ($langbindArr as $value) {
                        if ($value['value_id']) {
                            $newLangbind[$value['lang'].$value['value_id']] = $value;
                        } else {
                            $newLangbind[$value['lang'].$value['main_id']] = $value;
                        }
                    }
                    foreach ($refData as $value) {
                        if ($newLangbind && !isset($newLangbind[$value['lang'].$value['id']])) { // 有存在记录
                            lang_content_add('config', $value, ['value'],true);
                        } else if (empty($newLangbind)) {
                            // 第一次记录
                            lang_content_add('config', $value, ['value'],true);
                        }
                    }
                }
            }

            // 同步栏目
            if (Db::name('category')->count()) {
                $refData = Db::name('category')->where('lang','=', $curLang)->select()->toArray();
                $langbindArr = Db::name('lang_bind')->where(['table'=>'category'])->select()->toArray();
                $newLangbind = [];
                foreach ($langbindArr as $value) {
                    if ($value['value_id']) {
                        $newLangbind[$value['lang'].$value['value_id']] = $value;
                    } else {
                        $newLangbind[$value['lang'].$value['main_id']] = $value;
                    }
                }
                foreach ($refData as $value) {
                    if (empty($newLangbind) || ($newLangbind && !isset($newLangbind[$value['lang'].$value['id']]))) { // 有存在记录
                        $value['update_time'] = time();
                        $value['create_time'] = time();
                        $idArr = lang_content_add('category', $value, ['title']);
                        if (!app('user')->hasSuperAdmin()) {
                            if (!empty($idArr)) {
                                foreach ($idArr as $v) {
                                    Db::name('category_priv')->insert(['category_id'=>$v,'auth_group_id'=>app('user')->getUserGroupId()[0]]);
                                }
                            }
                        }
                    }
                }
            }

            // 同步站点模块
            if (Db::name('recommend')->count()) {
                $refData = Db::name('recommend')->where('lang','=', $curLang)->select()->toArray();
                $langbindArr = Db::name('lang_bind')->where(['table'=>'recommend'])->select()->toArray();
                $newLangbind = [];
                foreach ($langbindArr as $value) {
                    if ($value['value_id']) {
                        $newLangbind[$value['lang'].$value['value_id']] = $value;
                    } else {
                        $newLangbind[$value['lang'].$value['main_id']] = $value;
                    }
                }
                foreach ($refData as $value) {
                    if (empty($newLangbind) || ($newLangbind && !isset($newLangbind[$value['lang'].$value['id']]))) { // 有存在记录
                        $value['update_time'] = time();
                        $value['create_time'] = time();

                        $ids = lang_content_add('recommend', $value, ['remark'],true);

                        $arr = \app\admin\model\cms\Recommend::whereIn('id', $ids)->where('id','<>',$value['id'])->select()->toArray();
                        if ($value['type']==4) {
                            // 内容数据
                            foreach ($arr as $r) {
                                $valueId = json_decode($r['value_id'], true);
                                if (empty($valueId['column'])) {
                                    continue;
                                }
                                // 栏目id
                                $column = explode(',', $valueId['column']);
                                $tmpColumn = [];
                                foreach ($column as $k=>$v) {
                                    $tmpColumn[] = app()->make(LangBindService::class)->getBindValue($v,'category',$r['lang']);
                                }
                                // 保存新的栏目数据
                                $valueId['column'] = implode(',', $tmpColumn);
                                $valueId = json_encode($valueId);
                                \app\admin\model\cms\Recommend::where(['id'=>$r['id']])->save(['value_id'=>$valueId]);
                            }
                        } else {
                            // banner 表处理
                            $banner = Db::name('banner')->where('recommend_id','=', $value['id'])->select()->toArray();
                            foreach ($banner as $k=>$v) {
                                foreach ($arr as $r) {
                                    unset($v['id']);
                                    $v['recommend_id'] = $r['id'];
                                    $v['lang'] = $r['lang'];
                                    $v['update_time'] = time();
                                    $v['create_time'] = time();
                                    \app\admin\model\cms\Banner::create($v);
                                }
                            }
                        }
                    }
                }
            }

            // 同步文档属性
            if (Db::name('flags')->count()) {
                $refData = Db::name('flags')->where('lang','=',$curLang)->select()->toArray();
                $langbindArr = Db::name('lang_bind')->where(['table'=>'flags'])->select()->toArray();
                $newLangbind = [];
                foreach ($langbindArr as $value) {
                    if ($value['value_id']) {
                        $newLangbind[$value['lang'].$value['value_id']] = $value;
                    } else {
                        $newLangbind[$value['lang'].$value['main_id']] = $value;
                    }
                }
                foreach ($refData as $value) {
                    if (empty($newLangbind) || ($newLangbind && !isset($newLangbind[$value['lang'].$value['id']]))) { // 有存在记录
                        $value['update_time'] = time();
                        $value['create_time'] = time();
                        lang_content_add('flags', $value, ['title']);
                    }
                }
            }
        }
        Cache::tag(ConfigService::CACHE_TAG)->clear();
        Cache::tag(LangService::CACHE_TAG)->clear();
        Cache::delete("devstatus");
        $this->success();
    }

    /**
     * 字段分组
     * @return \think\response\Json
     */
    public function fieldGroup()
    {
        $searchValue = $this->request->param('searchValue', '');
        $name = $this->request->param('name', '');
        if (!empty($searchValue)) {
            return json(['rows'=>[['title'=>$searchValue]]]);
        }


        $data = $this->model->where(['name'=>'group'])->value('value');
        $data = json_decode($data, true);
        if (!empty($name) && !in_array($name, $data)) {
            array_push($data, $name);
        }

        $arr = [];
        foreach ($data as $key=>$value) {
            $arr[] = ['title'=>__($value),'name'=>$key];
        }

        return json(['rows'=>$arr]);
    }
}