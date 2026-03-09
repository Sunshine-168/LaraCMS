<?php
// +----------------------------------------------------------------------
// | HkCms 生成静态页面
// +----------------------------------------------------------------------
// | Copyright (c) 2012-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\library;

use app\admin\model\cms\Model;
use app\common\services\lang\LangService;
use app\index\controller\BaseController;
use app\index\model\cms\Category;
use think\facade\Db;
use think\Paginator;

class Html extends BaseController
{
    //数据
    protected $data = [];
    //错误信息
    protected $error = NULL;

    protected function initialize()
    {
        parent::initialize();

        // 取消模板缓存
        $this->view->config(['tpl_cache'=>false,'display_cache'=>false]);

        // 非开发者模式下,屏蔽错误
        if (!$this->app->isDebug()) {
            // 屏蔽掉notice错误
            error_reporting(E_ALL ^ E_NOTICE);
        }
    }

    /**
     * 获取错误提示
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 重置整站URL
     */
    public static function clearUrl()
    {
        // 栏目URL
        Db::name('category')->where(['status'=>'normal'])->where('model_id','>',0)->update(['url'=>'']);

        // 内容URL
        Db::name('archives')->where('url','<>','')->update(['url'=>'']);

        @unlink(public_path().'index.html');
    }

    /**
     * 指定栏目URL生成
     * @param $categorys array 栏目数组二维
     */
    public static function buildCategoryUrl($categorys)
    {
        // 获取栏目与内容规则
        $columnUrl = site('html_column_rules');

        foreach ($categorys as $key=>$category) {
            if ($category['model_id']<=0) {
                continue;
            }
            //需要替换的标签
            $replace_l = [];
            //替换的内容
            $replace_r = [];
            if (strstr($columnUrl, '[catdir]')) {
                //获取当前栏目父栏目
                $catdir = $category['parent_id'] ? Db::name('category')->where('id', '=', $category['parent_id'])->value('name'):'';
                $replace_r[] = $catdir;
                $replace_l[] = '[catdir]';
            }
            // 栏目目录
            if (strstr($columnUrl, '[list]')) {
                $replace_l[] = '[list]';
                $replace_r[] = $category['name'];
            }
            //标签替换
            $url = str_replace($replace_l, $replace_r, $columnUrl);
            $url = str_replace(["//"], '/', $url);
            $url = explode("|", $url);
            // 多语言的处理
            if (site('content_lang_on')==1) {
                foreach ($url as $k=>$value) {
                    $url[$k] = '/'.$category['lang'].$value;
                }
            }

            $url[0] = self::getRootPath().dirname($url[0]).'/';
            $url[0] = str_replace(["//", "\\"], '/', $url[0]);
            Db::name('category')->where('id','=',$category['id'])->update(['url'=>$url[0]]);
        }
    }

    /**
     * 生成内容URL
     * @param $category
     * @param $content
     */
    public static function buildContentUrl($category, $content)
    {
        $contentUrl = site('html_content_rules');

        $replace_l = []; //需要替换的标签
        $replace_r = []; //替换的内容

        // 父栏目
        if (strstr($contentUrl, '[catdir]')) {
            //获取当前栏目父栏目
            $replace_r[] = $category['parent_id'] ? Db::name('category')->where('id', '=', $category['parent_id'])->value('name'):'';
            $replace_l[] = '[catdir]';
        }
        // 栏目目录
        if (strstr($contentUrl, '[list]')) {
            $replace_l[] = '[list]';
            $replace_r[] = $category['name'];
        }
        //年份
        if (strstr($contentUrl, '[year]')) {
            $replace_l[] = '[year]';
            $replace_r[] = date('Y', $content['publish_time']??$content['create_time']);
        }
        //月份
        if (strstr($contentUrl, '[month]')) {
            $replace_l[] = '[month]';
            $replace_r[] = date('m', $content['publish_time']??$content['create_time']);
        }
        //日期
        if (strstr($contentUrl, '[day]')) {
            $replace_l[] = '[day]';
            $replace_r[] = date('d', $content['publish_time']??$content['create_time']);
        }
        //文件名，如果有自定义文件名则使用自定义文件名，否则默认使用当前内容ID
        if (!empty($content['diyname'])) {
            $replace_l[] = '[diyname]';
            $replace_r[] = $content['diyname'];
        } else {
            $replace_l[] = '[id]';
            $replace_r[] = $content['id'];
        }

        //标签替换
        $urlrule = str_replace($replace_l, $replace_r, $contentUrl);
        $urlrule = str_replace(["//"], '/', $urlrule);
        $urlrule = explode("|", $urlrule);
        // 多语言的处理
        if (site('content_lang_on')==1) {
            foreach ($urlrule as $kk=>$vo) {
                $urlrule[$kk] = '/'.$content['lang'].$vo;
            }
        }
        $urlrule[0] = self::getRootPath($urlrule[0]);
        $urlrule[0] = str_replace(array("//", "\\"), '/', $urlrule[0]);

        $find = Db::name('model')->where(['status'=>'normal'])->find($category['model_id']);
        $c = '\app\admin\model\cms\\'.$find['controller'];
        (new $c)->where(['id'=>$content['id']])->save(['url'=>$urlrule[0]]);
    }

