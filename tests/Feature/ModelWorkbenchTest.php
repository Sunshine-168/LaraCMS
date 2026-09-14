<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\ContentModel;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelWorkbenchTest extends TestCase
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
        $article = ContentModel::query()->where('slug', 'article')->first();
        $this->assertNotNull($article);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.models.index'))
            ->assertOk()
            ->assertSee('文章')
            ->assertSee('来源')
            ->assertSee('单行文本')
            ->assertSee('价格')
            ->assertSee('薪资')
            ->assertSee('加字段')
            ->assertSee('更多')
            ->assertSee(route('admin.entries.index', ['model_id' => $article->id]), false)
            ->assertSee('还没有扩展字段')
            ->assertDontSee('(article / list)')
            ->assertDontSee('>字段</th><th>标签</th>', false)
            ->assertDontSee('删模型')
            ->assertDontSee('全栏目')
            ->assertDontSee('导入 JSON');
    }

    public function test_create_form_is_name_first(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.models.create'))
            ->assertOk()
            ->assertSee('名称')
            ->assertSee('标识码')
            ->assertSee('列表（多篇，如文章、产品）')
            ->assertSee('模板和开关')
            ->assertDontSee('列表 list');
    }

    public function test_field_form_uses_chinese_types(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $article = ContentModel::query()->where('slug', 'article')->first();
        $this->assertNotNull($article);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.models.fields.create', $article))
            ->assertOk()
            ->assertSee('显示名')
            ->assertSee('单行文本')
            ->assertSee('添加字段')
            ->assertDontSee('字段名（英文）');
    }
}
