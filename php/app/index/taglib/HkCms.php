<?php
// +----------------------------------------------------------------------
// | HkCms
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\index\taglib;

use think\template\TagLib;
use Exception;

class HkCms extends TagLib
{
    /**
     * 定义标签列表
     */
    protected $tags   =  [
        // 标签定义： attr 属性列表 close 是否闭合（1-闭合、0-开放） alias 标签别名 level 嵌套层次

        // 生成SEO标签
        'seo' => ['attr'=>'', 'close'=>0],
        // 获取栏目列表
        'channel' => ['attr'=>'name,type,num,model,where,order,id,empty,currentstyle,cache,istotal,insub,ismenu', 'close'=>1],
        // 获取单条文章
        'arcone' => ['attr'=>'id,aid,model,cache,empty,more', 'close'=>1],
        // 面包屑导航
        'breadcrumb'  => ['attr'=>'catid,symbol,class,currentstyle,isclick', 'close'=>0],
        // 获取广告管理(站点模块)
        'adv' => ['attr'=>'name,itemid,cache,id,empty,currentstyle,current,num,mod,key', 'close'=>1],
        // 日期格式化
        'date' => ['attr'=>'name,format,api,lt', 'close'=>0],
        // 字符截取
        'substr'=>['attr'=>'name,len,dot,lang','close'=>0],
        // 字符高亮
        'color'=>['attr'=>'name,keyword,style','close'=>0],
        // 同tp的volist一致
        'volist' => ['attr'=>'name,id,offset,length,mod,empty,key,currentstyle,current', 'close'=>1],
        // 调取语言包
        'language' => ['attr'=>'currentstyle,id', 'close'=>1],
        // 指定在什么语言下显示内容
        'lang' => ['attr'=>'value', 'close'=>1],
        // 获取数据库数据
        'query' => ['attr'=>'id,sql,cache,empty,mod,key,num,table,field,alias,where,order,tableid', 'close'=>1],
        // 生成位置地图
        'map' => ['attr'=>'htmlid,attr,title,address,scale,point', 'close'=>0],
        // 检测插件是否存在
        'addons' => ['attr'=>'id,name', 'close'=>1],
        // 生成缩略图
        'thumb' => ['attr'=>'name,width,height,type,auto,ishtml', 'close'=>0],
        // 内容列表标签
        'content' => ['attr'=>'catid,field,model,order,num,where,page,id,empty,mod,key,cache,tagid,flag,inlist,aid,aids,filter', 'close'=>1],
        // 生成列表分页页码标签
        'contentpage' => ['attr'=>'item,size,home,pre,next,last,mobile_item,name,info,emptxt,hasemp', 'close'=>0], // 设置分页格式
        // 瀑布流分页
        'wfpage' => ['attr'=>'name,auto,num,icon,empty', 'close'=>1],
        // 输出筛选字段
        'filter' => ['attr'=>'id,currentstyle,multiple,field,alltxt', 'close'=>1],
        // 输出排序字段
        'order' => ['attr'=>'id,currentstyle', 'close'=>1],
        // 留言表单，即将作废
        'guestbook' => ['attr'=>'id,catid,captcha,cache', 'close'=>1],
        // 表单标签
        'form' => ['attr'=>'id,catid,cache,attr', 'close'=>1],
        // 上一篇
        'pre' => ['attr'=>'catid,id,target,msg,field,len,dot', 'close'=>0],
        // 下一篇
        'next' => ['attr'=>'catid,id,target,msg,field,len,dot', 'close'=>0],
        // 上一篇下一篇数组形式
        'prenext' => ['attr'=>'type,id,len,dot', 'close'=>1],
        // 获取附件信息
        'fileinfo'=>['attr'=>'id,name,is_url,field,empty,cache','close'=>1],
        // 原样输出字段的值，如果字段有html则原样输出，不会转义
        'raw' => ['attr'=>'name', 'close'=>0],
        // 获取所有标签列表
        'taglist' => ['attr'=>'tid,arcid,model,order,num,where,id,empty,page,cache,currentstyle', 'close'=>1],
        // 获取标签文档列表
        'tagarclist' => ['attr'=>'tid,order,num,where,page,id,empty,cache', 'close'=>1],
    ];

    /**
     * seo，生成SEO三大HTML标签
     * @param $tag
     * @return string
     */
    public function tagSeo($tag)
    {
        $parseStr = '<?php'."\r\n";
        $parseStr .= '$__SEO__ = "<title>".(empty($seo_title)?$site["home_title"]:$seo_title)."</title>\r\n";';
        $parseStr .= '$__SEO__ .= "<meta name=\"keywords\" content=\"".(empty($seo_keywords)?$site["keyword"]:$seo_keywords)."\">\r\n";';
        $parseStr .= '$__SEO__ .= "<meta name=\"description\" content=\"".(empty($seo_desc)?$site["description"]:$seo_desc)."\">";';
        $parseStr .= 'echo $__SEO__;'."\r\n";
        $parseStr .= ' ?>';
        return $parseStr;
    }