    /**
     * 生成整站url规则
     */
    public static function buildUrl()
    {
        // 获取栏目与内容规则
        $columnUrl = Db::name('config')->where(['name'=>'html_column_rules'])->find();
        $columnUrl = $columnUrl['value'];
        $contentUrl = Db::name('config')->where(['name'=>'html_content_rules'])->find();
        $contentUrl = $contentUrl['value'];

        // 栏目URL
        $categorys = Db::name('category')->where(['status'=>'normal'])->select()->toArray();
        $newCate = [];
        foreach ($categorys as $key=>$category) {
            if ($category['model_id']<=0) {
                continue;
            }
            //需要替换的标签
            $replace_l = [];
            //替换的内容
            $replace_r = [];
            // 父栏目
            $catdir = '';
            if (strstr($columnUrl, '[catdir]')) {
                //获取当前栏目父栏目
                $catdir = $category['parent_id'] ? Db::name('category')->where('id', '=', $category['parent_id'])->value('name'):'';
                $replace_r[] = $catdir;
                $replace_l[] = '[catdir]';
            }
            // 栏目目录
            if (strstr($columnUrl, '[list]')) {
                $replace_l[] = '[list]';
                $replace_r[] = $category['name'];
            }
            //标签替换
            $url = str_replace($replace_l, $replace_r, $columnUrl);
            $url = str_replace(["//"], '/', $url);
            $url = explode("|", $url);
            // 多语言的处理
            if (site('content_lang_on')==1) {
                foreach ($url as $k=>$value) {
                    $url[$k] = '/'.$category['lang'].$value;
                }
            }
            $url[0] = self::getRootPath().dirname($url[0]).'/';
            $url[0] = str_replace(["//", "\\"], '/', $url[0]);
            Db::name('category')->where('id','=',$category['id'])->update(['url'=>$url[0]]);
            $category['parent_dir'] = $catdir;
            $newCate[$category['id']] = $category;
        }

        // 内容URL
        $model = Db::name('model')->where(['status'=>'normal','controller'=>'Archives'])->select();
        foreach ($model as $key=>$value) {
            $c = '\app\admin\model\cms\\'.$value['controller'];
            $obj = (new $c)->where(['status'=>'normal'])->select();
            if (!empty($obj)) {
                foreach ($obj as $k=>$v) {
                    $replace_l = []; //需要替换的标签
                    $replace_r = []; //替换的内容

                    // 父栏目
                    if (strstr($contentUrl, '[catdir]')) {
                        //获取当前栏目父栏目
                        $replace_r[] = isset($newCate[$v['category_id']]) && $newCate[$v['category_id']]['parent_dir'] ? $newCate[$v['category_id']]['parent_dir']:'';
                        $replace_l[] = '[catdir]';
                    }
                    // 栏目目录
                    if (strstr($contentUrl, '[list]')) {
                        $replace_l[] = '[list]';
                        $replace_r[] = isset($newCate[$v['category_id']]) ? $newCate[$v['category_id']]['name']:'';
                    }
                    //年份
                    if (strstr($contentUrl, '[year]')) {
                        $replace_l[] = '[year]';
                        $replace_r[] = date('Y', $v['publish_time']??$v['create_time']);
                    }
                    //月份
                    if (strstr($contentUrl, '[month]')) {
                        $replace_l[] = '[month]';
                        $replace_r[] = date('m', $v['publish_time']??$v['create_time']);
                    }
                    //日期
                    if (strstr($contentUrl, '[day]')) {
                        $replace_l[] = '[day]';
                        $replace_r[] = date('d', $v['publish_time']??$v['create_time']);
                    }
                    //文件名，如果有自定义文件名则使用自定义文件名，否则默认使用当前内容ID
                    if (!empty($v['diyname'])) {
                        $replace_l[] = '[id]';
                        $replace_r[] = $v['diyname'];
                    } else {
                        $replace_l[] = '[id]';
                        $replace_r[] = $v['id'];
                    }
                    //标签替换
                    $urlrule = str_replace($replace_l, $replace_r, $contentUrl);
                    $urlrule = str_replace(["//"], '/', $urlrule);
                    $urlrule = explode("|", $urlrule);
                    // 多语言的处理
                    if (site('content_lang_on')==1) {
                        foreach ($urlrule as $kk=>$vo) {
                            $urlrule[$kk] = '/'.$v['lang'].$vo;
                        }
                    }
                    $urlrule[0] = self::getRootPath($urlrule[0]);
                    $urlrule[0] = str_replace(array("//", "\\"), '/', $urlrule[0]);
                    (new $c)->where(['id'=>$v['id']])->save(['url'=>$urlrule[0]]);
                }
            }
        }
    }

