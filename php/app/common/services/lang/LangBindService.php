<?php
// +----------------------------------------------------------------------
// | 内容语言关系绑定
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\services\lang;

use app\common\dao\lang\LangBindDao;
use app\common\services\BaseService;
use think\facade\Cache;
use think\facade\Db;

/**
 * @mixin LangBindDao
 */
class LangBindService extends BaseService
{
    /**
     * 初始化
     * @param LangBindDao $dao
     */
    public function __construct(LangBindDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取语言绑定的ID
     * @param $value
     * @param string $table
     * @param string $lang
     * @return mixed|Db
     */
    public function getBindValue($value, string $table, string $lang = '')
    {
        if (site('content_lang_on')!=1) {
            return $value;
        }

        $lang = empty($lang) ? app()->make(LangService::class)->getLang('content') : $lang;
        $l = Db::name($table)->where(['id'=>$value])->value('lang');
        if ($l==$lang) {
            return $value;
        }
        // 找出其他绑定的语言列表
        $data = $this->dao->searchLists(['table'=>$table,'source_id'=>$value])->select();
        foreach ($data as $v) {
            if ($lang==$v['lang'] && $v['value_id']==0) {
                return $v['main_id'];
            } else if ($lang==$v['lang'] && $v['value_id']!=0) {
                return $v['value_id'];
            } else if ($lang!=$v['lang'] && $v['value_id']==$value) {
                return $v['main_id'];
            }
        }
        return $value;
    }

    /**
     * 获取关联的ID数组
     * @param string $table
     * @param int $curId
     * @return array
     */
    public function contentGet(string $table, $curId)
    {
        $data = [];
        // 找出其他语言
        $info = $this->dao->searchLists(['table'=>$table,'main_id'=>$curId,'value_id'=>0])->find();
        if ($info) { // 主记录
            $data = $this->dao->searchLists(['table'=>$table,'main_id'=>$curId])->where('value_id','>',0)->column('value_id');
        } else {
            $curId = $this->dao->searchLists(['table'=>$table,'value_id'=>$curId])->value('main_id');
            if ($curId) {
                $data = $this->dao->searchLists(['table'=>$table,'main_id'=>$curId])->where('value_id','>',0)->column('value_id');
            }
        }
        if (!empty($curId)) {
            $data[] = $curId;
        }
        return $data;
    }

    /**
     * 多语言内容关联添加
     * @param string $table 表格
     * @param array $data 表数据数组
     * @param array $lanField 追加初次新增的语言标识字段
     * @param bool $isBind 是否往语言绑定表里添加数据
     * @return array 返回新增参数给定的table表的ID数组
     */
    public function contentAdd(string $table, array $data, array $lanField = [], bool $isBind = true): array
    {
        // 当前内容编辑模式
        $curLang = $data['lang'];
        // 当前内容主ID
        $mainId = $data['id'];
        // 添加到绑定关系
        if ($isBind) {
            $this->dao->create([
                'main_id'=>$mainId,
                'value_id'=>0,
                'table'=>$table,
                'lang'=>$curLang,
                'create_time'=>time(),
            ]);
        }

        $langs = Cache::remember('content_lang_list', function (){
            return app()->make(LangService::class)->getSearchList(['module'=>LangService::MARK_LIST['content'],'status'=>1]);
        }, 60);

        unset($data['id']);
        $idArr = [];
        $tempData = $data;
        foreach ($langs as $value) {
            if ($curLang!=$value['mark']) {
                $data['lang'] = $value['mark'];
                foreach ($lanField as $f) {
                    if (!empty($data[$f])) {
                        //$data[$f] = "[{$value['mark']}]".$tempData[$f];
                        $data[$f] = $tempData[$f];
                    }
                }
                if (isset($data['parent_id']) && $data['parent_id']>0) { // 有上下级的情况下
                    $tmpData = $this->contentGet($table, $data['parent_id']); // 获取父级不同语言的ID
                    $parentId = Db::name($table)->whereIn('id', $tmpData)->where(['lang'=>$value['mark']])->value('id');
                    $data['parent_id'] = $parentId ?:0;
                }
                $id = Db::name($table)->insertGetId($data);
                $idArr[] = $id;
                if ($isBind) {
                    $this->dao->create([
                        'main_id'=>$mainId,
                        'value_id'=>$id,
                        'table'=>$table,
                        'lang'=>$value['mark'],
                        'create_time'=>time(),
                    ]);
                }
            }
        }
        return $idArr;
    }

    /**
     * 内容多语言关联删除
     * @param string $table 表格
     * @param integer $curId 当前操作的ID
     * @param bool $bl true-直接删除，false-回收站
     * @return array 返回删除的表主键
     */
    public function contentDel(string $table, int $curId, bool $bl = true)
    {
        $data = [];
        // 找出其他语言
        $info = $this->dao->searchLists(['table'=>$table,'main_id'=>$curId,'value_id'=>0])->find();
        if ($info) { // 主记录
            $data = $this->dao->searchLists(['table'=>$table,'main_id'=>$curId])->where('value_id','>',0)->column('value_id');
            if ($bl) {
                // 删除主记录相关的其他语言包
                $this->dao->delete(['table'=>$table,'main_id'=>$curId]);
            }
        } else {
            $curId = $this->dao->searchLists(['table'=>$table,'value_id'=>$curId])->value('main_id');
            if ($curId) {
                $data = $this->dao->searchLists(['table'=>$table,'main_id'=>$curId])->where('value_id','>',0)->column('value_id');
                if ($bl) {
                    // 删除主记录相关的其他语言包
                    $this->dao->delete(['table'=>$table,'main_id'=>$curId]);
                }
            }
        }

        if (!empty($curId)) {
            $data[] = $curId;
        }
        if (!empty($data)) {
            if ($bl) {
                Db::name($table)->whereIn('id',$data)->delete();
            } else {
                Db::name($table)->whereIn('id',$data)->update(['delete_time'=>time()]);
            }
        }

        return $data;
    }
}