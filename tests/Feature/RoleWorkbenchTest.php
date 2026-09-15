<?php

namespace Tests\Feature;

use App\Models\Admin\AdminRole;
use App\Models\Admin\AdminUser;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleWorkbenchTest extends TestCase
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
        $editor = AdminRole::query()->where('slug', 'editor')->first();
        $this->assertNotNull($editor);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('>权限</h1>', false)
            ->assertSee('新增角色')
            ->assertSee('名称，如 审核员')
            ->assertSee('超级管理员')
            ->assertSee('能做所有事')
            ->assertSee('编辑')
            ->assertSee('运营')
            ->assertSee('editor')
            ->assertSee('项菜单')
            ->assertSee('1 人')
            ->assertSee('还没人')
            ->assertSee(route('admin.roles.edit', $editor), false)
            ->assertSee(route('admin.admin-users.index', ['role' => $editor->id]), false)
            ->assertDontSee('新建角色')
            ->assertDontSee('>ID</th>', false)
            ->assertDontSee('>标识</th>', false)
            ->assertDontSee('>类型</th>', false)
            ->assertDontSee('>权限数</th>', false)
            ->assertDontSee('btn-danger')
            ->assertDontSee('btn-sm btn-muted">编辑');
    }

    public function test_quick_add_and_cannot_delete_used_or_super(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $super = AdminRole::query()->where('slug', 'super_admin')->first();
        $editor = AdminRole::query()->where('slug', 'editor')->first();
        $operator = AdminRole::query()->where('slug', 'operator')->first();
        $this->assertNotNull($super);
        $this->assertNotNull($editor);
        $this->assertNotNull($operator);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.roles.index'))
            ->post(route('admin.roles.store'), ['name' => '审核员'])
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('status')
            ->assertSessionHas('role_quick_added');

        $row = AdminRole::query()->where('name', '审核员')->first();
        $this->assertNotNull($row);
        $this->assertNotSame('', (string) $row->slug);
        $this->assertFalse($row->is_super);

        $this->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('审核员');

        $this->delete(route('admin.roles.destroy', $super))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertNotNull($super->fresh());

        $this->delete(route('admin.roles.destroy', $editor))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertNotNull($editor->fresh());

        $this->delete(route('admin.roles.destroy', $operator))
            ->assertRedirect()
            ->assertSessionHas('status');
        $this->assertNull($operator->fresh());
    }

    public function test_create_form_is_name_first(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.roles.create'))
            ->assertOk()
            ->assertSee('名称')
            ->assertSee('标识码')
            ->assertSee('能进哪些菜单')
            ->assertSee('内容管理')
            ->assertSee('地图')
            ->assertSee('添加角色')
            ->assertDontSee('标识（slug')
            ->assertDontSee('模块权限')
            ->assertDontSee('超级管理员（拥有全部权限）')
            ->assertDontSee('自定义');
    }
}
