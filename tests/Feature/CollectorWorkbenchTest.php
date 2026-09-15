<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Category;
use App\Models\Cms\Collector;
use App\Models\Cms\CollectorLog;
use App\Models\Cms\ContentModel;
use App\Models\Cms\Entry;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CollectorWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
        Http::preventStrayRequests();
    }

    public function test_list_is_a_workbench(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $collector = Collector::query()->where('name', 'Laravel Blog RSS')->first();
        $this->assertNotNull($collector);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.collectors.index'))
            ->assertOk()
            ->assertSee('新建采集源')
            ->assertSee('运行日志')
            ->assertSee('Laravel Blog RSS')
            ->assertSee('RSS 订阅')
            ->assertSee('停用')
            ->assertSee('立即采集')
            ->assertSee(route('admin.collectors.edit', $collector), false)
            ->assertDontSee('新建采集器')
            ->assertDontSee('HTML 选择器')
            ->assertDontSee('CMS_COLLECT_MODE')
            ->assertDontSee('>ID</th>', false)
            ->assertDontSee('>类型</th>', false)
            ->assertDontSee('>状态</th>', false)
            ->assertDontSee('>rss</', false)
            ->assertDontSee('>编辑</a>', false);
    }

    public function test_create_form_puts_website_first(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.collectors.create'))
            ->assertOk()
            ->assertSee('新建采集源')
            ->assertSee('网站页面')
            ->assertSee('从列表页抓标题，可翻页、进详情取正文')
            ->assertSee('列表条目')
            ->assertSee('标题链接')
            ->assertSee('详情正文')
            ->assertSee('最多翻几页')
            ->assertSee('下一页按钮')
            ->assertSee('试抓')
            ->assertSee('添加采集源')
            ->assertSee('采集后直接发布')
            ->assertDontSee('HTML 选择器')
            ->assertDontSee('条目 item_selector')
            ->assertDontSee('新建采集器');
    }

    public function test_store_website_source_keeps_pagination(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $model = ContentModel::query()->where('slug', 'article')->first();
        $cat = Category::query()->where('slug->zh_cn', 'blog')->first();
        $this->assertNotNull($model);
        $this->assertNotNull($cat);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.collectors.store'), [
                'name' => '官网新闻',
                'type' => 'html',
                'source_url' => 'https://example.com/news',
                'model_id' => $model->id,
                'category_id' => $cat->id,
                'interval_minutes' => 90,
                'limit_items' => 20,
                'is_active' => '1',
                'item_selector' => 'article.post',
                'link_selector' => 'h2 a',
                'title_selector' => 'h2 a',
                'detail_content_selector' => 'article.body',
                'page_count' => 3,
                'page_url' => 'https://example.com/news?page={page}',
                'next_selector' => 'a.next',
            ])
            ->assertRedirect(route('admin.collectors.index'))
            ->assertSessionHas('status');

        $row = Collector::query()->where('name', '官网新闻')->first();
        $this->assertNotNull($row);
        $this->assertSame('html', $row->type);
        $this->assertSame(3, (int) ($row->options['page_count'] ?? 0));
        $this->assertSame('https://example.com/news?page={page}', $row->options['page_url'] ?? null);
        $this->assertSame('a.next', $row->options['next_selector'] ?? null);
        $this->assertFalse($row->publish_immediately);
    }

    public function test_toggle_and_preview_do_not_write_entries(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $collector = Collector::query()->where('name', 'Laravel Blog RSS')->first();
        $this->assertNotNull($collector);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.collectors.toggle', $collector))
            ->assertRedirect(route('admin.collectors.index'));
        $this->assertTrue($collector->fresh()->is_active);

        Http::fake([
            'https://example.com/blog' => Http::response(
                '<html><body><article class="post"><h2><a href="/p/one">Preview One</a></h2><p class="excerpt">Sum</p></article></body></html>',
                200
            ),
        ]);

        $this->postJson(route('admin.collectors.preview'), [
            'name' => '试抓',
            'type' => 'html',
            'source_url' => 'https://example.com/blog',
            'item_selector' => 'article.post',
            'link_selector' => 'h2 a',
            'title_selector' => 'h2 a',
            'summary_selector' => '.excerpt',
        ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.items.0.title', 'Preview One');

        $this->assertSame(0, Entry::query()->where('title->zh_cn', 'Preview One')->count());
        $this->assertSame(0, CollectorLog::query()->count());
    }

    public function test_logs_page_is_a_workbench(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.collectors.logs'))
            ->assertOk()
            ->assertSee('运行日志')
            ->assertSee('还没有运行记录')
            ->assertDontSee('>ID</th>', false)
            ->assertDontSee('>状态</th>', false);
    }
}
