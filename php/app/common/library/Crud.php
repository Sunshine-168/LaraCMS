<?php

namespace app\common\library;

use think\db\exception\DbException;
use think\exception\ValidateException;
use think\facade\Db;

/**
 * Trait Crud crud操作
 * @property \think\Model model
 * @property array postData
 * @package app\common\library
 */
trait Crud
{
    /**
     * 排除提交过来的字段
     * @param $params
     * @return array
     */
    protected function preExcludeFields($params)
    {
        if (is_array($this->excludeFields)) {
            foreach ($this->excludeFields as $field) {
                if (array_key_exists($field, $params)) {
                    unset($params[$field]);
                }
            }
        } else {
            if (array_key_exists($this->excludeFields, $params)) {
                unset($params[$this->excludeFields]);
            }
        }
        return $params;
    }

    /**
     * 列表显示
     * @return \think\response\Json
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
        return $this->view->fetch();
    }

    /**
     * 数据添加
     * @return mixed
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->postData ?? $this->request->post("row/a");
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
                    $this->success();
                } else {
                    $this->error(__('No rows added'));
                }
            }
            $this->error(__('Parameter %s can not be empty',['']));
        }
        return $this->view->fetch();
    }

    /**
     * 数据修改
     * @param null $id
     * @return mixed
     */
    public function edit($id=null)
    {
        $row = $this->model->find($id);
        if (!$row) {
            $this->error(__('No results were found'));
        }

        if ($this->request->isPost()) {
            $params = $this->postData ?? $this->request->post("row/a");
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
                    $this->success();
                } else {
                    $this->error(__('No changes'));
                }
            }
            $this->error(__('Parameter %s can not be empty',['']));
        }

        return $this->view->fetch('', compact('row'));
    }

    /**
     * 数据删除
     * @param string $ids
     * @throws DbException
     */
    public function del($ids = '')
    {
        if ($ids) {
            $pk = $this->model->getPk();
            $list = $this->model->where($pk, 'in', $ids)->select();

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
            $data = $this->postData ?? $this->request->only(['ids'=>'','params'=>'']);
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

            $pk = $this->model->getPk();
            $list = $this->model->where($pk, 'in', $data['ids'])->select();
            if ($list->isEmpty()) {
                $this->error(__('No results were found'));
            }

            $bl = 0;
            Db::startTrans();
            try {
                foreach ($list as $index => $item) {
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

    /**
     * 回收站
     * @return \think\response\Json
     */
    public function recycle()
    {
        if ($this->request->isAjax()) {

            list($map, $limit, $offset, $order, $sort) = $this->buildparams(null);

            $data = $this->model
                ->onlyTrashed()
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $total = $this->model
                ->onlyTrashed()
                ->where($map)
                ->order($sort, $order)
                ->count();
            return json(['total'=>$total, 'rows'=>$data]);
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
        $bl = 0;
        Db::startTrans();
        try {
            $list = $model->select();
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
     * 销毁
     * @param string $ids
     */
    public function destroy($ids="")
    {
        $pk = $this->model->getPk();
        $map = [];
        if ($ids) {
            $map[] = [$pk, 'in', $ids];
        }
        $bl = 0;
        Db::startTrans();
        try {
            $list = $this->model->onlyTrashed()->where($map)->select();
            foreach ($list as $index => $item) {
                $bl += $item->force()->delete();
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
}