    /**
     * 生成首页
     * @return bool
     */
    public function index()
    {
        // 首页模板文件
        $templateFile = $this->view->getConfig('view_path').'index/index.html';
        // 保存目录
        $htmlPath = public_path();
        $filename = 'index.html';

        $langSer = app()->make(LangService::class);
        // 多语言的处理
        if (site('content_lang_on')==1) {
            $list = $langSer->getListByModule('index');
            foreach ($list as $value) {
                $this->loadLang($value['mark']);
                $this->buildHtml(self::getRootPath($value['mark'].'/'.$filename), $htmlPath, $templateFile);
            }
        }

        // 首页
        $this->loadLang($langSer->getDefaultLang('index'));
        $this->buildHtml($filename, $htmlPath, $templateFile);
        return true;
    }

    /**
     * 生成栏目页
     * @param $cateInfo
     * @param $page
     * @return bool|mixed
     */
    public function category($cateInfo, $page)
    {
        // 静态HTML文件路径
        $htmlPath = public_path();
        // 模板根目录
        $templatePath = $this->view->getConfig('view_path');
        // 总页数
        $total_page = 1;
        if ($cateInfo['model_id']<=0) {
            return false;
        }

        // 确保必要字段存在
        $cateInfo = Category::where(['status'=>'normal','id'=>$cateInfo['id']])->find();
        $cateInfo = $cateInfo->append(['parent_url','fullurl'])->toArray();
        if ('category'==$cateInfo['type']) {
            // 栏目首页模板
            $template = 'category/'.(!empty($cateInfo['category_tpl']) ? $cateInfo['category_tpl']:'category');
        } else if ('list'==$cateInfo['type']) {
            $template = 'list/'.(!empty($cateInfo['list_tpl']) ? $cateInfo['list_tpl']:'list');
        } else if ('link'==$cateInfo['type'] && $cateInfo['model_id']) {
            $model = Model::where(['id'=>$cateInfo['model_id']])->cache('html_category_model',3600, 'model')->find();
            $c = '\app\admin\model\cms\\'.$model->controller;
            $info = (new $c)->with(['category','model'])->where(['category_id'=>$cateInfo['id']])->find();
            $info = empty($info) ? [] : $info->toArray();
            if (empty($info)) {
                return false;
            }
            // 获取副表
            $single = Db::name($model->tablename)->find($info['id']);
            $single = empty($single) ? [] : $single;
            $info = array_merge($info, $single);
            // 获取扩展字段
            $fields = Db::name('model_field')->where(['status'=>'normal','model_id'=>$info['model_id']])->cache('html_category_model', 3600, 'model_field')->select()->toArray();
            // 格式化
            foreach ($fields as $v) {
                field_format($v, $info);
            }
            $this->view->assign('Info', $info);
            if (!empty($info['show_tpl'])) {
                $cateInfo['show_tpl'] = $info['show_tpl'];
            }
            $template = 'page/'.(!empty($cateInfo['show_tpl']) ? $cateInfo['show_tpl']:'page');
        } else {
            return false;
        }

        // seo 标题
        $seo_title = empty($cateInfo['seo_title'])?$cateInfo['title']:$cateInfo['seo_title'];
        $seo_title = str_replace(['$name','$page','$site'], [$seo_title,$page,site("title")], site('category_format'));

        $this->view->assign('Cate', $cateInfo);
        $this->view->assign('seo_title', $seo_title);
        $this->view->assign('seo_keywords', $cateInfo['seo_keywords']);
        $this->view->assign('seo_desc', $cateInfo['seo_desc']);

        // 加载前台语言包
        $this->loadLang($cateInfo['lang']);
        // 总条数
        $tmpTotal = 0;
        // 每页行数
        $list_rows = 0;
        // 获取生成规则
        $categoryUrl = $this->generateCategoryUrl($cateInfo, $page);
        // 指定当前页码
        Paginator::currentPageResolver(function ($varPage) use($page) {
            return $page;
        });
        // 重置页码，生成栏目页的各个分页
        Paginator::maker(function ($items, $listRows, $currentPage, $total, $simple, $options) use($categoryUrl, &$list_rows, &$tmpTotal, $page) {
            \app\common\library\Bootstrap::diyUrlResolver(function ($currentPage, $options) use($categoryUrl) {
                if ($currentPage==1) {
                    return $categoryUrl['index'];
                }
                return str_replace('[page]', (string)$currentPage, $categoryUrl['page']);
            });
            $list_rows = $listRows;
            $tmpTotal = $total;
            return new \app\common\library\Bootstrap($items, $listRows, $page, $total, $simple, $options);
        });

        // 生成HTML
        $this->buildHtml($categoryUrl['path'], $htmlPath, $templatePath.$template);
        // 统计总页数
        return max($total_page,(int)ceil($tmpTotal / ($list_rows<=0?1:$list_rows)));
    }