    /**
     * 获取栏目列表
     * type='son' 获取下级栏目
     * type='peer' 表示同级栏目
     * type='top' 表示顶级栏目
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagChannel($tag, $content)
    {
        $tag['id'] = !empty($tag['id']) ? $tag['id'] : 'item';
        $tag['currentstyle'] = !empty($tag['currentstyle']) ? $tag['currentstyle'] : 'active';
        $tag['empty'] = !empty($tag['empty']) ? $tag['empty'] : '';
        //$tag['name'] = !empty($tag['name']) ? $tag['name'] : '';
        $tag['where'] = !empty($tag['where']) ? $this->parseCondition($tag['where']) : null;
        $tag['istotal'] = $tag['istotal'] ?? '';
        $tag['ismenu'] = $tag['ismenu'] ?? '';
        $tag['key'] = empty($tag['key']) ? '' : 'key="'.$tag['key'].'"';
        $tag['mod'] = isset($tag['mod']) && is_numeric($tag['mod']) ? 'mod="'.$tag['mod'].'"' : '';

        if (!empty($tag['name'])) {
            $this->autoBuildVar($tag['name']);
        } else {
            $tag['name'] = '';
        }
        if (!empty($tag['model'])) {
            $this->autoBuildVar($tag['model']);
        }


        $parseStr = '<?php'."\r\n";
        $parseStr .= '$__LISTS__ = (new \app\index\model\cms\Category)->getList('.self::arrToHtml($tag).');'."\r\n";
        $parseStr .= '$__CateArr__ = !empty($Cate) ? get_category_parent($Cate["id"], true) : [];'."\r\n";
        $parseStr .= '?>';
        $parseStr .= '{volist name="__LISTS__" id="'.$tag['id'].'" '.$tag['key'].' '.$tag['mod'].' empty="'.$tag['empty'].'"}';
        $parseStr .= '{php}$'.$tag['id'].'["currentstyle"]=get_current($'.$tag['id'].', $__CateArr__, "'.$tag['currentstyle'].'");{/php}';
        if ($tag['istotal']) {
            $parseStr .= '{php}$'.$tag['id'].'["total"]=get_doc_total($'.$tag['id'].');{/php}';
        }
        if ($tag['mod']) {
            $parseStr .= '{php}$mod=$mod+1;{/php}';
        }
        $parseStr .= $content;
        $parseStr .= '{/volist}';
        return $parseStr;
    }

    /**
     * 获取单条文档 id,aid,model,cache,empty
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagArcone($tag, $content)
    {
        $tag['id'] = !empty($tag['id']) ? $tag['id'] : 'item';
        $tag['model'] = !empty($tag['model']) ? $tag['model'] : '';
        $tag['aid'] = !empty($tag['aid']) ? $tag['aid'] : '';
        $tag['empty'] = !empty($tag['empty']) ? $tag['empty'] : '';
        $tag['more'] = !empty($tag['more']) && $tag['more']==1 ? 1 : 0;

        if (empty($tag['model']) || empty($tag['aid'])) {
            return '';
        }

        $this->autoBuildVar($tag['model']);
        $this->autoBuildVar($tag['aid']);

        $parseStr = '<?php'."\r\n";
        $parseStr .= '$__modelInfo__ = \app\admin\model\cms\Model::where(["status"=>"normal", "id"=>'.$tag['model'].'])->find();'."\r\n";
        $parseStr .= 'if (!empty($__modelInfo__) && $__modelInfo__["is_search"]!=-1) {'."\r\n";
        $parseStr .=    '$__action__ = "\app\admin\model\cms\Archives";'."\r\n";
        $parseStr .=    '$__CONTENTTAG__ = '.self::arrToHtml($tag).';'."\r\n";
        $parseStr .=    '$__LISTS__ = (new $__action__)->tagArcone($__CONTENTTAG__, $__modelInfo__);'."\r\n";
        $parseStr .= '}';
        $parseStr .= '?>';

        $parseStr .= '{volist name="__LISTS__" id="'.$tag['id'].'" empty="'.$tag['empty'].'"}';
        $parseStr .= $content;
        $parseStr .= '{/volist}';
        return $parseStr;
    }

    /**
     * 面包屑导航，catid,symbol-间隔符号,style-a标签的class
     * @param $tag
     * @return string
     */
    public function tagBreadcrumb($tag)
    {
        $tag['symbol'] = $tag['symbol'] ?? ' > ';
        $tag['class']   = $tag['class'] ?? '';
        $tag['currentstyle']   = $tag['currentstyle'] ?? 'active';
        $tag['isclick']   = $tag['isclick'] ?? '0';

        if (!empty($tag['catid'])) {
            $this->autoBuildVar($tag['catid']);
        }

        $parseStr = '<?php'."\r\n";
        $parseStr .= '  $__BreadcrumbPARAM__ = [];'."\r\n";

        if (empty($tag['catid'])) {
            $parseStr .= '$__BreadcrumbPARAM__ = '.self::arrToHtml($tag).';'."\r\n";
            $parseStr .= 'if (empty($__BreadcrumbPARAM__["catid"])): '."\r\n";
            $parseStr .= '   $__BreadcrumbPARAM__["catid"]=$Cate["id"]??"";'."\r\n";
            $parseStr .= 'endif;'."\r\n";
        } else {
            $parseStr .= 'if (isset('.$tag['catid'].')): '."\r\n";
            $parseStr .= '$__BreadcrumbPARAM__ = '.self::arrToHtml($tag).';'."\r\n";
            $parseStr .= 'endif;'."\r\n";
        }
        $parseStr .= '  echo (new \app\index\model\cms\Category)->getBreadcrumb($__BreadcrumbPARAM__);'."\r\n";
        $parseStr .= '?>';
        return $parseStr;
    }

    /**
     * 获取站点模块
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagAdv($tag, $content)
    {
        $tag['id'] = !empty($tag['id']) ? $tag['id'] : 'item';
        $tag['empty'] = !empty($tag['empty']) ? $tag['empty'] : '';
        $tag['name'] = !empty($tag['name']) ? $tag['name'] : '';
        $tag['num'] = !empty($tag['num']) ? $tag['num'] : '';
        $tag['currentstyle'] = !empty($tag['currentstyle']) ? $tag['currentstyle'] : 'active';  // 选中的class
        $tag['current'] = !empty($tag['current']) ? $tag['current'] : 1;  // 默认第一张
        $tag['key'] = empty($tag['key']) ? '' : 'key="'.$tag['key'].'"'; // 循环变量$i
        $tag['mod'] = isset($tag['mod']) && is_numeric($tag['mod']) ? 'mod="'.$tag['mod'].'"' : '';
        $adv = '$adv'.get_random_str();

        $parseStr = '<?php'."\r\n";
        $parseStr .= $adv.' = (new \app\index\model\cms\Recommend)->getList('.self::arrToHtml($tag).');'."\r\n";
        $parseStr .= '?>';
        $parseStr .= '{if !empty('.$adv.'["banner"])}';
        $parseStr .= '{volist name="'.$adv.'[\'banner\']" '.$tag['key'].' '.$tag['mod'].' id="'.$tag['id'].'"}';
        $parseStr .= '{php}$'.$tag['id'].'["currentstyle"] = $i=="'.$tag['current'].'"?"'.$tag['currentstyle'].'":"";{/php}';
        $parseStr .= '{if '.$adv.'["recommend"]["type"]!=4}';
        $parseStr .= '{php}$'.$tag['id'].'["target"] = $'.$tag['id'].'["new_window"]==1?"target=_blank":"";{/php}';
        $parseStr .= '{/if}';
        $parseStr .= '{php}$'.$tag['id'].'["recommend"] = '.$adv.'["recommend"];{/php}';
        if ($tag['mod']) {
            $parseStr .= '{php}$mod=$mod+1;{/php}';
        }
        $parseStr .= $content;
        $parseStr .= '{/volist}';
        $parseStr .= '{else /}{php}echo "'.$tag['empty'].'";{/php}';
        $parseStr .= '{/if}';
        return $parseStr;
    }

    /**
     * 日期格式化
     * @param $tag
     * @return string
     */
    public function tagDate($tag)
    {
        if (empty($tag['name'])) {
            return '';
        }

        $this->autoBuildVar($tag['name']);

        $tag['format'] = empty($tag['format'])?'Y-m-d':$tag['format'];
        $parseStr = '<?php'."\r\n";
        $parseStr .= 'echo !isset('.$tag['name'].') ? "":get_date_format('.self::arrToHtml($tag).');'."\r\n";
        $parseStr .= '?>';
        return $parseStr;
    }

