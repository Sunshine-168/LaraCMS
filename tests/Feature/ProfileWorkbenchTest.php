<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_account_menu_links_to_password_page(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('修改密码')
            ->assertSee(route('admin.profile.edit'), false);
    }

    public function test_self_password_page_is_a_workbench(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('修改密码')
            ->assertSee('当前密码')
            ->assertSee('新密码')
            ->assertSee('再输一次新密码')
            ->assertSee('admin@example.com')
            ->assertSee('超级管理员')
            ->assertDontSee('新增管理员');
    }

    public function test_editor_can_change_own_password(): void
    {
        $editor = AdminUser::query()->where('email', 'editor@example.com')->first();
        $this->assertNotNull($editor);

        $this->actingAs($editor, 'admin')
            ->from(route('admin.profile.edit'))
            ->put(route('admin.profile.password'), [
                'current_password' => 'Editor@12345',
                'password' => 'Editor@67890',
                'password_confirmation' => 'Editor@67890',
            ])
            ->assertRedirect(route('admin.profile.edit'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('Editor@67890', $editor->fresh()->password));
        $this->assertFalse(Hash::check('Editor@12345', $editor->fresh()->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->from(route('admin.profile.edit'))
            ->put(route('admin.profile.password'), [
                'current_password' => 'wrong-pass',
                'password' => 'Admin@67890',
                'password_confirmation' => 'Admin@67890',
            ])
            ->assertRedirect(route('admin.profile.edit'))
            ->assertSessionHas('error');

        $this->assertTrue(Hash::check('Admin@12345', $admin->fresh()->password));
    }
}
