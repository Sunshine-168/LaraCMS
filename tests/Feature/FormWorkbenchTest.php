<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\CmsForm;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormWorkbenchTest extends TestCase
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
        $form = CmsForm::query()->where('slug', 'contact')->first();
        $this->assertNotNull($form);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.forms.index'))
            ->assertOk()
            ->assertSee('表单定义')
            ->assertSee('联系我们')
            ->assertSee('contact')
            ->assertSee('3 个字段')
            ->assertSee('还没有留言')
            ->assertSee(route('admin.forms.edit', $form), false)
            ->assertSee('新建表单')
            ->assertSee('留言收件箱')
            ->assertSee('@cmsForm', false)
            ->assertDontSee('>ID</th>', false)
            ->assertDontSee('>Slug</th>', false)
            ->assertDontSee('>状态</th>', false)
            ->assertDontSee('>启用</span>', false)
            ->assertDontSee('>编辑</a>', false)
            ->assertDontSee('class="btn btn-sm btn-danger"', false);
    }

    public function test_form_is_name_first_and_keeps_theme_hint(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.forms.create'))
            ->assertOk()
            ->assertSee('名称')
            ->assertSee('标识码')
            ->assertSee('@cmsForm', false)
            ->assertSee('显示名')
            ->assertSee('>单行文本</option>', false)
            ->assertSee('>邮箱</option>', false)
            ->assertSee('>多行文本</option>', false)
            ->assertSee('加字段')
            ->assertSee('添加表单')
            ->assertDontSee('标识 slug')
            ->assertDontSee('标识 name')
            ->assertDontSee('>text</option>', false);
    }

    public function test_toggle_hides_from_front(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $form = CmsForm::query()->where('slug', 'contact')->first();
        $this->assertNotNull($form);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.forms.toggle', $form))
            ->assertRedirect(route('admin.forms.index'));

        $this->assertFalse($form->fresh()->is_active);
        $this->get(route('admin.forms.index'))
            ->assertOk()
            ->assertSee('>停用</span>', false);
    }
}
