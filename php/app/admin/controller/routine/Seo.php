<?php
// +----------------------------------------------------------------------
// | HkCms SEO配置
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\controller\routine;

use app\admin\controller\BaseController;
use app\admin\library\Html;
use app\admin\model\cms\Category;
use app\common\services\config\ConfigService;
use app\common\services\lang\LangService;
use libs\Tree;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Validate;

class Seo extends BaseController
{
    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [
        'login', // 登录中间件
        'auth'=>['except'=>['generation']],  // 权限认证中间件
    ];

    /**
     * 伪静态规则
     */
    protected $rules = [
        //['/:catname/$','/:catname/:id$.html'],
        ['/:catname/$,/:catname/list_:page$.html','/:catname/:id$.html'],
        ['/[:lang]/:catname/$,/[:lang]/:catname/list_:page$.html','/[:lang]/:catname/:id$.html'],
        ['/[:catdir]/:catname/$','/[:catdir]/:catname/:id$.html'],
        ['/[:lang]/[:catdir]/:catname/$','/[:lang]/[:catdir]/:catname/:id$.html'],
        ['/:model/:catname/$','/:model/:id$.html'],
    ];

    /**
     * 静态生成规则
     */
    protected $htmlRules = [
        'category'=>[
            // 父级栏目/子栏目/index.html|父级栏目/子栏目/index_2.html
            '/[catdir]/[list]/index.html|/[catdir]/[list]/index_[page].html',
            // 栏目名称/index.html|/栏目名称/index_2.html
            '/[list]/index.html|/[list]/index_[page].html',
        ],

        'content'=>[
            // 父级栏目/子栏目/文档ID.html|父级栏目/子栏目/文档ID_1.html
            '/[catdir]/[list]/[id].html|/[catdir]/[list]/[id]_[page].html',
            // 栏目名称/文档ID.html|栏目名称/文档ID_2.html
            '/[list]/[id].html|/[list]/[id]_[page].html'
        ]
    ];

