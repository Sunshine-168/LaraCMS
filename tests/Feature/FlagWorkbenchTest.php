<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\CmsFlag;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlagWorkbenchTest extends TestCase
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
        $flag = CmsFlag::query()->where('name', 'recommend')->first();
        $this->assertNotNull($flag);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.flags.index'))
            ->assertOk()
            ->assertSee('推荐属性')
            ->assertSee('新增推荐属性')
            ->assertSee('显示名，如 置顶')
            ->assertSee('置顶')
            ->assertSee('recommend')
            ->assertSee('1 篇')
            ->assertSee(route('admin.entries.index', ['flag' => 'recommend']), false)
            ->assertSee('上移')
            ->assertSee('停用')
            ->assertDontSee('Flags 字典')
            ->assertDontSee('新建 Flag')
            ->assertDontSee('>标识</th>', false)
            ->assertDontSee('>排序</th>', false)
            ->assertDontSee('>状态</th>', false);
    }

    public function test_quick_add_toggle_and_move(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->post(route('admin.flags.store'), ['title' => '视频', 'name' => 'video'])
            ->assertRedirect(route('admin.flags.index'))
            ->assertSessionHas('status')
            ->assertSessionHas('flag_quick_added');

        $flag = CmsFlag::query()->where('name', 'video')->first();
        $this->assertNotNull($flag);
        $this->assertSame('视频', $flag->title);
        $this->assertTrue($flag->is_active);

        $this->post(route('admin.flags.toggle', $flag))
            ->assertRedirect(route('admin.flags.index'));
        $this->assertFalse($flag->fresh()->is_active);

        $first = CmsFlag::query()->orderBy('sort')->orderBy('id')->first();
        $this->assertNotNull($first);
        $this->post(route('admin.flags.move', $first), ['dir' => 'down'])
            ->assertRedirect(route('admin.flags.index'));
        $this->assertNotSame($first->id, CmsFlag::query()->orderBy('sort')->orderBy('id')->value('id'));
    }

    public function test_usage_opens_filtered_entry_list(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.entries.index', ['flag' => 'recommend']))
            ->assertOk()
            ->assertSee('写给站长的第一篇博客')
            ->assertSee('属性 推荐')
            ->assertDontSee('智能网关');
    }

    public function test_edit_form_is_name_first(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $flag = CmsFlag::query()->where('name', 'top')->first();
        $this->assertNotNull($flag);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.flags.edit', $flag))
            ->assertOk()
            ->assertSee('显示名称')
            ->assertSee('标识码')
            ->assertSee('启用，出现在写内容页')
            ->assertDontSee('标识 name');
    }
}
