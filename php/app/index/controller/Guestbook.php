<?php
// +----------------------------------------------------------------------
// | HkCms 前台留言板处理
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\index\controller;

use addons\basesms\library\Sms;
use app\common\library\MsgSend;
use libs\Email;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Validate;
use think\helper\Str;

class Guestbook extends BaseController
{
    /**
     * 留言板提交
     * @return string
     */
    public function index()
    {
        if ($this->request->isPost()) {
            $catid = $this->request->param('catid', '', 'intval');
            $row = $this->request->param('row', [], 'stripslashes,strip_tags');
            $o_row = $row;
            $ip = $this->request->ip();
            if (empty($catid) || empty($row)) {
                return '';
            }
            $cate = \app\admin\model\cms\Category::where(['id' => $catid, 'status' => 'normal'])->find();
            if (empty($cate)) {
                $this->error(__('Column doesn\'t exist.'));
            }
            // 获取对应表
            $model = Db::name('model')->where(['id' => $cate['model_id'], 'controller' => 'Guestbook'])->find();
            if (empty($model) || empty($model['config'])) {
                $this->error(__('Model doesn\'t exist.'));
            }
            // 获取模型配置
            $config = json_decode($model['config'], true);
            if (empty($config)) {
                $this->error(__('No results were found'));
            }
            // 留言间隔设置
            $count = Db::name($model['tablename'])->where(['category_id' => $catid, 'ip' => $ip])->whereTime('create_time', '>', time() - $config['tcount'])->count();
            if ($count) {
                $this->error(__('Operation is too frequent'));
            }
            // 获取表字段,并过滤数据
            $modelField = (new \app\admin\model\cms\ModelFieldBind)->getAllowField($catid, $cate['model_id'], $row);
            list($valData, $msgData) = build_tp_rules($modelField);
            try {
                $this->validate($row, $valData, $msgData);
            } catch (ValidateException $e) {
                $this->error($e->getError());
            }

            // 验证码判断
            if ($config['captcha'] == 1) { // 开启验证码
                if (empty($o_row['captcha'])) {
                    $this->error(__('Please fill in the verification code'));
                }

                if ($config['type'] == 'mobile') {
                    if (empty($row['phone'])) {
                        $this->error(__('Mobile number cannot be empty'));
                    }
                    if (!Validate::is($row['phone'], 'mobile')) {
                        $this->error(__('Mobile number format is incorrect'));
                    }
                    $sms = Sms::instance();
                    $bl = $sms->check($row['phone'], $model['tablename'], $o_row['captcha']);
                    if (!$bl) {
                        $this->error(lang('Incorrect verification code'));
                    }
                } else if ($config['type'] == 'email') {
                    if (empty($row['email'])) {
                        $this->error(__('Email address cannot be empty'));
                    }
                    if (!Validate::is($row['email'], 'email')) {
                        $this->error(__('E-mail format is incorrect'));
                    }
                    $result = Email::instance()->check($row['email'], $model['tablename'], $o_row['captcha']);
                    if (!$result) {
                        $this->error(lang('Incorrect verification code'));
                    }
                } else {
                    if (!captcha_check($o_row['captcha'])) {
                        $this->error(__('Incorrect verification code'));
                    }
                }
            }

            // 表单token验证
            $__token__ = $this->request->param('tokenkey', '__token__');
            $check = $this->request->checkToken($__token__, [$__token__ => $this->request->param($__token__)]);
            if (false === $check) {
                $this->error(__('invalid token'));
            }

            Db::startTrans();
            try {
                $bl = (new \app\admin\model\cms\Guestbook)->setTable($model['tablename'])->save(array_merge($row, [
                    'model_id' => $cate['model_id'],
                    'category_id' => $catid,
                    'create_time' => time(),
                    'lang' => $cate['lang'],
                    'ip' => $ip
                ]));
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }

            if ($bl) {
                if ($config['msg'] == 1 && !empty($config['msgtype']) && is_array($config['msgtype']) && !empty($config['msgemail'])) {
                    if (site('mail_on') != 1) {
                        $this->error(__('Email is not opened, and email notification cannot be used'));
                    }
                    (new MsgSend())->send($config['msgemail'], $cate['title'], '有新的[' . $cate['title'] . ']表单信息提交，请前往后台查看');
                }
                $this->success();
            } else {
                $this->error(__('No rows added'));
            }
        } else {
            $this->error(__('Illegal access'));
        }
    }