    /**
     * 字符截取
     * @param $tag
     * @return string
     */
    public function tagSubstr($tag)
    {
        if (empty($tag['name']) || empty($tag['len'])) {
            return '';
        }

        $str = empty($tag['dot'])?'...':$tag['dot'];
        $lang = empty($tag['lang'])?'':$tag['lang'];

        $this->autoBuildVar($tag['name']);
        $this->autoBuildVar($tag['len']);

        $parseStr = '<?php'."\r\n";
        $parseStr .= '$__SUBSTR__ = '.$tag['name'].";\r\n";;
        $parseStr .= 'if(isset($__SUBSTR__)):'."\r\n";;
        $parseStr .= '  if(mb_strlen($__SUBSTR__)>'.$tag['len'].'):'."\r\n";

        if ($lang && site("content_lang_on")==1 && app()->lang->getLangSet() != $lang) {
            $parseStr .= '      echo "";'."\r\n";
        } else {
            $parseStr .= '      echo mb_substr($__SUBSTR__,0,'.$tag['len'].')."'.$str.'";'."\r\n";
        }

        $parseStr .= '  else:';

        if ($lang && site("content_lang_on")==1 && app()->lang->getLangSet() != $lang) {
            $parseStr .= '      echo "";'."\r\n";
        } else {
            $parseStr .= '      echo mb_substr($__SUBSTR__,0,'.$tag['len'].');'."\r\n";
        }

        $parseStr .= '  endif;';
        $parseStr .= 'endif;';
        $parseStr .= '?>';
        return $parseStr;
    }

    /**
     * 字符高亮(作废)
     * @param $tag
     * @return string
     */
    public function tagColor($tag)
    {
        if (empty($tag['name'])) {
            return '';
        }

        $tag['style'] = isset($tag['style']) ? $tag['style']: 'color:#dc3545;';
        $this->autoBuildVar($tag['name']);

        $parseStr = '<?php'."\r\n";
        if (!empty($tag['keyword'])) {
            $this->autoBuildVar($tag['keyword']);
            $parseStr .= '$SEARCH_STR='.$tag['keyword'].";\r\n";
        } else {
            $parseStr .= '$SEARCH_STR=isset($__param__["keyword"])?$__param__["keyword"]:""'.";\r\n";
        }
        $parseStr .= 'echo color('.$tag['name'].', $SEARCH_STR, "'.$tag['style'].'");'."\r\n";
        $parseStr .= '?>';
        return $parseStr;
    }

