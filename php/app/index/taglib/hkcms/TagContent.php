<?php
// +----------------------------------------------------------------------
// | HkCms 内容标签
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

namespace app\index\taglib\hkcms;

class TagContent extends Base
{
    public function initialize()
    {
        parent::initialize();
    }

    /**
     * 获取排序列表
     * @param array $tag
     * @param $page
     * @param $Cate
     * @return array
     */
    public function switchController($tag, &$page, $Cate)
    {
        // 都为空的情况下，若是栏目下那么获取当前栏目的列表
        if (empty($tag['catid']) && empty($tag['model']) && empty($tag['aid']) && empty($tag['aids'])) {
            if (empty($Cate["id"]) || empty($Cate["model_id"])){
                return [];
            }

            // 获取模型数据
            $modelInfo = \app\admin\model\cms\Model::where(["status"=>"normal", "id"=>$Cate["model_id"]])->find();
            if (empty($modelInfo)){
                return [];
            }
            $action = "\app\admin\model\cms\\".$modelInfo["controller"];
            $tag['catid'] = $Cate["id"];
            $tag['model'] = $Cate["model_id"];
            return (new $action)->tagContent($tag, $Cate, $modelInfo, $page);
        } else if (!empty($tag['model'])) {
            // 获取模型数据
            $modelInfo = \app\admin\model\cms\Model::where(["status"=>"normal", "id"=>$tag['model']])->find();
            if (empty($modelInfo)){
                return [];
            }
            $action = "\app\admin\model\cms\\".$modelInfo["controller"];
            return (new $action)->tagContent($tag, null, $modelInfo, $page);
        } else if (!empty($tag['catid'])) {
            $model_id = null;
            $cateInfo = null;

            if (is_numeric($tag['catid'])) {
                $cateInfo = (new \app\index\model\cms\Category)->getCateInfo($tag['catid']);
                if ($cateInfo) {
                    $tag['catid'] = $cateInfo['id'];
                    $model_id = $cateInfo['model_id'];
                }
            } else if(!empty($tag['catid'])) {
                $catidArr = explode(',', (string)$tag['catid']);
                $newidArr = [];
                foreach ($catidArr as $key=>$value) {
                    $temporary = (new \app\index\model\cms\Category)->getCateInfo($value);
                    if ($temporary) {
                        $model_id = $temporary['model_id'];
                        $cateInfo[] = $temporary;
                        $newidArr[] = $temporary['id'];
                    }
                }
                $tag['catid'] = implode(',',$newidArr);
            }
            if (!$model_id) {
                return [];
            }
            $modelInfo = \app\admin\model\cms\Model::where(["status"=>"normal", "id"=>$model_id])->find();
            if (empty($modelInfo)){
                return [];
            }
            $action = "\app\admin\model\cms\\".$modelInfo["controller"];
            return (new $action)->tagContent($tag,$cateInfo,$modelInfo,$page);
        } else {
            // 默认使用Archives模型
            return (new \app\admin\model\cms\Archives)->tagContent($tag,null,null, $page);
        }
    }
}