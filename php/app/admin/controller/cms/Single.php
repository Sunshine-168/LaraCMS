<?php
// +----------------------------------------------------------------------
// | HkCms 单页模型
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\controller\cms;

use app\admin\controller\BaseController;
use app\admin\library\Html;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;

class Single extends BaseController
{
    /**
     * 文章模型
     * @var \app\admin\model\cms\Archives
     */
    protected $model;

    protected $category_id = 0;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [
        'login',
    ];

    /**
     * 初始化操作
     */
    public function initialize()
    {
        parent::initialize();

        $this->model = new \app\admin\model\cms\Single;

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
        $category_id = $this->category_id;
        $categoryInfo = \app\admin\model\cms\Category::where(['id'=>$category_id,'status'=>'normal','lang'=>$this->contentLang])->find();
        if (empty($categoryInfo)) {
            $this->error(__('Column information does not exist'));
        }
        $modelInfo = \app\admin\model\cms\Model::where(['status'=>'normal', 'id'=>$categoryInfo['model_id']])->find();
        if (empty($modelInfo)) {
            $this->error(__('Model information does not exist'));
        }
        $row = $this->model->where(['category_id'=>$category_id])->find();

        if ($this->request->isPost()) {
            $data = $this->request->post('row/a', '', null);
            $tempData = $data;
            $modelField = (new \app\admin\model\cms\ModelFieldBind)->getAllowField($categoryInfo['id'], $categoryInfo['model_id'], $data);

            // 验证
            list($valData,$msgData) = build_tp_rules($modelField);
            try {
                $this->validate($tempData, $valData, $msgData);
            } catch (ValidateException $e) {
                $this->error($e->getError());
            }
            $tablename = $modelInfo['tablename'];
            Db::startTrans();
            try {
                if (!empty($row)) {
                    $row->save(array_merge([
                        'model_id'=>$categoryInfo['model_id'],
                        'admin_id'=>$this->user->id,
                        'category_id'=>$category_id,
                        'lang'=>$categoryInfo['lang']
                    ],$data['main']));
                    if (Db::name($tablename)->where(['id'=>$row->id])->find()) {
                        Db::name($tablename)->where(['id'=>$row->id])->update($data['vice']);
                    } else {
                        Db::name($tablename)->insert(array_merge(['id'=>$row->id], $data['vice']));
                    }
                } else {
                    $this->model->save(array_merge([
                        'model_id'=>$categoryInfo['model_id'],
                        'admin_id'=>$this->user->id,
                        'category_id'=>$category_id,
                        'lang'=>$categoryInfo['lang']
                    ],$data['main']));
                    Db::name($tablename)->insert(array_merge(['id'=>$this->model->id],$data['vice']));
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            Cache::tag('single_tag')->clear();
            // 静态生成
            if (site('url_mode')==2) {
                $this->view->layout(false);
                $lang = $this->app->lang->getLangSet();
                (new Html(app()))->category($categoryInfo->toArray(), 1);
                $this->app->lang->setLangSet($lang);
            }

            $this->success();
        }
        if ($row) {
            $content = Db::name($modelInfo['tablename'])->find($row->id);
            $content = empty($content) ? [] : $content;
            $row = array_merge($content, $row->toArray());
        }
        $this->buildPage($categoryInfo, $row);
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }
}