    /**
     * 根据模型生成内容页
     * @param $model
     * @param $page
     * @param $where
     * @return mixed
     */
    public function show($model, $page, $where = [])
    {
        // 模板主题路径
        $viewPath = $this->view->getConfig('view_path');
        // html存放public目录路径
        $htmlPath = public_path();

        $c = '\app\admin\model\cms\\'.$model['controller'];
        $obj = (new $c)->with(['category','model'])->where(['status'=>'normal','model_id'=>$model['id']])->where($where)->append(['publish_time_text','fullurl'])->paginate([
            'list_rows'=> 100,
            'page' => $page,
        ]);
        $data = $obj->toArray();

        // 获取扩展字段
        $fields = Db::name('model_field')->where(['status'=>'normal','model_id'=>$model['id']])->cache()->select()->toArray();
        //$type = Db::name('model_controller')->where(['status'=>'normal','name'=>$model['controller']])->cache()->value('type');
        foreach ($data['data'] as $key=>$info) {
            $cateInfo = Category::where(['status'=>'normal','id'=>$info['category_id']])->append(['parent_dir'])->find();
            if (empty($cateInfo)) {
                continue;
            }
            $templateFile = $viewPath.'show'.DIRECTORY_SEPARATOR.$info['show_tpl'];
            if (!is_file($templateFile)) {
                continue;
            }
            $this->view->assign('__page__', null);
            // 设定语言、加载语言包
            $this->loadLang($info['lang']);
            // 获取规则
            $urlrules = $this->generateShowUrl($cateInfo, $info);

            // 格式化字段
            $newInfo = $info;
            foreach ($fields as $k=>$v) {
                field_format($v, $newInfo);
            }
            $info = $newInfo;

            // 副表处理
            $obj = Db::name($model['tablename'])->where(['id'=>$info['id']])->find();
            if (isset($obj['content'])) {
                $content = htmlspecialchars_decode($obj['content']);

                // 内容分页
                $cpage = 1;
                $arr = explode('#page#', $content);
                $arr_count = count($arr);
                if ($arr_count>1) {
                    // 先生成分页页面
                    $tmpvalue = $info;
                    for ($p = 2;$p<=$arr_count; $p++) {
                        $pageUrl = $this->generateShowUrl($cateInfo, $tmpvalue, $p);
                        $obj['content'] = $arr[$p-1] ?? $arr[0];
                        $tmpvalue['more'] = empty($obj) ? [] : $obj;
                        $tmpvalue = array_merge($tmpvalue, empty($obj) ? [] : $obj);

                        // 自定义分页URL地址
                        \app\common\library\Bootstrap::diyUrlResolver(function ($currentPage, $options) use($pageUrl) {
                            if ($currentPage==1) {
                                return $pageUrl['index'];
                            }
                            return str_replace('[page]', (string)$currentPage, $pageUrl['page']);
                        });
                        $__tmppage__ = new \app\common\library\Bootstrap([], 1, $p, $arr_count, false, ['path'=>'']);
                        $this->view->assign('__page__', $__tmppage__);
                        // 格式化
                        foreach ($fields as $k=>$v) {
                            if ($model['type']=='more' && $v['iscore']==0) {
                                field_format($v, $tmpvalue['more']);
                            } else {
                                field_format($v, $tmpvalue);
                            }
                        }
                        // 模板赋值
                        $this->view->assign('Cate', $cateInfo);
                        $this->view->assign('Info', $tmpvalue);
                        // seo 模型固定的默认字段 keywords description
                        $seo_title = empty($info['seotitle'])?$info['title']:$info['seotitle'];
                        $seo_title = str_replace(['$title','$name','$site'], [$seo_title,$cateInfo['title'],site("title")], site('content_format'));
                        $this->view->assign('seo_title', $seo_title);
                        $this->view->assign('seo_keywords', isset($info['keywords'])?$info['keywords']:$cateInfo['seo_keywords']);
                        $this->view->assign('seo_desc', isset($info['description'])?$info['description']:$cateInfo['seo_desc']);
                        //生成页面
                        $this->buildHtml($pageUrl["path"], $htmlPath, $templateFile);
                    }
                    // 自定义首页分页URL地址
                    \app\common\library\Bootstrap::diyUrlResolver(function ($currentPage, $options) use($urlrules) { // 指定规则
                        if ($currentPage==1) {
                            return $urlrules['index'];
                        }
                        return str_replace('[page]', (string)$currentPage, $urlrules['page']);
                    });
                    $__page__ = new \app\common\library\Bootstrap([], 1, 1, $arr_count, false, ['path'=>'']);
                    $obj['content'] = $arr[$cpage-1] ?? $arr[0];
                    $this->view->assign('__page__', $__page__);
                }
            }

            if (!empty($obj)) {
                // 格式化
                foreach ($fields as $k=>$v) {
                    field_format($v, $obj);
                }
                $info = array_merge($info, $obj);
                // 兼容旧版
                $info['more'] = $obj;
            } else {
                // 兼容旧版
                $info['more'] = [];
            }

            $this->view->assign('Cate', $cateInfo);
            $this->view->assign('Info', $info);

            // seo 模型固定的默认字段 keywords description
            $seo_title = empty($info['seotitle'])?$info['title']:$info['seotitle'];
            $seo_title = str_replace(['$title','$name','$site'], [$seo_title,$cateInfo['title'],site("title")], site('content_format'));

            $this->view->assign('seo_title', $seo_title);
            $this->view->assign('seo_keywords', isset($info['keywords'])?$info['keywords']:$cateInfo['seo_keywords']);
            $this->view->assign('seo_desc', isset($info['description'])?$info['description']:$cateInfo['seo_desc']);
            //生成页面
            $this->buildHtml($urlrules["path"], $htmlPath, $templateFile);
        }
        return $data['last_page'];
    }

