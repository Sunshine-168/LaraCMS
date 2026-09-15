<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Recommend;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendWorkbenchTest extends TestCase
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
        $banner = Recommend::query()->where('name', 'home_banner')->first();
        $this->assertNotNull($banner);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.recommends.index'))
            ->assertOk()
            ->assertSee('首页轮播')
            ->assertSee('home_banner')
            ->assertSee('图片')
            ->assertSee('1 条')
            ->assertSee('2 条')
            ->assertSee('自动取数')
            ->assertSee('自动最多 6 篇')
            ->assertSee(route('admin.recommends.edit', $banner), false)
            ->assertSee('新建推荐位')
            ->assertDontSee('>标识</th>', false)
            ->assertDontSee('>image</td>', false)
            ->assertDontSee('>query</td>', false)
            ->assertDontSee('>编辑</a>', false);
    }

    public function test_create_form_is_name_first_and_keeps_theme_hint(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.recommends.create'))
            ->assertOk()
            ->assertSee('名称')
            ->assertSee('标识码')
            ->assertSee('@cmsAdv', false)
            ->assertSee('添加推荐位')
            ->assertSee('data-media-pick', false)
            ->assertDontSee('标识 name');
    }

    public function test_toggle_hides_from_front_call(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $block = Recommend::query()->where('name', 'partners')->first();
        $this->assertNotNull($block);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.recommends.toggle', $block))
            ->assertRedirect(route('admin.recommends.index'));

        $this->assertFalse($block->fresh()->is_active);
        $this->get(route('admin.recommends.index'))
            ->assertOk()
            ->assertSee('>停用</span>', false);
    }
}
