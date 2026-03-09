<?php
// +----------------------------------------------------------------------
// | HkCms 列表数据模型管理
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

namespace app\admin\controller\cms;

use app\admin\controller\BaseController;
use app\admin\library\Html;
use app\admin\model\cms\Category;
use app\common\services\cms\ArchivesService;
use libs\Tree;
use think\db\exception\DbException;
use think\exception\ValidateException;
use think\facade\Db;

class Archives extends BaseController
{
    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [
        'login',
    ];

    /**
     * 文章模型
     * @var \app\admin\model\cms\Archives
     */
    protected $model;

    protected $category_id = 0;

    /**
     * 初始化操作
     */
    public function initialize()
    {
        parent::initialize();

        $this->model = new \app\admin\model\cms\Archives;

        if (!$this->user->checkLogin()) {
            return json(['code'=>-1000, 'msg'=>__('Please log in and operate'), 'data'=>[]]);
        }

        $categoryArr = $this->user->getUserCategory(false);
        $this->category_id = $this->request->param('category_id','', 'intval');

        if (!in_array($this->category_id, $categoryArr)) {
            $this->error(__('No permission to operate this column'));
        }
    }

    public function index()
    {
        if ($this->request->isAjax()) {
            // 获取表名
            $model_id = $this->request->param('model_id');
            $modelInfo = \app\admin\model\cms\Model::field('tablename')->find($model_id);
            // 是否显示下级
            $issub = $this->request->param('issub','');
            $cateMap = [['category_id','=',$this->category_id]];
            if ($issub==1) {
                $cids = Db::name('category')->where(['parent_id'=>$this->category_id,'model_id'=>$model_id])->column('id');
                if (!empty($cids)) { // 上级显示下级的菜单
                    $cids[] = $this->category_id;
                    $cateMap = [['category_id','in',$cids]];
                }
            }

            list($map, $limit, $offset, $order, $sort) = $this->buildparams(null, 'a');

            $data = $this->model
                ->alias('a')
                ->leftJoin('admin','a.admin_id=admin.id')
                ->leftJoin('category category','a.category_id=category.id')
                ->leftJoin("{$modelInfo['tablename']} more",'a.id=more.id')
                ->where($map)
                ->where($cateMap)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->field('a.*,admin.username,category.title as category_title,more.*')
                ->append(['publish_time_text'])
                ->select()
                ->toArray();
            $total = $this->model
                ->alias('a')
                ->leftJoin('admin','a.admin_id=admin.id')
                ->leftJoin('category category','a.category_id=category.id')
                ->leftJoin("{$modelInfo['tablename']} more",'a.id=more.id')
                ->where($map)
                ->where($cateMap)
                ->order($sort, $order)
                ->count();
            $categoryIds = array_column($data,'category_ids');
            // 获取副栏目数据
            $categoryData = Category::whereIn('id', $categoryIds)->field('id,title')->select()->toArray();
            $newCategoryData = [];
            foreach ($categoryData as $item) {
                $newCategoryData[$item['id']] = $item['title'];
            }
            foreach ($data as $key=>$item) {
                // 获取副栏目标题
                if ($item['category_ids'] && $newCategoryData) {
                    $tmpCategoryIds = explode(',', $item['category_ids']);
                    $tmpArr = [];
                    foreach ($tmpCategoryIds as $val) {
                        if (isset($newCategoryData[$val])) {
                            $tmpArr[] = $newCategoryData[$val];
                        }
                    }
                    $data[$key]['category_ids'] = implode(',', $tmpArr);
                }
            }
            return json(['total'=>$total,'rows'=>$data]);
        }

        // 文档属性
        $flags = Db::name('flags')->where(['lang'=>$this->contentLang])->select()->toArray();
        $flagsJson = [];
        foreach ($flags as $value) {
            $flagsJson[$value['name']] = $value['title'];
        }
        $params = $this->request->param();
        // 获取所有字段
        $modelField = (new \app\admin\model\cms\ModelFieldBind)->getAllowField($this->category_id, $params['model_id'], $data);
        // 获取管理员显示列字段
        $columnData = Db::name('admin_field')->where(['admin_id'=>$this->user->id, 'table_name'=>'archives','model_id'=>$params['model_id']])->value('column_data');
        $this->view->assign('modelField', json_encode($modelField, JSON_UNESCAPED_UNICODE));
        $this->view->assign('flags', $flags);
        $this->view->assign('flagsJson', json_encode($flagsJson, JSON_UNESCAPED_UNICODE));
        $this->view->assign('columnData', $columnData ?: "{}");
        $this->view->assign($params);
        return $this->view->fetch();
    }