    /**
     * 生成单个
     * @param $id
     * @param null $category
     * @param null $model
     * @return bool|string
     */
    public function showSing($id, $category = null, $model = null)
    {
        // 模板主题路径
        $viewPath = $this->view->getConfig('view_path');
        // html存放public目录路径
        $htmlPath = public_path();
        // 栏目信息
        $cateInfo = Category::where(['status'=>'normal','id'=>$category['id']])->append(['parent_dir'])->find();
        if (empty($cateInfo)) {
            return '';
        }

        $model = Model::where(['id'=>$cateInfo['model_id']])->find();
        // 获取扩展字段
        $fields = Db::name('model_field')->where(['status'=>'normal','model_id'=>$model['id']])->cache()->select()->toArray();
        $type = Db::name('model_controller')->where(['status'=>'normal','name'=>$model['controller']])->cache()->value('type');

        // 文档
        $c = '\app\admin\model\cms\\'.$model->controller;
        $info = (new $c)->with(['category','model'])->where(['id'=>$id,'status'=>'normal'])->append(['publish_time_text','fullurl'])->find()->toArray();
        // 模板不存在
        $templateFile = $viewPath.'show'.DIRECTORY_SEPARATOR.$info['show_tpl'];
        if (!is_file($templateFile)) {
            return '';
        }
        // 设定语言、加载语言包
        $this->loadLang($info['lang']);
        // 获取规则
        $urlrules = $this->generateShowUrl($cateInfo, $info);

        // 格式化字段
        foreach ($fields as $k=>$v) {
            field_format($v, $info);
        }
        // 副表处理
        $obj = Db::name($model['tablename'])->where(['id'=>$info['id']])->find();
        if (isset($obj['content'])) {
            $content = htmlspecialchars_decode($obj['content']);

            // 内容分页
            $cpage = 1;
            $arr = explode('#page#', $content);
            $arr_count = count($arr);
            if ($arr_count>1) {

                // 自定义首页分页URL地址
                \app\common\library\Bootstrap::diyUrlResolver(function ($currentPage, $options) use($urlrules) { // 指定规则
                    if ($currentPage==1) {
                        return $urlrules['index'];
                    }
                    return str_replace('[page]', (string)$currentPage, $urlrules['page']);
                });
                $__page__ = new \app\common\library\Bootstrap([], 1, 1, $arr_count, false, ['path'=>'']);

                // 生成分页页面
                $tmpvalue = $info;
                for ($p = 2;$p<=$arr_count; $p++) {
                    $pageUrl = $this->generateShowUrl($cateInfo, $tmpvalue, $p);
                    $obj['content'] = $arr[$p-1] ?? $arr[0];
                    $tmpvalue['more'] = empty($obj) ? [] : $obj;
                    $tmpvalue = array_merge($tmpvalue, empty($obj) ? [] : $obj);

                    // 自定义分页URL地址
                    \app\common\library\Bootstrap::diyUrlResolver(function ($currentPage, $options) use($pageUrl) {
                        if ($currentPage==1) {
                            return $pageUrl['index'];
                        }
                        return str_replace('[page]', (string)$currentPage, $pageUrl['page']);
                    });
                    $__tmppage__ = new \app\common\library\Bootstrap([], 1, $p, $arr_count, false, ['path'=>'']);
                    $this->view->assign('__page__', $__tmppage__);
                    // 格式化
                    foreach ($fields as $k=>$v) {
                        if ($model['type']=='more' && $v['iscore']==0) {
                            field_format($v, $tmpvalue['more']);
                        } else {
                            field_format($v, $tmpvalue);
                        }
                    }
                    // 模板赋值
                    $this->view->assign('Cate', $cateInfo);
                    $this->view->assign('Info', $tmpvalue);
                    // seo 模型固定的默认字段 keywords description
                    $seo_title = empty($info['seotitle'])?$info['title']:$info['seotitle'];
                    $seo_title = str_replace(['$title','$name','$site'], [$seo_title,$cateInfo['title'],site("title")], site('content_format'));
                    $this->view->assign('seo_title', $seo_title);
                    $this->view->assign('seo_keywords', isset($info['keywords'])?$info['keywords']:$cateInfo['seo_keywords']);
                    $this->view->assign('seo_desc', isset($info['description'])?$info['description']:$cateInfo['seo_desc']);
                    //生成页面
                    $this->buildHtml($pageUrl["path"], $htmlPath, $templateFile);
                }
                $obj['content'] = $arr[$cpage-1] ?? $arr[0];
                $this->view->assign('__page__', $__page__);
            }
        }

        if (!empty($obj)) {
            // 格式化
            foreach ($fields as $k=>$v) {
                field_format($v, $obj);
            }
            $info = array_merge($info, $obj);
            // 兼容旧版
            $info['more'] = $obj;
        } else {
            // 兼容旧版
            $info['more'] = [];
        }


        $this->view->assign('Cate', $cateInfo);
        $this->view->assign('Info', $info);

        // seo 模型固定的默认字段 keywords description
        $seo_title = empty($info['seotitle'])?$info['title']:$info['seotitle'];
        $seo_title = str_replace(['$title','$name','$site'], [$seo_title,$cateInfo['title'],site("title")], site('content_format'));

        $this->view->assign('seo_title', $seo_title);
        $this->view->assign('seo_keywords', isset($info['keywords'])?$info['keywords']:$cateInfo['seo_keywords']);
        $this->view->assign('seo_desc', isset($info['description'])?$info['description']:$cateInfo['seo_desc']);
        //生成页面
        $this->buildHtml($urlrules["path"], $htmlPath, $templateFile);
        return true;
    }

