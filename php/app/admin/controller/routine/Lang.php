<?php
// +----------------------------------------------------------------------
// | HkCms 多语言控制器
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\admin\controller\routine;

use app\admin\controller\BaseController;
use app\admin\validate\routine\LangValidate;
use app\common\model\lang\Lang as LangModel;
use app\common\services\lang\LangService;
use think\db\exception\DbException;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;

class Lang extends BaseController
{
    /**
     * 模型
     * @var LangModel
     */
    protected $model;

    /**
     * 权限、登录控制
     * @var string[]
     */
    protected $middleware = [
        'login',
        'auth'
    ];

    /**
     * 初始化操作
     */
    public function initialize()
    {
        parent::initialize();
        // 模型
        $this->model = new LangModel();
        $this->view->assign("moduleArr", [
            1=>__("Frontend language"),
            2=>__("Backend language"),
            3=>__("Content language")
        ]);
    }

    /**
     * 添加
     * @return mixed|string|null
     */
    public function add()
    {
        $module = $this->request->param('module');
        if ($this->request->isPost()) {
            $row = $this->request->post("row/a");
            // 验证
            $validate = validate(LangValidate::class);
            $validate->check($row);
            // 检查是否存在
            if ($this->model->where(['module'=>$row['module'],'mark'=>$row['mark']])->find()) {
                $this->error(__('Language mark already exists'));
            }
            /** @var LangService $langService 语言服务 */
            $langService = app()->make(LangService::class);
            try {
                Db::startTrans();
                if (!empty($row['sync']) && in_array(1, $row['sync'])) {
                    if (!$langService->isExist(['mark'=>$row['mark'], 'module'=>1])) {
                        $add = $row;
                        $add['module'] = 1;
                        LangModel::create($add);
                    }
                }
                if (!empty($row['sync']) && in_array(2, $row['sync'])) {
                    if (!$langService->isExist(['mark'=>$row['mark'], 'module'=>2])) {
                        $add = $row;
                        $add['module'] = 2;
                        LangModel::create($add);
                    }
                }
                if (!empty($row['sync']) && in_array(3, $row['sync']) || $row['module']==3) {
                    if (!$langService->isExist(['mark'=>$row['mark'], 'module'=>3])) {
                        $langService->sync($row['mark'], 'add');
                        if ($row['module']!=3) {
                            $add = $row;
                            $add['module'] = 3;
                            LangModel::create($add);
                        }
                    }
                }
                $this->model->save($row);
                Db::commit();
            } catch (\Exception $exception) {
                Db::rollback();
                $this->error($exception->getMessage());
            }

            Cache::tag(LangService::CACHE_TAG)->clear();
            $this->success();
        }
        $this->view->assign('module', $module);
        return $this->view->fetch();
    }

    public function edit($id = null)
    {
        $row = $this->model->find($id);
        if (!$row) {
            $this->error(lang('Record does not exist'));
        }

        if ($this->request->isPost()) {
            $data = $this->request->post("row/a");
            // 验证
            $validate = validate(LangValidate::class);
            $validate->check($data);
            // 检查是否存在
            if ($this->model->where(['module'=>$row['module'],'mark'=>$data['mark']])->where('id', '<>', $row['id'])->find()) {
                $this->error(__('Language mark already exists'));
            }
            if ($data['status']==0 && $row['is_default']==1) {
                $this->error(__('The default language cannot be set to hidden'));
            }
            /** @var LangService $langService 语言服务 */
            $langService = app()->make(LangService::class);
            Db::startTrans();
            try {
                if ($row['mark'] != $data['mark'] && $data['module']==3) { // 同步

                    $langService->sync($data['mark'], 'edit', $row['mark']);
                }
                $row->save($data);
                Db::commit();
            } catch (\Exception $exception) {
                Db::rollback();
                $this->error($exception->getMessage());
            }
            Cache::tag(LangService::CACHE_TAG)->clear();
            $this->success();
        }
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }

    /**
     * 设置默认
     * @param LangService $langService
     * @return void
     * @throws \app\common\exception\ServiceException
     */
    public function setDefault(LangService $langService)
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $this->error("非法参数");
        }
        $langService->setDefaultLang($id);
        $this->success();
    }

    public function del($ids = '')
    {
        if ($ids) {
            $list = $this->model->where('id', 'in', $ids)->select();

            $count = 0;
            Db::startTrans();
            $langService = app()->make(LangService::class);
            try {
                foreach ($list as $k => $v) {
                    if ($v['is_default']==1) {
                        throw new \Exception(__('The default language cannot be deleted'));
                    }
                    if ($v['module']==3) {
                        $langService->sync($v['mark'], 'del');
                    }
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
                Cache::tag(LangService::CACHE_TAG)->clear();
                $this->success();
            } else {
                $this->error(__('No rows deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty',['ids']));
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
                if (in_array($key, ['status'])) {
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
                foreach ($list as $index => $item) {
                    if ($item['is_default']==1) {
                        throw new \Exception(__('The default language cannot be set to hidden'));
                    }
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
                $this->success();
            } else {
                $this->error(__('No changes'));
            }
        } else {
            $this->error(__('Illegal request'));
        }
    }
}