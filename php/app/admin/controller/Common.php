<?php
// +----------------------------------------------------------------------
// | HkCms 通用访问
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\library\Upload;
use think\facade\Db;

class Common extends BaseController
{
    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [
        'login', // 登录中间件
        'auth'=>['only'=>['upload']],  // 权限认证中间件
    ];

    /**
     * 文件上传
     */
    public function upload()
    {
        $data = $this->request->only(['chunksize'=>'','filesize'=>'','fileid'=>'', 'fileindex'=>'', 'action'=>'chunk']);

        if (!empty($data['fileid'])) {
            if (site('chunk')!=1) {
                $this->error(__('Sharding is turned off'));
            }

            $obj = Upload::instance(['user_id'=>$this->user->id,'user_type'=>1]);
            if ('clear'==$data['action']) { // 出错清理
                $obj->clear($data['fileid']);
                $this->success('','', []);
            } else {
                if (empty($data['chunksize']) || empty($data['filesize']) || empty($data['fileindex'])) {
                    $this->error(__('Parameter %s can not be empty',['']));
                }
                $files = $this->request->file('files');
                if (empty($files)) {
                    $this->error(__('No files uploaded'));
                }

                try {
                    // 保存分块
                    $res = ['chunk'=>1];
                    $chunkInfo = $obj->chunk($files, $data['fileid'], $data['fileindex']);

                    // 合并
                    if ($data['chunksize']==$data['filesize']) {
                        $res = $obj->merge($data['fileid'], $data['fileindex'], $chunkInfo);
                    }
                } catch (\Exception $exception) {
                    $this->error($exception->getMessage());
                }
                $this->success('','', $res);
            }
        } else {
            $files = $this->request->file('files');
            if (empty($files)) {
                $this->error(__('No files uploaded'));
            }

            try {
                $res = Upload::instance(['user_id'=>$this->user->id,'user_type'=>1])->upload($files);
            } catch (\Exception $exception) {
                $this->error($exception->getMessage());
            }
            $this->success('','', $res);
        }
    }

    /**
     * 用于动态下拉
     * @return \think\response\Json
     */
    public function getSelectpage()
    {
        $id = $this->request->param('id','','intval');
        $ty = $this->request->param('t','','intval');
        $field = $this->request->param('field'); // 配置文件字段
        $group = $this->request->param('group'); // 配置文件分组
        if (empty($id) || empty($ty)) {
            $this->error(__('Parameter %s can not be empty',['']));
        }

        if ($ty==1) {
            $info = Db::name('model_field')->find($id);
        } else if ($ty==3) { // 插件、模板配置文件
            $info = Db::name('app')->find($id);
            $info = get_addons_config($info['type'], $info['name'], $info['module'], true); // 获取整个配置文件
            $info = !empty($group) ? (isset($info[$group]['item'][$field])?$info[$group]['item'][$field]:'') : (isset($info[$field]) ? $info[$field] : '');
        } else if ($ty==4) {
            $info = Db::name('config')->find($id);
        } else {
            $info = Db::name('fields')->find($id);
        }

        if (empty($info)){
            $this->error(__('No results were found'));
        }

        if ($ty!=3) {
            // 配置文件，需要获取表前缀
            $info['data_list'] = json_decode($info['data_list'], true);
        }

        if (empty($info['data_list']['type']) || $info['data_list']['type']!='table' || empty($info['data_list']['table'])) {
            $this->error(__('No results were found'));
        }

        if (!empty($info['data_list']['enable-lang']) && $info['data_list']['enable-lang']==1) {
            $this->enableLang = true;
        }

        // table不含前缀
        $this->model = Db::name($info['data_list']['table']);
        if (isset($info['data_list']['delete_time']) && $info['data_list']['delete_time']==1) {
            $this->model->where('delete_time', 'exp', Db::raw('is null'));
        }
        return parent::selectPage();
    }
}