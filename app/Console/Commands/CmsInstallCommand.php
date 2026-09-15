<?php

namespace App\Console\Commands;

use App\Services\Cms\InstallService;
use Illuminate\Console\Command;

class CmsInstallCommand extends Command
{
    protected $signature = 'cms:install
        {--site=我的网站 : 网站名称}
        {--name=管理员 : 管理员姓名}
        {--email= : 管理员邮箱}
        {--password= : 管理员密码}
        {--demo : 导入示例栏目和文章}';

    protected $description = '用命令行安装 CMS：建表、创建管理员、写入安装锁';

    public function handle(InstallService $install): int
    {
        if ($install->locked()) {
            $this->error('已经安装过了。要重装请先删除 storage/app/install.lock');

            return self::FAILURE;
        }

        $site = trim((string) $this->option('site')) ?: '我的网站';
        $name = trim((string) $this->option('name')) ?: '管理员';
        $email = trim((string) $this->option('email'));
        if ($email === '' && $this->input->isInteractive()) {
            $email = trim((string) $this->ask('管理员邮箱'));
        }
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('请用 --email 提供有效的管理员邮箱');

            return self::FAILURE;
        }

        $password = (string) $this->option('password');
        if ($password === '' && $this->input->isInteractive()) {
            $password = (string) $this->secret('管理员密码（至少 8 位）');
        }
        if (strlen($password) < 8) {
            $this->error('密码至少 8 位，可用 --password 传入');

            return self::FAILURE;
        }

        $demo = (bool) $this->option('demo');

        try {
            $install->run([
                'site_name' => $site,
                'admin_name' => $name,
                'admin_email' => $email,
                'admin_password' => $password,
                'seed_demo' => $demo,
            ]);
        } catch (\Throwable $e) {
            @unlink($install->lockPath());
            $this->error('安装失败：'.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('安装完成。后台登录 '.$email);
        $this->line('前台 '.rtrim((string) config('app.url'), '/').'/');
        $this->line('后台 '.rtrim((string) config('app.url'), '/').'/admin/login');

        return self::SUCCESS;
    }
}
