<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Setting;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_page_is_grouped_tabs(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin);

        $this->actingAs($admin, 'admin')
            ->withSession(['admin_site_id' => Site::default()->id])
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('网站名称')
            ->assertSee('外观主题')
            ->assertSee('企业')
            ->assertSee('暂时关闭网站')
            ->assertSee('浏览器小图标')
            ->assertSee('备案号')
            ->assertSee('允许在文章下留言')
            ->assertSee('栏目名/文章名（推荐）')
            ->assertSee('保存设置')
            ->assertSee('class="settings-pane active" data-pane="site"', false)
            ->assertDontSee('基本信息')
            ->assertDontSee('外观与品牌')
            ->assertDontSee('伪静态 URL')
            ->assertDontSee('REST API')
            ->assertDontSee('SMTP Host')
            ->assertDontSee('Favicon')
            ->assertDontSee('PHP GD')
            ->assertDontSee('Leaflet')
            ->assertDontSee('地图选点默认中心');
    }

    public function test_look_tab_has_image_preview_slots(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();

        $this->actingAs($admin, 'admin')
            ->withSession(['admin_site_id' => Site::default()->id])
            ->get(route('admin.settings.edit', ['tab' => 'look']))
            ->assertOk()
            ->assertSee('data-file-preview', false)
            ->assertSee('settings-file-thumb', false)
            ->assertSee('还没有 Logo')
            ->assertSee('还没有小图标')
            ->assertSee('name="logo_file"', false)
            ->assertSee('name="favicon_file"', false);

        $this->get(route('admin.settings.edit', ['tab' => 'more']))
            ->assertOk()
            ->assertSee('还没有水印图')
            ->assertSee('name="watermark_image"', false);
    }

    public function test_more_tab_and_save_keeps_tab(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();

        $this->actingAs($admin, 'admin')
            ->withSession(['admin_site_id' => Site::default()->id])
            ->get(route('admin.settings.edit', ['tab' => 'more']))
            ->assertOk()
            ->assertSee('文章链接怎么写')
            ->assertSee('邮件服务器')
            ->assertSee('立即备份')
            ->assertSee('class="settings-pane active" data-pane="more"', false);

        $this->actingAs($admin, 'admin')
            ->withSession(['admin_site_id' => Site::default()->id])
            ->put(route('admin.settings.update'), [
                'tab' => 'look',
                'site_name' => '设置工作台站',
                'theme' => 'enterprise',
                'icp' => '京ICP备12345678号',
                'comments_enabled' => '1',
                'comments_guest' => '1',
                'tag_autolink' => '1',
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'look']))
            ->assertSessionHas('status', '设置已保存');

        $this->assertSame('设置工作台站', Setting::getValue('site_name'));
        $this->assertSame('京ICP备12345678号', Setting::getValue('icp'));
    }
}
