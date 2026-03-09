<?php
// +----------------------------------------------------------------------
// | HkCms 推荐位管理
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\controller\cms;

use app\admin\controller\BaseController;
use app\admin\model\cms\Banner;
use app\common\services\lang\LangBindService;
use think\db\exception\DbException;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;

class Recommend extends BaseController
{
    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [
        'login',
        'auth'=>['column']
    ];

    /**
     * 推荐位模型
     * @var \app\admin\model\cms\Recommend
     */
    protected $model;

    /**
     * 允许批量修改的字段
     * @var array
     */
    protected $allowFields = ['status','weigh','new_window'];

    /**
     * 初始化操作
     */
    public function initialize()
    {
        parent::initialize();

        $this->model = new \app\admin\model\cms\Recommend();
    }

    public function index()
    {
        if ($this->request->isAjax()) {
            $lang = $this->request->param('clang', $this->contentLang);

            list($map, $limit, $offset, $order, $sort) = $this->buildparams();

            $data = $this->model->where($map)->where(['lang'=>$lang])->order($sort, $order)->limit($offset, $limit)->select()->toArray();
            $total = $this->model->where($map)->where(['lang'=>$lang])->order($sort, $order)->count();

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
            $row = $this->request->param('row/a','',null);
            $admin_id = $this->user->id;

            $data = [
                'admin_id' => $admin_id,
                'name' => $row['name'],
                'remark' => $row['remark'],
                'type' => $row['type']
            ];
            $this->validate($data, ['name'=>'require|alphaDash'], ['name.alphaDash'=>'标识只能是字母、数字和下划线_及破折号-']);

            // 判断唯一
            $tmpId = \app\admin\model\cms\Recommend::where(['name'=>$row['name'],'lang'=>$this->contentLang])->value('id');
            if ($tmpId) {
                $this->error(__('%s existed',[$row['name']]));
            }

            if ($row['type'] == 1 && empty($row['image'][0])) {
                $this->error(__('Please upload a picture'));
            }
            if ($row['type'] == 2 && empty($row['v_content'][0])) {
                $this->error(__('Please upload the video or specify the link'));
            }
            if ($row['type'] == 3 && empty($row['html_content'])) {
                $this->error(__('HTML content cannot be empty'));
            }
            if ($row['type']==4) {
                if (empty($row['model']) || empty($row['limit'])) {
                    $this->error(__('%s not exist', [__('Limit')]));
                }
                $json = [
                    'model'=>$row['model'],
                    'limit'=>$row['limit'],
                    'order'=>$row['order']??'',
                    'column'=>!empty($row['column'])?implode(',', $row['column']):'',
                ];
                $data['value_id'] = json_encode($json);
            }
            if ($row['type'] == 5 && empty($row['link_url'][0])) {
                $this->error(__('Please add a text link item'));
            }

            $data['lang'] = $this->contentLang;
            $Banner = new \app\admin\model\cms\Banner;
            Db::startTrans();
            try {
                $result = $this->model->save($data);
                if ($row['type']==1) {
                    $arr = [];
                    foreach ($row['image'] as $k => $v) {
                        $arr[$k] = [
                            'admin_id'   => $admin_id,
                            'title'      => $row['title'][$k] ?? '',
                            'image'      => $v,
                            'type'       => 1,
                            'url'        => $row['url'][$k] ?? '',
                            'notes'      => $row['notes'][$k] ?? '',
                            'weigh' => $row['weigh'][$k] ?? 0,
                            'lang'=>$this->model->getAttr('lang'),
                            'new_window' => $row['new_window'][$k] ?? '',
                            'recommend_id' => $this->model->getAttr('id'),
                        ];
                    }
                    $Banner->saveAll($arr,false);
                } else if ($row['type'] == 5) {
                    $arr = [];
                    foreach ($row['link_url'] as $k => $v) {
                        $arr[$k] = [
                            'admin_id'   => $admin_id,
                            'title'      => $row['link_title'][$k] ?? '',
                            'image'      => '',
                            'type'       => 5,
                            'url'        => $row['link_url'][$k] ?? '',
                            'notes'      => $row['link_notes'][$k] ?? '',
                            'weigh' => $row['link_weigh'][$k] ?? 0,
                            'lang'=>$this->model->getAttr('lang'),
                            'new_window' => $row['link_new_window'][$k] ?? '',
                            'recommend_id' => $this->model->getAttr('id'),
                        ];
                    }
                    $Banner->saveAll($arr,false);
                } else if ($row['type'] == 2) {
                    $arr = [];
                    foreach ($row['v_content'] as $k => $v) {
                        $arr[$k] = [
                            'admin_id'   => $admin_id,
                            'title'      => $row['v_title'][$k] ?? '',
                            'content'      => $v,
                            'type'       => 2,
                            'url'        => $row['v_url'][$k] ?? '',
                            'notes'      => $row['v_notes'][$k] ?? '',
                            'weigh' => $row['v_weigh'][$k] ?? 0,
                            'lang'=>$this->model->getAttr('lang'),
                            'new_window' => $row['v_new_window'][$k] ?? '',
                            'recommend_id' => $this->model->getAttr('id'),
                        ];
                    }
                    $Banner->saveAll($arr,false);
                } else if ($row['type'] == 3) {
                    $arr = [
                        'recommend_id' => $this->model->getAttr('id'),
                        'admin_id'   => $admin_id,
                        'type'       => 3,
                        'lang'=>$this->model->getAttr('lang'),
                        'content'       => $row['html_content'],
                    ];
                    $Banner->save($arr);
                }

                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                // banner 同步
                if (site('content_lang_on')==1) {
                    $id = $this->model->getAttr('id');
                    $arr = lang_content_get('recommend',$id);
                    $arr = \app\admin\model\cms\Recommend::whereIn('id', $arr)->where('id','<>',$id)->select()->toArray();
                    if ($row['type'] == 4) {
                        // 内容数据，修改正确的栏目
                        foreach ($arr as $key=>$value) {
                            $valueId = json_decode($value['value_id'], true);
                            if (empty($valueId['column'])) {
                                continue;
                            }
                            // 栏目id
                            $column = explode(',', $valueId['column']);
                            $tmpColumn = [];
                            foreach ($column as $k=>$v) {
                                $tmpColumn[] = app()->make(LangBindService::class)->getBindValue($v,'category',$value['lang']);
                            }
                            // 保存新的栏目数据
                            $valueId['column'] = implode(',', $tmpColumn);
                            $valueId = json_encode($valueId);
                            \app\admin\model\cms\Recommend::where(['id'=>$value['id']])->save(['value_id'=>$valueId]);
                        }
                    } else {
                        $banners = \app\admin\model\cms\Banner::where(['recommend_id'=>$id])->select()->toArray();
                        foreach ($arr as $k=>$v) {
                            foreach ($banners as $key=>$value) {
                                unset($value['id']);
                                $value['recommend_id'] = $v['id'];
                                $value['lang'] = $v['lang'];
                                $value['update_time'] = time();
                                $value['create_time'] = time();
                                \app\admin\model\cms\Banner::create($value);
                            }
                        }
                    }
                }
                Cache::tag('recommend_tag')->clear();
                $this->success();
            } else {
                $this->error(__('No rows added'));
            }
        }

        $modelList = Db::name('model')->where(['status'=>'normal'])->select();
        $this->view->assign('modelList', $modelList);
        return $this->view->fetch();
    }

