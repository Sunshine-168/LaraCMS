<?php
// +----------------------------------------------------------------------
// | HkCms 操作日志模型
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: HkCms team <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types = 1);

namespace app\admin\model\auth;

use think\Model;

class AdminLog extends Model
{
    protected $autoWriteTimestamp = false;

    protected static $title = '';

    /**
     * 格式化时间日期
     * @param $value
     * @return false|string
     */
    public function getCreateTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    public function getTitleAttr($value)
    {
        $arr = explode('-', $value);
        $temp = [];
        foreach ($arr as $key=>$value) {
            $temp[] = lang($value);
        }
        return implode('-', $temp);
    }

    /**
     * 设置标题
     * @param $title
     */
    public static function setTitle($title)
    {
        self::$title = $title;
    }

    /**
     * 记录操作
     */
    public static function logs()
    {
        $data['admin_id'] = 0;
        $data['username'] = '';
        $info = app()->user->getUserInfo();
        if (!empty($info)) {
            $data['admin_id'] = $info['id'];
            $data['username'] = $info['username'];
        }

        $request = request();

        $htmlspecialchars = htmlspecialchars(strip_tags($request->header('user-agent')));
        $data['useragent'] = mb_strlen($htmlspecialchars) > 500 ? mb_substr($htmlspecialchars, 0, 500) : $htmlspecialchars;
        //$data['title'] = lang('Default action');
        $data['title'] = 'Default action';

        if (!empty(self::$title)) {
            $data['title'] = self::$title;
        } else {
            // 获取title
            $action = strtolower(str_replace('.','/', $request->controller()).'/'.$request->action());
            $info = AuthRule::where(['name'=>$action])->find();
            if (!empty($info)) {
                $tempArr[] = $info->toArray();
                while (true) { // 循环获取上级
                    $info = AuthRule::where(['id'=>$info['parent_id']])->find();
                    if (empty($info)) {
                        break;
                    }
                    $tempArr[] = $info->toArray();
                }

                $title = '';
                for ($i = count($tempArr)-1; $i >= 0; $i--)
                {
                    $title .= $tempArr[$i]['title'].'-';
                }
                $data['title'] = trim($title,'-');
            }
        }

        $data['url'] = $request->url();
        $data['ip'] = $request->ip();
        $content = $request->param('', null, 'trim,strip_tags,htmlspecialchars');
        foreach ($content as $k => $v) {
            if (is_array($v) && strlen(json_encode($v))>500) {
                unset($content[$k]);
            }
            if (is_string($v) && strlen($v) > 200 || stripos($k, 'password') !== false) {
                unset($content[$k]);
            }
        }

        $data['content'] = !is_scalar($content)?json_encode($content):$content;
        $data['create_time'] = time();

        self::create($data);
    }
}
