<?php
// +----------------------------------------------------------------------
// | 栏目数据访问
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\dao\cms;

use app\common\dao\BaseDao;
use app\common\model\BaseModel;
use app\common\model\cms\Category;
use app\common\services\cms\FieldsService;
use think\db\Query;

class CategoryDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return Category::class;
    }

    /**
     * 列表筛选
     * @param array $where
     * @return BaseModel
     */
    public function listSearch(array $where = [])
    {
        // 扩展字段处理
        $fieldsService = app()->make(FieldsService::class);
        [$extendWhere, $raw, $bind] = $this->buildWhere($where['extend_field']??[], $where['extend_op']??[], $fieldsService->getFieldsNameCache());
        return $this->getModel()
            ->when(!empty($extendWhere), function (Query $query) use($extendWhere) {
                $query->where($extendWhere);
            })
            ->when(!empty($raw) && !empty($bind), function (Query $query) use($raw, $bind) {
                $query->whereRaw($raw, $bind);
            })
            ->when(isset($where['model_id']) && is_numeric($where['model_id']), function (Query $query) use($where) {
                $query->where('model_id', $where['model_id']);
            })
            ->when(isset($where['parent_id']) && is_numeric($where['parent_id']), function (Query $query) use($where) {
                $query->where('parent_id', $where['parent_id']);
            })
            ->when(isset($where['type']) && $where['type']!=='', function (Query $query) use($where) {
                $query->where('type', $where['type']);
            })
            ->when(isset($where['name']) && $where['name']!=='', function (Query $query) use($where) {
                $query->where('name', $where['name']);
            })
            ->when(isset($where['title']) && $where['title']!=='', function (Query $query) use($where) {
                $query->whereLike('title', "%{$where['title']}%");
            })
            ->when(isset($where['ismenu']) && is_numeric($where['ismenu']), function (Query $query) use($where) {
                $query->where('ismenu', $where['ismenu']);
            })
            ->when(isset($where['user_auth']) && is_numeric($where['user_auth']), function (Query $query) use($where) {
                $query->where('user_auth', $where['user_auth']);
            })
            ->when(isset($where['lang']) && $where['lang']!=='', function (Query $query) use($where) {
                $query->where('lang', $where['lang']);
            })
            ->when(isset($where['status']) && $where['status']!=='', function (Query $query) use($where) {
                $query->where('status', $where['status']);
            })
            ->when(isset($where['id']) && is_numeric($where['id']), function (Query $query) use($where) {
                $query->where('id', $where['id']);
            })
            ->when(isset($where['ids']) && is_array($where['ids']), function (Query $query) use($where) {
                $query->whereIn('ids', $where['ids']);
            });
    }
}