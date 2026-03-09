<?php
// +----------------------------------------------------------------------
// | 站点模块数据
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\dao\cms;

use app\common\dao\BaseDao;
use app\common\model\cms\Recommend;

class RecommendDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return Recommend::class;
    }

    /**
     * @param array $where
     * @return \app\common\model\BaseModel
     */
    public function searchWhere(array $where = [])
    {
        return $this->getModel()
            ->when(isset($where['status']) && $where['status'] != '', function ($query) use ($where) {
                $query->where('status', $where['status']);
            })
            ->when(isset($where['name']) && $where['name'] != '', function ($query) use ($where) {
                $query->where('name', $where['name']);
            })
            ->when(isset($where['lang']) && $where['lang'] != '', function ($query) use ($where) {
                $query->where('lang', $where['lang']);
            });
    }
}