    /**
     * 修改
     * @param null $id
     */
    public function edit($id = null)
    {
        $row = $this->model->find($id);
        if (!$row) {
            $this->error(__('No results were found'));
        }

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a",'',null);
            if ($params) {
                $admin_id = $this->user->id;
                $data = [
                    'admin_id' => $admin_id,
                    'name' => $params['name'],
                    'remark' => $params['remark'],
                    'type' => $row['type']
                ];
                $this->validate($data, ['name'=>'require|alphaDash'], ['name.alphaDash'=>'标识只能是字母、数字和下划线_及破折号-']);
                if (Db::name('recommend')->where(['name'=>$data['name'],'lang'=>$row['lang']])->where('id','<>', $row['id'])->value('id')) {
                    $this->error(__('Identification already exists'));
                }

                if ($row['type']==4) {
                    if (empty($params['model']) || empty($params['limit'])) {
                        $this->error(__('%s not exist', [__('Limit')]));
                    }
                    $json = [
                        'model'=>$params['model'],
                        'limit'=>$params['limit'],
                        'order'=>$params['order']??'',
                        'column'=>!empty($params['column'])?implode(',', $params['column']):'',
                    ];
                    $data['value_id'] = json_encode($json);
                }
                if ($row['type'] == 5 && empty($params['link_url'][0])) {
                    $this->error(__('Please add a text link item'));
                }

                $oldName = $row['name'];

                $result = false;
                Db::startTrans();
                try {
                    $result = $row->save($data);

                    if ($row['type']==1) {
                        $add = [];
                        $ids = [];
                        foreach ($params['image'] as $k => $v) {
                            $temp = [
                                'admin_id'   => $admin_id,
                                'title'      => $params['title'][$k] ?? '',
                                'image'      => $v,
                                'type'       => 1,
                                'url'        => $params['url'][$k] ?? '',
                                'notes'      => $params['notes'][$k] ?? '',
                                'weigh' => $k+1,
                                'lang'=>$row['lang'],
                                'new_window' => $params['new_window'][$k] ?? '',
                                'recommend_id' => $row->getAttr('id'),
                            ];
                            if (!empty($params['banner_id'][$k])) {
                                $ids[] = $params['banner_id'][$k];
                                Banner::where(['id'=>$params['banner_id'][$k]])->save($temp);
                            } else {
                                $add[$k] = $temp;
                            }
                        }
                        if (!empty($ids)) {
                            Db::name('banner')->where(['recommend_id'=>$row->getAttr('id')])->where('id','not in', $ids)->delete();
                        } else {
                            Db::name('banner')->where(['recommend_id'=>$row->getAttr('id')])->delete();
                        }
                        Db::name('banner')->insertAll($add);
                    } else if ($row['type'] == 5) {
                        $add = [];
                        $ids = [];
                        foreach ($params['link_url'] as $k => $v) {
                            $temp = [
                                'admin_id'   => $admin_id,
                                'title'      => $params['link_title'][$k] ?? '',
                                'image'      => '',
                                'type'       => 5,
                                'url'        => $params['link_url'][$k] ?? '',
                                'notes'      => $params['link_notes'][$k] ?? '',
                                'weigh' => $k+1,
                                'lang'=>$row['lang'],
                                'new_window' => $params['link_new_window'][$k] ?? '',
                                'recommend_id' => $row->getAttr('id'),
                            ];
                            if (!empty($params['banner_id'][$k])) {
                                $ids[] = $params['banner_id'][$k];
                                Banner::where(['id'=>$params['banner_id'][$k]])->save($temp);
                            } else {
                                $add[$k] = $temp;
                            }
                        }
                        if (!empty($ids)) {
                            Db::name('banner')->where(['recommend_id'=>$row->getAttr('id')])->where('id','not in', $ids)->delete();
                        } else {
                            Db::name('banner')->where(['recommend_id'=>$row->getAttr('id')])->delete();
                        }
                        Db::name('banner')->insertAll($add);
                    } else if ($row['type'] == 2) {
                        $add = [];
                        $ids = [];
                        foreach ($params['v_content'] as $k => $v) {
                            $temp = [
                                'admin_id'   => $admin_id,
                                'title'      => $params['v_title'][$k] ?? '',
                                'content'      => $v,
                                'type'       => 2,
                                'url'        => $params['v_url'][$k] ?? '',
                                'notes'      => $params['v_notes'][$k] ?? '',
                                'weigh' => $k+1,
                                'lang'=>$row['lang'],
                                'new_window' => $params['v_new_window'][$k] ?? '',
                                'recommend_id' => $row->getAttr('id'),
                            ];
                            if (!empty($params['banner_id'][$k])) {
                                $ids[] = $params['banner_id'][$k];
                                Banner::where(['id'=>$params['banner_id'][$k]])->save($temp);
                            } else {
                                $add[$k] = $temp;
                            }
                        }
                        if (!empty($ids)) {
                            Db::name('banner')->where(['recommend_id'=>$row->getAttr('id')])->where('id','not in', $ids)->delete();
                        } else {
                            Db::name('banner')->where(['recommend_id'=>$row->getAttr('id')])->delete();
                        }
                        Db::name('banner')->insertAll($add);
                    } else if ($row['type'] == 3) {
                        if (empty($params['html_banner_id'])) {
                            $arr = [
                                'recommend_id' => $row->getAttr('id'),
                                'admin_id'   => $admin_id,
                                'type'       => 3,
                                'lang'=>$row['lang'],
                                'content'       => $params['html_content']
                            ];
                            Db::name('banner')->where(['recommend_id'=>$row->getAttr('id')])->delete();
                            Db::name('banner')->save($arr);
                        } else {
                            $arr = [
                                'recommend_id' => $row->getAttr('id'),
                                'admin_id'   => $admin_id,
                                'type'       => 3,
                                'lang'=>$row['lang'],
                                'content'       => $params['html_content'],
                            ];
                            Db::name('banner')->where(['id'=>$params['html_banner_id']])->save($arr);
                        }
                    } else if ($row['type']==4) {
                        if ($oldName!=$params['name'] && site('content_lang_on')==1) {
                            Db::name('recommend')->where(['name'=>$oldName])->update(['name'=>$params['name']]);
                        }
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }

                if ($result !== false) {
                    Cache::tag('recommend_tag')->clear();
                    $this->success();
                } else {
                    $this->error(__('No changes'));
                }
            }
            $this->error(__('Parameter %s can not be empty',['']));
        }

        $row['banner'] = Banner::where(['recommend_id'=>$row->id])->order('weigh','asc')->select()->toArray();
        $row['value_id'] = $row['type']==4?json_decode($row['value_id'], true) : $row['value_id'];
        $modelList = Db::name('model')->where(['status'=>'normal'])->select();
        $this->view->assign('modelList', $modelList);
        return $this->view->fetch('', compact('row'));
    }

    /**
     * 批量处理
     * @return void
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
                Cache::tag('recommend_tag')->clear();
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
     * @param $ids
     * @return void
     * @throws \think\db\exception\DbException
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
                Cache::tag('recommend_tag')->clear();
                $this->success();
            } else {
                $this->error(__('No rows deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty',['ids']));
    }

    /**
     * 获取栏目管理数据
     */
    public function column()
    {
        $id = $this->request->param('m', '', 'intval');
        $lang = $this->request->param('lan', '', '');
        $lang = empty($lang) ? $this->contentLang : $lang;
        if (empty($id)) {
            $this->error(__('Parameter %s can not be empty',['']));
        }

        $column = Db::name('category')->where(['model_id'=>$id,'status'=>'normal','lang'=>$lang])->select()->toArray();
        $this->success('','',$column);
    }
}