<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Category;
use App\Models\Cms\Entry;
use App\Models\Cms\Menu;
use App\Models\Cms\Setting;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinishUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_without_demo_creates_super_admin(): void
    {
        $lock = storage_path('app/install.lock');
        $backup = is_file($lock) ? file_get_contents($lock) : null;
        if (is_file($lock)) {
            unlink($lock);
        }

        try {
            $this->post('/install', [
                'site_name' => '空白站',
                'admin_name' => '站长',
                'admin_email' => 'owner@example.com',
                'admin_password' => 'Secret#123',
                'admin_password_confirmation' => 'Secret#123',
            ])->assertRedirect('/admin/login');

            $this->assertFileExists($lock);
            $this->assertNotNull(AdminUser::query()->where('email', 'owner@example.com')->first());
            $this->assertSame(0, Category::query()->count());

            $this->post('/admin/login', [
                'email' => 'owner@example.com',
                'password' => 'Secret#123',
            ])->assertRedirect();
            $owner = AdminUser::query()->where('email', 'owner@example.com')->first();
            $this->assertNotNull($owner?->last_login_at);
            $this->assertNotNull($owner->last_login_ip);
        } finally {
            if ($backup !== null) {
                file_put_contents($lock, $backup);
            } elseif (is_file($lock)) {
                unlink($lock);
            }
        }
    }

    public function test_header_menu_and_channel_fallback(): void
    {
        $this->seed(CmsSeeder::class);

        $this->get('/')->assertOk()->assertSee('新闻中心');

        Menu::query()->delete();
        $this->get('/')->assertOk()->assertSee('新闻中心');
    }

    public function test_theme_show_pages_are_readable(): void
    {
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());

        $download = Entry::query()->with(['category', 'modelDef'])->find(4);
        $download->attrs = array_merge($download->attrs ?? [], ['require_login' => false]);
        $download->save();
        $source = Entry::query()->with(['category', 'modelDef'])->find(7);
        $sports = Entry::query()->with(['category', 'modelDef'])->find(12);

        Setting::setValue('theme', 'download');
        $this->get($download->url)
            ->assertOk()
            ->assertSee('立即下载')
            ->assertDontSee('å');

        Setting::setValue('theme', 'source');
        $this->get($source->url)
            ->assertOk()
            ->assertSee('下载源码包')
            ->assertDontSee('å');

        Setting::setValue('theme', 'sports');
        $this->get($sports->url)
            ->assertOk()
            ->assertSee('打开信号')
            ->assertDontSee('å');
    }

    public function test_category_by_id_respects_single_type(): void
    {
        $this->seed(CmsSeeder::class);
        $about = Category::query()->where('type', 'single')->first();
        $this->assertNotNull($about);

        $this->get('/c/'.$about->id)
            ->assertOk()
            ->assertSee('关于我们');
    }

    public function test_member_and_tag_pages_work_on_secondary_themes(): void
    {
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
        Setting::setValue('theme', 'blog');

        $this->get('/member/login')->assertOk()->assertSee('会员登录');
        $this->get('/member/register')->assertOk()->assertSee('会员注册');
        $this->get('/member/forgot')->assertOk()->assertSee('找回密码');
        $this->get('/tag/laravel')->assertOk()->assertSee('标签');
        $this->get('/news')->assertOk()->assertSee('新闻中心');

        Setting::setValue('theme', 'download');
        $this->get('/tag/laravel')->assertOk()->assertSee('标签');
        $this->get('/member/login')->assertOk()->assertSee('会员登录');
    }

    public function test_blog_theme_keeps_own_skin_and_renders_html(): void
    {
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
        Setting::setValue('theme', 'blog');

        $this->get('/products')->assertOk()->assertSee('Literata')->assertSee('产品中心');

        $entry = Entry::query()->published()->whereHas('modelDef', fn ($q) => $q->where('slug', 'article'))->first();
        $this->assertNotNull($entry);
        $entry->setTranslation('content', 'zh_cn', '<p>富文本段落</p>');
        $entry->save();
        $this->get($entry->url)
            ->assertOk()
            ->assertSee('富文本段落', false)
            ->assertDontSee('&lt;p&gt;');
    }

    public function test_legacy_pages_routes_are_hidden(): void
    {
        $this->seed(CmsSeeder::class);
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();

        $this->actingAs($admin, 'admin')->get('/admin/pages')->assertNotFound();
    }
}
