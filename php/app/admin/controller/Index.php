<?php
// +----------------------------------------------------------------------
// | HkCms 后台首页
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\controller;

use app\common\services\cache\CacheService;
use app\common\services\lang\LangService;
use libs\Tree;
use think\addons\Cloud;
use think\App;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

class Index extends BaseController
{
    protected $middleware = [
        'login',
        'auth' => ['except'=>['index','menu','profile','upgrade','changeContentLang']]
    ];

    public function index()
    {
        $langs = app()->make(LangService::class)->getListByGroup(['status'=>1]);
        $this->view->layout(false);
        $this->view->assign('langs', $langs);
        return $this->view->fetch();
    }

    /**
     * 首页面板
     * @return string|void
     */
    public function dashboard()
    {
        //快速导航
        $nav_list = Db::name('admin_panel')
                    ->alias('ap')
                    ->join('auth_rule ar','ap.auth_rule_id = ar.id')
                    ->where(['ap.admin_id'=>session('User.id'),'ar.status'=>'normal'])
                    ->field('ap.*,ar.name,ar.title,ar.route,ar.icon')
                    ->select();

        $assign = [
            'nav_list'    => $nav_list,
            'system_info' => [
                'OS' => PHP_OS,
                'domain' => $this->request->host(),
                'running_system' => $this->request->server()['SERVER_SOFTWARE'],
                'phpv' => phpversion(),
                'mysqlv' => Db::query("SELECT VERSION() as mysqlv")[0]['mysqlv'],
                'gdv' => gd_info()['GD Version'],
            ]
        ];
        $this->view->assign($assign);
        return $this->view->fetch();
    }

    /**
     * 个人资料
     * @return string|void
     */
    public function profile()
    {
        if ($this->request->isPost()) {
            $data = $this->request->only(['nickname'=>'','email'=>'','password'=>'','avatar']);
            if (!empty($data['password'])) {
                $data['salt'] = get_random_str();
                $data['password'] = $this->user->hashPassword($data['password'],$data['salt']);
            } else {
                unset($data['password']);
            }
            $result = Db::name('admin')->where(['id'=>$this->user->id])->save($data);
            if ($result) {
                foreach ($data as $k => $v) {
                    session('User.'.$k,$v);
                }
                $this->success();
            } else {
                $this->error(__("No changes"));
            }
        }

        $info = Db::name('admin')->where(['id'=>$this->user->id])->find();
        $this->view->assign('userinfo',$info);
        return $this->view->fetch();
    }

    /**
     * 获取菜单
     */
    public function menu()
    {
        $this->view->layout(false);

        $menu = Cache::get('Menu_'.$this->user->id);
        if (empty($menu)) {
            $menu = $this->user->getUserRules([['type','in','1,2']]);
            Cache::tag('menu')->set('Menu_'.$this->user->id, $menu);
        }
        $menu = Tree::instance()->init($menu)->getTreeArray(0);
        $this->view->assign('menu', $menu);
        $this->success('','',$this->view->fetch());
    }

    /**
     * 内容多语言
     */
    public function changeContentLang(LangService $langService)
    {
        $l = $this->request->get('l');
        $t = $this->request->get('t');
        if (!empty($l)) {
            $langService->setContentMode($this->user->id, $l);
            $this->success(__('The current content editing mode has changed to 【%s】.',[$t]));
        }
        $this->error(__("No changes"));
    }

    /**
     * 清空缓存
     */
    public function clearCache()
    {
        $type = $this->request->param('type', '');
        if ($type=='log') {
            (new CacheService())->clearLog();
            $this->success();
        } else if ($type=='all') {
            clear_cache();
            $this->success();
        } else if ($type=='close_cache') {
            $s = $this->request->param('st', '');
            \think\facade\Db::name('config')->where(['name'=>'dev'])->update(['value'=>$s==1?'disabled':'enable']);
            $this->cache->set('devstatus', $s==1?'disabled':'enable');
            $this->success($s==1?__('Cache is on'):__('Cache closed'));
        }
    }

    /**
     * CMS检测更新
     */
    public function upgrade()
    {
        if ($this->request->isPost()) {
            $p = $this->request->param('p');
            $v = $this->request->param('v');
            $path = $this->request->param('path');
            ini_set('max_execution_time', '0');
            try {
                $res = Cloud::getInstance()->upgradeCms($v, $p, $path);
            } catch (\Throwable $exception) {
                $this->error($exception->getMessage());
            }
            $this->cache->clear();
            $this->success();
        }
        $v = $this->config->get('ver.cms_version');
        $p = $this->config->get('ver.cms_build');
        try {
            $res = Cloud::getInstance()->checkUpgrade($v, $p);
        } catch (\Throwable $exception) {
            Log::error("检测CMS更新失败：".$exception->getMessage());
            $res = [];
        }

        if (empty($res)) {
            $this->error(__('Server connection failed'),'',['upgrade'=>-2]);
        } else {
            $this->error('','',$res);
        }
    }
}