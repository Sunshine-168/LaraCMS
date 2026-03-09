<?php
// +----------------------------------------------------------------------
// | HkCms 栏目分类验证
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\validate\cms;

use app\admin\validate\BaseValidate;
use think\facade\Db;

class Category extends BaseValidate
{
    public function __construct(array $rules = [], $message = [], $field = [])
    {
        $this->field = [
            'type' => __('Column type'),
            'model_id' => __('Owning model'),
            'parent_id' => __('Superior column'),
            'name' => __('Column name'),
            'seo_title' => __('SEO title'),
            'seo_keywords' => __('SEO keywords'),
            'seo_desc' => __('SEO description'),
            'category_tpl' => __('Column home'),
            'list_tpl' => __('List template'),
            'show_tpl' => __('Content template'),
            'weigh' => __('Weigh'),
            'ismenu' => __('Navigation display'),
            'num' => __('Paging size'),
            'status' => __('Status')
        ];
        parent::__construct();
    }

    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'type' => 'require|in:category,list,link',
        'model_id' => 'requireCallback:requireModelId|number',
        'parent_id' => 'require|number|checkPid',
        'title' => 'require',
//        'image' => 'url',
//        'url' => 'url',
        'seo_title' => 'max:250',
        'seo_keywords' => 'max:250',
        'seo_desc' => 'max:250',
        'category_tpl' => 'requireIf:type,category',
        'list_tpl' => 'requireIf:type,list',
        'show_tpl' => 'requireIf:type,list',
        'weigh' => 'number',
        'ismenu' => 'require|in:1,0',
        'num' => 'number',
        'status' => 'require|in:normal,hidden'
    ];

    /**
     * 最大数量
     * @param $value
     * @param $data
     * @return bool
     */
    public function requireModelId($value, $data)
    {
        if (in_array($data['type'], ['category','list'])) {
            return true;
        }
        return false;
    }

    /**
     * 验证父级合法性
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     */
    public function checkPid($value, $rule, $data=[])
    {
        $id = $this->request->param('id');
        if ($id) {
            if ($id == $value) {
                return __("Can't choose oneself as parent");
            }

            $arr = Db::name('category')->where(['parent_id'=>$id])->column('id');
            if (in_array($value, $arr)) {
                return __("Can't choose one's own subordinate as the parent");
            }
        }
        return true;
    }
}