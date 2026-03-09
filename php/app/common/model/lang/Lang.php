<?php
// +----------------------------------------------------------------------
// | 多语言管理模型
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\model\lang;

use app\admin\library\Html;
use app\common\model\BaseModel;
use app\common\services\lang\LangService;

class Lang extends BaseModel
{
    protected $autoWriteTimestamp = true;

    /**
     * @var string 表名
     */
    protected $name = 'lang';

    /**
     * @var string 主键
     */
    protected $pk = 'id';

    protected $append = [
        'url'
    ];

    /**
     * 状态
     * @param $query
     * @param $value
     * @param $data
     * @return void
     */
    public function searchStatusAttr($query, $value, $data)
    {
        if (!is_null($value)) {
            $query->where('status', $value);
        }
    }

    /**
     * 所属模块
     * @param $query
     * @param $value
     * @param $data
     * @return void
     */
    public function searchModuleAttr($query, $value, $data)
    {
        if (in_array($value, array_values(LangService::MARK_LIST))) {
            $query->where('module', $value);
        }
    }

    /**
     * 生成URL地址给前台使用
     * @param $value
     * @param $data
     * @return string
     */
    public function getUrlAttr($value, $data)
    {
        if (!isset($data['mark'])) {
            return "";
        }
        $url = '/?lang='.$data['mark'];
        $site = site();
        if ($site['url_mode'] == 1 && isset($site['url_rewrite']['/'])) {
            if (strstr($site['url_rewrite']['/'],':lang')) {
                $url = '/'.$data['mark'].'/';
            }
        } else if ($site['url_mode'] == 2) {
            $url = Html::getRootPath().$data['mark'].'/';
        }
        return $url;
    }
}