    /**
     * 验证码
     * @return \think\Response
     */
    public function captcha()
    {
        return captcha();
    }

    /**
     * 手机、邮件验证码发送
     */
    public function send()
    {
        $mid = $this->request->param('mid', '', 'intval');
        if (empty($mid)) {
            $this->error(__('Illegal request'));
        }
        // 获取对应表
        $model = Db::name('model')->where(['id' => $mid, 'controller' => 'Guestbook'])->find();
        if (empty($model) || empty($model['config'])) {
            $this->error(__('Model doesn\'t exist.'));
        }
        // 获取模型配置
        $config = json_decode($model['config'], true);
        if (empty($config)) {
            $this->error(__('No results were found'));
        }

        // 验证码判断
        if ($config['captcha'] == 1 && $config['type'] == 'email') {
            if (site('mail_on') != 1) {
                $this->error(__('Email function is not enabled'));
            }
            $email = $this->request->post('obj');
            if (empty($email)) {
                $this->error(__('Email address cannot be empty'));
            }
            if (!Validate::is($email, 'email')) {
                $this->error(__('E-mail format is incorrect'));
            }

            $create_time = \app\common\model\Ems::where(['email' => $email, 'event' => $model['tablename']])->order('create_time', 'desc')->value('create_time');
            if ($create_time && time() - $create_time < 60) {
                $this->error(__('Operation is too frequent'));
            }

            $random = get_random_str();
            $html = "<div>
                        <p>您的验证码是：</p>
                        <h2>{$random}</h2>
                        <p>验证码有效期为一小时，请及时完成验证。</p>
                        <p>如非本人操作，请忽略此邮件。</p>
                    </div>";
            Db::startTrans();
            $ems = Email::instance();
            try {
                $bl = \app\common\model\Ems::insert([
                    'event' => $model['tablename'],
                    'email' => $email,
                    'code' => $random,
                    'ip' => $this->request->ip(),
                    'create_time' => time(),
                ]);

                if ($bl) {
                    $bl = $ems->email($email)->subject('邮箱验证码')->message($html)->send();
                    if (!$bl) {
                        throw new \Exception($ems->getError());
                    }
                }

                Db::commit();
            } catch (\Exception $exception) {
                Db::rollback();
                $this->error($exception->getMessage());
            }
            $this->success(__('Sent successfully'));
        } else if ($config['captcha'] == 1 && $config['type'] == 'mobile') { // 手机验证码
            if (!get_addons_info('basesms')) {
                $this->error(__('SMS plugin not installed'));
            }
            $mobile = $this->request->post('obj');
            if (empty($mobile)) {
                $this->error(__('Mobile number cannot be empty'));
            }
            if (!Validate::is($mobile, 'mobile')) {
                $this->error(__('Mobile number format is incorrect'));
            }

            // 限制1分钟一条
            $create_time = Db::name('sms')->where(['mobile' => $mobile, 'event' => $model['tablename']])->order('create_time', 'desc')->value('create_time');
            if ($create_time && time() - $create_time < 60) {
                $this->error(__('Operation is too frequent'));
            }
            // 限制单个手机号2小时内不得超过3条
            if (Db::name('sms')->where(['mobile' => $mobile, 'event' => $model['tablename']])->count() > 3) {
                $this->error(__('Sending too frequently, please try again in two hours'));
            }

            $random = Str::random(6, 1);
            $sms = Sms::instance();
            $bl = $sms->send($mobile, $random, $model['tablename']);
            if ($bl) {
                $this->success(__('Sent successfully'));
            } else {
                $this->error($sms->getError());
            }
        }
        $this->error(__('Illegal request'));
    }
}