<?php
// +----------------------------------------------------------------------
// | HkCms
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\index\controller;

use think\facade\Validate;

class Ajax extends BaseController
{

    /**
     * 用于瀑布流分页标签
     * @return string
     */
    public function wfpagelist()
    {
        $key = $this->request->param('key','','');
        $num = $this->request->param('num','','intval');
        $num = $num<=0?1:$num;
        if (empty($key) || !Validate::is($key, 'alphaDash') || !cache($key)) {
            $this->error(lang('Illegal access'));
        }

        $tag = cache($key);
        $tag['num'] = $num;

        $order = $this->arrToHtml($tag['order']??'');
        $html = '{php}$_order_ = '.($order?:'""').';{/php}';
        $html .= '{hkcms:content order="$_order_" ';
        foreach ($tag as $key=>$value) {
            if (in_array($key,['catid','model','num','where','page','more','id','empty','cache','tagid']) && $value!="") {

                if (is_array($value)) {
                    $value = $this->arrToHtml($value);
                    $html .= "{$key}=\"{$value}\" ";
                } else {
                    $html .= "{$key}=\"{$value}\" ";
                }

            }
        }
        $html .= '}{include file="common/page_'.$tag['tagid'].'"}{/hkcms:content}';

        $html = $this->view->display($html);
        $this->success('','',['html'=>$html,'total'=>$tag['total'],'last_page'=>ceil($tag['total'] / $num)]);
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
}