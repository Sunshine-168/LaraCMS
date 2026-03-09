<?php

if (!function_exists('color')) {
    /**
     * 字符高亮
     * @param $str
     * @param $keyword
     * @param string $style
     * @return string|string[]
     */
    function color($str, $keyword, $style="color:#dc3545;")
    {
        if (!$keyword || !$str) {
            return $str;
        }

        $q = explode(' ', $keyword);
        foreach ($q as $key=>$value) {
            $str = str_replace($value, '<span style="'.$style.'">'.$value.'</span>',$str);
        }

        return $str;
    }
}

if (!function_exists('user_auth_check')) {
    /**
     * 前台权限规则判断
     * @param string $name 规则
     * @return mixed
     */
    function user_auth_check($name)
    {
        if (strstr($name,'.') || strstr($name, '/')) {
            $name = strstr($name,'.') ? str_replace('.','/', $name):$name;
            $name = ltrim($name, '/');
        } else {
            $controller = strtolower(\think\facade\Request::controller());
            $name = str_replace('.','/',$controller.'/').$name;
        }
        $user = \app\index\library\User::instance();
        return $user->check($name, $user->id);
    }
}