<?php
// +----------------------------------------------------------------------
// | HkCms 消息推送
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2022 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\library;

use libs\Email;
use think\facade\Db;
use think\facade\Log;
use think\facade\Validate;

class MsgSend
{
    /**
     * 单用户消息推送
     * @param $obj
     * @param $subject
     * @param $message
     * @param string $type
     */
    public function send($obj, $subject, $message, $type = 'email')
    {
        if (is_string($type)) {
            $type = [$type];
        }

        if (in_array('email', $type) && Validate::is($obj,'email')) {
            $email = Email::instance();
            $bl = $email->email($obj)->subject($subject)->message($message,true)->send();
            if (!$bl) {
                Log::write('邮件推送：发送给'.$obj.'失败，错误信息：'.$email->getError(),'notice');
            }
        }
    }

    public function sendUser($userId, $subject, $message, $type = 'email')
    {
        return true;
    }
}