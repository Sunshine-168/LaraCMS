<?php
// +----------------------------------------------------------------------
// | 多语言数据访问
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\dao\lang;

use app\common\dao\BaseDao;
use app\common\model\lang\LangBind;

class LangBindDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return LangBind::class;
    }

    /**
     * 搜索列表
     * @param array $where
     */
    public function searchLists(array $where)
    {
        return $this->getModel()
        ->when(!empty($where['table']), function ($query) use($where) {
            $query->where('table', $where['table']);
        })->when(!empty($where['source_id']), function ($query) use ($where) {
            $query->where(function ($query) use ($where) {
                $query->whereOr(['value_id'=>$where['source_id']])->whereOr(['main_id'=>$where['source_id']]);
            });
        })->when(!empty($where['main_id']), function ($query) use ($where) {
            $query->where('main_id', $where['main_id']);
        })->when(isset($where['value_id']) && $where['value_id']!='', function ($query) use ($where) {
            $query->where('value_id', $where['value_id']);
        });
    }
}