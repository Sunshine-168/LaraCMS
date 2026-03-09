<?php
// +----------------------------------------------------------------------
// | 内容管理
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\dao\cms;

use app\common\dao\BaseDao;
use app\common\model\cms\Archives;
use app\common\services\cms\CategoryService;
use app\common\services\cms\ModelFieldsService;
use app\common\services\cms\ModelService;
use think\db\Query;

class ArchivesDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return Archives::class;
    }

    /**
     * 列表搜索
     * @param array $where
     * @return \app\common\model\BaseModel
     */
    public function listSearchJoin(string $tableName, int $modelId, array $where = [])
    {
        $modelFieldSer = app()->make(ModelFieldsService::class);
        // 扩展字段处理
        [$extendWhere, $raw, $bind] = $this->buildWhere($where['extend_field']??[], $where['extend_op'] ?? [], $modelFieldSer->getFieldsNameCache($modelId));
        return $this->getModel()
            ->alias('A')
            ->join($tableName.' B', 'A.id = B.id')
            ->when(!empty($extendWhere), function (Query $query) use($extendWhere) {
                $query->where($extendWhere);
            })
            ->when(!empty($raw) && !empty($bind), function (Query $query) use($raw, $bind) {
                $query->whereRaw($raw, $bind);
            })
            ->when(isset($where['model_id']) && is_numeric($where['model_id']), function (Query $query) use($where) {
                $query->where('A.model_id', $where['model_id']);
            })
            ->when(!empty($where['catid']), function (Query $query) use($where) {
                if (is_array($where['catid'])) {
                    $query->whereIn('A.category_id', $where['catid']);
                } else {
                    $query->where('A.category_id', $where['catid']);
                }
            })
            ->when(isset($where['lang']) && $where['lang']!=='', function (Query $query) use($where) {
                $query->where('A.lang', $where['lang']);
            })
            ->when(isset($where['title']) && $where['title']!=='', function (Query $query) use($where) {
                $query->whereLike('A.title', "%{$where['title']}%");
            })
            ->when(isset($where['status']) && $where['status']!=='', function (Query $query) use($where) {
                $query->where('A.status', $where['status']);
            });
    }
}