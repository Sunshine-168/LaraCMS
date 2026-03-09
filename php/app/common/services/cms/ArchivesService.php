<?php
// +----------------------------------------------------------------------
// | 文档服务
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\services\cms;

use app\common\dao\cms\ArchivesDao;
use app\common\exception\ServiceException;
use app\common\services\BaseService;
use think\facade\Cache;
use think\facade\Db;

/**
 * @mixin ArchivesDao
 */
class ArchivesService extends BaseService
{
    /**
     * 初始化
     * @param ArchivesDao $dao
     */
    public function __construct(ArchivesDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 列表分页
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function listPage(array $where = [], int $page = 1, int $limit = 10): array
    {
        $modelSer = app()->make(ModelService::class);
        $modelFieldSer = app()->make(ModelFieldsService::class);
        // 获取附表
        if (!empty($where['model_id'])) {
            $tableName = $modelSer->tableNameCache($where['model_id']);
            $modelId = $where['model_id'];
        } else {
            $catid = is_array($where['catid']) ? $where['catid'][0] : $where['catid'];
            $modelId = app()->make(CategoryService::class)->getOne($catid,'model_id');
            $tableName = $modelSer->tableNameCache($modelId);
        }
        $query = $this->dao->listSearchJoin($tableName, $modelId, $where);
        $count = $query->count();
        // 获取附表字段
        $fields = $modelFieldSer->getFieldsNameBySubTbCache($modelId);
        $newFields = [];
        foreach ($fields as $val) {
            if (in_array($val, ['content'])) {
                continue;
            }
            $newFields[] = $val;
        }
        $fieldStr = implode(',B.', $newFields);
        $fieldStr = $fieldStr ? ',B.'.$fieldStr : '';
        $lists = $query->page($page, $limit)
            ->with(['category'])
            ->order($this->dao->buildOrder($where['sort_by']??['weigh'=>'asc', 'publish_time'=>'desc'], $where['sort_type']??''))
            ->field('A.*'.$fieldStr)
            ->select();
        return compact('lists', 'count');
    }

    /**
     * 清理缓存
     * @param array $categoryIds
     * @return void
     */
    public function clearCache(array $categoryIds)
    {
        $totalSub = Cache::get('category_doc_total_sub');
        $totalCount = Cache::get('get_doc_total');
        if (is_array($totalSub)) {
            $newArr = [];
            foreach ($totalSub as $key=>$value) {
                if (!in_array($key, $categoryIds)) {
                    $newArr[$key] = $value;
                }
            }
            Cache::set('category_doc_total_sub', $newArr);
        }
        if (is_array($totalCount)) {
            $newArr = [];
            foreach ($totalCount as $key=>$value) {
                if (!in_array($key, $categoryIds)) {
                    $newArr[$key] = $value;
                }
            }
            Cache::set('get_doc_total', $newArr);
        }
        Cache::tag('archives_content_tag')->clear();
    }

    /**
     * 详情
     * @param int $id
     * @param int $page
     * @return array
     */
    public function details(int $id, int $page): array
    {
        $info = $this->dao->getOne($id);
        if (empty($info)) {
            throw new ServiceException("No results were found", 400);
        }
        $modelSer = app()->make(ModelService::class);
        $tableName = $modelSer->tableNameCache($info['model_id']);
        $moreInfo = Db::name($tableName)->where('id', $info['id'])->find();
        if (!empty($moreInfo)) {
            $content = htmlspecialchars_decode($moreInfo['content']);
            $arr = explode('#page#', $content);
            $moreInfo['content'] = $arr[$page-1] ?? $arr[0];
        }
        $info = array_merge($info->toArray(), $moreInfo);
        // 获取附表详细字段
        $modelFieldSer = app()->make(ModelFieldsService::class);
        $fields = $modelFieldSer->search([])->where(['status'=>'normal','model_id'=>$info['model_id']])->select()->toArray();
        foreach ($fields as $v) {
            field_format($v, $info);
        }
        return $info;
    }
}