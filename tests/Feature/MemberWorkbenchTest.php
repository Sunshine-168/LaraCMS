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

class MemberWorkbenchTest extends TestCase
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
        $member = Member::query()->where('email', 'member@example.com')->first();
        $this->assertNotNull($member);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.members.index'))
            ->assertOk()
            ->assertSee('会员管理')
            ->assertSee('新建会员')
            ->assertSee('会员分组')
            ->assertSee('演示会员')
            ->assertSee('member@example.com')
            ->assertSee('普通会员')
            ->assertSee(route('admin.members.edit', $member), false)
            ->assertSee('搜索姓名、邮箱或手机')
            ->assertDontSee('前台会员')
            ->assertDontSee('>ID</th>', false)
            ->assertDontSee('>状态</th>', false)
            ->assertDontSee('>邮箱</th>', false)
            ->assertDontSee('>启用</td>', false)
            ->assertDontSee('>编辑</a>', false);
    }

    public function test_search_group_filter_and_toggle(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $member = Member::query()->where('email', 'member@example.com')->first();
        $group = MemberGroup::query()->where('slug', 'default')->first();
        $this->assertNotNull($member);
        $this->assertNotNull($group);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.members.index', ['q' => 'member@example']))
            ->assertOk()
            ->assertSee('演示会员');

        $this->get(route('admin.members.index', ['q' => 'nobody-here']))
            ->assertOk()
            ->assertSee('没有符合条件的会员');

        $this->get(route('admin.members.index', ['group' => (string) $group->id]))
            ->assertOk()
            ->assertSee('演示会员');

        $this->get(route('admin.members.index', ['group' => 'none']))
            ->assertOk()
            ->assertSee('没有符合条件的会员');

        $this->post(route('admin.members.toggle', $member))
            ->assertRedirect();
        $this->assertFalse($member->fresh()->is_active);

        $this->get(route('admin.members.index'))
            ->assertOk()
            ->assertSee('>停用</span>', false);

        $this->get(route('admin.members.index', ['off' => '1']))
            ->assertOk()
            ->assertSee('演示会员');
    }

    public function test_create_form_is_name_first(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.members.create'))
            ->assertOk()
            ->assertSee('名称')
            ->assertSee('登录邮箱')
            ->assertSee('添加会员')
            ->assertSee('启用，可以登录前台')
            ->assertSee('普通会员')
            ->assertDontSee('密码（留空不改）');
    }

    public function test_store_keeps_default_group(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $group = MemberGroup::query()->where('slug', 'default')->first();
        $this->assertNotNull($group);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.members.store'), [
                'name' => '李四',
                'email' => 'lisi@example.com',
                'password' => 'Password1',
                'group_id' => $group->id,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.members.index'))
            ->assertSessionHas('status');

        $row = Member::query()->where('email', 'lisi@example.com')->first();
        $this->assertNotNull($row);
        $this->assertSame('李四', $row->name);
        $this->assertSame($group->id, $row->group_id);
        $this->assertTrue($row->is_active);
    }
}
