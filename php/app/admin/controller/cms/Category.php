<?php
// +----------------------------------------------------------------------
// |HkCms 栏目管理
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\controller\cms;

use app\admin\controller\BaseController;
use app\admin\library\Html;
use libs\Tree;
use Overtrue\Pinyin\Pinyin;
use think\db\exception\DbException;
use think\Exception;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Validate;

class Category extends BaseController
{
    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [
        'login',
        'auth'=>['except'=>['getCategoryJstree','selectColumn','selectModel']]
    ];

    /**
     * 栏目管理模型
     * @var \app\admin\model\cms\Category
     */
    protected $model;

    /**
     * 允许批量修改的字段
     * @var array
     */
    protected $allowFields = ['status','ismenu','weigh','user_auth'];

    /**
     * 初始化操作
     */
    public function initialize()
    {
        parent::initialize();

        $this->model = new \app\admin\model\cms\Category;
    }

    public function index()
    {
        if ($this->request->isAjax()) {
            if ($this->request->param('searchTable')) {
                return $this->selectPage(); // 判断请求。如果是动态下拉组件请求，则交接给selectPage方法
            }

            $lang = $this->request->param('clang', $this->contentLang);
            $arr = $this->getCateList($lang);

            return json(['total'=>count($arr),'rows'=>$arr]);
        }

        return $this->view->fetch();
    }

    /**
     * 获取栏目数据，有限
     * @param $lang
     * @return array
     */
    private function getCateList($lang)
    {
        $model = $this->model->alias('c')
            ->leftJoin('model m','m.id=c.model_id')
            ->field('c.*,m.name as model_name')
            ->where('lang','=',$lang);
        if (!$this->user->hasSuperAdmin()) { // 判断是否是超级管理员
            $group = $this->user->getUserGroupId();
            $categoryIdArr = Db::name('category_priv')->whereIn('auth_group_id', $group)->column('category_id');
            $data = [];
            if (!empty($categoryIdArr)) {
                $data = $model->whereIn('c.id', $categoryIdArr)->order(['c.weigh'=>'asc','c.id'=>'asc'])->select()->append(['type_text'])->toArray();
            }
        } else {
            $data = $model->order(['c.weigh'=>'asc','c.id'=>'asc'])->select()->append(['type_text'])->toArray();
        }

        return Tree::instance()->getTreeList(Tree::instance()->init($data)->getTreeArray(0));
    }

