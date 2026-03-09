<?php
// +----------------------------------------------------------------------
// |HkCms 模型字段
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\controller\cms;

use app\admin\controller\BaseController;
use app\common\model\LangBind;
use think\db\exception\DbException;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Validate;

class ModelField extends BaseController
{
    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [
        'login',
        'auth'=>['except'=>['getRules','fieldGroup','getFields','setSetting']]
    ];

    /**
     * 允许批量修改的字段
     * @var array
     */
    protected $allowFields = ['status','weigh','user_auth','is_order','is_filter','admin_auth'];

    /**
     * @var \app\admin\model\cms\ModelField
     */
    public $model;

    private $modelId = 0;

    /**
     * 开启验证
     * @var bool
     */
    protected $enableValidate = true;
    /**
     * 开启场景验证
     * @var bool
     */
    protected $enableScene = true;

    public function initialize()
    {
        parent::initialize();
        $this->model = new \app\admin\model\cms\ModelField;
        $this->modelId = $this->request->param('model_id');
        $this->view->assign('model_id', $this->modelId);
    }

    public function index()
    {
        if ($this->request->isAjax()) {

            if ($this->request->param('searchTable')) {
                return $this->selectPage(); // 判断请求。如果是动态下拉组件请求，则交接给selectPage方法
            }

            $list = $this->model->where('model_id', $this->modelId)->order(['iscore'=>'asc','weigh'=>'asc'])->select();
            $data = $list->append(['iscore_text','default_field_text'])->toArray();
            return json(['total'=>count($list),'rows'=>$data]);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     * @return mixed|string|void
     */
    public function add()
    {
        $info = Db::name('model')->where(['id'=>$this->modelId])->find();
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
                            $validate = $validate->scene('add');
                        }
                        $validate->check($params);
                    }
                    $result = $this->model->save($params);
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
                    Cache::tag([strtolower($info['controller']).'_tag', 'model_field', 'modelFieldsService'])->clear();
                    $this->success();
                } else {
                    $this->error(__('No rows added'));
                }
            }
            $this->error(__('Parameter %s can not be empty',['']));
        }
        $table = Db::getTables();
        foreach ($table as $key=>$value) {
            if (strpos($value,env('DATABASE.PREFIX'))===false) {
                unset($table[$key]);
                continue;
            }
            $table[$key] = preg_replace('/'.env('DATABASE.PREFIX').'/','',$value,1);
        }
        $this->view->assign('info', $info);
        $this->view->assign('table', $table);
        return $this->view->fetch();
    }

    public function edit($id = null)
    {
        $row = $this->model->find($id);
        $info = Db::name('model')->where(['id'=>$this->modelId])->find();
        if (!$row) {
            $this->error(__('No results were found'));
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params['field_name'] = $row->default_field==1?$row->field_name:$params['field_name'];
                $params['form_type'] = $row->form_type;
                $params['status'] = empty($params['status']) ? $row->status : $params['status'];
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
                    Cache::tag([strtolower($info['controller']).'_tag', 'model_field', 'modelFieldsService'])->clear();
                    $this->success();
                } else {
                    $this->error(__('No changes'));
                }
            }
            $this->error(__('Parameter %s can not be empty',['']));
        } else {
            $valArr = [];
            $row = $row->toArray();
            $row['setting'] = !empty($row['setting']) ? json_decode($row['setting'], true) : [];
            if ('array'!=$row['form_type'] && 'selectpage'!=$row['form_type']) {
                foreach ($row['data_list'] as $key=>$value) {
                    $valArr[] = $key.'|'.$value;
                }
                $row['data_list'] = implode("\r\n", $valArr);
            }

            if ($row['is_filter']==1 && !empty($row['setting']['filter_option'])) {
                $fo = json_decode($row['setting']['filter_option'], true);
                $valArr = [];
                foreach ($fo as $key=>$value) {
                    $valArr[] = $key.'|'.$value;
                }
                $row['setting']['filter_option'] = implode("\r\n", $valArr);
            } else {
                $row['setting']['filter_option'] = '';
            }
            // 获取表
            $info = Db::name('model')->where(['id'=>$row['model_id']])->find();

            // 获取表
            $table = $field = [];
            if ($row['form_type']=='selectpage') {
                $table = Db::getTables();
                if ($row['data_list']['type']=='table') {
                    $field = Db::getFields(env('DATABASE.PREFIX').$row['data_list']['table']);
                    $f = [];
                    foreach ($field as $key=>$value) {
                        $f[] = $key;
                    }
                    $field = $f;
                }
            }

            if (!empty($table)) {
                foreach ($table as $key=>$value) {
                    if (strpos($value,env('DATABASE.PREFIX'))===false) {
                        unset($table[$key]);
                        continue;
                    }
                    $table[$key] = preg_replace('/'.env('DATABASE.PREFIX').'/','',$value,1);
                }
            }

            $this->view->assign('table', $table);
            return $this->view->fetch('', compact('row','info','table','field'));
        }
    }

    /**
     * 获取验证规则
     * @return \think\response\Json
     */
    public function getRules()
    {
        $searchValue = $this->request->param('searchValue');
        $searchValue = $searchValue?explode(',', $searchValue):[];
        $rules = config('cms.rule_lists');
        $arr = [];
        foreach ($rules as $key=>$value) {
            if (!empty($searchValue) && !in_array($key, $searchValue)) {
                continue;
            }
            $arr[] = ['name'=>$key,'title'=>$value];
        }
        return json(['rows'=>$arr]);
    }

    /**
     * 字段分组
     * @return \think\response\Json
     */
    public function fieldGroup()
    {
        $searchValue = $this->request->param('searchValue', '');
        if (!empty($searchValue)) {
            return json(['rows'=>[['title'=>$searchValue]]]);
        }

        $arr = $this->request->param('searchField');
        $name = $this->request->param($arr[0], '');

        $model_id = $this->request->param('model_id', '');
        $data = $this->model->where(['model_id'=>$model_id])->group('field_group')->column('field_group');
        if (!empty($name) && !in_array($name, $data)) {
            array_push($data, $name);
        }

        $arr = [];
        foreach ($data as $key=>$value) {
            $arr[] = ['title'=>$value];
        }

        return json(['rows'=>$arr]);
    }

    /**
     * 字段与栏目绑定
     * @return string|void
     */
    public function fieldCategory()
    {
        if ($this->request->isPost()) {
            $row = $this->request->post('row/a');
            $add = [];

            if (site('content_lang_on')==1) {
                foreach ($row['category_id'] as $key=>$value) {
                    if (!is_numeric($value) || $value<=0) {
                        continue;
                    }
                    $value = lang_content_get('category', $value);
                    foreach ($value as $k=>$v) {
                        $temp['model_field_id'] = $row['model_field_id'];
                        $temp['category_id'] = $v;
                        $temp['update_time'] = time();
                        $temp['create_time'] = time();
                        $add[] = $temp;
                    }
                }
            } else {
                foreach ($row['category_id'] as $key=>$value) {
                    if (!is_numeric($value) || $value<=0) {
                        continue;
                    }
                    $temp['model_field_id'] = $row['model_field_id'];
                    $temp['category_id'] = $value;
                    $temp['update_time'] = time();
                    $temp['create_time'] = time();
                    $add[] = $temp;
                }
            }

            \app\admin\model\cms\ModelFieldBind::where(['model_field_id'=>$row['model_field_id']])->delete();
            if ($add) {
                \app\admin\model\cms\ModelFieldBind::insertAll($add);
            }
            $this->success();
        }

        $model_id = $this->request->param('model_id','','intval');
        $field_id = $this->request->param('field_id','','intval');
        if (!$model_id || !$field_id) {
            $this->error(__('Parameter %s can not be empty',['']));
        }

        // 获取对应字段的栏目
        $categoryCurArr = \app\admin\model\cms\ModelFieldBind::where(['model_field_id'=>$field_id])->column('category_id');
        $category = (new \app\admin\model\cms\Category)->getModelCategory($model_id);

        $this->view->assign(compact('model_id','field_id','category','categoryCurArr'));
        return $this->view->fetch();
    }

    /**
     * 获取表字段
     */
    public function getFields()
    {
        $table = $this->request->param('t');
        if (empty($table)) {
            $this->error(__('Parameter %s can not be empty',['']));
        }
        if (!Validate::is($table,'alphaDash')) {
            $this->error(__('Parameter %s can not be empty',['']));
        }

        $field = Db::getFields(env('DATABASE.PREFIX').$table);
        $f = [];
        foreach ($field as $key=>$value) {
            $f[] = $key;
        }
        $this->success('','',['field'=>$f]);
    }

    /**
     * 设置多语言默认值
     */
    public function setSetting()
    {
        $val = $this->request->post('val');
        $field = $this->request->post('field');
        $model_id = $this->request->post('model_id');

        $info = $this->model->where(['field_name'=>$field,'model_id'=>$model_id])->find();

        $setting = $info->setting ? json_decode($info->setting, true) : [];
        $setting['default_value'] = [];
        $setting['default_value'][$this->contentLang] = json_decode($val, true);
        $setting = json_encode($setting);

        $this->model->where(['field_name'=>$field,'model_id'=>$model_id])->update(['setting'=>$setting]);
        $this->success();
    }

    /**
     * 批量修改指定字段。
     */
    public function batches()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->only(['ids'=>'','params'=>'']);
            if (empty($data['ids']) || empty($data['params'])) {
                $this->error(__('Parameter %s can not be empty',['']));
            }

            // 参数转换
            parse_str($data['params'], $arr);
            $addArr = [];
            foreach ($arr as $key=>$value) {
                if (!\think\facade\Validate::is($key,'alphaDash')) {
                    $this->error(__('The field name can only be letters, numbers, underscores, dashes'));
                }
                if (!\think\facade\Validate::is($value,'chsDash')) {
                    $this->error(__('Field value Chinese characters, letters, numbers, and underscores _ and dashes -'));
                }
                if (in_array($key, $this->allowFields)) {
                    $addArr[$key] = $value;
                }
            }
            if (empty($addArr)) {
                $this->error(__('Operation failed: there are no fields to operate!'));
            }

            $list = $this->model->where('id', 'in', $data['ids'])->select();
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
                $info = Db::name('model')->where(['id'=>$this->modelId])->find();
                Cache::tag([strtolower($info['controller']).'_tag', 'model_field', 'modelFieldsService'])->clear();
                $this->success();
            } else {
                $this->error(__('No changes'));
            }
        } else {
            $this->error(__('Illegal request'));
        }
    }

    /**
     * 数据删除
     * @param string $ids
     * @throws DbException
     */
    public function del($ids = '')
    {
        if ($ids) {
            $list = $this->model->where('id', 'in', $ids)->select();
            $count = 0;
            Db::startTrans();
            try {
                foreach ($list as $k => $v) {
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
                $info = Db::name('model')->where(['id'=>$this->modelId])->find();
                Cache::tag([strtolower($info['controller']).'_tag', 'model_field', 'modelFieldsService'])->clear();
                $this->success();
            } else {
                $this->error(__('No rows deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty',['ids']));
    }
}