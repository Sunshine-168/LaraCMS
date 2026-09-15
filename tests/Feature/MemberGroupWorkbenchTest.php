<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Member;
use App\Models\Cms\MemberGroup;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberGroupWorkbenchTest extends TestCase
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
        $group = MemberGroup::query()->where('slug', 'default')->first();
        $this->assertNotNull($group);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.member-groups.index'))
            ->assertOk()
            ->assertSee('会员分组')
            ->assertSee('新增分组')
            ->assertSee('显示名，如 普通会员')
            ->assertSee('普通会员')
            ->assertSee('default')
            ->assertSee('1 人')
            ->assertSee(route('admin.members.index', ['group' => $group->id]), false)
            ->assertSee(route('admin.member-groups.edit', $group), false)
            ->assertSee('停用')
            ->assertDontSee('新建分组')
            ->assertDontSee('>ID</th>', false)
            ->assertDontSee('>标识</th>', false)
            ->assertDontSee('>排序</th>', false)
            ->assertDontSee('>状态</th>', false)
            ->assertDontSee('>编辑</a>', false);
    }

    public function test_quick_add_toggle_and_move(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->post(route('admin.member-groups.store'), ['name' => '黄金会员', 'slug' => 'gold'])
            ->assertRedirect(route('admin.member-groups.index'))
            ->assertSessionHas('status')
            ->assertSessionHas('group_quick_added');

        $group = MemberGroup::query()->where('slug', 'gold')->first();
        $this->assertNotNull($group);
        $this->assertSame('黄金会员', $group->name);
        $this->assertTrue($group->is_active);
        $this->assertSame(MemberGroup::permissionKeys(), $group->permissions);

        $this->get(route('admin.member-groups.index'))
            ->assertOk()
            ->assertSee('下移')
            ->assertSee('上移');

        $this->post(route('admin.member-groups.toggle', $group))
            ->assertRedirect(route('admin.member-groups.index'));
        $this->assertFalse($group->fresh()->is_active);

        $first = MemberGroup::query()->orderBy('sort')->orderBy('id')->first();
        $this->assertNotNull($first);
        $this->post(route('admin.member-groups.move', $first), ['dir' => 'down'])
            ->assertRedirect(route('admin.member-groups.index'));
        $this->assertNotSame($first->id, MemberGroup::query()->orderBy('sort')->orderBy('id')->value('id'));
    }

    public function test_member_count_opens_filtered_list(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $group = MemberGroup::query()->where('slug', 'default')->first();
        $this->assertNotNull($group);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.members.index', ['group' => $group->id]))
            ->assertOk()
            ->assertSee('演示会员');
    }

    public function test_edit_form_is_name_first(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $group = MemberGroup::query()->where('slug', 'default')->first();
        $this->assertNotNull($group);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.member-groups.edit', $group))
            ->assertOk()
            ->assertSee('显示名称')
            ->assertSee('标识码')
            ->assertSee('前台能做什么')
            ->assertSee('前台投稿')
            ->assertSee('启用，这组权限生效')
            ->assertSee('1 人')
            ->assertDontSee('标识（slug')
            ->assertDontSee('>排序</label>', false);
    }

    public function test_inactive_group_blocks_member_permission(): void
    {
        $member = Member::query()->where('email', 'member@example.com')->first();
        $this->assertNotNull($member);
        $this->assertTrue($member->canMember('download'));

        $group = $member->group;
        $this->assertNotNull($group);
        $group->is_active = false;
        $group->save();
        $member->unsetRelation('group');

        $this->assertFalse($member->fresh()->canMember('download'));
    }
}
