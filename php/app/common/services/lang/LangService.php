<?php
// +----------------------------------------------------------------------
// | 多语言服务
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\services\lang;

use app\common\dao\lang\LangDao;
use app\common\exception\ServiceException;
use app\common\services\BaseService;
use app\common\services\cache\CacheService;
use app\common\services\cms\ArchivesService;
use app\common\services\cms\BannerService;
use app\common\services\cms\CategoryPrivService;
use app\common\services\cms\CategoryService;
use app\common\services\cms\FlagsService;
use app\common\services\cms\ModelFieldBindService;
use app\common\services\cms\ModelService;
use app\common\services\cms\RecommendService;
use app\common\services\config\ConfigService;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Lang;

/**
 * @mixin LangDao
 */
class LangService extends BaseService
{
    /**
     * 语言模块对应列表
     */
    const MARK_LIST = [
        'admin' => 2,
        'index' => 1,
        'content' => 3,
    ];

    // 多语言缓存标签
    const CACHE_TAG = "lang";

    /**
     * 初始化
     * @param LangDao $langDao
     */
    public function __construct(LangDao $langDao, CacheService $cacheService)
    {
        $this->dao = $langDao;
        // 加入统一缓存管理
        $cacheService->bucket(self::CACHE_TAG);
    }

    /**
     * 内容语言同步
     * @param string $lang
     * @param string $event
     * @param string $oldLang
     * @return false|mixed
     */
    public function sync(string $lang, string $event, string $oldLang = '')
    {
        // 配置服务
        $configService = app()->make(ConfigService::class);
        // 栏目服务
        $categoryService = app()->make(CategoryService::class);
        // 站点配置服务
        $recommendService = app()->make(RecommendService::class);
        $bannerService = app()->make(BannerService::class);
        // 文档属性服务
        $flagsService = app()->make(FlagsService::class);
        // 语言绑定关系服务
        $langBindSer = app()->make(LangBindService::class);

        if ('add'==$event) { // 内容语言新增，同步语言
            // 获取默认语言
            $defaultLang = $this->getDefaultLang('content');
            $refData = $configService->search()->where(['lang'=>$defaultLang])->select()->toArray();;
            $cateData = $categoryService->search()->where(['lang'=>$defaultLang])->order('parent_id', 'asc')->select()->toArray();
            $recData = $recommendService->search()->where(['lang'=>$defaultLang])->select()->toArray();
            $flagsData = $flagsService->search()->where(['lang'=>$defaultLang])->select()->toArray();
            return $this->transaction(function () use($lang, $refData, $configService, $categoryService, $cateData, $recommendService, $recData, $langBindSer, $flagsService, $flagsData, $bannerService) {
                // 生成语言，写入数据
                foreach ($refData as $v) {
                    $tid = $v['id'];
                    unset($v['id']);
                    $v['lang'] = $lang;
                    if ($configService->isExist(['lang' => $v['lang'], 'name' => $v['name']])) {
                        continue;
                    }
                    $item = $configService->create($v);
                    if ($v['is_default'] != 1) {
                        $langBindSer->create([
                            'main_id' => $tid,
                            'value_id' => $item->id,
                            'table' => 'config',
                            'lang' => $lang,
                            'create_time' => time(),
                        ]);
                    }
                }
                foreach ($cateData as $v) { // 栏目同步
                    $tid = $v['id'];
                    unset($v['id']);
                    $v['lang'] = $lang;
                    $v['title'] = "[{$lang}]" . $v['title'];
                    if ($v['parent_id']) {
                        $v['parent_id'] = $langBindSer->getBindValue($v['parent_id'], 'category', $lang);
                    }
                    $item = $categoryService->create($v);
                    $langBindSer->create([
                        'main_id' => $tid,
                        'value_id' => $item->id,
                        'table' => 'category',
                        'lang' => $lang,
                        'create_time' => time(),
                    ]);
                }
                foreach ($recData as $v) { // 站点模块
                    $tid = $v['id'];
                    unset($v['id']);
                    $v['lang'] = $lang;
                    $item = $recommendService->create($v);
                    $langBindSer->create([
                        'main_id' => $tid,
                        'value_id' => $item->id,
                        'table' => 'recommend',
                        'lang' => $lang,
                        'create_time' => time(),
                    ]);

                    // 模块资源
                    $banData = $bannerService->search()->where('recommend_id', '=', $tid)->select()->toArray();
                    foreach ($banData as $vv) {
                        unset($vv['id']);
                        $vv['lang'] = $lang;
                        $vv['recommend_id'] = $item->id;
                        $bannerService->create($vv);
                    }
                }
                foreach ($flagsData as $v) {
                    $tid = $v['id'];
                    unset($v['id']);
                    $v['lang'] = $lang;
                    $item = $flagsService->create($v);
                    $langBindSer->create([
                        'main_id' => $tid,
                        'value_id' => $item->id,
                        'table' => 'flags',
                        'lang' => $lang,
                        'create_time' => time(),
                    ]);
                }
                return true;
            });
        } else if ('edit'==$event) {
            // 文档服务
            $archivesService = app()->make(ArchivesService::class);
            return $this->transaction(function () use($lang, $oldLang, $configService, $recommendService, $bannerService, $archivesService, $categoryService, $flagsService, $langBindSer) {
                // 配置
                $configService->update(['lang'=>$oldLang],['lang'=>$lang]);
                // 站点模块
                $recommendService->update(['lang'=>$oldLang],['lang'=>$lang]);
                $bannerService->update(['lang'=>$oldLang],['lang'=>$lang]);
                // 文档
                $archivesService->update(['lang'=>$oldLang],['lang'=>$lang]);
                // 栏目
                $categoryService->update(['lang'=>$oldLang],['lang'=>$lang]);
                // 文档属性
                $flagsService->update(['lang'=>$oldLang],['lang'=>$lang]);
                $langBindSer->update(['lang'=>$oldLang],['lang'=>$lang]);
                return true;
            });
        } else if ('del'==$event) {
            return $this->transaction(function () use($lang, $configService, $categoryService, $recommendService, $bannerService, $flagsService, $langBindSer){
                $archivesService = app()->make(ArchivesService::class);
                $modelSer = app()->make(ModelService::class);
                $categoryPrivSer = app()->make(CategoryPrivService::class);
                $modelFieldBindSer = app()->make(ModelFieldBindService::class);
                // 配置
                $configService->delete($lang, 'lang');
                // 栏目
                $category = $categoryService->search()->where(['lang'=>$lang])->select();
                $ids = [];
                // 获取语言所有文档id
                $idArr = $archivesService->column(['lang'=>$lang],'id');
                foreach ($category as $v) {
                    if ($v['model_id'] && !isset($modelArr[$v['model_id']])) {
                        $modelArr[$v['model_id']] = $modelSer->getOne($v['model_id'], 'id,tablename,is_search');
                        if ($modelArr[$v['model_id']]['is_search']!='-1') {
                            Db::name($modelArr[$v['model_id']]['tablename'])->whereIn('id',$idArr)->delete();
                        }
                    }
                    $ids[] = $v['id'];
                }
                // 文档删除
                Db::name('archives')->whereIn('id',$idArr)->delete();
                // 删除栏目数据
                $categoryService->delete([['id','in',$ids]]);
                $categoryPrivSer->delete([['category_id','in',$ids]]);
                $modelFieldBindSer->delete([['category_id','in',$ids]]);
                // 站点模块删除
                $ids = $recommendService->column(['lang'=>$lang]);
                $bannerService->delete([['recommend_id','in',$ids]]);
                $recommendService->delete([['id','in',$ids]]);
                // 文档属性删除
                $flagsService->delete($lang, 'lang');
                // 语言关系删除
                $langBindSer->delete($lang, 'lang');
                return true;
            });
        } else {
            return false;
        }
    }

