<?php
// +----------------------------------------------------------------------
// | HkCms sql查询标签
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\index\taglib\hkcms;

use think\facade\Db;

class TagQuery extends Base
{
    public function initialize()
    {
        parent::initialize();
    }

    /**
     * 数据库查询
     * @param $tag
     */
    public function query($tag)
    {
        $cacheID = to_guid_string($tag);
        if (!app()->isDebug() && $cacheData = cache($cacheID)) {
            return $cacheData;
        }

        $array = [];

        // 判断是否是直接sql查询
        if (!empty($tag['sql'])) {
            $__SQL__ = str_replace("__PREFIX__",Db::getConfig("connections.mysql.prefix"),$tag['sql']);
            $array = Db::query($__SQL__);
        } else {
            // 表名
            $db = Db::name($tag['table']);
            // 别名
            $alias = $tag['alias'] ?? '';
            if ($alias) {
                $db->alias($alias);
            }
            // 限制字段
            $field = $tag['field'] ?? '*';
            $db->field($field);
            // 连表，格式为：join="admin_log al,a.id=al.admin_id,left|xxx x,a.id=x.admin_id,left"
            if (!empty($tag['join'])) {
                $joinarr = explode('|', $tag['join']);
                foreach ($joinarr as $key=>$value) {
                    $tmp = explode(',', $value);
                    $tmp && $db->join(...$tmp);
                }
            }
            // 查询条件
            if (!empty($tag['tableid']) && is_numeric($tag['tableid'])) {
                $db->where($alias.'id','=', $tag['tableid']);
            }
            if (!empty($tag['where'])) {
                $db->where($tag['where']);
            }
            // 排序
            if (!empty($tag['order'])) {
                $db->order($tag['order']);
            }
            // 结果限制
            if (!empty($tag['num']) && is_numeric($tag['num']) && $tag['num']>0) { // 指定分页
                $db->limit(intval($tag['num']));
            } else if (!empty($tag['num']) && strpos($tag['num'], ',') !== false) {
                $temp = explode(',', $tag['num']);
                if (count($temp)==2 && is_numeric($temp[0]) && is_numeric($temp[1])) {
                    $offset = (int)$temp[0]-1;
                    $length = (int)$temp[1];
                    $db->limit($offset, $length);
                }
            }

            $array = $db->select();
        }

        // 结果进行缓存
        if (!app()->isDebug()) {
            cache($cacheID, $array, $tag['cache'], 'tagquery_tag');
        }
        return $array;
    }
}