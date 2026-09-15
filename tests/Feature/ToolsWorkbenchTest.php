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

    public function test_page_is_a_workbench(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.cache.edit'))
            ->assertOk()
            ->assertSee('系统工具')
            ->assertSee('清空缓存')
            ->assertSee('去管全页缓存')
            ->assertSee('立即备份')
            ->assertSee('还没有备份')
            ->assertSee('导入演示数据')
            ->assertSee('正式上线的站点不要点')
            ->assertDontSee('一键清空缓存')
            ->assertDontSee('Laravel')
            ->assertDontSee('CmsSeeder')
            ->assertDontSee('install.lock')
            ->assertDontSee('php artisan')
            ->assertDontSee('storage/app/public')
            ->assertDontSee('幂等')
            ->assertDontSee('sqlite')
            ->assertDontSee('.env');
    }

    public function test_clear_cache_shows_plain_status(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.cache.clear'))
            ->assertRedirect(route('admin.cache.edit'))
            ->assertSessionHas('status', '缓存已清空');
    }
}
