<?php
// +----------------------------------------------------------------------
// | HkCms 留言模型
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\controller\cms;

use app\admin\controller\BaseController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\db\exception\DbException;
use think\facade\Db;

class Guestbook extends BaseController
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
     * @var \app\admin\model\cms\Guestbook
     */
    protected $model;

    protected $category_id = 0;

    /**
     * 初始化操作
     */
    public function initialize()
    {
        parent::initialize();

        $this->model = new \app\admin\model\cms\Guestbook;
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
        $input = $this->request->param();
        if (empty($input['category_id'])) {
            $this->error(__('Column information does not exist'));
        }
        if (empty($input['model_id'])) {
            $this->error(__('Model information does not exist'));
        }
        // 获取对应表
        $tablename = Db::name('model')->where(['id'=>$input['model_id']])->value('tablename');
        if (empty($tablename)) {
            $this->error(__('Model information does not exist'));
        }

        // 获取表字段
        $data = [];
        $modelField = (new \app\admin\model\cms\ModelFieldBind)->getAllowField($input['category_id'], $input['model_id'], $data);
        $col = ['id','category_tilte'];
        $colName = ['id'=>'ID','category_tilte'=>__('Column')];
        $i = 5;
        foreach ($modelField as $key=>$value) {
            if ($i<=0) {
                break; // 只显示5个字段
            }
            if (in_array($value['form_type'],['editor','textarea'])) {
                continue;
            }
            $col[] = $value['field_name'];
            $colName[$value['field_name']] = $value['field_title'];
            $i--;
        }
        $col[] = 'create_time';
        $colName['create_time'] = __('Create time');

        // 筛选条件
        $map = [['category_id','=',$input['category_id']]];
        $param = [];
        if (!empty($input['id']) && is_numeric($input['id'])) {
            $map[] = ['id','=',$input['id']];
            $param['id'] = $input['id'];
        }
        if (!empty($input['is_read']) && is_numeric($input['is_read'])) {
            $map[] = ['is_read','=',$input['is_read']==1?1:0];
            $param['is_read'] = $input['is_read'];
        }
        if (!empty($input['create_time'])) {
            $arr = explode(' - ', $input['create_time']);
            if (count($arr)==2) {
                $map[] = ['create_time', 'BETWEEN TIME', $arr];
            }
            $param['create_time'] = $input['create_time'];
        }

        $list = $this->model
            ->name($tablename)
            ->with(['category'])
            ->where($map)
            ->order('create_time','desc')
            ->paginate(['query'=>$input,'list_rows'=>10],false);
        $page = $list->render(['item'=>'pre,pageno,next','pre'=>'‹','next'=>'›']);
        $data = $list->toArray();

        $this->view->assign($input);
        $this->view->assign(['col'=>$col,'data'=>$data,'colName'=>$colName,'page'=>$page,'model_id'=>$input['model_id'],'category_id'=>$input['category_id'],'param'=>$param]);
        return $this->view->fetch();
    }

    /**
     * 导出
     */
    public function export()
    {
        $category_id = $this->request->param('category_id', '', 'intval');
        $modelInfo = $this->model->getTableInfo($category_id);

        $cateInfo = \app\admin\model\cms\Category::where(['id'=>$category_id])->find();

        // 获取表字段
        $data = [];
        $modelField = (new \app\admin\model\cms\ModelFieldBind)->getAllowField($category_id, $modelInfo['id'], $data);
        $col = ['id','category_tilte'];
        $colName = ['id'=>'ID','category_tilte'=>__('Column')];
        foreach ($modelField as $key=>$value) {
            $col[] = $value['field_name'];
            $colName[$value['field_name']] = $value['field_title'];
        }
        $col[] = 'ip';
        $col[] = 'create_time';
        $colName['ip'] = __('IP');
        $colName['create_time'] = __('Create time');

        $data = $this->model
            ->name($modelInfo['tablename'])
            ->with(['category'])
            ->where(['category_id'=>$category_id])
            ->order('create_time','desc')
            ->select()->toArray();

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle($cateInfo['title']);
        $j = 1;
        foreach ($colName as $key=>$value) {
            $worksheet->setCellValueByColumnAndRow($j, 1, $value);
            $j++;
        }

        $j = 2;
        foreach ($data as $key=>$value) {
            foreach ($col as $k=>$v) {
                if ($v=='category_tilte'){
                    $worksheet->setCellValueByColumnAndRow($k+1, $j, $value['category']['title']);
                } else {
                    $worksheet->setCellValueByColumnAndRow($k+1, $j, $value[$v]);
                }
            }
            $j++;
        }

        $filename = $cateInfo['title'].'_'.date('YmdHis').'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
    }

    /**
     * 删除
     * @param string $ids
     */
    public function del($ids = '')
    {
        if ($ids) {
            $category_id = $this->request->param('category_id', '', 'intval');
            $modelInfo = $this->model->getTableInfo($category_id);
            $list = $this->model->setTable($modelInfo['tablename'])->where('id', 'in', $ids)->select();
            $count = 0;
            Db::startTrans();
            try {
                foreach ($list as $v) {
                    $count += $v->setTable($modelInfo['tablename'])->delete();
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
                $this->success();
            } else {
                $this->error(__('No rows deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', ['ids']));
    }

    /**
     * 查看详情
     */
    public function view()
    {
        $id = $this->request->param('id', '', 'intval');
        $category_id = $this->request->param('category_id', '', 'intval');
        $modelInfo = $this->model->getTableInfo($category_id);

        $data = [];
        $modelField = (new \app\admin\model\cms\ModelFieldBind)->getAllowField($category_id, $modelInfo['id'], $data);

        $col = ['ip','read_time'];
        $colName = ['ip'=>__('IP'),'read_time'=>__('View time')];
        foreach ($modelField as $key=>$value) {
            $col[] = $value['field_name'];
            $colName[$value['field_name']] = $value['field_title'];
        }
        $col[] = 'create_time';
        $colName['create_time'] = __('Create time');

        $info = $this->model->name($modelInfo['tablename'])->where(['id'=>$id])->find();
        if (empty($info)) {
            $this->error(__('No results were found'));
        }

        if (isset($info['is_read'])) {
            if ($info['is_read']==0) {
                $this->model->name($modelInfo['tablename'])->where(['id'=>$id])->update(['is_read'=>1,'read_time'=>time()]);
                $info['read_time'] = time();
            }
            $info['read_time'] = is_numeric($info['read_time']) ? date('Y-m-d H:i:s', $info['read_time']) : $info['read_time'];
        }

        $this->view->assign(['col'=>$col,'data'=>$data,'colName'=>$colName,'info'=>$info->toArray()]);
        return $this->view->fetch();
    }
}