    /**
     * 添加
     * @return mixed|string|void
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $row = $this->request->post("row/a");

            $this->validate($row, 'cms/Category');

            $arr = [];
            if (strpos($row['title'], '|')) {
                $arr = explode("\r\n", $row['title']);
                // 判断类型取值
                unset($row['name']);
                unset($row['title']);
            }
            $row['show_tpl'] = !empty($row['page_tpl']) ? $row['page_tpl'] : $row['show_tpl'];

            unset($row['page_tpl']);
            if ($row['type']=='category') {
                unset($row['list_tpl'],$row['show_tpl']);
            } else if ($row['type']=='link' && !empty($row['model_id'])) {
                $row['url'] = '';
                unset($row['category_tpl'],$row['list_tpl']);
            } else if ($row['type']=='link' && empty($row['model_id'])) {
                unset($row['category_tpl'],$row['list_tpl'],$row['show_tpl'],$row['model_id'],$row['seo_title'],$row['seo_keywords'],$row['seo_desc']);
            } else {
                unset($row['category_tpl']);
            }

            // 扩展字段处理
            $extend = $row;
            $modelField = (new \app\admin\model\cms\Fields)->getAllowField('category', 0, $extend);
            // 验证
            list($valData, $msgData) = build_tp_rules($modelField);
            try {
                $this->validate($extend, $valData, $msgData);
            } catch (ValidateException $e) {
                $this->error($e->getError());
            }
            $row = array_merge($row, $extend);
            $row['lang'] = $this->contentLang;

            // 有父级的情况下，取父级的语言标识
            if (site('content_lang_on')==1 && isset($row['parent_id']) && $row['parent_id']>0) {
                $row['lang'] = $this->model->where(['id'=>$row['parent_id']])->value('lang');
            }

            $add = $inName = $inNameEn = [];
            if (!empty($arr)) {
                foreach ($arr as $key=>$value) {
                    $tempArr = explode('|', $value);
                    if (count($tempArr)!==2) {
                        $this->error(__('Use "|" to separate the column name from the English format'));
                    }
                    $tempArr[0] = strip_tags(stripslashes(trim($tempArr[0])));
                    if (empty($tempArr[0])) {
                        $this->error(__('Column name is required'));
                    }
                    if (!Validate::is($tempArr[1], 'alphaDash')) {
                        $this->error(__('Directory name can only be letters and numbers, underscore "_" and dash "-"'));
                    }

                    // 判断是否已存在
                    if ($this->model->where(['name'=>$tempArr[1],'lang'=>$this->contentLang])->value('name')) {
                        $this->error(__('Directory name already exists'));
                    }

                    $inName[] = $tempArr[0];
                    $inNameEn[] = $tempArr[1];

                    $add[] = array_merge(['name'=>$tempArr[1],'title'=>$tempArr[0]], $row);
                }
            } else {
                if (empty($row['name'])) {
                    $pinyin = new Pinyin();
                    $row['name'] = $pinyin->sentence($row['title'],'');
                    // 判断是否已存在
                    if ($this->model->where(['name'=>$row['name'],'lang'=>$this->contentLang])->value('name')) {
                        $row['name'] = $row['name'].get_random_str(2).mt_rand(100,999);
                    }
                } else {
                    if (!Validate::is($row['name'], 'alphaDash')) {
                        $this->error(__('Directory name can only be letters and numbers, underscore "_" and dash "-"'));
                    }
                    // 判断是否已存在
                    if ($this->model->where(['name'=>$row['name'],'lang'=>$this->contentLang])->value('name')) {
                        $this->error(__('Directory name already exists'));
                    }
                }
                $add[] = $row;
            }

            if (count(array_unique($inName)) != count($inName)) {
                $this->error(__('Column name has duplicate value'));
            }
            if (count(array_unique($inNameEn)) != count($inNameEn)) {
                $this->error(__('Directory name has duplicate values'));
            }

            Db::startTrans();
            try {
                $res = $this->model->saveAll($add);
                $res = $res->toArray();
                if (!$this->user->hasSuperAdmin()) {
                    foreach ($res as $key=>$value) {
                        Db::name('category_priv')->insert(['category_id'=>$value['id'],'auth_group_id'=>$this->user->getUserGroupId()[0]]);
                    }
                }

                // 单页自动生成单页文章
                foreach ($res as $key=>$value) {
                    if ($value['type']=='link' && !empty($value['model_id'])) {
                        $single = Db::name('model')->find($value['model_id']);
                        if ($single['is_search']!=-1) {  // 不支持搜索的通常不是一个主表
                            $id = Db::name('archives')->insertGetId(['category_id'=>$value['id'],'model_id'=>$value['model_id'],'title'=>$value['title'],'show_tpl'=>$value['show_tpl'],'create_time'=>time(),'lang'=>$this->contentLang]);
                            Db::name($single['tablename'])->insert(['id'=>$id,'content'=>'']);
                        }
                    }
                }

                Db::commit();
            } catch (DbException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }

            // 静态生成
            if ($res && site('url_mode')==2 && !empty($row['model_id'])) {
                $this->view->layout(false);
                $lang = $this->app->lang->getLangSet();
                foreach ($res as $value) {
                    if (site('content_lang_on')==1) {
                        $arr = lang_content_get('category',$value['id']);
                        $tmp = \app\index\model\cms\Category::whereIn('id', $arr)->select()->toArray();
                        Html::buildCategoryUrl($tmp); // 生成URL地址
                    } else {
                        $tmp = [$value];
                    }
                    $p = 1;
                    foreach ($tmp as $v) {
                        (new Html(app()))->category($v,$p);
                    }
                }
                Html::buildCategoryUrl($res);
                $this->app->lang->setLangSet($lang);
            }
            Cache::tag(['category_tag','guestbook_tag'])->clear();
            $this->success();
        }

        // 获取扩展字段
        $fields = \app\admin\model\cms\Fields::where(['status'=>'normal','source'=>'category'])->order('weigh', 'asc')->select()->toArray();
        // 生成表单
        $this->view->layout(false);
        $html = $this->view->fetch('cms/content/field', ['data'=>$fields,'row'=>[],'t'=>2]);
        $this->view->layout(true);
        $this->view->assign(['html'=>$html]);

        $sedPid = $this->request->param('parent_id');

        $parenInfo = $this->model->where(['id'=>$sedPid])->find();
        $listData = $this->getCateList(!empty($parenInfo['lang'])?$parenInfo['lang']:$this->contentLang);

        $parent_id = [0=>__('As a first-level menu')];
        foreach ($listData as $key=>$value) {
            $parent_id[$value['id']] = $value['title'];
        }
        $assign = [
            'parent_list' => $parent_id,
            'parenInfo' => $parenInfo,
        ];
        $this->view->assign($assign);
        return $this->view->fetch();
    }

    /**
     * 修改
     * @param null $id
     * @return mixed|string|void
     * @throws DbException
     */
    public function edit($id = null)
    {
        $row = $this->model->find($id);
        if (!$row) {
            $this->error(__('No results were found'));
        }

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $this->validate($params, 'cms/Category');

                $params['title'] = strip_tags(stripslashes(trim($params['title'])));
                if (empty($params['title'])) {
                    $this->error(__('Column name is required'));
                }
                if (!Validate::is($params['name'], 'alphaDash')) {
                    $this->error(__('Directory name can only be letters and numbers, underscore "_" and dash "-"'));
                }

                // 判断是否已存在
                if ($this->model->where(['name'=>$params['name'],'lang'=>$row->lang])->where('id','<>',$row->id)->value('name')) {
                    $this->error(__('Directory name already exists'));
                }

                if ($params['type']=='category') {
                    $params['list_tpl'] = $params['show_tpl'] = '';
                    unset($params['url']);
                } else if ($params['type']=='link' && !empty($params['model_id'])) {
                    $params['category_tpl']=$params['list_tpl']='';
                    $params['show_tpl'] = $params['page_tpl'];
                    unset($params['url']);
                } else if ($params['type']=='link' && empty($params['model_id'])) {
                    $params['model_id'] = 0;
                    $params['category_tpl'] = $params['list_tpl'] = $params['show_tpl'] = '';
                    //$params['seo_title'] = $params['seo_keywords'] = $params['seo_desc'] = '';
                } else {
                    unset($params['url']);
                    $params['category_tpl'] = '';
                }

                // 旧的
                $show_tpl = $row->show_tpl;

                $extend = $params;
                $modelField = (new \app\admin\model\cms\Fields)->getAllowField('category', 0, $extend);
                // 验证
                list($valData, $msgData) = build_tp_rules($modelField);
                try {
                    $this->validate($extend, $valData, $msgData);
                } catch (ValidateException $e) {
                    $this->error($e->getError());
                }

                $params = array_merge($params, $extend);
                Db::startTrans();
                try {
                    $row->save($params);
                    if ($row->model_id && $show_tpl != $params['show_tpl']) { // 更新模型对应表的show_tpl字段
                        $info  = \app\admin\model\cms\Model::where(['id'=>$row->model_id])->find();
                        if ($info) {
                            $c = '\\app\\admin\\model\\cms\\'.$info->controller;
                            (new $c)->where(['category_id'=>$id,'model_id'=>$row->model_id])->save(['show_tpl'=>$params['show_tpl']]);
                        }
                    }
                    Db::commit();
                } catch (DbException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (\Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                // 静态生成
                if (site('url_mode')==2 && $row['model_id']>0) {
                    $this->view->layout(false);
                    $lang = $this->app->lang->getLangSet();
                    $res = $row->toArray();
                    Html::buildCategoryUrl([$res]);
                    $page = 1;
                    do {
                        $total_page = (new Html(app()))->category($res,$page);
                        $page++;
                    } while ($total_page!==false && $page<=$total_page);
                    $this->app->lang->setLangSet($lang);
                }
                Cache::tag(['category_tag','guestbook_tag'])->clear();
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty',['']));
        }

        // 获取扩展字段
        $fields = \app\admin\model\cms\Fields::where(['status'=>'normal','source'=>'category'])->order('weigh', 'asc')->select()->toArray();
        // 生成表单
        $this->view->layout(false);
        $html = $this->view->fetch('cms/content/field', ['data'=>$fields,'row'=>$row]);
        $this->view->layout(true);
        $this->view->assign(['html'=>$html]);

        $parent_id = [0=>__('As a first-level menu')];
        $listData = $this->getCateList($row['lang']);
        foreach ($listData as $key=>$value) {
            $parent_id[$value['id']] = $value['title'];
        }

        // 获取其他链接选择的栏目
        $category_title = '';
        if ($row->type=='link' && is_numeric($row->getOrigin('url'))) {
            $category_title = Db::name('category')->where(['id'=>$row->getOrigin('url')])->value('title');
        }

        $assign = [
            'parent_list' => $parent_id,
            'category_title' => $category_title,
        ];
        $this->view->assign($assign);
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }

    /**
     * 数据删除
     * @param string $ids
     * @throws DbException
     */
    public function del($ids = '')
    {
        if ($ids) {
            $list = $this->model->where('id','in',$ids)->select();
            if ($list->isEmpty()) {
                $this->error(__('No results were found'));
            }
            $arr = [];
            foreach ($list as $key=>$value) {
                $arr[$value->id] = $value->id;
                $listData = $this->getCateList($value->lang);
                $temp = Tree::instance()->init($listData)->getChildIds($value->id);
                if ($temp) {
                    $arr = $temp + $arr;
                }
            }
            Cache::tag(['category_tag','guestbook_tag'])->clear();
            parent::del(implode(',',$arr));
        }
        $this->error(__('Parameter %s can not be empty',['ids']));
    }

    /**
     * 回收站
     * @return string|void
     */
    public function recycle()
    {
        if ($this->request->isAjax()) {

            list($map, $limit, $offset, $order, $sort) = $this->buildparams(null,'c');

            // 语言包
            $lang = $this->request->param('clang', $this->contentLang);
            $map[] = ['lang','=',$lang];

            $data = $this->model
                ->onlyTrashed()
                ->alias('c')
                ->leftJoin('model m','c.model_id=m.id')
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->field('c.*,m.name as model_name')
                ->select();

            $total = $this->model
                ->onlyTrashed()
                ->alias('c')
                ->leftJoin('model m','c.model_id=m.id')
                ->where($map)
                ->order($sort, $order)
                ->count();

            return json(['total'=>$total, 'rows'=>$data->append(['type_text'])]);
        }
        return $this->view->fetch();
    }

    /**
     * 还原
     * @param string $ids 为空的时候还原全部
     */
    public function restore($ids="")
    {
        $pk = $this->model->getPk();
        $model = $this->model->onlyTrashed();
        if ($ids) {
            $model = $model->where($pk, 'in', $ids);
        }

        $lang = $this->contentLang;

        $bl = 0;
        Db::startTrans();
        try {
            $list = $model->where('lang','=', $lang)->select();
            foreach ($list as $index => $item) {
                $bl += $item->restore();
            }
            Db::commit();
        } catch (DbException $e) {
            Db::rollback();
            $this->error($e->getMessage());
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($bl) {
            $this->success();
        } else {
            $this->error(__('Operation failed'));
        }
    }

    /**
     * 批量处理
     * @return void
     */
    public function batches()
    {
        if ($this->request->isAjax()) {
            $data = $this->postData ?? $this->request->only(['ids'=>'','params'=>'']);
            if (empty($data['ids']) || empty($data['params'])) {
                $this->error(__('Parameter %s can not be empty',['']));
            }

            // 参数转换
            parse_str($data['params'], $arr);
            $addArr = [];
            foreach ($arr as $key=>$value) {
                if (!Validate::is($key,'alphaDash')) {
                    $this->error(__('The field name can only be letters, numbers, underscores, dashes'));
                }
                if (!Validate::is($value,'chsDash')) {
                    $this->error(__('Field value Chinese characters, letters, numbers, and underscores _ and dashes -'));
                }
                if (in_array($key, $this->allowFields)) {
                    $addArr[$key] = $value;
                }
            }
            if (empty($addArr)) {
                $this->error(__('Operation failed: there are no fields to operate!'));
            }

            $pk = $this->model->getPk();
            $list = $this->model->where($pk, 'in', $data['ids'])->select();
            if ($list->isEmpty()) {
                $this->error(__('No results were found'));
            }

            $bl = 0;
            Db::startTrans();
            try {
                foreach ($list as $item) {
                    $bl += $item->save($addArr);
                }
                Db::commit();
            } catch (ValidateException $e) {
                Db::rollback();
                $this->error($e->getError());
            } catch (DbException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }

            if ($bl) {
                Cache::tag(['category_tag','guestbook_tag'])->clear();
                $this->success();
            } else {
                $this->error(__('No changes'));
            }
        } else {
            $this->error(__('Illegal request'));
        }
    }

    /**
     * 获取栏目的jstree
     */
    public function getCategoryJstree()
    {
        if ($this->request->isAjax()) {

            $model = $this->model->where(['status'=>'normal','lang'=>$this->contentLang]);
            if (!$this->user->hasSuperAdmin()) { // 判断是否是超级管理员
                $group = $this->user->getUserGroupId();
                $categoryIdArr = Db::name('category_priv')->whereIn('auth_group_id', $group)->column('category_id');
                if (empty($categoryIdArr)) {
                    $this->success('','', []);
                }
                $model = $model->whereIn('id', $categoryIdArr);
            }
            $category = $model->order(['weigh'=>'asc','id'=>'asc'])->select()->toArray();

            $categoryArr = [];
            $dis = [];
            foreach ($category as $key=>$value) {
                if ($value['type'] == 'list') {
                    $value['icon'] = 'fas fa-list';
                } else if ($value['type'] == 'link' && !empty($value['model_id'])) {
                    $value['icon'] = 'far fa-file';
                    $value['popup'] = '1';
                } else if ($value['type'] == 'link' && empty($value['model_id'])) {
                    $value['icon'] = 'fas fa-link';
                    $value['popup'] = '1';
                } else {
                    $value['icon'] = 'fas fa-folder';
                }

                $controller = Db::name('Model')->where(['id'=>$value['model_id']])->value('controller');
                if (empty($controller)) {
                    $bl = $this->catFilter($category,$value['id']);
                    if ($bl) {
                        continue;
                    }
                    $value['to_url'] = '';
                    $categoryArr[$key] = $value;
                    $dis[] = $value['id'];
                    continue;
                }
                $value['to_url'] = $controller?(string)url('/cms.'.$controller.'/index',['model_id'=>$value['model_id'],'category_id'=>$value['id'],'popup'=>$value['popup']??'']):'';
                $categoryArr[$key] = $value;
            }
            $category = Tree::instance()->init($categoryArr)->getJsTree(0,'title',[],['to_url','model_id'],$dis);
            $this->success('','',$category);
        }
    }

    /**
     * 过滤链接类型的栏目
     * @param $category
     * @param $id
     * @return bool
     */
    private function catFilter($category, $id)
    {
        foreach ($category as $key=>$value) {
            if ($value['parent_id']==$id) {
                if ($value['model_id']>0) {
                    return false;
                }
                if ($this->catFilter($category, $value['id'])===false) {
                    return false;
                }
            }

        }
        return true;
    }

    /**
     * 栏目权限配置
     * @return string|void
     */
    public function auth()
    {
        if ($this->request->isAjax() && $this->request->isPost()) {
            $row = $this->request->param('row');
            if (empty($row['auth_group_id']) || !is_numeric($row['auth_group_id'])) {
                $this->error(__('Failed to obtain role group ID'));
            }
            if (empty($row['category_id'])) { // 为空。清除所有权限
                Db::name('category_priv')->where(['auth_group_id'=>$row['auth_group_id']])->delete();
                $this->success('');
            }
            $categoryIdArr = explode(',', $row['category_id']);
            $data = [];
            foreach ($categoryIdArr as $key=>$value) {
                $temp['category_id'] = $value;
                $temp['auth_group_id'] = $row['auth_group_id'];
                $data[] = $temp;
            }
            if (empty($data)) {
                $this->error(__('Parameter %s can not be empty',['']));
            }

            $res = Db::name('category_priv')->where(['auth_group_id'=>$row['auth_group_id']])->select();
            Db::startTrans();
            try {
                if (!empty($res)) {
                    Db::name('category_priv')->where(['auth_group_id'=>$row['auth_group_id']])->delete();
                }
                Db::name('category_priv')->insertAll($data);
                Db::commit();
            } catch (Exception $exception) {
                Db::rollback();
                $this->error($exception->getMessage());
            }
            $this->success('');
        } else if ($this->request->isAjax() && $this->request->isGet()) {
            $groupId = $this->request->param('id');
            if (empty($groupId) || !is_numeric($groupId)) {
                $this->error(__('Failed to obtain role group ID'));
            }

            $category = $this->user->getUserCategory();
            foreach ($category as $key=>&$value) {
                if ($value['type'] == 'list') {
                    $value['icon'] = 'fas fa-file';
                } else if ($value['type'] == 'link') {
                    $value['icon'] = 'fas fa-link';
                    $value['popup'] = '1';
                } else {
                    $value['icon'] = 'fas fa-folder';
                }
                if ($value['model_id']==0) {
                    $sonIds = Db::name('category')->where(['parent_id'=>$value['id'],'status'=>'normal'])->where('model_id','<>',0)->find();
                    if (empty($sonIds)) {
                        unset($category[$key]);
                    }
                }
            }

            $selectIdArr = Db::name('category_priv')->where(['auth_group_id'=>$groupId])->column('category_id');
            $auth = Tree::instance()->init($category)->getJsTree(0,'title',$selectIdArr);
            $this->success('', '', $auth);
        }

        // 获取管理员拥有的角色ID
        $groupIds = $this->user->getUserGroupId();
        $data = \app\admin\model\auth\AuthGroup::whereIn('id', $groupIds)->select()->toArray();
        $data = Tree::instance()->getTreeList(Tree::instance()->init($data)->getTreeArray($data[0]['parent_id']),'name');
        $this->view->assign('data', $data);
        return $this->view->fetch();
    }

    /**
     * 选择栏目
     * @return string|void
     * @throws \Exception
     */
    public function selectColumn()
    {
        $category_id = $this->request->param('cid', '');
        $clang = $this->request->param('clang', $this->contentLang);
        $arr = $this->getCateList($clang);

        $this->view->assign('categoryList', $arr);
        $this->view->assign('category_id', $category_id);
        return $this->view->fetch();
    }

    /**
     * 模型选择
     * @return \think\response\Json
     */
    public function selectModel()
    {
        if ($searchValue = $this->request->param('searchValue')) {
            $info = Db::name('model')->where(['id'=>$searchValue])->find();
            return json(['rows'=>[$info],'total'=>1,'code'=>200,'data'=>[$info]]);
        }

        $type = $this->request->param('type');
        $model = Db::name('model')->where(['status'=>'normal','type'=>$type=='category'||$type=='list'?'more':'single'])->select()->toArray();
        return json(['rows'=>$model,'total'=>count($model),'code'=>200,'data'=>$model]);
    }
}