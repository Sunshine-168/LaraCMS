<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Services\Admin\QuickPanelService;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
    }

    public function test_admin_can_configure_quick_panel(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->put(route('admin.quick-panels.update'), [
                'keys' => ['entries', 'front', 'settings'],
            ])
            ->assertRedirect(route('admin.dashboard'));

        $nav = app(QuickPanelService::class)->forAdmin($admin);
        $keys = array_column($nav, 'key');
        $this->assertSame(['entries', 'front', 'settings'], $keys);

        $options = array_column(app(QuickPanelService::class)->editOptions($admin), 'key');
        $this->assertContains('friend_links', $options);
        $this->assertContains('sitemap', $options);
        $this->assertContains('recycle', $options);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('配置');
    }
}
