<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Category;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use App\Support\Locale;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTreeWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_index_shows_tree_and_add_child_not_raw_fields(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $news = Category::query()->get()->first(fn (Category $c) => $c->t('slug') === 'news');
        $this->assertNotNull($news);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('添加下级')
            ->assertSee('>列表</span>', false)
            ->assertDontSee('>Slug</th>', false)
            ->assertDontSee('>list</span>', false)
            ->assertSee(route('admin.categories.create', ['parent_id' => $news->id]), false);
    }

    public function test_reserved_slug_sitemap_is_rejected(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $news = Category::query()->get()->first(fn (Category $c) => $c->t('slug') === 'news');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.categories.create'))
            ->assertOk()
            ->assertSee('系统路径');

        $this->post(route('admin.categories.store'), $this->categoryPayload($news->model_id, '地图栏', 'sitemap'))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('error');
        $this->assertNull(Category::query()->get()->first(fn (Category $c) => $c->t('slug') === 'sitemap'));
    }

    public function test_child_is_indented_under_parent(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $news = Category::query()->get()->first(fn (Category $c) => $c->t('slug') === 'news');
        $this->actingAs($admin, 'admin')
            ->post(route('admin.categories.store'), $this->categoryPayload($news->model_id, '公司新闻', 'company-news', $news->id))
            ->assertRedirect(route('admin.categories.index'));

        $child = Category::query()->get()->first(fn (Category $c) => $c->t('slug') === 'company-news');
        $this->assertNotNull($child);
        $this->assertSame((int) $news->id, (int) $child->parent_id);

        $html = $this->get(route('admin.categories.index'))->assertOk()->getContent();
        $this->assertStringContainsString('cat-branch', $html);
        $this->assertStringContainsString('公司新闻', $html);
        $this->assertTrue(strpos($html, '新闻中心') < strpos($html, '公司新闻'));
    }

    public function test_create_with_parent_preselects_and_cannot_nest_under_own_child(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $news = Category::query()->get()->first(fn (Category $c) => $c->t('slug') === 'news');
        $this->actingAs($admin, 'admin');

        $this->get(route('admin.categories.create', ['parent_id' => $news->id]))
            ->assertOk()
            ->assertSee('新建下级栏目')
            ->assertSee('将建在「新闻中心」下面')
            ->assertSee('selected', false);

        $this->post(route('admin.categories.store'), $this->categoryPayload($news->model_id, '公司新闻', 'company-news', $news->id));
        $child = Category::query()->get()->first(fn (Category $c) => $c->t('slug') === 'company-news');

        $this->put(route('admin.categories.update', $news), $this->categoryPayload($news->model_id, '新闻中心', 'news', $child->id))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('error');
        $this->assertSame(null, $news->fresh()->parent_id);
    }

    public function test_delete_blocked_when_child_exists(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $news = Category::query()->get()->first(fn (Category $c) => $c->t('slug') === 'news');
        $this->actingAs($admin, 'admin')
            ->post(route('admin.categories.store'), $this->categoryPayload($news->model_id, '公司新闻', 'company-news', $news->id));

        $this->delete(route('admin.categories.destroy', $news))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertNotNull(Category::query()->find($news->id));
    }

    /** @return array<string, mixed> */
    protected function categoryPayload(?int $modelId, string $title, string $slug, ?int $parentId = null): array
    {
        $locales = [];
        foreach (Locale::codes() as $code) {
            $locales[$code] = [
                'title' => $title,
                'slug' => $slug,
                'meta_title' => '',
                'meta_description' => '',
            ];
        }

        return [
            'model_id' => $modelId,
            'type' => 'list',
            'parent_id' => $parentId,
            'page_size' => 10,
            'sort_order' => 0,
            'status' => 'published',
            'locales' => $locales,
        ];
    }
}
