<?php
// +----------------------------------------------------------------------
// | 模型字段
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\services\cms;

use app\common\dao\cms\ModelFieldBindDao;
use app\common\dao\cms\ModelFieldsDao;
use app\common\services\BaseService;
use app\common\services\cache\CacheService;
use think\facade\Cache;

/**
 * @mixin ModelFieldBindDao
 */
class ModelFieldsService extends BaseService
{
    const CacheTag = 'modelFieldsService';

    /**
     * 初始化
     * @param ModelFieldsDao $dao
     */
    public function __construct(ModelFieldsDao $dao, CacheService $cacheService)
    {
        $this->dao = $dao;
        $cacheService->bucket(self::CacheTag);
    }

    /**
     * 获取缓存字段一维数组
     * @param $modelId
     * @return array
     */
    public function getFieldsNameCache($modelId)
    {
        $fields = Cache::get('fields_name_'.$modelId);
        if (empty($fields)) {
            $fields = $this->dao->column(['model_id'=>$modelId, 'status'=>'normal'],'field_name');
            Cache::tag(self::CacheTag)->set('fields_name'.$modelId, $fields, 600);
        }
        return $fields;
    }

    /**
     * 获取缓存附表字段一维数组
     * @param $modelId
     * @return array
     */
    public function getFieldsNameBySubTbCache($modelId)
    {
        $fields = Cache::get('fields_name_sub_'.$modelId);
        if (empty($fields)) {
            $fields = $this->dao->column(['model_id'=>$modelId, 'status'=>'normal','iscore'=>0],'field_name');
            Cache::tag(self::CacheTag)->set('fields_name_sub_'.$modelId, $fields, 700);
        }
        return $fields;
    }

    /**
     * 根据模型ID获取所有模型字段 缓存数据
     * @param $modelId
     * @return \app\common\model\BaseModel|array|mixed|\think\Model|null
     */
    public function getFieldsCache($modelId)
    {
        $fields = Cache::get('fields_'.$modelId);
        if (empty($fields)) {
            $fields = $this->dao->getOne(['model_id'=>$modelId, 'status'=>'normal']);
            Cache::tag(self::CacheTag)->set('fields_'.$modelId, $fields, 3600);
        }
        return $fields;
    }
}