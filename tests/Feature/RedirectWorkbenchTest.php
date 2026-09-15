<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Redirect;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_empty_list_is_a_workbench(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.redirects.index'))
            ->assertOk()
            ->assertSee('添加跳转')
            ->assertSee('旧地址，如 /old-news')
            ->assertSee('打开完整表单')
            ->assertSee('还没有跳转')
            ->assertSee(route('admin.redirects.create'), false)
            ->assertDontSee('新建规则')
            ->assertDontSee('暂无重定向规则')
            ->assertDontSee('精确匹配路径')
            ->assertDontSee('>源路径</th>', false)
            ->assertDontSee('>状态码</th>', false)
            ->assertDontSee('>启用</th>', false);
    }

    public function test_quick_add_toggle_and_search(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->from(route('admin.redirects.index'))
            ->post(route('admin.redirects.store'), [
                'source_path' => 'old-news',
                'target_url' => '/news',
            ])
            ->assertRedirect(route('admin.redirects.index'))
            ->assertSessionHas('status')
            ->assertSessionHas('redirect_quick_added');

        $row = Redirect::query()->where('source_path', '/old-news')->first();
        $this->assertNotNull($row);
        $this->assertSame('/news', $row->target_url);
        $this->assertSame(301, (int) $row->status_code);
        $this->assertTrue($row->is_active);

        $this->get(route('admin.redirects.index'))
            ->assertOk()
            ->assertSee('/old-news')
            ->assertSee('/news')
            ->assertSee('还没走过')
            ->assertDontSee('>301</td>', false)
            ->assertDontSee('>编辑</a>', false)
            ->assertDontSee('>是</td>', false);

        $this->post(route('admin.redirects.toggle', $row))
            ->assertRedirect();
        $this->assertFalse($row->fresh()->is_active);

        $this->get(route('admin.redirects.index'))
            ->assertOk()
            ->assertSee('>停用</span>', false);

        $this->get(route('admin.redirects.index', ['q' => 'old-news']))
            ->assertOk()
            ->assertSee('/old-news');

        $this->get(route('admin.redirects.index', ['q' => 'does-not-exist']))
            ->assertOk()
            ->assertSee('没有符合条件的跳转');
    }

    public function test_create_form_is_name_first(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.redirects.create'))
            ->assertOk()
            ->assertSee('旧地址')
            ->assertSee('新地址')
            ->assertSee('永久搬走')
            ->assertSee('先临时跳转')
            ->assertSee('添加跳转')
            ->assertSee('启用，访客打开旧地址会跳走')
            ->assertDontSee('源路径（如')
            ->assertDontSee('301 永久')
            ->assertDontSee('状态码');
    }

    public function test_temporary_shows_as_badge_not_status_column(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        Redirect::query()->create([
            'source_path' => '/promo',
            'target_url' => '/news',
            'status_code' => 302,
            'is_active' => true,
            'note' => '活动页',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.redirects.index'))
            ->assertOk()
            ->assertSee('/promo')
            ->assertSee('临时')
            ->assertSee('活动页')
            ->assertDontSee('>302</td>', false)
            ->assertDontSee('>状态码</th>', false);
    }
}