    /**
     * 数据添加
     * @return mixed|string|void
     */
    public function add()
    {
        $category_id = $this->request->param('category_id','', 'intval');
        if (empty($category_id)) {
            $this->error(__('Parameter %s can not be empty', ['category_id']));
        }
        $categoryInfo = \app\admin\model\cms\Category::where(['id'=>$category_id,'status'=>'normal'])->find();
        if (empty($categoryInfo)) {
            $this->error(__('Column information does not exist'));
        }

        if ($this->request->isPost()) { // 数据添加
            $data = $this->request->post('row/a', '', 'stripslashes');
            $tempData = $data;
            // 副栏目
            if (!empty($tempData['category_ids'])) {
                $tempData['category_ids'] = implode(',', $tempData['category_ids']);
            }
            $modelField = (new \app\admin\model\cms\ModelFieldBind)->getAllowField($categoryInfo['id'], $categoryInfo['model_id'], $data);

            // 验证
            list($valData,$msgData) = build_tp_rules($modelField);
            try {
                $this->validate($tempData, $valData, $msgData);
            } catch (ValidateException $e) {
                $this->error($e->getError());
            }

            // 别名验证
            if (!empty($tempData['diyname']) && !\think\facade\Validate::is($tempData['diyname'], 'alphaDash')) {
                $this->error(__('Custom URLs only support letters and numbers, underscores.'));
            }
            if (!empty($tempData['diyname']) && Db::name('archives')->where(['diyname'=>$tempData['diyname']])->find()) {
                $this->error(__('Custom URL name already exists'));
            }

            $tablename = Db::name('model')->where(['id'=>$categoryInfo['model_id']])->value('tablename');

            Db::startTrans();
            try {
                $this->model->save(array_merge([
                    'model_id'=>$categoryInfo['model_id'],
                    'admin_id'=>$this->user->id,
                    'category_id'=>$category_id,
                    'category_ids'=>$tempData['category_ids']??'',
                    'style'=>$tempData['style']??'',
                    'lang'=>$categoryInfo['lang']
                ],$data['main']));
                Db::name($tablename)->insert(array_merge(['id'=>$this->model->id],$data['vice']));
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            // 更新缓存
            if (isset($tempData['status']) && $tempData['status']=='normal') {
                // 更新缓存
                app()->make(ArchivesService::class)->clearCache([$category_id]);
            }

            // 静态生成
            if (site('url_mode')==2) {
                $this->view->layout(false);
                $lang = $this->app->lang->getLangSet();
                $res = $this->model->toArray();
                $categoryInfo = $categoryInfo->toArray();
                Html::buildContentUrl($categoryInfo, $res);
                (new Html(app()))->showSing($res['id'], $categoryInfo);
                $page = 1;
                do {
                    $total_page = (new Html(app()))->category($categoryInfo,$page);
                    $page++;
                } while ($total_page!==false && $page<=$total_page);
                $this->app->lang->setLangSet($lang);
            }

            $this->success();
        }

        $this->buildPage($categoryInfo);
        return $this->view->fetch();
    }

    /**
     * 修改
     * @param null $id
     * @return mixed|string|void
     */
    public function edit($id = null)
    {
        $row = $this->model->find($id);
        if (!$row) {
            $this->error(__('No results were found'));
        }

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a",'','stripslashes');
            $modelInfo = $this->model->getTableInfo($params['category_id'],$categoryInfo);
            $tempData = $params;
            // 副栏目
            if (!empty($tempData['category_ids'])) {
                $tempData['category_ids'] = implode(',', $tempData['category_ids']);
            }
            // 别名验证
            if (!empty($tempData['diyname']) && !\think\facade\Validate::is($tempData['diyname'], 'alphaDash')) {
                $this->error(__('Custom URLs only support letters and numbers, underscores.'));
            }
            if (!empty($tempData['diyname']) && Db::name('archives')->where('id','<>',$id)->where(['diyname'=>$tempData['diyname']])->find()) {
                $this->error(__('Custom URL name already exists'));
            }

            $modelField = (new \app\admin\model\cms\ModelFieldBind)->getAllowField($categoryInfo['id'], $categoryInfo['model_id'], $params);

            Db::startTrans();
            try {
                $row->save(array_merge([
                    'model_id'=>$categoryInfo['model_id'],
                    'admin_id'=>$this->user->id,
                    'style'=>$tempData['style']??'',
                    'category_id'=>$tempData['category_id'],
                    'category_ids'=>$tempData['category_ids']??'',
                ],$params['main']));
                if (Db::name($modelInfo['tablename'])->where(['id'=>$row->id])->find()) {
                    Db::name($modelInfo['tablename'])->where(['id'=>$row->id])->save($params['vice']);
                } else {
                    $params['vice'] = array_merge($params['vice'], ['id'=>$row->id]);
                    Db::name($modelInfo['tablename'])->insert($params['vice']);
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }

            // 更新缓存
            app()->make(ArchivesService::class)->clearCache([$tempData['category_id']]);

            // 静态生成
            if (site('url_mode')==2) {
                $this->view->layout(false);
                $lang = $this->app->lang->getLangSet();
                $res = $row->toArray();
                $categoryInfo = $categoryInfo->toArray();
                Html::buildContentUrl($categoryInfo, $res);
                (new Html(app()))->showSing($res['id'], $categoryInfo);
                $page = 1;
                do {
                    $total_page = (new Html(app()))->category($categoryInfo,$page);
                    $page++;
                } while ($total_page!==false && $page<=$total_page);
                $this->app->lang->setLangSet($lang);
            }

            $this->success();
        }

        // 获取栏目数据
        $category_id = $this->request->param('category_id', '', 'intval');
        $modelInfo = $this->model->getTableInfo($category_id,$categoryInfo);
        $content = Db::name($modelInfo['tablename'])->find($row->id);
        $content = empty($content) ? [] : $content;
        $row = array_merge($content, $row->toArray());
        if ($newid = $this->request->param('newid','','intval')) {
            $categoryInfo = Category::where(['id'=>$newid,'status'=>'normal'])->find();
        }
        $this->buildPage($categoryInfo, $row);
        return $this->view->fetch();
    }

    /**
     * 软删除
     * @param string $ids
     */
    public function del($ids = '')
    {
        if ($ids) {
            $list = $this->model->where('id', 'in', $ids)->select();
            $count = 0;
            Db::startTrans();
            $category_ids = [];
            try {
                foreach ($list as $v) {
                    $category_ids[] = $v['category_id'];
                    $count += $v->delete();
                }
                Db::commit();
            } catch (DbException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($count) {
                // 更新缓存
                app()->make(ArchivesService::class)->clearCache($category_ids);
                $this->success();
            } else {
                $this->error(__('No rows deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty',['ids']));
    }

    /**
     * 批量修改
     */
    public function batches()
    {
        if ($this->request->isAjax()) {
            $ids = $this->request->param('ids', '');
            $params = $this->request->param('params', '');

            $this->postData = compact('ids','params');
            parent::batches();
        }
    }

    /**
     * 复制
     * @param string $ids
     * @return string|void
     * @throws \Exception
     */
    public function push($ids = '')
    {
        $model_id = $this->request->param('model_id','');
        $category_id = $this->request->param('category_id','');

        if ($this->request->isPost()) {
            $to = $this->request->param('to','');
            if (empty($to)) {
                $this->error(__('Please select'));
            }

            // 获取源数据
            list($data, $obj, $model) = controller($model_id, function ($obj, $model, $category) use($ids) {
                return [$obj->whereIn('id',$ids)->select()->toArray(), $obj, $model];
            });
            $model = $model->toArray();
            $toArr = explode(',', $to);
            foreach ($data as $key=>$value) {
                $id = $value['id'];
                unset($value['id']);
                unset($value['url']);
                foreach ($toArr as $k=>$v) {
                    $value['category_id'] = $v;
                    $value['admin_id'] = $this->user->id;
                    $value['create_time'] = time();
                    $value['update_time'] = time();
                    $value['diyname'] = '';

                    $archives = (new \app\admin\model\cms\Archives);
                    $archives->save($value);

                    $info = Db::name($model['tablename'])->where(['id'=>$id])->find();
                    $info['id'] = $archives->id;
                    Db::name($model['tablename'])->insert($info);
                }
            }
            $this->success();
        }

        $model = Category::alias('c')->leftJoin('model m','m.id=c.model_id')->field('c.*,m.name as model_name')->where(['lang'=>$this->contentLang]);
        if (!$this->user->hasSuperAdmin()) { // 判断是否是超级管理员
            $group = $this->user->getUserGroupId();
            $categoryIdArr = Db::name('category_priv')->whereIn('auth_group_id', $group)->column('category_id');
            $data = [];
            if (!empty($categoryIdArr)) {
                $data = $model->whereIn('c.id', $categoryIdArr)->order(['c.weigh'=>'desc','c.id'=>'asc'])->select()->append(['type_text'])->toArray();
            }
        } else {
            $data = $model->order(['c.weigh'=>'desc','c.id'=>'asc'])->select()->append(['type_text'])->toArray();
        }

        // 剔除不是本模型的栏目
        $isEnd = true;
        while ($isEnd) {
            $tmpIsEnd = false;
            foreach ($data as $key=>$value) {
                $isSub = false;
                foreach ($data as $k=>$v) {
                    if ($v['parent_id']==$value['id']) {
                        $isSub = true;
                    }
                }
                if (!$isSub && $value['model_id']!=$model_id) { // 最下级
                    unset($data[$key]);
                    $tmpIsEnd = true;
                    break;
                }
            }
            if ($tmpIsEnd) {
                $isEnd = true;
            } else {
                $isEnd = false;
            }
        }

        $arr = Tree::instance()->getTreeList(Tree::instance()->init($data)->getTreeArray(0));

        $this->view->assign('categoryList', $arr);
        $this->view->assign('category_id', $category_id);
        $this->view->assign('model_id', $model_id);
        $this->view->assign('ids', $ids);
        return $this->view->fetch();
    }

    /**
     * 移动
     * @param string $ids
     * @return string|void
     * @throws \Exception
     */
    public function move($ids = '')
    {
        $model_id = $this->request->param('model_id','');
        $category_id = $this->request->param('category_id','');

        if ($this->request->isPost()) {
            $to = $this->request->param('to','');
            if (empty($to)) {
                $this->error(__('Please select'));
            }

            // 获取源数据
            $data = controller($model_id, function ($obj, $model, $category) use($ids) {
                return $obj->whereIn('id',$ids)->select();
            });

            foreach ($data as $key=>$value) {
                $value->save(['category_id'=>$to]);
            }
            $this->success();
        }

        $model = \app\admin\model\cms\Category::alias('c')->leftJoin('model m','m.id=c.model_id')->field('c.*,m.name as model_name')->where(['lang'=>$this->contentLang]);
        if (!$this->user->hasSuperAdmin()) { // 判断是否是超级管理员
            $group = $this->user->getUserGroupId();
            $categoryIdArr = Db::name('category_priv')->whereIn('auth_group_id', $group)->column('category_id');
            $data = [];
            if (!empty($categoryIdArr)) {
                $data = $model->whereIn('c.id', $categoryIdArr)->order(['c.weigh'=>'desc','c.id'=>'asc'])->select()->append(['type_text'])->toArray();
            }
        } else {
            $data = $model->order(['c.weigh'=>'desc','c.id'=>'asc'])->select()->append(['type_text'])->toArray();
        }

        // 剔除不是本模型的栏目
        $isEnd = true;
        while ($isEnd) {
            $tmpIsEnd = false;
            foreach ($data as $key=>$value) {
                $isSub = false;
                foreach ($data as $k=>$v) {
                    if ($v['parent_id']==$value['id']) {
                        $isSub = true;
                    }
                }
                if (!$isSub && $value['model_id']!=$model_id) { // 最下级
                    unset($data[$key]);
                    $tmpIsEnd = true;
                    break;
                }
            }
            if ($tmpIsEnd) {
                $isEnd = true;
            } else {
                $isEnd = false;
            }
        }

        $arr = Tree::instance()->getTreeList(Tree::instance()->init($data)->getTreeArray(0));
        $this->view->assign('categoryList', $arr);
        $this->view->assign('category_id', $category_id);
        $this->view->assign('model_id', $model_id);
        $this->view->assign('ids', $ids);
        return $this->view->fetch();
    }

    /**
     * 回收站
     * @return string|\think\response\Json|void
     */
    public function recycle()
    {
        $category_id = $this->request->param('category_id', '', 'intval');
        $modelInfo = $this->model->getTableInfo($category_id);
        if ($this->request->isAjax()) {

            list($map, $limit, $offset, $order, $sort) = $this->buildparams(null, 'a');

            $data = $this->model
                ->onlyTrashed()
                ->alias('a')
                ->leftJoin('admin admin','a.admin_id=admin.id')
                ->leftJoin('category category','a.category_id=category.id')
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->field('a.*,admin.username,category.title as category_title')
                ->select();

            $total = $this->model
                ->onlyTrashed()
                ->alias('a')
                ->leftJoin('admin admin','a.admin_id=admin.id')
                ->leftJoin('category category','a.category_id=category.id')
                ->where($map)
                ->order($sort, $order)
                ->count();
            return json(['total'=>$total, 'rows'=>$data->append(['publish_time_text','delete_time_text'])->toArray()]);
        }
        $this->view->assign('category_id', $category_id);
        return $this->view->fetch();
    }

    /**
     * 还原
     * @param string $ids 为空的时候还原全部
     */
    public function restore($ids="")
    {
        $pk = 'id';
        $bl = 0;
        Db::startTrans();
        try {
            $model = $this->model->onlyTrashed();
            if ($ids) {
                $model = $model->where($pk, 'in', $ids);
            }
            $list = $model->select();
            foreach ($list as $index => $item) {
                $bl += $item->restore();
            }
            Db::commit();
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
     * 销毁
     * @param string $ids
     */
    public function destroy($ids="")
    {
        $category_id = $this->request->param('category_id', '', 'intval');
        $modelInfo = $this->model->getTableInfo($category_id);

        $pk = 'id';

        $bl = 0;
        Db::startTrans();
        try {
            $model = $this->model->onlyTrashed();
            if ($ids) {
                $model = $model->where($pk, 'in', $ids);
            }
            $list = $model->select();
            foreach ($list as $index => $item) {
                Db::name($modelInfo['tablename'])->where(['id'=>$item->getAttr('id')])->delete();
                $bl += $item->force()->delete();
            }
            Db::commit();
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
     * 生成文章临时预览
     * @return string|void
     */
    public function preview()
    {
        $id = $this->request->param('id', 0, 'intval');

        $info = Category::where(['id'=>$this->category_id])->find();
        if (empty($info)) {
            $this->error(__('Column information does not exist'));
        }
        $info = $info->toArray();
        $key = md5(app('session')->getId());
        $info['aid'] = $id;

        $site = site();
        if ($site['url_mode']==1 && !empty($site['url_rewrite'])) {
            $value = index_url('/index/show', $info,'','','',[],['key'=>$key]);
        } else {
            $param['key'] = $key;
            $param['id'] = $id;
            $param['catname'] = $info['name'];
            if ($site['content_lang_on']==1) {
                $param['lang'] = $this->request->param('lang');
            }
            $value = index_url('/index/show', $param);
        }

        return redirect($value);
    }

    /**
     * 属性
     */
    public function property()
    {
        $ids = $this->request->param('ids');
        $p = $this->request->param('p');
        $type = $this->request->param('type','','intval');

        $ids = explode(',', $ids);
        if (empty($ids) || empty($p)) {
            $this->error(__('Parameter %s can not be empty'));
        }

        $data = $this->model->whereIn('id', $ids)->select()->toArray();
        foreach ($data as $key=>$val) {
            if ($type==1) {
                // 新增属性
                if (!empty($val['flags'])) {
                    $flags = explode(',', $val['flags']);
                    $flags = array_unique(array_merge($flags, $p));
                } else {
                    $flags = $p;
                }
                $up = [];
                if (in_array('top', $flags)) {
                    $up['weigh'] = 1;
                }
                $up['flags'] = implode(',', $flags);
                Db::name('archives')->where(['id'=>$val['id']])->update($up);
            } else if ($type==2 && !empty($val['flags'])) {
                // 删除属性
                $flags = explode(',', $val['flags']);
                $flags = array_diff($flags, $p);
                $up = [];
                if (!in_array('top', $flags) && $val['weigh']==1) {
                    $up['weigh'] = 100;
                }
                $up['flags'] = empty($flags)?'':implode(',', $flags);
                Db::name('archives')->where(['id'=>$val['id']])->update($up);
            }
        }
        $this->success();
    }

    /**
     * 保存管理员列字段
     * @return void
     */
    public function saveField()
    {
        $columnData = $this->request->param('column_data');
        $modelId = $this->request->param('model_id');
        if (empty($columnData)) {
            $this->error(__('Parameter %s can not be empty', ['column_data']));
        }
        if ($info = Db::name('admin_field')->where(['admin_id'=>$this->user->id, 'table_name'=>'archives', 'model_id'=>$modelId])->find()) {
            Db::name('admin_field')->where(['id'=>$info['id']])->update(['column_data'=>json_encode($columnData)]);
            $this->success();
        }
        Db::name('admin_field')->insert([
            'admin_id'=>$this->user->id,
            'table_name'=>'archives',
            'catid'=>0,
            'model_id'=>$modelId,
            'column_data'=>json_encode($columnData)
        ]);
        $this->success();
    }

}