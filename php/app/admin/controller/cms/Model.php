<?php
// +----------------------------------------------------------------------
// |HkCms 模型管理
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\controller\cms;

use app\admin\controller\BaseController;
use think\db\exception\DbException;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;

class Model extends BaseController
{
    /**
     * @var \app\admin\model\cms\Model
     */
    public $model;

    protected $enableValidate = true;

    /**
     * 允许批量修改的字段
     * @var array
     */
    protected $allowFields = ['status','weigh','is_search'];

    protected $searchField = 'name';

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [
        'login',
        'auth'=>['except'=>['getTplName','preview','index','config']]
    ];

    public function initialize()
    {
        parent::initialize();
        $this->model = new \app\admin\model\cms\Model;
    }

    /**
     * 列表显示
     * @return string|void
     */
    public function index()
    {
        if ($this->request->isAjax()) {

            if ($this->request->param('searchTable')) {
                return $this->selectPage(); // 判断请求。如果是动态下拉组件请求，则交接给selectPage方法
            }

            list($map, $limit, $offset, $order, $sort) = $this->buildparams();

            $data = $this->model->where($map)->order($sort, $order)->limit($offset, $limit)->select();
            $total = $this->model->where($map)->order($sort, $order)->count();
            return json(['total'=>$total,'rows'=>$data]);
        }

        $conData = Db::name('model_controller')->where(['status'=>'normal'])->select()->toArray();

        $con = [];
        foreach ($conData as $key=>$value) {
            if (!empty($value['config'])) {
                $con[$value['name']] = $value['title'];
            }
        }

        $this->view->assign('con',json_encode($con));
        return $this->view->fetch();
    }

    public function add()
    {
        if ($this->request->isAjax()) {
            parent::add();
        }
        $conData = Db::name('model_controller')->field('title,name,type,is_search,single_sql')->select()->toArray();
        $con = [];
        $controllers = [];
        foreach ($conData as $key=>$value) {
            $con[$value['name']] = $value['title'];
            $controllers[$value['name']] = $value;
        }
        $this->view->assign('con',$con);
        $this->view->assign('controllers', json_encode($controllers));
        return $this->view->fetch();
    }

