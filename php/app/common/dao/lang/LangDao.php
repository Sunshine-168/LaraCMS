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
use app\common\model\lang\Lang;

class LangDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return Lang::class;
    }

    /**
     * 获取默认语言
     * @param int $module
     * @param string $field
     */
    public function getDefaultLang(int $module, string $field="*")
    {
        return $this->getModel()->where(['module'=>$module,'is_default'=>1])->field($field)->find();
    }

    /**
     * 获取列表
     * @param array $where
     */
    public function getSearchList(array $where = [])
    {
        return $this->search($where)->order('weigh','asc')->select()->toArray();
    }
}