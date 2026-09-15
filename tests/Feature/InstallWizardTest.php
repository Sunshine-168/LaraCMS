<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallWizardTest extends TestCase
{
    use RefreshDatabase;

    protected ?string $lockBackup = null;

    protected function setUp(): void
    {
        parent::setUp();
        $lock = storage_path('app/install.lock');
        $this->lockBackup = is_file($lock) ? file_get_contents($lock) : null;
        if (is_file($lock)) {
            unlink($lock);
        }
    }

    protected function tearDown(): void
    {
        $lock = storage_path('app/install.lock');
        if ($this->lockBackup !== null) {
            file_put_contents($lock, $this->lockBackup);
        } elseif (is_file($lock)) {
            unlink($lock);
        }
        parent::tearDown();
    }

    public function test_wizard_shows_three_steps(): void
    {
        $this->get('/install')
            ->assertOk()
            ->assertSee('三步即可用')
            ->assertSee('环境检查')
            ->assertSee('数据库')
            ->assertSee('网站和管理员')
            ->assertSee('文件数据库')
            ->assertSee('MySQL')
            ->assertSee('开始安装')
            ->assertSee('带上示例栏目和文章')
            ->assertSee('网页安装')
            ->assertSee('Laravel 命令行')
            ->assertSee('Docker')
            ->assertSee('上线部署')
            ->assertDontSee('admin@example.com / Admin@12345')
            ->assertDontSee('php artisan')
            ->assertDontSee('请先配置好');
    }

    public function test_laravel_and_docker_guides(): void
    {
        $this->get('/install?way=laravel')
            ->assertOk()
            ->assertSee('用 Laravel 命令行安装')
            ->assertSee('composer install')
            ->assertSee('php artisan cms:install')
            ->assertSee('php artisan serve');

        $this->get('/install?way=docker')
            ->assertOk()
            ->assertSee('用 Docker 安装')
            ->assertSee('docker compose up --build')
            ->assertSee('admin@example.com')
            ->assertSee('docker compose exec app php artisan test');

        $this->get('/install?way=deploy')
            ->assertOk()
            ->assertSee('上线要准备什么')
            ->assertSee('PHP 环境')
            ->assertSee('pdo_mysql')
            ->assertSee('CREATE DATABASE cms')
            ->assertSee('DB_CONNECTION=mysql')
            ->assertSee('try_files')
            ->assertSee('php8.3-fpm.sock')
            ->assertSee('APP_DEBUG=false');
    }

    public function test_artisan_install_command(): void
    {
        $this->artisan('cms:install', [
            '--site' => '命令行站',
            '--name' => '站长',
            '--email' => 'cli@example.com',
            '--password' => 'Secret#123',
        ])->assertSuccessful();

        $this->assertFileExists(storage_path('app/install.lock'));
        $this->assertNotNull(AdminUser::query()->where('email', 'cli@example.com')->first());
    }

    public function test_sqlite_probe_passes(): void
    {
        $this->postJson('/install/probe', [
            'db_connection' => 'sqlite',
        ])
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_task_sequence_installs_without_demo(): void
    {
        $payload = [
            'site_name' => '向导站',
            'admin_name' => '站长',
            'admin_email' => 'wizard@example.com',
            'admin_password' => 'Secret#123',
            'admin_password_confirmation' => 'Secret#123',
        ];

        $this->postJson('/install/task', $payload + ['phase' => 'prepare'])->assertOk();
        $this->postJson('/install/task', $payload + ['phase' => 'migrate'])->assertOk();
        $this->postJson('/install/task', $payload + ['phase' => 'demo'])->assertOk();
        $this->postJson('/install/task', $payload + ['phase' => 'account'])->assertOk();
        $this->postJson('/install/task', $payload + ['phase' => 'finish'])->assertOk();

        $this->assertFileExists(storage_path('app/install.lock'));
        $this->assertNotNull(AdminUser::query()->where('email', 'wizard@example.com')->first());

        $this->get('/install/done')
            ->assertOk()
            ->assertSee('向导站')
            ->assertSee('wizard@example.com');
    }

    public function test_done_without_session_goes_elsewhere(): void
    {
        $this->get('/install/done')->assertRedirect('/install');
    }
}
