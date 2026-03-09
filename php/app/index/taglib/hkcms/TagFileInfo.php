<?php
// +----------------------------------------------------------------------
// | HkCms 文件信息获取
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\index\taglib\hkcms;

use app\admin\model\routine\Attachment;
use think\facade\Event;

class TagFileInfo extends Base
{
    public function initialize()
    {
        parent::initialize();
    }

    /**
     * 根据path路径获取附件详细信息
     * @param array $tag
     * @return array|mixed|object|\think\App
     */
    public function getAttachment(array $tag)
    {
        // 下载统计
        cache(md5(app('session')->getId()).$tag['field'].$tag['aid'].$tag['model'],$tag);

        $tag['cacheTime'] = !empty($tag['cache']) && is_numeric($tag['cache']) ? intval($tag['cache']) : 3600;
        $cacheID = to_guid_string($tag);
        if (!app()->isDebug() && $cacheData = cache($cacheID)) {
            return $cacheData;
        }

        $atta = Attachment::whereIn('path', $tag['name'])->select()->toArray();
        foreach ($atta as $key=>$value) {
            $atta[$key]['url'] = index_url('/index/download', ['id'=>$value['id'],'fd'=>$tag['field'],'aid'=>$tag['aid'],'m'=>$tag['model']]);
            $atta[$key]['fullurl'] = cdn_url($value['path'],true);
        }

        // 结果进行缓存
        if (!app()->isDebug()) {
            cache($cacheID, $atta, $tag['cacheTime'], 'common');
        }

        return $atta;
    }
}