    /**
     * 获取生成的根路径
     * @param string $file
     * @return string
     */
    public static function getRootPath(string $file = '')
    {
        $html_dir = site('html_dir');
        return ($html_dir ? '/'.$html_dir.'/' : '/').$file;
    }

    /**
     * 静态文件生成
     * @param string $fileName
     * @param string $htmlPath
     * @param string $templateFile
     * @return string
     */
    protected function buildHtml(string $fileName='', string $htmlPath='', string $templateFile=''): string
    {
        if (!is_file($templateFile)) {
            return '';
        }
        $content = $this->view->fetch($templateFile);
        $file = str_replace(['\\'],'/', $htmlPath.$fileName);
        $file = str_replace(['//'],'/', $file);
        // 生成静态文件
        $File = new \think\template\driver\File();
        $File->write($file, $content);
        return $content;
    }

    /**
     * 获取栏目页URL规则处理后的
     * @param array $category 栏目信息
     * @param integer $page 分页号
     * @return array
     */
    protected function generateCategoryUrl(array $category, int $page = 1): array
    {
        // 栏目规则
        $columnUrl = site('html_column_rules');
        //需要替换的标签
        $replace_l = [];
        //替换的内容
        $replace_r = [];
        if (strstr($columnUrl, '[catdir]')) {
            //获取当前栏目父栏目
            $catdir = $category['parent_id'] ? Db::name('category')->where('id', '=', $category['parent_id'])->value('name'):'';
            $replace_r[] = $catdir;
            $replace_l[] = '[catdir]';
        }
        // 栏目目录
        if (strstr($columnUrl, '[list]')) {
            $replace_l[] = '[list]';
            $replace_r[] = $category['name'];
        }
        //标签替换
        $columnUrl = str_replace($replace_l, $replace_r, $columnUrl);
        $columnUrl = str_replace(['//', '\\'], '/', $columnUrl);
        $urlArr = explode("|", $columnUrl);
        // 多语言的处理
        if (site('content_lang_on')==1) {
            foreach ($urlArr as $k=>$value) {
                $urlArr[$k] = self::getRootPath().$category['lang'].$value;
            }
        } else {
            foreach ($urlArr as $k=>$value) {
                $urlArr[$k] = self::getRootPath().$value;
            }
        }

        $path = $page > 1 ? str_replace('[page]', (string)$page, $urlArr[1]) : $urlArr[0];

        $urlArr[0] = str_replace(['//', '\\'], '/', $urlArr[0]);
        $urlArr[1] = str_replace(['//', '\\'], '/', $urlArr[1]);
        return [
            'index' => $urlArr[0], // 主页
            'path'=> $path, // 生成html
            'page'=>$urlArr[1]
        ];
    }