    public function edit($id=null)
    {
        $row = $this->model->find($id);
        if (!$row) {
            $this->error(__('No results were found'));
        }

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                $result = false;
                Db::startTrans();
                try {
                    // 是否开启验证器验证，默认无验证。
                    if ($this->enableValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = validate($name);
                        if ($this->enableScene) { // 开启场景验证
                            $validate = $validate->scene('edit');
                        }
                        $validate->check($params);
                    }
                    $result = $row->save($params);
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

                if ($result !== false) {
                    Cache::tag(['model'])->clear();
                    $this->success();
                } else {
                    $this->error(__('No changes'));
                }
            }
            $this->error(__('Parameter %s can not be empty',['']));
        }
        $conData = Db::name('model_controller')->field('title,name')->select();
        $con = [];
        foreach ($conData as $key=>$value) {
            $con[$value['name']] = $value['title'];
        }
        $this->view->assign('con',$con);
        return $this->view->fetch('', compact('row'));
    }

    /**
     * 导出
     * @param null $id
     */
    public function export($id=null)
    {
        $row = $this->model->find($id);
        if (!$row) {
            $this->error(__('No results were found'));
        }

        $modelField = Db::name('model_field')->where(['model_id'=>$id])->select()->toArray();
        $data = [];
        foreach ($modelField as $key=>&$value) {
            unset($value['id']);
        }
        $data['model'] = $row->toArray();
        unset($data['model']['id']);
        $data['model_field'] = $modelField;

        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename=model_". $row->name . $id . '.txt');
        echo base64_encode(json_encode($data));
    }

    /**
     * 导入
     * @return string|void
     */
    public function import()
    {
        if ($this->request->isPost()) {
            $row = $this->request->post('row');
            $this->validate($row, '\app\admin\validate\cms\Model.import',[]);

            // 验证上传的文件
            if (empty($row['file'])) {
                $this->error(__('Please upload the file'));
            }
            $file = str_replace('\\', '/', rtrim(public_path(), DIRECTORY_SEPARATOR).$row['file']);
            if (!file_exists($file)) {
                $this->error(__('File does not exist'));
            }

            $data = file_get_contents($file);
            //解析
            $data = json_decode(base64_decode($data), true);
            if (empty($data) || !isset($data['model']) || !isset($data['model_field'])) {
                $this->error(__('Parsing failed'));
            }

            // 获取sql模板
            $one = Db::name('model_controller')->where(['name'=>$data['model']['controller']])->find();
            $path = $one['sql_file'];
            if (empty($path)) {
                $this->error(__('SQL file is empty'));
            }
            $path = explode(',', $path);

            Db::startTrans();
            try {
                // 模型表数据
                $addModel = array_merge($data['model'], $row);
                unset($addModel['file']);
                $addModel['update_time'] = time();
                $addModel['create_time'] = time();
                $id = Db::name('model')->insertGetId($addModel);

                // 数据库创建
                $prefix = Db::name('model')->getConfig('prefix');
                $res = \libs\table\TableOperate::instance([
                    'tablename' => $addModel['tablename'],
                    'prefix'    => $prefix,
                    'model_id'  => $id,
                    'sql_file'  => $path
                ])->createTables();
                if (is_string($res)) {
                    throw new \think\Exception($res);
                }

                // 获取新增表的模型字段
                $newFields = Db::name('model_field')->where(['model_id'=>$id])->select()->toArray();

                // 用导入的模型字段跟新增的表字段对比，不在新增的表字段里面的新增记录
                $addModelField = [];
                foreach ($data['model_field'] as $value) {
                    $bl = true;
                    foreach ($newFields as $k=>$v) {
                        if ($value['field_name']==$v['field_name']) {
                            $bl = false;
                            break;
                        }
                    }

                    if ($bl) { // 不在新增表里的字段，新增进去
                        $bl = \libs\table\TableOperate::instance()
                            ->setTable($prefix.$addModel['tablename'])
                            ->setField($value['field_name'])
                            ->setType($value['form_type'])
                            ->setDataList($value['data_list'] ?? null)
                            ->setDecimals($value['decimals'] ?? null)
                            ->setLength(intval($value['length'] ?? 0))
                            ->setDefault($value['default_value'] ?? '')
                            ->setComment($value['field_title'])
                            ->addField();
                        if (is_string($bl)) {
                            throw new \Exception("$bl");
                        }
                        $value['update_time'] = time();
                        $value['create_time'] = time();
                        $value['model_id'] = $id;
                        $addModelField[] = $value;
                    }
                }
                Db::name('model_field')->insertAll($addModelField);

                // 用新增的表字段跟导入的做对比，新表字段不在导入里面的，删除新表字段
                foreach ($newFields as $k=>$v) {
                    $bl = true;
                    foreach ($data['model_field'] as $value) {
                        if ($v['field_name']==$value['field_name']) {
                            $bl = false;
                            break;
                        }
                    }
                    if ($bl) {
                        $bl = \libs\table\TableOperate::instance()
                            ->setTable($prefix.$addModel['tablename'])
                            ->setField($v['field_name'])
                            ->deleteField();
                        if (is_string($bl)) {
                            throw new \Exception("$bl");
                        }
                        Db::name('model_field')->where(['id'=>$v['id']])->delete();
                    }
                }

                Db::commit();
                @unlink($file);
            } catch (\Exception $exception) {
                Db::rollback();
                $this->error($exception->getMessage());
            }
            $this->success();
        }
        return $this->view->fetch();
    }

    /**
     * 复制
     * @param null $id
     * @return string|void
     */
    public function copy($id = null)
    {
        $row = $this->model->find($id);
        if (!$row) {
            $this->error(__('No results were found'));
        }

        if ($this->request->isAjax()) {
            $post = $this->request->post('row');
            $this->validate($post, '\app\admin\validate\cms\Model.import',[]);

            // 获取原模型数据
            $modelField = Db::name('model_field')->where(['model_id'=>$id])->select()->toArray();
            $data['model'] = $row->toArray();
            $data['model_field'] = $modelField;

            // 获取sql模板
            $one = Db::name('model_controller')->where(['name'=>$data['model']['controller']])->find();
            $path = $one['sql_file'];
            if (empty($path)) {
                $this->error(__('SQL file is empty'));
            }
            $path = explode(',', $path);

            Db::startTrans();
            try {
                // 模型表数据
                $addModel = array_merge($data['model'], $post);
                unset($addModel['id']);
                $addModel['update_time'] = time();
                $addModel['create_time'] = time();
                $id = Db::name('model')->insertGetId($addModel);

                // 数据库创建
                $prefix = Db::name('model')->getConfig('prefix');
                $res = \libs\table\TableOperate::instance([
                    'tablename' => $addModel['tablename'],
                    'prefix'    => $prefix,
                    'model_id'  => $id,
                    'sql_file'  => $path
                ])->createTables();
                if (is_string($res)) {
                    throw new \think\Exception($res);
                }

                // 获取新增表的模型字段
                $newFields = Db::name('model_field')->where(['model_id'=>$id])->select()->toArray();

                // 用导入的模型字段跟新增的表字段对比，不在新增的表字段里面的新增记录
                $addModelField = [];
                foreach ($data['model_field'] as $value) {
                    $bl = true;
                    foreach ($newFields as $k=>$v) {
                        if ($value['field_name']==$v['field_name']) {
                            // 更新新建字段信息
                            $tmpValue = $value;
                            unset($tmpValue['id']);
                            unset($tmpValue['model_id']);
                            unset($tmpValue['length']);
                            unset($tmpValue['default_value']);
                            Db::name('model_field')->where(['id'=>$v['id']])->update($tmpValue);
                            $bl = false;
                            break;
                        }
                    }

                    if ($bl) { // 不在新增表里的字段，新增进去
                        $bl = \libs\table\TableOperate::instance()
                            ->setTable($prefix.$addModel['tablename'])
                            ->setField($value['field_name'])
                            ->setType($value['form_type'])
                            ->setDataList($value['data_list'] ?? null)
                            ->setDecimals($value['decimals'] ?? null)
                            ->setLength(intval($value['length'] ?? 0))
                            ->setDefault($value['default_value'] ?? '')
                            ->setComment($value['field_title'])
                            ->addField();
                        if (is_string($bl)) {
                            throw new \Exception("$bl");
                        }
                        unset($value['id']);
                        $value['update_time'] = time();
                        $value['create_time'] = time();
                        $value['model_id'] = $id;
                        $addModelField[] = $value;
                    }
                }

                Db::name('model_field')->insertAll($addModelField);

                // 用新增的表字段跟导入的做对比，新表字段不在导入里面的，删除新表字段
                foreach ($newFields as $k=>$v) {
                    $bl = true;
                    foreach ($data['model_field'] as $value) {
                        if ($v['field_name']==$value['field_name']) {
                            $bl = false;
                            break;
                        }
                    }
                    if ($bl) {
                        $bl = \libs\table\TableOperate::instance()
                            ->setTable($prefix.$addModel['tablename'])
                            ->setField($v['field_name'])
                            ->deleteField();
                        if (is_string($bl)) {
                            throw new \Exception("$bl");
                        }
                        Db::name('model_field')->where(['id'=>$v['id']])->delete();
                    }
                }

                Db::commit();
            } catch (\Exception $exception) {
                Db::rollback();
                $this->error($exception->getMessage());
            }
            $this->success();
        }

        $this->view->assign('row', $row->toArray());
        return $this->view->fetch();
    }

    /**
     * 模型预览
     * @return string|void
     */
    public function preview()
    {
        $model_id = $this->request->get('model_id');
        $row = $this->model->find($model_id);
        if (!$row) {
            $this->error(__('No results were found'));
        }

        // 获取字段分组
        $fieldGroup = \app\admin\model\cms\ModelField::where(['status'=>'normal','model_id'=>$model_id,'admin_auth'=>1])->group('field_group')->column('field_group');
        $modelField = \app\admin\model\cms\ModelField::where(['status'=>'normal','model_id'=>$model_id,'admin_auth'=>1])->order('weigh', 'asc')->select()->toArray();
        $modelFieldArr = [];
        foreach ($modelField as $key=>$value) {
            foreach ($fieldGroup as $k=>$v) {
                if ($value['field_group'] == $v) { // 字段分组
                    $modelFieldArr[$v][] = $value;
                }
            }
        }

        // 生成表单
        $this->view->layout(false);
        foreach ($modelFieldArr as $key=>$value) {
            $modelFieldArr[$key] = $this->view->fetch('cms/content/field', ['data'=>$value,'row'=>$row]);
        }

        // 获取当前模型所有栏目
        $category_list = (new \app\admin\model\cms\Category)->getModelCategory($model_id);

        $this->view->assign(compact('modelFieldArr','category_list','fieldGroup','row'));
        $this->view->layout(true);
        return $this->view->fetch();
    }

    /**
     * 获取模板列表
     * @return \think\response\Json
     */
    public function getTplName()
    {
        $type = $this->request->param('type', 'category');
        $searchValue = $this->request->param('searchValue');
        $path = get_template_path('index');

        $path = $path.$type.DIRECTORY_SEPARATOR;
        if (!is_dir($path)) {
            $this->error(__('%s not exist',[$path]));
        }

        $list = str_replace($path, '', glob($path.'*.html'));
        $arr = [];
        foreach ($list as $key=>$value) {
            if (!empty($searchValue) && $value!=$searchValue) {
                continue;
            }
            $arr[]['name'] = $value;
        }
        return json(['rows'=>$arr,'total'=>count($arr),'code'=>200,'data'=>$arr]);
    }

    /**
     * 模型配置
     * @return string|void
     */
    public function config()
    {
        $model_id = $this->request->get('id');
        $row = $this->model->find($model_id);
        if (!$row) {
            $this->error(__('No results were found'));
        }

        // 获取整个配置文件
        $mc = Db::name('model_controller')->where(['name'=>$row['controller']])->find();
        if (empty($mc) || empty($mc['config'])) {
            die(__('No results were found'));
        }
        $config = json_decode($mc['config'], true);

        // 获取模型保存的配置
        $modelCofnig = $row['config'] ? json_decode($row['config'], true) : [];

        if ($this->request->isPost()) {
            $post = $this->request->post('row');
            if (empty($post)) {
                $this->error(__('Parameter %s can not be empty',['']));
            }

            $up = [];
            foreach ($config as $key=>&$value) {
                if ($value['type']=='checkbox' && !isset($post[$key])) {
                    $up[$key] = '';
                }
                if (isset($post[$key])) {
                    $up[$key] = $post[$key];
                }
            }

            $this->model->where(['id'=>$model_id])->save(['config'=>json_encode($up,JSON_UNESCAPED_UNICODE)]);

            Cache::tag(['guestbook_tag'])->clear();
            $this->success();
        }

        // 生成表单
        foreach ($modelCofnig as $key=>$value) {
            if (isset($config[$key]['value'])) {
                if (is_array($value)) {
                    $config[$key]['value'] = implode(',', $value);
                } else {
                    $config[$key]['value'] = $value;
                }
            }
        }
        $this->view->layout(false);
        $sdata = $this->view->fetch('appcenter/field', ['data'=>$config,'group'=>'']);

        $this->view->layout(true);
        $this->view->assign('html', $sdata);

        return $this->view->fetch();
    }
}