    /**
     * url 配置
     * @return string|\think\response\Json|void
     */
    public function index()
    {
        if ($this->request->isPost()) {
            $row = $this->request->post('row');
            $isstatic = 0;
            if ($row['url_mode']==0) { // 动态
                Db::name('config')->where(['name'=>'url_file'])->save(['value'=>$row['url_file']]);
                // 更新静态URL地址
                if (site('url_mode')==2) {
                    (new Html(app()))->clearUrl();
                }
            } else if ($row['url_mode']==1) { // 伪静态、使用内置规则
                $index = $row['url_format']-1;
                if ($index==-1) {
                    $this->error(__('自定义模式前往站点配置->高级配置->伪静态配置修改'));
                }
                $rule = $this->rules[$row['url_format']-1];
                $info = Db::name('config')->where(['name'=>'url_rewrite'])->find();
                $rewrite = json_decode($info['value'], true);
                $rewrite['index/lists'] = $rule[0];
                $rewrite['index/show'] = $rule[1];
                if (($row['url_format']==4||$row['url_format']==2) && empty($rewrite['/'])) { // 多语言
                    $newRe = ['/'=>'/[:lang]/$'];
                    $rewrite = array_merge($newRe,$rewrite);
                } else if (in_array($row['url_format'],['1','3','4']) && !empty($rewrite['/']) && $rewrite['/']=='/[:lang]/$') {
                    unset($rewrite['/']);
                }
                Db::name('config')->where(['name'=>'url_rewrite'])->update(['value'=>json_encode($rewrite)]);
                // 更新静态URL地址
                if (site('url_mode')==2) {
                    Html::clearUrl();
                }
            } else {
                // 静态生成
                if (empty($row['html_column_rules'])) {
                    $this->error(__('Column rules cannot be empty'));
                }
                if (empty($row['html_content_rules'])) {
                    $this->error(__('Content rules cannot be empty'));
                }
                if (!empty($row['html_dir']) && !Validate::is($row['html_dir'],'alphaDash')) {
                    $this->error(__('The static file storage directory name can only be alphanumeric underscore'));
                }

                $isstatic = 1;

                Db::name('config')->where(['name'=>'html_dir'])->save(['value'=>$row['html_dir']]);
                Db::name('config')->where(['name'=>'html_column_rules'])->save(['value'=>$row['html_column_rules']==-1?$row['html_column_rules']:$this->htmlRules['category'][$row['html_column_rules']-1]]);
                Db::name('config')->where(['name'=>'html_content_rules'])->save(['value'=>$row['html_content_rules']==-1?$row['html_content_rules']:$this->htmlRules['content'][$row['html_content_rules']-1]]);


                Cache::tag(ConfigService::CACHE_TAG)->clear();

                // 更新静态URL地址
                Html::buildUrl();
            }

            if (($row['category_format']==-1 && empty($row['category_format_diy'])) || ($row['content_format']==-1 && empty($row['content_format_diy']))) {
                $this->error(__('Custom content cannot be empty'));
            }

            Db::name('config')->where(['name'=>'category_format'])->save(['value'=>$row['category_format']==-1?$row['category_format_diy']:$row['category_format']]);
            Db::name('config')->where(['name'=>'content_format'])->save(['value'=>$row['content_format']==-1?$row['content_format_diy']:$row['content_format']]);
            Db::name('config')->where(['name'=>'url_mode'])->save(['value'=>$row['url_mode']]);
            clear_cache();
            $this->cache->set('admin_content_lang'.$this->user->id, $this->contentLang);
            $this->success('','',['isstatic'=>$isstatic]);
        }

        $config = Db::name('config')->select();
        $field = [];
        foreach ($config as $key=>$value) {
            $field[$value['name']] = $value['value'];
            if ($value['name']=='url_rewrite') {
                $rewrite = json_decode($value['value'], true);
                if (!isset($rewrite['index/lists']) || !isset($rewrite['index/show'])) {
                    continue;
                }
                foreach ($this->rules as $k=>$v) {
                    if ($v[0]==$rewrite['index/lists'] && $v[1]==$rewrite['index/show']) {
                        $field[$value['name']] = $k+1;
                        break;
                    }
                }
            }
        }

        // 获取栏目信息
        $model = Category::alias('c')
            ->leftJoin('model m','m.id=c.model_id')
            ->field('c.*,m.name as model_name')
            ->where('lang','=',$this->contentLang);
        $data = $model->order(['c.weigh'=>'asc','c.id'=>'asc'])->select()->append(['type_text'])->toArray();

        $category = Tree::instance()->getTreeList(Tree::instance()->init($data)->getTreeArray(0));

        $this->view->assign('field', $field);
        $this->view->assign('htmlRules', $this->htmlRules);
        $this->view->assign('category', $category);
        return $this->view->fetch();
    }

