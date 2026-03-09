<?php
// +----------------------------------------------------------------------
// | 站点模块服务
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\services\cms;

use app\common\dao\cms\RecommendDao;
use app\common\exception\ServiceException;
use app\common\services\BaseService;

/**
 * @mixin RecommendDao
 */
class RecommendService extends BaseService
{
    /**
     * 初始化
     * @param RecommendDao $dao
     */
    public function __construct(RecommendDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取站点模块内容数据
     * @param $where
     * @return array
     */
    public function contentList($where)
    {
        $info = $this->dao->searchWhere($where)->find();
        if (empty($info)) {
            throw new ServiceException("No results were found", 400);
        }

        if ($info['type']==4) { // 内容数据
            $jsonArray = json_decode($info['value_id'], true);
            try {
                $modelFieldService = app()->make(ModelFieldsService::class);
                $banner = controller($jsonArray['model'], function ($model) use ($jsonArray, $where, $modelFieldService) {
                    $model = $model->where(['status'=>'normal','lang'=>$where['lang']]);
                    if (!empty($jsonArray['column'])) {
                        $model = $model->whereIn('category_id', $jsonArray['column']);
                    }
                    if (!empty($jsonArray['order'])) {
                        $model = $model->order($jsonArray['order']);
                    }
                    $array = $model->limit((int)$jsonArray['limit'])->select()->toArray();
                    // 获取扩展字段
                    $fields = $modelFieldService->getFieldsNameCache($jsonArray['model']);
                    foreach ($array as &$value) {
                        // 格式化
                        foreach ($fields as $v) {
                            field_format($v, $value);
                        }
                    }
                    return $array;
                });
            } catch (\Exception $exception) {
                throw new ServiceException($exception->getMessage(), 400);
            }
        } else {
            /** @var BannerService $bannerService */
            $bannerService = app()->make(BannerService::class);
            if (!empty($where['itemid']) && is_numeric($where['itemid'])) {
                $banner = $bannerService->getOneByItemid($info->getAttr('id'), $where['itemid']);
            } else {
                $banner = $bannerService->search([])->where(['recommend_id'=>$info->getAttr('id')])->order('weigh','asc');
                $offset = 0;
                $length = null;
                if (!empty($tag['num']) && is_numeric($tag['num']) && $tag['num']>0) {
                    $offset = intval($tag['num']);
                } else if (!empty($tag['num']) && strpos($tag['num'], ',') !== false) {
                    $temp = explode(',', $tag['num']);
                    if (count($temp)==2 && is_numeric($temp[0]) && is_numeric($temp[1])) {
                        $offset = (int)$temp[0]-1;
                        $length = (int)$temp[1];
                    }
                }
                if ($offset) {
                    $banner->limit($offset, $length);
                }
                $banner = $banner->select()->toArray();
            }
        }
        return ['recommend'=>$info->toArray(),'banner'=>$banner];
    }
}