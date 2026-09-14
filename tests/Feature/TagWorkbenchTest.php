<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Site;
use App\Models\Cms\Tag;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagWorkbenchTest extends TestCase
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
        $tag = Tag::query()->first();
        $this->assertNotNull($tag);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.tags.index'))
            ->assertOk()
            ->assertSee('新增标签')
            ->assertSee('输入名称，如 PHP')
            ->assertSee('打开完整表单')
            ->assertSee(route('admin.tags.create'), false)
            ->assertSee('搜索标签名或网址标识')
            ->assertSee('Laravel')
            ->assertSee('1 篇')
            ->assertSee(route('admin.entries.index', ['tag_id' => $tag->id]), false)
            ->assertDontSee('新建标签')
            ->assertDontSee('不用先打开新建页')
            ->assertDontSee('>ID</th>', false)
            ->assertDontSee('>Slug</th>', false);
    }

    public function test_quick_add_and_search(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->from(route('admin.tags.index'))
            ->post(route('admin.tags.store'), ['title' => 'PHP'])
            ->assertRedirect(route('admin.tags.index'))
            ->assertSessionHas('status')
            ->assertSessionHas('tag_quick_added');

        $this->assertNotNull(Tag::query()->get()->first(fn (Tag $t) => $t->t('title') === 'PHP'));

        $this->get(route('admin.tags.index', ['q' => 'PHP']))
            ->assertOk()
            ->assertSee('PHP')
            ->assertDontSee('Laravel');

        $this->get(route('admin.tags.index', ['unused' => '1']))
            ->assertOk()
            ->assertSee('PHP')
            ->assertDontSee('Laravel');
    }

    public function test_tag_count_opens_filtered_entry_list(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $tag = Tag::query()->first();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.entries.index', ['tag_id' => $tag->id]))
            ->assertOk()
            ->assertSee('商城源码 Pro')
            ->assertSee('标签 Laravel')
            ->assertDontSee('智能网关');
    }

    public function test_create_form_is_a_short_name_first_flow(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.tags.create'))
            ->assertOk()
            ->assertSee('添加标签')
            ->assertSee('如 Laravel')
            ->assertSee('网址和其他语言')
            ->assertDontSee('简体中文')
            ->assertDontSee('data-locale-tab', false);

        $this->post(route('admin.tags.store'), [
            'locales' => ['zh_cn' => ['title' => 'Go', 'slug' => '']],
        ])->assertRedirect(route('admin.tags.index'))->assertSessionHas('status');

        $tag = Tag::query()->get()->first(fn (Tag $t) => $t->t('title') === 'Go');
        $this->assertNotNull($tag);
        $this->assertSame('Go', $tag->t('title', 'en'));
        $this->assertNotSame('', (string) $tag->t('slug'));
    }
}