    /**
     * 循环，主要用于内容页，支持多文件，多图片等数据的循环。
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagVolist($tag, $content)
    {
        if (empty($tag['name'])) {
            return '';
        }

        $tag['id'] = $tag['id'] ?? 'item';
        $tag['offset'] = !empty($tag['offset']) && is_numeric($tag['offset']) ? intval($tag['offset']) : 0;
        $tag['length'] = !empty($tag['length']) && is_numeric($tag['length']) ? intval($tag['length']) : 'null';
        $tag['mod'] = $tag['mod'] ?? '2';
        $tag['empty'] = $tag['empty'] ?? '';
        $tag['key'] = $tag['key'] ?? 'i';

        $tag['currentstyle'] = !empty($tag['currentstyle']) ? $tag['currentstyle'] : 'active';  // 选中的class
        $tag['current'] = !empty($tag['current']) ? $tag['current'] : 1;  // 默认第一张

        $parseStr = '<?php'."\r\n";
        $parseStr .= '$__NAME__ = '.$this->autoBuildVar($tag['name'])."??[]; \r\n";
        $parseStr .= 'if (!empty($__NAME__) && !is_array($__NAME__) && ":"!=substr($__NAME__, 0, 1)) {'."\r\n";
        $parseStr .= '  $__NAME__ = explode(\',\', $__NAME__);'."\r\n";
        $parseStr .= '}'."\r\n";
        $parseStr .= '?>';
        $parseStr .= '{volist name="__NAME__" id="'.$tag['id'].'" offset="'.$tag['offset'].'" length="'.$tag['length'].'" mod="'.$tag['mod'].'" empty="'.$tag['empty'].'" key="'.$tag['key'].'" }';
        $parseStr .= '{php}$currentstyle = $i=="'.$tag['current'].'"?"'.$tag['currentstyle'].'":"";{/php}';
        $parseStr .= '{php}$mod=$mod+1;{/php}';
        $parseStr .= $content;
        $parseStr .= '{/volist}';
        return $parseStr;
    }

    /**
     * 获取语言包
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagLanguage($tag, $content)
    {
        $tag['currentstyle'] = !empty($tag['currentstyle']) ? $tag['currentstyle'] : 'active';  // 选中的class
        $tag['id'] = $tag['id'] ?? 'item';

        $lang = app('lang')->getLangset();

        $parseStr = '<?php'."\r\n";
        $parseStr .= '$__language__ = site("index_lang_on")==1?app()->make(\app\common\services\lang\LangService::class)->getSearchList(["status"=>1,"module"=>1]):[];'."\r\n";
        $parseStr .= ''."\r\n";
        $parseStr .= '?>';
        $parseStr .= '{if (!empty($__language__))}';
        $parseStr .= '{volist name="$__language__" id="'.$tag['id'].'"}';
        $parseStr .= '{php}$'.$tag['id'].'["key"] = $'.$tag['id'].'["mark"];{/php}';
        $parseStr .= '{php}$'.$tag['id'].'["value"] = $'.$tag['id'].'["title"];{/php}';
        $parseStr .= '{php}$'.$tag['id'].'["target_html"] = $'.$tag['id'].'["target"]==1?"target=_blank":"";{/php}';
        $parseStr .= '{php}$currentstyle = $'.$tag['id'].'["key"]=="'.$lang.'"?"'.$tag['currentstyle'].'":"";{/php}';
        $parseStr .= $content;
        $parseStr .= '{/volist}';
        $parseStr .= '{/if}';

        return $parseStr;
    }

    /**
     * 原生SQL查询
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagQuery($tag, $content)
    {
        $tag['id'] = $tag['id'] ?? 'item';
        $tag['empty'] = !empty($tag['empty']) ? $tag['empty'] : '';
        $tag['cache'] = !empty($tag['cache']) && is_numeric($tag['cache']) ? intval($tag['cache']) : 3600;
        $tag['key'] = empty($tag['key']) ? '' : 'key="'.$tag['key'].'"';
        $tag['mod'] = isset($tag['mod']) && is_numeric($tag['mod']) ? 'mod="'.$tag['mod'].'"' : '';

        if (empty($tag['sql']) && empty($tag['table'])) {
            return '';
        }

        $parseStr = '<?php'."\r\n";
        $parseStr .= '$__QUERY__ = (new \app\index\taglib\hkcms\TagQuery())->query('.self::arrToHtml($tag).');'."\r\n";
        $parseStr .= '?>';

        $parseStr .= '{volist name="__QUERY__" id="'.$tag['id'].'" '.$tag['key'].' '.$tag['mod'].' empty="'.$tag['empty'].'"}';
        if ($tag['mod']) {
            $parseStr .= '{php}$mod=$mod+1;{/php}';
        }
        $parseStr .= $content;
        $parseStr .= '{/volist}';

        return $parseStr;
    }

    /**
     * 生成地图，需要address位置生成插件
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagMap($tag, $content)
    {
        $tag['attr'] = $tag['attr'] ?? '';
        $tag['title'] = $tag['title'] ?? '';
        $tag['address'] = $tag['address'] ?? '';
        $tag['scale'] = $tag['scale'] ?? 19;
        $tag['point'] = $tag['point'] ?? '';
        $tag['id'] = $tag['htmlid'] ?? 'dituContent';

        $tag['title'] = $this->autoBuildVar($tag['title']).'??""';
        $tag['address'] = $this->autoBuildVar($tag['address']).'??""';
        $tag['point'] = $this->autoBuildVar($tag['point']).'??""';

        $parseStr = '<?php'."\r\n";
        $parseStr .= '$__parammap__ = '.self::arrToHtml($tag).';'."\r\n";
        $parseStr .= 'echo hook("showMap", $__parammap__);'."\r\n";
        $parseStr .= '?>';
        return $parseStr;
    }

    /**
     * 插件安装检测
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagAddons($tag, $content)
    {
        if (empty($tag['name'])) {
            return '';
        }

        $tag['id'] = $tag['id'] ?? 'item';

        // 兼容旧版，对user、tags插件默认为已安装
        if ($tag['name']=='user' || $tag['name']=='tags') {
            return $content;
        }

        $parseStr = '<?php'."\r\n";
        $parseStr .= '$__paraaddons__ = '.self::arrToHtml($tag).';'."\r\n";
        $parseStr .= '$cacheID = to_guid_string($__paraaddons__);'."\r\n";
        $parseStr .= 'if (!app()->isDebug() && $cacheData = cache($cacheID)) {'."\r\n";
        $parseStr .= '  $__addons_exist__ = $cacheData;'."\r\n";
        $parseStr .= '} else {'."\r\n";
        $parseStr .= '  $__addons_exist__ = addons_exist($__paraaddons__["name"]);'."\r\n";
        $parseStr .= '  if (!app()->isDebug()) {'."\r\n";
        $parseStr .= '      cache($cacheID, $__addons_exist__);'."\r\n";
        $parseStr .= '  }'."\r\n";
        $parseStr .= '}'."\r\n";
        $parseStr .= '?>';
        $parseStr .= '{if $__addons_exist__}';
        $parseStr .= '{php}$'.$tag['id'].' = $__addons_exist__;{/php}';
        $parseStr .= $content;
        $parseStr .= '{/if}';

        return $parseStr;
    }

    /**
     * 生成缩略图
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagThumb($tag, $content)
    {
        $tag['name'] = $this->autoBuildVar($tag['name']).'??""';
        // 缩略图宽度
        $tag['width'] = !empty($tag['width']) ? intval($tag['width']) : null;
        // 缩略图高度
        $tag['height'] = !empty($tag['height']) ? intval($tag['height']) : null;
        // 生成方式
        $tag['type'] = $tag['type'] ?? null;
        // 自动生成
        $tag['auto'] = $tag['auto'] ?? '1';
        // 是否生成html标签
        $tag['ishtml'] = $tag['ishtml'] ?? '';

        $parseStr = '<?php'."\r\n";
        $parseStr .= '$__parammap__ = '.self::arrToHtml($tag).';'."\r\n";
        $parseStr .= '$__thumb__ = thumb($__parammap__["name"],$__parammap__["width"],$__parammap__["height"],$__parammap__["type"],$__parammap__["auto"]);'."\r\n";
        if ($tag['ishtml']==1) {
            $parseStr .= 'echo "<img src=".$__thumb__." >";'."\r\n";
        } else {
            $parseStr .= 'echo $__thumb__;'."\r\n";
        }
        $parseStr .= '?>';
        return $parseStr;
    }

    /**
     * 内容模型标签
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagContent($tag, $content)
    {
        // 栏目id
        $tag['catid'] = isset($tag['catid']) ? $tag['catid'] : '';
        $tag['model'] = isset($tag['model']) ? $tag['model'] : '';
        $tag['aid'] = isset($tag['aid']) ? $tag['aid'] : '';
        $tag['aids'] = isset($tag['aids']) ? $tag['aids'] : '';
        $tag['id'] = !empty($tag['id']) ? $tag['id'] : 'item';
        $tag['empty'] = !empty($tag['empty']) ? $tag['empty'] : '';
        $tag['where'] = !empty($tag['where']) ? $this->parseCondition($tag['where']) : null;
        $tag['page'] = $tag['page'] ?? 0;
        $tag['num'] = isset($tag['num']) ? $tag['num'] : '10';
        $tag['field'] = $tag['field'] ?? '*';
        $this->autoBuildVar($tag['num']);
        $tag['key'] = empty($tag['key']) ? '' : 'key="'.$tag['key'].'"';
        $tag['mod'] = isset($tag['mod']) && is_numeric($tag['mod']) ? 'mod="'.$tag['mod'].'"' : '';
        // 是否包含下级
        $tag['insub'] = !isset($tag['insub']) || ($tag['insub']!=1 && $tag['insub']!=0)?1:$tag['insub'];
        // 是否开启筛选
        $tag['filter'] = isset($tag['filter']) && $tag['filter'] != 1 ? 0 : 1;
        // ajax分页
        $tag['tagid'] = empty($tag['tagid']) ? '' : $tag['tagid'];
        // 分页变量
        $grs = 'page_'.get_random_str(8);

        $this->autoBuildVar($tag['catid']);
        $this->autoBuildVar($tag['model']);
        $this->autoBuildVar($tag['aid']);
        $this->autoBuildVar($tag['aids']);

        $parseStr = '<?php'."\r\n";
        $parseStr .= '$__LISTS__=[];'."\r\n";
        $parseStr .= '$'.$grs.'=null;'."\r\n";
        $parseStr .=  '$__CONTENTTAG__ = '.self::arrToHtml($tag).';'."\r\n";
        $parseStr .= '$__LISTS__ = (new \app\index\taglib\hkcms\TagContent())->switchController($__CONTENTTAG__, $'.$grs.',$Cate??"");'."\r\n";
        if ($tag['page']) {
            $parseStr .=  '$__page__ = $'.$grs.';'."\r\n";
        }
        $parseStr .= '?>';

        $parseStr .= '{volist name="__LISTS__" id="'.$tag['id'].'" '.$tag['key'].' '.$tag['mod'].' empty="'.$tag['empty'].'"}';
        if ($tag['mod']) {
            $parseStr .= '{php}$mod=$mod+1;{/php}';
        }
        $parseStr .= $content;
        $parseStr .= '{/volist}';
        return $parseStr;
    }

    /**
     * 设置分页格式
     * home-首页,pre-上一页,pageno-页码,next-下一页,last-尾页,info-数量信息,jump-跳转页码
     * size 属性指定多少个页码则显示省略号。至少6个。
     * @param $tag
     * @return string
     */
    public function tagContentpage($tag)
    {
        $tag['item'] = empty($tag['item']) ? 'pre,pageno,next' : $tag['item'];
        $tag['name'] = empty($tag['name']) ? '$__page__' : $this->autoBuildVar($tag['name']);
        //$tag['dots'] = isset($tag['dots']) && $tag['dots']==1 ? 0 : 1; // 是否显示 1-隐藏，0-显示
        if (request()->isMobile() && !empty($tag['mobile_item'])) {
            $tag['item'] = $tag['mobile_item'];
        }

        $parseStr = '<?php'."\r\n";
        $parseStr .= 'if (!empty('.$tag['name'].')) : '."\r\n";
        $parseStr .= '  $params = [];if(isset($Cate)) : $params = site("url_mode")==1?$Cate:["catname"=>$Cate["name"]];endif;'."\r\n";
        $parseStr .= '  if(isset($Info) && site("url_mode")==1 && isset($Info["id"])) : $params["aid"] = $Info["id"];endif;'."\r\n";
        $parseStr .= '  if(isset($Info) && site("url_mode")==2 && isset($Info["id"])) : $params["id"] = $Info["id"];endif;'."\r\n";
        $parseStr .= '  if (!\app\common\library\Bootstrap::getDiyUrl()) {'."\r\n";
        $parseStr .= '  \app\common\library\Bootstrap::diyUrlResolver(function ($page, $options) use($params) {'."\r\n";
        $parseStr .= '      $params = array_merge($params, $options["query"])'.";\r\n";
        $parseStr .= '      $params["page"] = $page;'."\r\n";
        $parseStr .= '      $GLOBALS["JUMP_query"] = $params;'."\r\n";
        $parseStr .= '      $ruleParam = $options["rule"]??[];'."\r\n";
        $parseStr .= '      return index_url($options["path"],$params,true,false,"",$ruleParam,$options["query"]);'."\r\n";
        $parseStr .= '  });'."\r\n";
        $parseStr .= '  }'."\r\n";
        $parseStr .= 'echo is_object('.$tag['name'].')?'.$tag['name'].'->render('.self::arrToHtml($tag).'):"";'."\r\n";
        $parseStr .= 'endif; ?>';

        return $parseStr;
    }

