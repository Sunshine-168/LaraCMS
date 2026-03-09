<?php
// +----------------------------------------------------------------------
// | HkCms 字段管理
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

namespace app\admin\controller\cms;

use app\admin\controller\BaseController;
use think\facade\Db;

class Fields extends BaseController
{
    /**
     * @var \app\admin\model\cms\Fields
     */
    public $model;

    protected $middleware = [
        'login'
    ];

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

    protected $source = '';
    protected $source_id = 0;

    public function initialize()
    {
        parent::initialize();
        $this->model = new \app\admin\model\cms\Fields;

        $this->source = $this->request->param('source','');
        $this->source_id = $this->request->param('source_id',0);
        $this->view->assign('source', $this->source);
        $this->view->assign('source_id', $this->source_id);
    }

    public function index()
    {
        if ($this->request->isAjax()) {
            list($map, $limit, $offset, $order, $sort) = $this->buildparams();
            $map[] = ['source','=',$this->source];
            if ($this->source_id) {
                $map[] = ['source_id','=',$this->source_id];
            }
            $data = $this->model->where($map)->order($sort, $order)->limit($offset, $limit)->select()->toArray();
            $total = $this->model->where($map)->order($sort, $order)->count();
            return json(['total'=>$total,'rows'=>$data]);
        }

        return $this->view->fetch();
    }

    /**
     * 添加
     * @return mixed|string|void
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $add = $this->request->post("row/a");
            $this->postData = $add;
            parent::add();
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

    public function edit($id = null)
    {
        $row = $this->model->find($id);
        if (!$row) {
            $this->error(__('No results were found'));
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $params['form_type'] = $row->form_type;
            $this->postData = $params;
            parent::edit($id);
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

            // 动态下拉的处理
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
            return $this->view->fetch('', compact('row','field','table'));
        }
    }

    /**
     * 字段分组
     * @return \think\response\Json
     */
    public function fieldGroup()
    {
        $searchValue = $this->request->param('searchValue', '');
        $arr = $this->request->param('searchField');
        $name = $this->request->param($arr[0], '');
        if (!empty($searchValue)) {
            return json(['rows'=>[['title'=>$searchValue]]]);
        }

        $source = $this->request->param('source', '');
        $source_id = $this->request->param('source_id', 0);
        $data = $this->model->where(['source'=>$source,'source_id'=>$source_id])->group('field_group')->column('field_group');
        if (!empty($name) && !in_array($name, $data)) {
            array_push($data, $name);
        }

        $arr = [];
        foreach ($data as $key=>$value) {
            $arr[] = ['title'=>$value];
        }

        return json(['rows'=>$arr]);
    }
}