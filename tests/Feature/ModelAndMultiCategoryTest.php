<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Category;
use App\Models\Cms\ContentModel;
use App\Models\Cms\Entry;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ModelAndMultiCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_model_copy_and_export_import(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $model = ContentModel::query()->where('slug', 'article')->first();
        $this->assertNotNull($model);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.models.copy', $model))
            ->assertRedirect();
        $this->assertTrue(ContentModel::query()->where('slug', 'article_copy')->exists());

        $export = $this->actingAs($admin, 'admin')
            ->get(route('admin.models.export', $model))
            ->assertOk();
        $json = $export->getContent();
        $payload = json_decode((string) $json, true);
        $this->assertIsArray($payload);
        $payload['model']['slug'] = 'article_imported';
        $payload['model']['name'] = '导入文章';

        $file = UploadedFile::fake()->createWithContent('m.json', json_encode($payload));
        $this->actingAs($admin, 'admin')
            ->post(route('admin.models.import.submit'), ['file' => $file, 'overwrite' => '1'])
            ->assertRedirect(route('admin.models.index'));
        $this->assertTrue(ContentModel::query()->where('slug', 'article_imported')->exists());
    }

    public function test_entry_extra_categories_list_on_front(): void
    {
        $article = ContentModel::query()->where('slug', 'article')->first();
        $news = Category::query()->get()->first(fn ($c) => ($c->slug['zh_cn'] ?? '') === 'news');
        $blog = Category::query()->get()->first(fn ($c) => ($c->slug['zh_cn'] ?? '') === 'blog');
        $this->assertNotNull($news);
        $this->assertNotNull($blog);

        $entry = Entry::query()->create([
            'category_id' => $news->id,
            'model_id' => $article->id,
            'title' => ['zh_cn' => '多栏目文档'],
            'slug' => ['zh_cn' => 'multi-cat-doc'],
            'content' => ['zh_cn' => '正文'],
            'status' => 'published',
            'published_at' => now(),
        ]);
        $entry->categories()->sync([$news->id, $blog->id]);

        $this->assertTrue(Entry::query()->inCategories([$blog->id])->whereKey($entry->id)->exists());
        $found = app(\App\Services\Cms\SiteFrontService::class)->entryBySlug($blog, 'multi-cat-doc');
        $this->assertNotNull($found);
        $this->assertSame($entry->id, $found->id);
    }
}