    /**
     * 获取默认语言标识
     * @param string $module
     * @return string
     */
    public function getDefaultLang(string $module)
    {
        $key = md5($module.'_lang_default');
        $module = self::MARK_LIST[$module]??'index';
        $defaultLang = Cache::get($key);
        if (empty($defaultLang)) {
            $defaultLang = $this->dao->getValue(['module'=>$module,'is_default'=>1], 'mark');
            Cache::tag(self::CACHE_TAG)->set($key, $defaultLang, 86400);
        }
        return $defaultLang;
    }

    /**
     * 设置默认值
     * @param int $id
     * @return \app\common\model\BaseModel
     * @throws ServiceException
     */
    public function setDefaultLang(int $id)
    {
        $info = $this->dao->getOne($id);
        if (empty($info)) {
            throw new ServiceException("No results were found");
        }
        $this->dao->update(['module'=>$info->module,'is_default'=>1],['is_default'=>0]);
        $key = md5(array_flip(self::MARK_LIST)[$info->module].'_lang_default');
        Cache::tag(self::CACHE_TAG)->set($key, $info->mark, 86400);
        return $this->dao->update($id, ['is_default'=>1]);
    }

    /**
     * 获取当前语言
     * @param string $module admin、api、index
     * @return string
     */
    public function getLang(string $module)
    {
        // LoadLangPack 中间件已经拿到合法的语言，无需任何处理
        return Lang::getLangset();
        // if ($this->langStatus($module)!=1) {
        //     return $this->getDefaultLang($module);
        // }
        // $lang = Lang::getLangset();
        // $column = $this->dao->column(['module'=>self::MARK_LIST[$module]], 'mark');
        // return in_array($lang,$column) ? $lang : $this->getDefaultLang($module);
    }

    /**
     * 获取语言开启状态
     * @param string $module admin、api、index
     * @return string | int
     */
    public function langStatus(string $module)
    {
        $configSer = app()->make(ConfigService::class);
        $info = $configSer->getOne(['name'=>$module.'_lang_on'],'value');
        return $info ? $info->value : "";
    }

    /**
     * 当前内容编辑模式
     * @param int $userId
     * @return array|mixed|string
     */
    public function contentMode(int $userId)
    {
        if ($lang = Cache::get('admin_content_lang'.$userId)) {
            return $lang;
        }
        return $this->getDefaultLang('content');
    }

    /**
     * 设置内容编辑模式
     * @param int $userId
     * @param string $lang
     * @return void
     */
    public function setContentMode(int $userId, string $lang): void
    {
        Cache::tag(self::CACHE_TAG)->set('admin_content_lang'.$userId, $lang);
    }

    /**
     * 获取所有语言列表，以前台或后台组列表形式展现
     * @param array $where
     * @return array
     */
    public function getListByGroup(array $where): array
    {
        $data = $this->dao->getSearchList($where);
        $newData = [];
        $markList = array_flip(self::MARK_LIST);
        foreach ($data as $item) {
            $newData[$markList[$item['module']]][] = $item;
        }
        return $newData;
    }

    /**
     * 获取对应模块语言列表
     * @param string $module
     */
    public function getListByModule(string $module)
    {
        return $this->dao->getSearchList(['module'=>self::MARK_LIST[$module],'status'=>1]);
    }

    /**
     * 获取对应模块语言列表
     * @param string $module
     */
    public function getListByModuleCache(string $module)
    {
        return Db::name('lang')
            ->where(['module'=>self::MARK_LIST[$module],'status'=>1])
            ->cache('lang_list_'.$module,3600, self::CACHE_TAG)
            ->column('mark');
    }
}