    /**
     * 瀑布流分页
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagWfpage($tag, $content)
    {
        $tag['name'] = empty($tag['name']) ? '$__page__' : $this->autoBuildVar($tag['name']);
        $tag['icon'] = empty($tag['icon']) ? lang("Loading").'...' : $this->autoBuildVar($tag['icon']);
        $tag['empty'] = empty($tag['empty']) ? lang('No more data') : $this->autoBuildVar($tag['empty']);
        $tag['num'] = empty($tag['num']) ? '' : $this->autoBuildVar($tag['num']);
        $tag['auto'] = empty($tag['auto']) ? 0 : $tag['auto'];

        $parseStr = '{if !empty('.$tag['name'].'["tagid"])}';
        $parseStr .= '{php}$item = '.$tag['name'].';{/php}';
        $parseStr .= '{php}$requestURl = "?".http_build_query(array_merge($item["map_param"],["page"=>"_PAGE_","key"=>$item["key"],"num"=>$item["num"]]));{/php}';
        if ($tag['num']) {
            $parseStr .= '{php}$item["num"] = "'.$tag['num'].'";{/php}';
        }
        $parseStr .= '{php}$item["click"] = '.($tag['auto']==1?'"id=".$item["tagid"].\'_id\'. ':"").'" data-page=".$item["page"]." data-tagid=".$item["tagid"]." data-empty=\''.$tag['empty'].'\' data-icon=\''.$tag['icon'].'\' onclick=get_page_list(this,\'".$item["key"]."\');";{/php}';
        $parseStr .= $content;
        $parseStr .= '<script type="text/javascript">
                        if (typeof(get_page_list) == "undefined") {
                            function get_page_list(obj,key) {
                                if (obj.className.match(new RegExp("(\\\s|^)active(\\\s|$)"))!=null) {
                                    return false;
                                }
                                var ajax = new XMLHttpRequest();
                                var page = obj.attributes["data-page"].value;
                                var tagid = obj.attributes["data-tagid"].value;
                                var icon = obj.attributes["data-icon"].value;
                                var empty = obj.attributes["data-empty"].value;
                                var defaulttxt = obj.innerHTML;
                                obj.innerHTML = icon;
                                obj.className += " active"
                                page = parseInt(page)+1;
                                var request = "{$requestURl|raw}".replace("_PAGE_",page);
                                ajax.open("GET","{:url(\'/ajax/wfpagelist\')}"+request)
                                ajax.setRequestHeader("X-Requested-With","XMLHttpRequest");
                                ajax.send();
                                ajax.onreadystatechange = function () {  // 绑定响应状态事件
                                    if (ajax.readyState == 4 && ajax.status == 200) {
                                        var arr = JSON.parse(ajax.response);
                                        if (arr.code==200) {
                                            obj.innerHTML = defaulttxt;
                                            obj.className = obj.className.replace(new RegExp("(\\\s|^)active(\\\s|$)"), " ");
                                            var main = document.getElementById(tagid).innerHTML;
                                            document.getElementById(tagid).innerHTML = main + arr.data.html;
                                            obj.attributes["data-page"].value = page;
                                            if (!arr.data.html || page==arr.data.last_page) {
                                                obj.innerHTML = empty;
                                                obj.onclick = null;
                                            }
                                        } else {
                                            alert(arr.msg);
                                            obj.innerHTML = defaulttxt;
                                            obj.className = obj.className.replace(new RegExp("(\\\s|^)active(\\\s|$)"), " ");
                                        }
                                    }
                                }
                                return false;
                            }
                            var obj = document.getElementById("{$item["tagid"]}_id");
                            window.onscroll = function(e){
                                if (obj && !obj.className.match(new RegExp("(\\\s|^)active(\\\s|$)"))) {
                                    if (document.documentElement.scrollTop-obj.offsetHeight>obj.offsetTop-document.documentElement.clientHeight) {
                                        var e = document.createEvent("MouseEvents");
                                        e.initEvent("click", true, true);
                                        obj.dispatchEvent(e);
                                    }
                                }
                            }
                        }
                      </script>';
        $parseStr .= '{/if}';
        return $parseStr;
    }

    /**
     * 筛选标签
     * currentstyle-选择的class,multiple-允许多选,field-指定字段，多个使用英文逗号分隔,alltxt-指定第一个选项的文字，默认“全部”，若指定为off，则不显示。
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagFilter($tag, $content)
    {
        $tag['id'] = !empty($tag['id']) ? $tag['id'] : 'item';
        $tag['currentstyle'] = !empty($tag['currentstyle']) ? $tag['currentstyle'] : 'active';  // 选中的class
        $tag['multiple'] = isset($tag['multiple']) && $tag['multiple']==1 ? 1 : '0';  // 是否多选
        $tag['field'] = !empty($tag['field']) ? $tag['field'] : '';
        $tag['alltxt'] = !empty($tag['alltxt']) ? $tag['alltxt'] : lang('All');

        $parseStr = '<?php'."\r\n";
        $parseStr .= '$__FILTERLIST__ = [];'."\r\n";
        $parseStr .= 'if (isset($Cate)):'."\r\n";
        $parseStr .= '$__FILTERLIST__ = (new \app\index\taglib\hkcms\TagFilter())->lists($Cate["model_id"], '.self::arrToHtml($tag).');'."\r\n";
        $parseStr .= 'endif;'."\r\n";
        $parseStr .= '?>';
        $parseStr .= '{volist name="__FILTERLIST__" id="'.$tag['id'].'" }';
        $parseStr .= $content;
        $parseStr .= '{/volist}';
        return $parseStr;
    }

    /**
     * 排序标签
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagOrder($tag, $content)
    {
        $tag['id'] = !empty($tag['id']) ? $tag['id'] : 'item';
        $tag['currentstyle'] = !empty($tag['currentstyle']) ? $tag['currentstyle'] : 'active';  // 选中的class

        $parseStr = '<?php'."\r\n";
        $parseStr .= '$__ORDERLIST__ = [];'."\r\n";
        $parseStr .= 'if (isset($Cate)):'."\r\n";
        $parseStr .= '$__ORDERLIST__ = (new \app\index\taglib\hkcms\TagOrder())->lists($Cate["model_id"], '.self::arrToHtml($tag).');'."\r\n";
        $parseStr .= 'endif;'."\r\n";
        $parseStr .= '?>';
        $parseStr .= '{volist name="__ORDERLIST__" id="'.$tag['id'].'" }';
        $parseStr .= $content;
        $parseStr .= '{/volist}';
        return $parseStr;
    }

    /**
     * 留言板
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagGuestbook($tag, $content)
    {
        if (!empty($tag['catid'])) {
            $this->autoBuildVar($tag['catid']);
        }

        $tag['captcha'] = isset($tag['captcha'])?$tag['captcha']:'';
        $id = !empty($tag['id']) ? $tag['id'] : 'item';

        $parseStr = '<?php'."\r\n";
        $parseStr .= '$GuestBookTAG = '.self::arrToHtml($tag).';';
        $parseStr .= '$GuestBookTAG["catid"] = empty($GuestBookTAG["catid"])?(isset($Cate["id"])?$Cate["id"]:""):$GuestBookTAG["catid"];';
        $parseStr .= '$__fields__ = (new \app\admin\model\cms\Guestbook)->tagGuestbook($GuestBookTAG);'."\r\n";

        $parseStr .= ''."\r\n";
        $parseStr .= '?>';
        $parseStr .= '{if ($__fields__)}';
        $parseStr .= '{php}$'.$id.' = $__fields__;$GuestBookTAG["captcha"]==1?session("tagGuestbook".$__fields__["category"]["id"], 1):session("tagGuestbook".$__fields__["category"]["id"], -1);{/php}'."\r\n";
        $parseStr .= $content;
        $parseStr .= '{/if}';

        return $parseStr;
    }

    /**
     * 表单标签
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagForm($tag, $content)
    {
        if (!empty($tag['catid'])) {
            $this->autoBuildVar($tag['catid']);
        }

        $id = !empty($tag['id']) ? $tag['id'] : 'item';
        $tag['attr'] = $tag['attr'] ?? '';

        $parseStr = '<?php'."\r\n";
        $parseStr .= '$GuestBookTAG = '.self::arrToHtml($tag).';';
        $parseStr .= '$GuestBookTAG["catid"] = empty($GuestBookTAG["catid"])?(isset($Cate["id"])?$Cate["id"]:""):$GuestBookTAG["catid"];';
        $parseStr .= '$__fields__ = (new \app\admin\model\cms\Guestbook)->tagGuestbook($GuestBookTAG);'."\r\n";

        $parseStr .= ''."\r\n";
        $parseStr .= '?>';
        $parseStr .= '{if ($__fields__)}';
        $parseStr .= '{php}$'.$id.' = $__fields__;{/php}'."\r\n";
        $parseStr .= '<form action="{$__fields__.action}" '.$tag['attr'].' method="post" role="form">'."\r\n";
        $parseStr .= '<input type="hidden" name="tokenkey" value="token{$__fields__.category.id}">'."\r\n";
        $parseStr .= '{:token_field("token".$__fields__["category"]["id"], "md5")}'."\r\n";
        $parseStr .= $content;
        $parseStr .= '</form>';
        $parseStr .= '{/if}';

        return $parseStr;
    }

    /**
     * 上一篇标签
     * @param $tag
     * @return string
     */
    public function tagPre($tag)
    {
        // 栏目ID
        $tag['catid'] = !empty($tag['catid']) ? $tag['catid'] : '';
        // 文章ID
        $tag['id'] = !empty($tag['id']) ? $tag['id'] : '';
        // 显示字段
        $tag['field'] = !empty($tag['field']) ? $tag['field'] : 'title';
        // 当没有内容时的提示语
        $tag['msg'] = !empty($tag['msg']) ? lang($tag['msg']) : lang('No more');
        // 标题长度
        $tag['len'] = !empty($tag['len']) ? $tag['len'] : '';
        // 长度超过限制长度加...
        $tag['dot'] = isset($tag['dot']) ? $tag['dot'] : '';
        // 是否新窗口打开
        $tag['target'] = empty($tag['target'])?'':$tag['target'];
        // 上一篇下一篇
        $tag['type'] = !empty($tag['type']) ? $tag['type'] : 'pre';

        $this->autoBuildVar($tag['catid']);

        $parseStr = '<?php'."\r\n";
        $parseStr .= '$__TAGPARAM__ = '.self::arrToHtml($tag).";\r\n";
        $parseStr .= 'if(empty($__TAGPARAM__["catid"])):'."\r\n";
        $parseStr .= '  $__TAGPARAM__["catid"]=$Cate["id"]??"";'."\r\n";
        $parseStr .= 'endif;'."\r\n";
        $parseStr .= 'if(empty($__TAGPARAM__["id"])):'."\r\n";
        $parseStr .= '  $__TAGPARAM__["id"]=$Info["id"]??"";'."\r\n";
        $parseStr .= 'endif;'."\r\n";
        $parseStr .= '$__TITLE__ = (new \app\index\taglib\hkcms\TagPreNext())->preNextHtml($__TAGPARAM__);'."\r\n";

        $parseStr .= 'if ($__TITLE__) : '."\r\n";
        $parseStr .= '  echo $__TITLE__;'."\r\n";
        $parseStr .= 'else: '."\r\n";
        $parseStr .= '  echo "'.$tag['msg'].'"; '."\r\n";
        $parseStr .= 'endif; ?>';
        return $parseStr;
    }

