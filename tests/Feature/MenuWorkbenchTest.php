<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Menu;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuWorkbenchTest extends TestCase
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
        $menu = Menu::query()->where('location', 'header')->first();
        $this->assertNotNull($menu);
        $n = $menu->items()->count();
        $this->assertGreaterThan(0, $n);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.menus.index'))
            ->assertOk()
            ->assertSee('导航菜单')
            ->assertSee('主导航')
            ->assertSee('页头')
            ->assertSee($n.' 项')
            ->assertSee(route('admin.menus.edit', $menu), false)
            ->assertSee('新建菜单')
            ->assertDontSee('>ID</th>', false)
            ->assertDontSee('>位置</th>', false)
            ->assertDontSee('>启用</th>', false)
            ->assertDontSee('>header</td>', false)
            ->assertDontSee('>是</td>', false)
            ->assertDontSee('>编辑</a>', false)
            ->assertDontSee('class="btn btn-sm btn-danger"', false);
    }

    public function test_form_is_name_first_and_keeps_theme_hint(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.menus.create'))
            ->assertOk()
            ->assertSee('名称')
            ->assertSee('出现位置')
            ->assertSee('@cmsMenu', false)
            ->assertSee('>链接</option>', false)
            ->assertSee('>栏目</option>', false)
            ->assertSee('>文档</option>', false)
            ->assertSee('增加一条')
            ->assertSee('添加菜单')
            ->assertSee('menu-items-table', false)
            ->assertSee('>标题</th>', false)
            ->assertDontSee('父级行号')
            ->assertDontSee('引用 ID')
            ->assertDontSee('>中文标题</label>', false)
            ->assertDontSee('>英文标题</label>', false)
            ->assertDontSee('>URL</option>', false)
            ->assertDontSee('>栏目 ID</option>', false);
    }

    public function test_edit_items_are_a_compact_table(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $menu = Menu::query()->where('location', 'header')->with('items')->first();
        $this->assertNotNull($menu);
        $first = $menu->items->first();
        $this->assertNotNull($first);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.menus.edit', $menu))
            ->assertOk()
            ->assertSee('menu-items-table', false)
            ->assertSee($first->t('title'))
            ->assertSee('>标题</th>', false)
            ->assertSee('>指向</th>', false)
            ->assertDontSee('>中文标题</label>', false);
    }

    public function test_toggle_hides_from_front(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $menu = Menu::query()->where('location', 'header')->first();
        $this->assertNotNull($menu);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.menus.toggle', $menu))
            ->assertRedirect(route('admin.menus.index'));

        $this->assertFalse($menu->fresh()->is_active);
        $this->get(route('admin.menus.index'))
            ->assertOk()
            ->assertSee('>停用</span>', false);
    }
}
