<?php
// +----------------------------------------------------------------------
// | 栏目管理
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\services\cms;

use app\common\dao\cms\CategoryDao;
use app\common\services\BaseService;
use app\common\services\common\TreeService;
use think\exception\ValidateException;

/**
 * @mixin CategoryDao
 */
class CategoryService extends BaseService
{
    /**
     * 字段
     * @var string[]
     */
    public $field = ['id', 'model_id', 'parent_id', 'type', 'app', 'name', 'title', 'url', 'image', 'seo_title', 'seo_keywords', 'seo_desc', 'ismenu', 'target', 'user_auth', 'lang', 'weigh', 'num', 'status',  'create_time', 'update_time'];

    /**
     * 初始化
     * @param CategoryDao $dao
     */
    public function __construct(CategoryDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取分页列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function listPage(array $where, int $page, int $limit): array
    {
        // 扩展字段处理
        $fieldsService = app()->make(FieldsService::class);
        $extendFields = $fieldsService->getFieldsNameCache();
        $query = $this->dao->listSearch($where);
        $count = $query->count();
        $lists = $query->page($page, $limit)
            ->order($this->dao->buildOrder($where['sort_by']??['weigh'=>'asc', 'id'=>'desc'], $where['sort_type']??''))
            ->field(array_merge($this->field, $extendFields))
            ->select();
        foreach ($lists as $key=>$value) {
            $lists[$key]['image'] = cdn_url($value['image'], true);
            // 扩展字段格式化
            $fieldsService->fieldFormat($value);
        }
        return compact('lists', 'count');
    }

    /**
     * 获取栏目详情
     * @param array $where
     * @return \app\common\model\BaseModel|array|mixed|\think\Model|null
     */
    public function detail(array $where)
    {
        $info = $this->dao->listSearch($where)->find();
        if (empty($info)) {
            throw new ValidateException(__("No results were found"));
        }
        return $info;
    }

    /**
     * 树形
     * @param array $where
     * @return array
     */
    public function tree(array $where)
    {
        $data = $this->dao->listSearch($where)->order('weigh asc,id desc')->field(['id', 'title', 'model_id', 'parent_id'])->select()->toArray();
        return TreeService::optionTree($data);
    }
}