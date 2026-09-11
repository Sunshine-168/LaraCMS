<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Category;
use App\Models\Cms\ContentModel;
use App\Models\Cms\Entry;
use App\Models\Cms\Member;
use App\Models\Cms\ModelField;
use App\Models\Cms\Site;
use App\Services\Admin\FieldBindService;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FieldBindSearchUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_field_bind_filters_by_category(): void
    {
        $model = ContentModel::query()->where('slug', 'article')->firstOrFail();
        $news = Category::query()->get()->first(fn ($c) => ($c->slug['zh_cn'] ?? '') === 'news');
        $blog = Category::query()->get()->first(fn ($c) => ($c->slug['zh_cn'] ?? '') === 'blog');
        $this->assertNotNull($news);
        $this->assertNotNull($blog);

        $field = ModelField::query()->create([
            'model_id' => $model->id,
            'name' => 'news_only',
            'label' => '仅新闻',
            'type' => 'text',
            'sort_order' => 99,
        ]);
        app(FieldBindService::class)->saveBinds($field, [$news->id]);

        $model->load(['fields.categories']);
        $forNews = app(FieldBindService::class)->resolveFields($model, $news->id);
        $forBlog = app(FieldBindService::class)->resolveFields($model, $blog->id);
        $this->assertTrue($forNews->contains('id', $field->id));
        $this->assertFalse($forBlog->contains('id', $field->id));
    }

    public function test_search_respects_model_switch_and_filters(): void
    {
        $article = ContentModel::query()->where('slug', 'article')->firstOrFail();
        $article->is_searchable = false;
        $article->save();

        $news = Category::query()->get()->first(fn ($c) => ($c->slug['zh_cn'] ?? '') === 'news');
        Entry::query()->create([
            'category_id' => $news->id,
            'model_id' => $article->id,
            'title' => ['zh_cn' => '不可搜索关键词XYZ'],
            'slug' => ['zh_cn' => 'no-search-xyz'],
            'content' => ['zh_cn' => '正文'],
            'search_text' => '不可搜索关键词XYZ',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->get(route('search', ['q' => '关键词XYZ']))
            ->assertOk()
            ->assertDontSee('不可搜索关键词XYZ');

        $article->is_searchable = true;
        $article->save();

        Entry::query()->create([
            'category_id' => $news->id,
            'model_id' => $article->id,
            'title' => ['zh_cn' => '可搜索关键词ABC'],
            'slug' => ['zh_cn' => 'yes-search-abc'],
            'content' => ['zh_cn' => '正文'],
            'search_text' => '可搜索关键词ABC',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->get(route('search', ['q' => '关键词ABC', 'model_id' => $article->id, 'category_id' => $news->id]))
            ->assertOk()
            ->assertSee('可搜索关键词ABC');
    }

    public function test_member_chunk_upload_avatar(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $member = Member::query()->where('email', 'member@example.com')->firstOrFail();
        $this->actingAs($member, 'member');

        // 最小合法 JPEG（避免依赖 GD extension）
        $bytes = base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAn/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAGfAP/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAQUCf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQMBAT8Bf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQIBAT8Bf//Z');
        $this->assertNotFalse($bytes);
        $half = (int) ceil(strlen($bytes) / 2);

        $init2 = $this->postJson(route('member.upload.init'), [
            'filename' => 'avatar.jpg',
            'total_chunks' => 2,
            'total_size' => strlen($bytes),
        ])->assertOk()->json();
        $this->assertTrue($init2['ok']);
        $uploadId = $init2['data']['upload_id'];

        $this->post(route('member.upload.chunk'), [
            'upload_id' => $uploadId,
            'index' => 0,
            'chunk' => UploadedFile::fake()->createWithContent('c0.bin', substr($bytes, 0, $half)),
        ])->assertOk();
        $this->post(route('member.upload.chunk'), [
            'upload_id' => $uploadId,
            'index' => 1,
            'chunk' => UploadedFile::fake()->createWithContent('c1.bin', substr($bytes, $half)),
        ])->assertOk();

        $done = $this->postJson(route('member.upload.complete'), [
            'upload_id' => $uploadId,
            'purpose' => 'avatar',
        ])->assertOk()->json();
        $this->assertTrue($done['ok']);
        $member->refresh();
        $this->assertNotEmpty($member->avatar);
    }

    public function test_admin_can_save_field_binds(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->firstOrFail();
        $model = ContentModel::query()->where('slug', 'article')->firstOrFail();
        $news = Category::query()->get()->first(fn ($c) => ($c->slug['zh_cn'] ?? '') === 'news');
        $field = ModelField::query()->create([
            'model_id' => $model->id,
            'name' => 'bound_field',
            'label' => '绑定字段',
            'type' => 'text',
        ]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.models.fields.update', [$model, $field]), [
                'name' => 'bound_field',
                'label' => '绑定字段',
                'type' => 'text',
                'sort_order' => 0,
                'bind_categories' => '1',
                'category_ids' => [$news->id],
            ])
            ->assertRedirect(route('admin.models.index'));

        $this->assertTrue($field->fresh()->categories->contains('id', $news->id));
    }
}
