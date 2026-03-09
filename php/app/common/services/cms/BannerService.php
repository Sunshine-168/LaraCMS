<?php
// +----------------------------------------------------------------------
// | 站点模块资源服务
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\services\cms;

use app\common\dao\cms\BannerDao;
use app\common\services\BaseService;

/**
 * @mixin BannerDao
 */
class BannerService extends BaseService
{
    /**
     * 初始化
     * @param BannerDao $dao
     */
    public function __construct(BannerDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 根据序号获取状态正常的数据
     * @param $id
     * @param $itemid
     * @return array
     */
    public function getOneByItemid($id, $itemid)
    {
        $data = $this->dao->search([])->where(['recommend_id'=>$id,'status'=>'normal'])->order('weigh','asc')->select()->toArray();
        return isset($data[$itemid-1])?[$data[$itemid-1]]:[];
    }
}