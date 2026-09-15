<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\CmsForm;
use App\Models\Cms\FormSubmission;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormInboxWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_empty_inbox_explains(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.form-submissions.index'))
            ->assertOk()
            ->assertSee('还没有留言')
            ->assertSee('去表单')
            ->assertDontSee('暂无留言')
            ->assertDontSee('>ID</th>', false)
            ->assertDontSee('未读 0');
    }

    public function test_list_is_a_workbench(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $form = CmsForm::query()->where('slug', 'contact')->first();
        $this->assertNotNull($form);
        $row = FormSubmission::query()->create([
            'form_id' => $form->id,
            'data' => ['name' => '张三', 'email' => 'a@example.com', 'message' => '想咨询合作'],
            'ip' => '127.0.0.1',
            'is_read' => false,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.form-submissions.index'))
            ->assertOk()
            ->assertSee('张三')
            ->assertSee('想咨询合作')
            ->assertSee('联系我们')
            ->assertSee('>未读</span>', false)
            ->assertSee(route('admin.form-submissions.show', $row), false)
            ->assertSee('搜索姓名、留言或邮箱')
            ->assertDontSee('>ID</th>', false)
            ->assertDontSee('>IP</th>', false)
            ->assertDontSee('>查看</a>', false)
            ->assertDontSee('class="btn btn-sm btn-danger"', false)
            ->assertDontSee('全部状态');
    }

    public function test_unread_filter_and_search(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $form = CmsForm::query()->where('slug', 'contact')->first();
        $this->assertNotNull($form);
        FormSubmission::query()->create([
            'form_id' => $form->id,
            'data' => ['name' => '未读客', 'message' => '请回电'],
            'is_read' => false,
        ]);
        FormSubmission::query()->create([
            'form_id' => $form->id,
            'data' => ['name' => '已读客', 'message' => '谢谢'],
            'is_read' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.form-submissions.index', ['status' => 'unread']))
            ->assertOk()
            ->assertSee('未读客')
            ->assertDontSee('已读客');

        $this->get(route('admin.form-submissions.index', ['q' => '请回电']))
            ->assertOk()
            ->assertSee('未读客')
            ->assertDontSee('已读客');
    }
}