    /**
     * 下一篇标签
     * @param $tag
     * @return string
     */
    public function tagNext($tag)
    {
        $tag['type'] = 'next';
        return $this->tagPre($tag);
    }

    /**
     * 获取上一篇、下一篇数组形式
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagPrenext($tag, $content)
    {
        if (empty($tag['type']) || ($tag['type']!='pre'&&$tag['type']!='next')) {
            return '';
        }

        // 循环变量
        $tag['id'] = !empty($tag['id']) ? $tag['id'] : 'item';
        // 标题长度
        $tag['len'] = !empty($tag['len']) ? $tag['len'] : '';
        // 长度超过限制长度加...
        $tag['dot'] = isset($tag['dot']) ? $tag['dot'] : '';
        // 显示字段
        $tag['field'] = !empty($tag['field']) ? $tag['field'] : 'title';

        $parseStr = '<?php'."\r\n";
        $parseStr .= '$__TAGPARAM__ = '.self::arrToHtml($tag).";\r\n";
        $parseStr .= 'if(empty($__TAGPARAM__["catid"])):'."\r\n";
        $parseStr .= '  $__TAGPARAM__["catid"]=$Cate["id"]??"";'."\r\n";
        $parseStr .= 'endif;'."\r\n";
        $parseStr .= 'if(empty($__TAGPARAM__["aid"])):'."\r\n";
        $parseStr .= '  $__TAGPARAM__["aid"]=$Info["id"]??"";'."\r\n";
        $parseStr .= 'endif;'."\r\n";
        $parseStr .= '$__INFO__ = (new \app\index\taglib\hkcms\TagPreNext())->preNext($__TAGPARAM__);'."\r\n";
        $parseStr .= '$XX_INFO = $__INFO__;'."\r\n";
        $parseStr .= '$__INFO__ = empty($__INFO__)?[["title"=>""]]:$__INFO__;'."\r\n";
        $parseStr .= '?>';


        $parseStr .= '{volist name="__INFO__" id="'.$tag['id'].'"}';
        $parseStr .= '{if !empty($XX_INFO)}';
        $parseStr .= $content;
        $parseStr .= '{/if}';
        $parseStr .= '{/volist}';
        return $parseStr;
    }

    /**
     * 获取附件信息
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagFileinfo($tag, $content)
    {
        if (empty($tag['name'])) {
            return '';
        }
        $this->autoBuildVar($tag['name']);

        $tag['id'] = empty($tag['id']) ? 'item' : $tag['id'];
        $tag['field'] = empty($tag['field']) ? '' : $tag['field'];
        $tag['empty'] = !empty($tag['empty']) ? $tag['empty'] : '';
        $tag['aid'] = !empty($tag['aid']) ? $tag['aid'] : '';
        $tag['model'] = !empty($tag['model']) ? $tag['model'] : '';

        $this->autoBuildVar($tag['aid']);
        $this->autoBuildVar($tag['model']);

        $parseStr = '<?php'."\r\n";
        $parseStr .= '$__FileInfo__ = [];'."\r\n";
        $parseStr .= 'if (isset('.$tag['name'].')): '."\r\n";
        $parseStr .= '$__FILEINFOPARAM__ = '.self::arrToHtml($tag).";\r\n";
        $parseStr .= 'if(empty($__FILEINFOPARAM__["aid"])):'."\r\n";
        $parseStr .= '  $__FILEINFOPARAM__["aid"]=$Info["id"]??"";'."\r\n";
        $parseStr .= 'endif;'."\r\n";
        $parseStr .= 'if(empty($__FILEINFOPARAM__["model"])):'."\r\n";
        $parseStr .= '  $__FILEINFOPARAM__["model"]=$Info["model_id"]??"";'."\r\n";
        $parseStr .= 'endif;'."\r\n";
        $parseStr .= '$__FileInfo__ = (new \app\index\taglib\hkcms\TagFileInfo())->getAttachment($__FILEINFOPARAM__);'."\r\n";
        $parseStr .= 'endif; ?>';
        $parseStr .= '{volist name="$__FileInfo__" id="'.$tag['id'].'" empty="'.$tag['empty'].'"}';
        $parseStr .= $content;
        $parseStr .= '{/volist}';
        return $parseStr;
    }

    /**
     * 原样输出HTML内容
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagRaw($tag, $content)
    {
        if (empty($tag['name'])) {
            return '';
        }

        $this->autoBuildVar($tag['name']);

        $parseStr = '<?php'."\r\n";
        $parseStr .= 'echo !isset('.$tag['name'].')?"":htmlspecialchars_decode('.$tag['name'].');'."\r\n";
        $parseStr .= '?>';
        return $parseStr;
    }

    /**
     * 获取所有标签
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagTaglist($tag, $content)
    {
        $tag['id'] = !empty($tag['id']) ? $tag['id'] : 'item';
        $tag['where'] = !empty($tag['where']) ? $this->parseCondition($tag['where']) : null;
        $tag['empty'] = !empty($tag['empty']) ? $tag['empty'] : '';
        $tag['currentstyle'] = !empty($tag['currentstyle']) ? $tag['currentstyle'] : 'active';
        $tag['tid'] = $tag['tid'] ?? '';
        $tag['arcid'] = $tag['arcid'] ?? '';
        $tag['page'] = $tag['page'] ?? 0;
        $tag['model'] = $tag['model'] ??'';
        $tag['num'] = isset($tag['num']) ? ( (substr($tag['num'], 0, 1) == '$') ? $tag['num'] : (int) $tag['num'] ) : 10;

        $this->autoBuildVar($tag['arcid']);
        $this->autoBuildVar($tag['model']);

        $parseStr = '<?php'."\r\n";
        $parseStr .= '$__pagetaglist__=null;'."\r\n";
        $parseStr .= '$__TAGLIST__ = (new \app\admin\model\Tags)->getList('.self::arrToHtml($tag).',$__pagetaglist__);'."\r\n";
        $parseStr .= '$__TagsInfo__ = $Tags ?? [];'."\r\n";
        if ($tag['page']==1) {
            $parseStr .= '$__page__=$__pagetaglist__;'."\r\n";
        }
        $parseStr .= '?>';
        $parseStr .= '{volist name="__TAGLIST__" id="'.$tag['id'].'" empty="'.$tag['empty'].'"}';
        $parseStr .= '{php}$'.$tag['id'].'["currentstyle"]=!empty($__TagsInfo__)?($'.$tag['id'].'["id"]==$__TagsInfo__["id"]?"'.$tag['currentstyle'].'":""):"";{/php}';
        $parseStr .= $content;
        $parseStr .= '{/volist}';
        return $parseStr;
    }

    /**
     * 内容标签页
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagTagarclist($tag, $content)
    {
        $tag['id'] = !empty($tag['id']) ? $tag['id'] : 'item';
        $tag['where'] = !empty($tag['where']) ? $this->parseCondition($tag['where']) : null;
        $tag['empty'] = !empty($tag['empty']) ? $tag['empty'] : '';
        $tag['page'] = $tag['page'] ?? 0;
        $tag['num'] = isset($tag['num']) ? ( (substr($tag['num'], 0, 1) == '$') ? $tag['num'] : (int) $tag['num'] ) : 10;

        $parseStr = '<?php'."\r\n";
        $parseStr .= '$__pagearclist__=null;'."\r\n";
        $parseStr .= '$__TAGCONT__ = (new \app\admin\model\Tags)->getContent('.self::arrToHtml($tag).',$__pagearclist__);'."\r\n";
        if ($tag['page']==1) {
            $parseStr .= '$__page__=$__pagearclist__;'."\r\n";
        }
        $parseStr .= '?>';
        $parseStr .= '{volist name="__TAGCONT__" id="'.$tag['id'].'" empty="'.$tag['empty'].'"}';
        $parseStr .= $content;
        $parseStr .= '{/volist}';
        return $parseStr;
    }

    /**
     * 特定语言下显示内容
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagLang($tag, $content)
    {
        if (empty($tag['value'])) {
            return "";
        }
        $tag['value'] = explode(',', $tag['value']);
        $lang = app()->lang->getLangSet();
        $parseStr = '{if in_array("'.$lang.'",'.$this->arrToHtml($tag['value']).')}';
        $parseStr .= $content;
        $parseStr .= '{/if}';
        return $parseStr;
    }

    /**
     * 转换数据为HTML代码
     * @param $data
     * @return string
     */
    private static function arrToHtml($data)
    {
        if (is_array($data)) {
            $str = '[';
            foreach ($data as $key => $val) {
                if (is_array($val)) {
                    $str .= "'$key'=>" . self::arrToHtml($val) . ",";
                } else {
                    //如果是变量的情况
                    if (is_int($val)) {
                        $str .= "'$key'=>$val,";
                    } else if (is_null($val)) {
                        $str .= "'$key'=>null,";
                    } else if (strpos($val, '$') === 0) {
                        $str .= "'$key'=>$val,";
                    } else if (preg_match("/^([a-zA-Z_].*)\(/i", $val, $matches)) {//判断是否使用函数
                        if (function_exists($matches[1])) {
                            $str .= "'$key'=>$val,";
                        } else {
                            $str .= "'$key'=>'" . self::newAddslashes($val) . "',";
                        }
                    } else {
                        $str .= "'$key'=>'" . self::newAddslashes($val) . "',";
                    }
                }
            }
            $str = rtrim($str,',');
            return $str . ']';
        }
        return '';
    }

