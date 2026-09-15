<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Entry;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntryListWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_list_is_a_workbench_not_a_raw_table(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $live = Entry::query()->published()->first();
        $this->assertNotNull($live);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.entries.index'))
            ->assertOk()
            ->assertSee('全部内容')
            ->assertSee('id="entryTypeTabs"', false)
            ->assertSee('id="entryTypeMore"', false)
            ->assertSee('搜索标题、正文或 ID')
            ->assertSee('>栏目</span>', false)
            ->assertSee('>状态</span>', false)
            ->assertSee('form="batchForm"', false)
            ->assertSee('name="action" value="publish"', false)
            ->assertSee('id="batchBar" hidden', false)
            ->assertSee('更多操作')
            ->assertSee('create-menu', false)
            ->assertDontSee('先勾选下面的方框')
            ->assertDontSee('批量发布')
            ->assertDontSee('>执行</button>', false)
            ->assertDontSee('>Flags</th>', false)
            ->assertDontSee('>投稿</th>', false)
            ->assertDontSee('>状态</th>', false)
            ->assertDontSee('>编辑</a>', false)
            ->assertSee('entry-row-title', false)
            ->assertSee(route('admin.entries.edit', $live), false)
            ->assertSee('>前台</a>', false)
            ->assertSee('>回收站</button>', false)
            ->assertSee('>推荐</span>', false);
    }

    public function test_create_asks_for_type_until_a_model_tab_is_chosen(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $article = \App\Models\Cms\ContentModel::query()->where('slug', 'article')->first();
        $this->assertNotNull($article);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.entries.index'))
            ->assertOk()
            ->assertSee('create-menu', false)
            ->assertSee(route('admin.entries.create', ['model_id' => $article->id]), false)
            ->assertDontSee('新建文章');

        $this->get(route('admin.entries.index', ['model_id' => $article->id]))
            ->assertOk()
            ->assertSee('新建文章')
            ->assertDontSee('create-menu', false);
    }

    public function test_search_finds_title_and_id(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.entries.index', ['q' => '关于我们']))
            ->assertOk()
            ->assertSee('关于我们')
            ->assertDontSee('智能网关');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.entries.index', ['q' => '12']))
            ->assertOk()
            ->assertSee('示例德比')
            ->assertDontSee('智能网关');
    }

    public function test_published_filter_hides_scheduled_and_scheduled_filter_shows_it(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $entry = Entry::query()->get()->first(fn (Entry $e) => $e->t('slug') === 'hello-blog');
        $this->assertNotNull($entry);
        $entry->status = 'published';
        $entry->published_at = now()->addDay();
        $entry->save();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.entries.index', ['status' => 'published']))
            ->assertOk()
            ->assertDontSee('写给站长的第一篇博客');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.entries.index', ['status' => 'scheduled']))
            ->assertOk()
            ->assertSee('写给站长的第一篇博客')
            ->assertSee('>定时</span>', false);
    }

    public function test_editor_cannot_see_row_publish_but_super_can(): void
    {
        $pending = Entry::query()->get()->first(fn (Entry $e) => $e->t('slug') === 'cms-launch');
        $this->assertNotNull($pending);
        $pending->status = 'pending';
        $pending->published_at = null;
        $pending->save();

        $editor = AdminUser::query()->where('email', 'editor@example.com')->first();
        $this->actingAs($editor, 'admin')
            ->get(route('admin.entries.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('综合 CMS 上线')
            ->assertDontSee('name="action" value="publish"', false)
            ->assertDontSee('>发布</button>', false);

        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.entries.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('>发布</button>', false);
    }

    public function test_batch_keeps_current_filters_on_redirect(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $entry = Entry::query()->first();
        $this->assertNotNull($entry);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.entries.index', ['q' => 'CMS', 'status' => 'published']))
            ->post(route('admin.entries.batch'), [
                'action' => 'pending',
                'ids' => [$entry->id],
                'keep_status' => 'published',
                'keep_q' => 'CMS',
            ])
            ->assertRedirect(route('admin.entries.index', ['status' => 'published', 'q' => 'CMS']))
            ->assertSessionHas('status');
    }

    public function test_empty_search_explains_and_offers_clear(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.entries.index', ['q' => '绝对不会出现的关键词XYZ']))
            ->assertOk()
            ->assertSee('没有符合条件的内容')
            ->assertSee('清除筛选');
    }
}
