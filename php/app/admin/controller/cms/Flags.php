<?php
// +----------------------------------------------------------------------
// | HkCms 文档属性
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\controller\cms;

use app\admin\controller\BaseController;

class Flags extends BaseController
{
    /**
     * 文章模型
     * @var \app\admin\model\cms\Flags
     */
    protected $model;

    /**
     * 快速搜索字段
     * @var string
     */
    protected $searchField = 'title';

    /**
     * 开启验证
     * @var bool
     */
    protected $enableValidate = true;

    /**
     * 启用多语言绑定查询
     */
    protected $enableLang = true;

    /**
     * 初始化操作
     */
    public function initialize()
    {
        parent::initialize();

        $this->model = new \app\admin\model\cms\Flags;
    }
}