    /**
     * 获取内容分页规则
     * @param $category
     * @param $content
     * @param int $page
     * @return array
     */
    public function generateShowUrl($category, $content, $page=1)
    {
        $replace_l = []; //需要替换的标签
        $replace_r = []; //替换的内容
        $contentUrl = site('html_content_rules');

        // 父栏目
        if (strstr($contentUrl, '[catdir]')) {
            //获取当前栏目父栏目
            $replace_r[] = $category['parent_id'] ? Db::name('category')->where('id', '=', $category['parent_id'])->value('name'):'';
            $replace_l[] = '[catdir]';
        }
        // 栏目目录
        if (strstr($contentUrl, '[list]')) {
            $replace_l[] = '[list]';
            $replace_r[] = $category['name'];
        }
        //年份
        if (strstr($contentUrl, '[year]')) {
            $replace_l[] = '[year]';
            $replace_r[] = date('Y', $content['publish_time']??$content['create_time']);
        }
        //月份
        if (strstr($contentUrl, '[month]')) {
            $replace_l[] = '[month]';
            $replace_r[] = date('m', $content['publish_time']??$content['create_time']);
        }
        //日期
        if (strstr($contentUrl, '[day]')) {
            $replace_l[] = '[day]';
            $replace_r[] = date('d', $content['publish_time']??$content['create_time']);
        }
        //文件名，如果有自定义文件名则使用自定义文件名，否则默认使用当前内容ID
        if (!empty($content['diyname'])) {
            $replace_l[] = '[id]';
            $replace_r[] = $content['diyname'];
        } else {
            $replace_l[] = '[id]';
            $replace_r[] = $content['id'];
        }

        //标签替换
        $urlrule = str_replace($replace_l, $replace_r, $contentUrl);
        $urlrule = explode("|", $urlrule);
        // 多语言的处理
        if (site('content_lang_on')==1) {
            foreach ($urlrule as $kk=>$vo) {
                $urlrule[$kk] = '/'.$content['lang'].$vo;
            }
        }
        $urlrule[0] = self::getRootPath($urlrule[0]);
        $urlrule[0] = str_replace(array("//", "\\"), '/', $urlrule[0]);
        $path = $page > 1 ? str_replace('[page]', (string)$page, $urlrule[1]) : $urlrule[0];
        return [
            'index' => $urlrule[0], // 主页
            'path'=> $path, // 生成html
            'page'=>$urlrule[1]
        ];
    }

    /**
     * 设置语言包
     * @param $lang
     */
    protected function loadLang($lang)
    {
        $this->app->lang->setLangSet($lang);
        // 模板语言包
        $langRoot = config('cms.tpl_static').'index'.DIRECTORY_SEPARATOR.site('index_theme').DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR;
        if (is_file($langRoot.$lang.'.json')) {
            $this->app->lang->load($langRoot.$lang.'.json');
        }
        if (is_file($langRoot.$lang.DIRECTORY_SEPARATOR.'index.json')) {
            $this->app->lang->load($langRoot.$lang.DIRECTORY_SEPARATOR.'index.json');
        }
        // 控制器语言包
        $langRoot = $this->app->getBasePath().'index'.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR;
        if (is_file($langRoot.$lang.'.php')) {
            $this->app->lang->load($langRoot.$lang.'.php');
        }
        if (is_file($langRoot.$lang.DIRECTORY_SEPARATOR.'index.php')) {
            $this->app->lang->load($langRoot.$lang.'.php');
        }
    }
}