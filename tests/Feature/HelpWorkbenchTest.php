<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_usage_guide_is_default(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.help'))
            ->assertOk()
            ->assertSee('说明')
            ->assertSee('怎么用')
            ->assertSee('后台')
            ->assertSee('主题')
            ->assertSee('模板')
            ->assertSee('标签')
            ->assertSee('环境')
            ->assertSee('第一次建议按这个顺序')
            ->assertSee('建栏目')
            ->assertSee('常见问题')
            ->assertSee('换了主题，前台还是旧样子')
            ->assertSee('不想让搜索引擎抓某几页')
            ->assertSee('robots.txt 补充规则')
            ->assertSee(route('admin.settings.edit'), false)
            ->assertSee(route('admin.help', ['topic' => 'admin']), false)
            ->assertSee(route('admin.help', ['topic' => 'themes']), false)
            ->assertSee(route('admin.help', ['topic' => 'tags']), false)
            ->assertDontSee('全页缓存');
    }

    public function test_theme_and_tag_guides(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.help', ['topic' => 'themes']))
            ->assertOk()
            ->assertSee('用于本站')
            ->assertSee('复制一套')
            ->assertSee('补上缺少的内容模型')
            ->assertSee('已有模型字段')
            ->assertSee('企业')
            ->assertSee(route('admin.themes.index'), false);

        $this->get(route('admin.help', ['topic' => 'theme']))
            ->assertOk()
            ->assertSee('目录长什么样')
            ->assertSee('index/index.blade.php')
            ->assertSee('list.list')
            ->assertSee('品牌站')
            ->assertSee('@cmsContent')
            ->assertSee('二次开发用事件')
            ->assertSee('App\\Events\\EntrySaved')
            ->assertSee('Event::listen');

        $this->get(route('admin.help', ['topic' => 'tags']))
            ->assertOk()
            ->assertSee('常用例子')
            ->assertSee('@cmsChannel')
            ->assertSee('@cmsAdv')
            ->assertSee('<img src=')
            ->assertDontSee('<img src="{{ $item->image }}"', false)
            ->assertSee('cms_media($item->image)', false)
            ->assertSee('@cmsForm')
            ->assertSee('CMS_MEDIA_DISK')
            ->assertSee('必须改名');
    }

    public function test_admin_module_catalog(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.help', ['topic' => 'admin']))
            ->assertOk()
            ->assertSee('仪表盘')
            ->assertSee('采集')
            ->assertSee('跳转')
            ->assertSee('语言')
            ->assertSee('发布内容')
            ->assertSee(route('admin.collectors.index'), false)
            ->assertSee(route('admin.stats.index'), false)
            ->assertSee(route('admin.locales.index'), false)
            ->assertSee(route('admin.friend-links.index'), false)
            ->assertSee('友链')
            ->assertSee('robots.txt 补充规则')
            ->assertSee('从媒体库挑图')
            ->assertSee('CMS_MEDIA_DISK');
    }

    public function test_env_laravel_and_docker_topics(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.help', ['topic' => 'env']))
            ->assertOk()
            ->assertSee('PHP 环境')
            ->assertSee('MySQL')
            ->assertSee('nginx')
            ->assertSee('CREATE DATABASE cms')
            ->assertSee('try_files')
            ->assertSee('对象存储')
            ->assertSee('搜索引擎 robots.txt')
            ->assertSee('robots.txt 补充规则')
            ->assertSee('CMS_MEDIA_DISK');

        $this->get(route('admin.help', ['topic' => 'schedule']))
            ->assertOk()
            ->assertSee('定时任务和队列')
            ->assertSee('CMS_COLLECT_MODE')
            ->assertSee('schedule:run')
            ->assertSee('queue:work')
            ->assertSee('cms:collect');

        $this->get(route('admin.help', ['topic' => 'laravel']))
            ->assertOk()
            ->assertSee('用 Laravel 命令行安装')
            ->assertSee('php artisan cms:install')
            ->assertSee('App\\Events\\*')
            ->assertSee(route('admin.help', ['topic' => 'env']), false);

        $this->get(route('admin.help', ['topic' => 'docker']))
            ->assertOk()
            ->assertSee('用 Docker 安装')
            ->assertSee('docker compose up --build');
    }

    public function test_editor_can_read_help(): void
    {
        $editor = AdminUser::query()->where('email', 'editor@example.com')->first();
        $this->actingAs($editor, 'admin')
            ->get(route('admin.help'))
            ->assertOk()
            ->assertSee('第一次建议按这个顺序')
            ->assertSee('建栏目')
            ->assertDontSee('改站名');
    }

    public function test_help_follows_admin_ui_locale(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->post(route('admin.ui-locale'), ['ui_locale' => 'en'])
            ->assertRedirect();

        $this->get(route('admin.help'))
            ->assertOk()
            ->assertSee('Help')
            ->assertSee('Getting started')
            ->assertSee('Suggested first steps')
            ->assertSee('What the sidebar is for')
            ->assertDontSee('第一次建议按这个顺序')
            ->assertDontSee('侧栏对应什么');

        $this->get(route('admin.help', ['topic' => 'admin']))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Languages')
            ->assertDontSee('按侧栏把后台每一项说一遍');

        $this->get(route('admin.help', ['topic' => 'env']))
            ->assertOk()
            ->assertSee('What you need to go live')
            ->assertSee('robots.txt extra rules')
            ->assertDontSee('上线要准备什么');

        $this->get(route('admin.help', ['topic' => 'theme']))
            ->assertOk()
            ->assertSee('Extend with events')
            ->assertSee('App\\Events\\EntrySaved')
            ->assertDontSee('二次开发用事件');
    }
}
