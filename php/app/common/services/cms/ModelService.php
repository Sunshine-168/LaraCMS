<?php
// +----------------------------------------------------------------------
// | 模型服务
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\services\cms;

use app\common\dao\cms\ModelDao;
use app\common\services\BaseService;
use app\common\services\cache\CacheService;
use think\facade\Cache;

/**
 * 模型服务
 * @mixin ModelDao
 */
class ModelService extends BaseService
{
    // 多语言缓存标签
    const CACHE_TAG = "model";

    /**
     * 初始化
     * @param ModelDao $dao
     */
    public function __construct(ModelDao $dao, CacheService $cacheService)
    {
        $this->dao = $dao;
        $cacheService->bucket(self::CACHE_TAG);
    }

    /**
     * 获取模型表名
     * @param $modelId
     * @return string
     */
    public function tableNameCache($modelId)
    {
        $tableName = Cache::get('model_table_name'.$modelId);
        if (empty($tableName)) {
            $info = $this->dao->getOne($modelId, 'tablename');
            if ($info) {
                Cache::tag(self::CACHE_TAG)->set('model_table_name'.$modelId, $info->tablename);
                return $info->tablename;
            }
            return "";
        }
        return $tableName;
    }
}