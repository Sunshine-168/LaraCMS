<?php
// +----------------------------------------------------------------------
// | HkCms  邮箱类
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace libs;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;

class Email
{
    protected static $instance;

    /**
     * @var \Symfony\Component\Mime\Email
     */
    protected $mail;

    /**
     * 错误信息
     * @var string
     */
    protected $error = '';

    /**
     * 最大验证次数
     * @var int
     */
    public $maxCheck = 10;

    /**
     * 最大过期时间
     * @var int
     */
    public $maxExpire = 1800;

    /**
     * 配置
     * @var
     */
    protected $config = [
        "mail_type" => "smtp",
        "mail_server" => "smtp.exmail.qq.com",
        "mail_port" => "465",
        "mail_from" => "",
        "mail_fname" => "",
        "mail_auth" => "ssl",
        "mail_user" => "",
        "mail_password" => ""
    ];

    /**
     * 单例模式
     * @param array $options
     * @return Email
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }

        return self::$instance;
    }

    /**
     * Email constructor.
     * @param array $options
     */
    public function __construct($options = [])
    {
        if ($config = site()) {
            $this->config = array_merge($this->config, $config);
        }
        $this->config = array_merge($this->config, $options);

        $this->mail = new \Symfony\Component\Mime\Email();

        // 设置发件人姓名、邮件
        $this->setForm($this->config['mail_from'], $this->config['mail_fname']);
    }

    /**
     * 邮件的发件人电子邮件地址, 发件人姓名
     * @param $from
     * @param $fname
     * @return $this
     */
    public function setForm($from, $fname)
    {
        $this->mail->from(new Address($from, $fname));
        return $this;
    }

    /**
     * 设置邮件标题
     * @param $title string 标题
     * @return $this
     */
    public function subject($title)
    {
        $this->mail->subject($title);
        return $this;
    }

    /**
     * 收件人，支持多个邮箱（数组[a,b,c]）
     * @param $address
     * @return $this
     * @throws \Exception
     */
    public function email($address)
    {
        if (is_array($address)) {
            $this->mail->to(...$address);
        } else {
            $this->mail->to($address);
        }
        return $this;
    }

    /**
     * 设置邮箱正文
     * @param $message string
     * @param bool $isHtml true-支持HTML，false-普通文本
     * @return $this
     */
    public function message($message, bool $isHtml = true)
    {
        if ($isHtml) {
            $this->mail->html($message);
        } else {
            $this->mail->text($message);
        }
        return $this;
    }

    /**
     * 发送邮件
     * @return bool
     */
    public function send()
    {
        $result = false;
        switch ($this->config['mail_type']) {
            case 'smtp':
                try {
                    $transport = Transport::fromDsn("smtp://{$this->config['mail_user']}:{$this->config['mail_password']}@{$this->config['mail_server']}:{$this->config['mail_port']}");
                    $mailer = new Mailer($transport);
                    $mailer->send($this->mail);
                } catch (\Exception $e) {
                    $this->error = $e->getMessage();
                    return false;
                }
                return true;
                break;
            default: $this->error=lang('The mailbox interface is down');break;
        }

        return $result;
    }

    /**
     * 验证验证码
     * @param $email
     * @param $event
     * @param $code
     * @return bool
     */
    public function check($email, $event, $code)
    {
        $info = (new \app\common\model\Ems)->where(['email'=>$email,'event'=>$event])->order('id','desc')->find();
        if (!$info) {
            return false;
        }

        $createTime = strtotime($info['create_time']);

        // 判断是否已经过期
        if ($createTime > (time()-$this->maxExpire) && $info['count'] <= $this->maxCheck) {
            if ($info['code']==$code) {
                return true;
            } else {
                $info->count = $info->count+1;
                $info->save();
                return false;
            }
        } else {
            $info->delete();
            return false;
        }
    }

    /**
     * 错误信息
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }
}