    /**
     * 生成整站HTML
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function generation()
    {
        if (!has_rule('routine/seo/index')) {
            $this->error(__('No permission'));
        }
        if (site('url_mode')!=2) {
            $this->error(__('Please change the URL pattern to static generation and try again'));
        }
        $this->view->layout(false);
        $lang = $this->app->lang->getLangSet();
        $type = $this->request->post('type');
        if ($type=='home') { // 生成首页
            $html = new Html(app());
            $html->index();
            $this->app->lang->setLangSet($lang);
            $this->success('','',['status'=>0]);
        } else if ($type=='list') {
            $page = $this->request->post('page',1,'intval');
            $catid = $this->request->post('catid',0,'intval');
            if ($catid) {
                $category = session('generation-category');
                if (!isset($category[$catid-1]) || $catid>count($category)) {
                    $this->success('','',['status'=>0]);
                }
                $html = new Html(app());
                $total_page = $html->category($category[$catid-1], $page);

                if ($page>=$total_page || $total_page===false) {
                    $this->success('','',['status'=>1,'next'=>$catid+1,'max_next'=>count($category),'title'=>$category[$catid-1]['title'],'type'=>'list']);
                } else {
                    $this->success('','',['status'=>2,'next'=>$catid,'max_next'=>count($category),'title'=>$category[$catid-1]['title'],'type'=>'list','page'=>$page+1,'total_page'=>$total_page]);
                }
            } else {
                $where[] = ['status','=','normal'];
                if (site('content_lang_on')!=1) {
                    $where[] = ['lang','=',app()->make(LangService::class)->getDefaultLang('content')];
                }
                $category = \app\index\model\cms\Category::where($where)->where('model_id','>',0)->append(['parent_url','fullurl'])->select()->toArray();
                if ($category) {
                    session('generation-category',$category);
                    $this->success('','',['status'=>1,'next'=>1,'max_next'=>count($category),'title'=>$category[0]['title'],'type'=>'list']);
                }
            }
            $this->app->lang->setLangSet($lang);
            $this->success('','',['status'=>0]);
        } else if ($type=='content') {
            $page = $this->request->post('page',1,'intval');
            $catid = $this->request->post('catid',0,'intval');
            if ($catid) {
                $category = session('generation-content');
                if (!isset($category[$catid-1]) || $catid>count($category)) {
                    $this->success('','',['status'=>0]);
                }
                $html = new Html(app());
                $total_page = $html->show($category[$catid-1], $page);
                if ($page>=$total_page || $total_page===false) {
                    $this->success('','',['status'=>1,'next'=>$catid+1,'max_next'=>count($category),'title'=>$category[$catid-1]['name'],'type'=>'content']);
                } else {
                    $this->success('','',['status'=>2,'next'=>$catid,'max_next'=>count($category),'title'=>$category[$catid-1]['name'],'type'=>'content','page'=>$page+1,'total_page'=>$total_page]);
                }
            } else {
                $category = \app\admin\model\cms\Model::where(['controller'=>'Archives'])->select()->toArray();
                if ($category) {
                    session('generation-content',$category);
                    $this->success('','',['status'=>1,'next'=>1,'max_next'=>count($category),'title'=>$category[0]['name'],'type'=>'content']);
                }
            }
            $this->app->lang->setLangSet($lang);
            $this->success('','',['status'=>0]);
        } else if ($type=='list-sing') {
            $catid = $this->request->post('catid',0,'intval');
            $arr = get_category_sub($catid,true);

            // 多语言判断
            if (site('content_lang_on')==1) {
                $tmpArr = [];
                foreach ($arr as $value) {
                    $tmp = lang_content_get('category',$value);
                    $tmpArr = array_merge($tmpArr,$tmp);
                }
                $arr = \app\index\model\cms\Category::whereIn('id', $tmpArr)->append(['parent_url','fullurl'])->select()->toArray();
            } else {
                $arr = \app\index\model\cms\Category::whereIn('id', $arr)->append(['parent_url','fullurl'])->select()->toArray();
            }

            foreach ($arr as $v) {
                Html::buildCategoryUrl([$v]);
                $res = (new Html(app()))->category($v,1);
                if ($res>1) {
                    for ($i=2; $i<=$res; $i++) {
                        (new Html(app()))->category($v,$i);
                    }
                }
            }
            $this->app->lang->setLangSet($lang);
            $this->success('','',['status'=>0]);
        } else if ($type=='content-cate') {
            $catid = $this->request->post('catid',0,'intval');
            $arr = get_category_sub($catid,true);

            // 多语言判断
            if (site('content_lang_on')==1) {
                $tmpArr = [];
                foreach ($arr as $key=>$value) {
                    $tmp = lang_content_get('category',$value);
                    $tmpArr = array_merge($tmpArr,$tmp);
                }
                $arr = \app\index\model\cms\Category::alias('c')->join('model m','c.model_id=m.id')->whereIn('c.id', $tmpArr)->field('m.*,c.id as c_id')->select()->toArray();
            } else {
                $arr = \app\index\model\cms\Category::alias('c')->join('model m','c.model_id=m.id')->whereIn('c.id', $arr)->field('m.*,c.id as c_id')->select()->toArray();
            }

            if ($arr) {
                $html = new Html(app());
                foreach ($arr as $key=>$value) {
                    $p = 1;
                    $total_page = $html->show($value, $p, ['category_id'=>$value['c_id']]);
                    if ($p>=$total_page || $total_page===false) {
                        continue;
                    }

                    for ($i = 2; $i<=$total_page; $i++) {
                        $total_page = $html->show($value, $i, ['category_id'=>$value['c_id']]);
                        if ($total_page===false) {
                            break;
                        }
                    }
                }
            }
            $this->app->lang->setLangSet($lang);
            $this->success('','',['status'=>0]);
        } else {
            $this->error(__('Illegal request'));
        }
    }
}