    /**
     * 返回经addslashes处理过的字符串或数组
     * @param string $string 需要处理的字符串或数组
     * @return mixed
     */
    private static function newAddslashes($string)
    {
        if (!is_array($string)) {
            return addslashes($string);
        }
        foreach ($string as $key => $val) {
            $string[$key] = self::newAddslashes($val);
        }
        return $string;
    }

    /**
     * 分析标签属性 正则方式。重写父类方法，[更新tp框架时留意该方法]
     * @access public
     * @param  string $str 标签属性字符串
     * @param  string $name 标签名
     * @param  string $alias 别名
     * @return array
     */
    public function parseAttr(string $str, string $name, string $alias = ''): array
    {
        $regex  = '/\s+(?>(?P<name>[\w-]+)\s*)=(?>\s*)([\"\'])(?P<value>(?:(?!\\2).)*)\\2/is';
        $result = [];

        if (preg_match_all($regex, $str, $matches)) {
            foreach ($matches['name'] as $key => $val) {
                $result[$val] = $matches['value'][$key];
            }

            if (!isset($this->tags[$name])) {
                // 检测是否存在别名定义
                foreach ($this->tags as $key => $val) {
                    if (isset($val['alias'])) {
                        $array = (array) $val['alias'];
                        if (in_array($name, explode(',', $array[0]))) {
                            $tag           = $val;
                            $type          = !empty($array[1]) ? $array[1] : 'type';
                            $result[$type] = $name;
                            break;
                        }
                    }
                }
            } else {
                $tag = $this->tags[$name];
                // 设置了标签别名
                if (!empty($alias) && isset($tag['alias'])) {
                    $type          = !empty($tag['alias'][1]) ? $tag['alias'][1] : 'type';
                    $result[$type] = $alias;
                }
            }

            if (!empty($tag['must'])) {
                $must = explode(',', $tag['must']);
                foreach ($must as $name) {
                    if (!isset($result[$name])) {
                        throw new Exception('tag attr must:' . $name);
                    }
                }
            }
        } else {
            // 允许直接使用表达式的标签
            if (!empty($this->tags[$name]['expression'])) {
                static $_taglibs;
                if (!isset($_taglibs[$name])) {
                    $_taglibs[$name][0] = strlen($this->tpl->getConfig('taglib_begin_origin') . $name);
                    $_taglibs[$name][1] = strlen($this->tpl->getConfig('taglib_end_origin'));
                }
                $result['expression'] = substr($str, $_taglibs[$name][0], -$_taglibs[$name][1]);
                // 清除自闭合标签尾部/
                $result['expression'] = rtrim($result['expression'], '/');
                $result['expression'] = trim($result['expression']);
//            } elseif (empty($this->tags[$name]) || !empty($this->tags[$name]['attr'])) {
            } elseif (empty($this->tags[$name])) {
                throw new Exception('tag error:' . $name);
            }
        }

        return $result;
    }
}