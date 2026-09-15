<?php

namespace Tests\Feature;

use App\Models\Admin\AdminRole;
use App\Models\Admin\AdminUser;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserWorkbenchTest extends TestCase
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
        $editor = AdminUser::query()->where('email', 'editor@example.com')->first();
        $this->assertNotNull($admin);
        $this->assertNotNull($editor);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.admin-users.index'))
            ->assertOk()
            ->assertSee('管理员')
            ->assertSee('新增管理员')
            ->assertSee('名称，如 小王')
            ->assertSee('超级管理员')
            ->assertSee('编辑')
            ->assertSee('admin@example.com')
            ->assertSee('当前账号')
            ->assertSee('能做所有事')
            ->assertSee(route('admin.admin-users.edit', $editor), false)
            ->assertSee('停用')
            ->assertDontSee('新建管理员')
            ->assertDontSee('>ID</th>', false)
            ->assertDontSee('>邮箱</th>', false)
            ->assertDontSee('>状态</th>', false)
            ->assertDontSee('btn-danger')
            ->assertDontSee('btn-sm btn-muted">编辑');
    }

    public function test_quick_add_and_toggle(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $editorRole = AdminRole::query()->where('slug', 'editor')->first();
        $this->assertNotNull($editorRole);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.admin-users.index'))
            ->post(route('admin.admin-users.store'), [
                'name' => '运营',
                'email' => 'ops@example.com',
                'password' => 'OpsPass@123',
                'role_id' => $editorRole->id,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.admin-users.index'))
            ->assertSessionHas('status')
            ->assertSessionHas('admin_user_quick_added');

        $row = AdminUser::query()->where('email', 'ops@example.com')->first();
        $this->assertNotNull($row);
        $this->assertSame('运营', $row->name);
        $this->assertTrue($row->is_active);
        $this->assertTrue($row->sites->contains('id', Site::default()->id));

        $this->get(route('admin.admin-users.index'))
            ->assertOk()
            ->assertSee('运营')
            ->assertSee('ops@example.com');

        $this->post(route('admin.admin-users.toggle', $row))
            ->assertRedirect()
            ->assertSessionHas('status');
        $this->assertFalse($row->fresh()->is_active);

        $this->get(route('admin.admin-users.index', ['off' => '1']))
            ->assertOk()
            ->assertSee('运营')
            ->assertDontSee('editor@example.com');
    }

    public function test_create_form_is_name_first(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.admin-users.create'))
            ->assertOk()
            ->assertSee('名称')
            ->assertSee('登录邮箱')
            ->assertSee('能管哪些网站')
            ->assertSee('能改哪些栏目')
            ->assertSee('添加管理员')
            ->assertDontSee('可管理站点')
            ->assertDontSee('栏目授权')
            ->assertDontSee('（全部权限）');
    }

    public function test_cannot_toggle_or_delete_self_or_last_super(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.admin-users.toggle', $admin))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertTrue($admin->fresh()->is_active);

        $this->delete(route('admin.admin-users.destroy', $admin))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertNotNull($admin->fresh());
    }

    public function test_last_login_ip_opens_logs(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin);
        $admin->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => '203.0.113.8',
        ])->save();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.admin-users.index'))
            ->assertOk()
            ->assertSee('上次登录')
            ->assertSee('203.0.113.8')
            ->assertSee('保留地址')
            ->assertSee(route('admin.logs.index', ['ip' => '203.0.113.8']), false);
    }
}
