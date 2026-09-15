<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToolsWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_old_tools_page_goes_to_themes(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.cache.edit'))
            ->assertRedirect(route('admin.themes.index'));

        $this->get(route('admin.themes.index'))
            ->assertOk()
            ->assertSee('清空缓存')
            ->assertSee('外观主题')
            ->assertDontSee('立即备份')
            ->assertDontSee('导入演示数据')
            ->assertDontSee('系统工具')
            ->assertDontSee('>工具</span>', false);
    }

    public function test_clear_cache_returns_to_themes(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.cache.clear'))
            ->assertRedirect(route('admin.themes.index'))
            ->assertSessionHas('status', '缓存已清空');
    }

    public function test_backup_and_demo_live_in_settings_more(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.edit', ['tab' => 'more']))
            ->assertOk()
            ->assertSee('立即备份')
            ->assertSee('导入演示数据')
            ->assertSee('正式上线的站点不要点')
            ->assertDontSee('一键清空缓存')
            ->assertDontSee('CmsSeeder');
    }
}
