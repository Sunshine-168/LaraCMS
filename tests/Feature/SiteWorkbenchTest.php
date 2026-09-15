<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_list_is_a_workbench(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $site = Site::default();
        $this->assertNotNull($site);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.sites.index'))
            ->assertOk()
            ->assertSee('站点管理')
            ->assertSee('新增站点')
            ->assertSee('名称，如 英文站')
            ->assertSee($site->name)
            ->assertSee($site->domain)
            ->assertSee('默认')
            ->assertSee(route('admin.sites.edit', $site), false)
            ->assertDontSee('主题覆盖')
            ->assertDontSee('前台按访问域名匹配')
            ->assertDontSee('>状态</th>', false)
            ->assertDontSee('>默认</th>', false)
            ->assertDontSee('>编辑</a>', false);
    }

    public function test_quick_add_toggle_and_make_default(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->from(route('admin.sites.index'))
            ->post(route('admin.sites.store'), [
                'name' => '英文站',
                'domain' => 'en.example.com',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.sites.index'))
            ->assertSessionHas('status')
            ->assertSessionHas('site_quick_added');

        $row = Site::query()->where('domain', 'en.example.com')->first();
        $this->assertNotNull($row);
        $this->assertSame('英文站', $row->name);
        $this->assertTrue($row->is_active);
        $this->assertFalse($row->is_default);

        $this->get(route('admin.sites.index'))
            ->assertOk()
            ->assertSee('英文站')
            ->assertSee('去编辑')
            ->assertSee('设为默认');

        $this->post(route('admin.sites.toggle', $row))
            ->assertRedirect();
        $this->assertFalse($row->fresh()->is_active);

        $this->post(route('admin.sites.toggle', $row))->assertRedirect();
        $this->post(route('admin.sites.default', $row->fresh()))
            ->assertRedirect()
            ->assertSessionHas('status');
        $this->assertTrue($row->fresh()->is_default);
        $this->assertFalse(Site::query()->where('id', '!=', $row->id)->where('is_default', true)->exists());
    }

    public function test_create_form_is_name_first(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.sites.create'))
            ->assertOk()
            ->assertSee('名称')
            ->assertSee('域名')
            ->assertSee('不用加 http://')
            ->assertSee('外观主题')
            ->assertSee('跟站点设置走')
            ->assertSee('企业')
            ->assertSee('添加站点')
            ->assertDontSee('主题覆盖')
            ->assertDontSee('绑定域名（不含协议');
    }

    public function test_cannot_toggle_or_delete_default_site(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $site = Site::default();
        $this->assertNotNull($site);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.sites.toggle', $site))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertTrue($site->fresh()->is_active);

        $this->delete(route('admin.sites.destroy', $site))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertNotNull($site->fresh());
    }
}
