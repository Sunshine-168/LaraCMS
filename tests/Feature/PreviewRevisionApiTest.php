<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Entry;
use App\Models\Cms\Site;
use App\Models\Cms\Tag;
use App\Services\Admin\EntryService;
use App\Support\CurrentSite;
use App\Support\Locale;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreviewRevisionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_draft_is_hidden_but_preview_token_works(): void
    {
        $entry = $this->seededEntry('hello-blog');
        $publicUrl = $entry->url;
        $entry->status = 'draft';
        $entry->published_at = null;
        $entry->views = 3;
        $entry->save();

        $this->get($publicUrl)->assertNotFound();

        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->post(route('admin.entries.preview', $entry))
            ->assertRedirect(route('admin.entries.edit', $entry));

        $entry->refresh();
        $this->assertNotEmpty($entry->preview_token);

        $this->get('/preview/'.$entry->preview_token)
            ->assertOk()
            ->assertSee($entry->t('title'))
            ->assertSee('预览稿', false)
            ->assertSee('noindex', false);

        $this->assertSame(3, $entry->fresh()->views);

        $entry->preview_expires_at = now()->subMinute();
        $entry->save();
        $this->get('/preview/'.$entry->preview_token)->assertNotFound();
    }

    public function test_save_snapshots_revision_and_restore_reverts_title(): void
    {
        $entry = $this->seededEntry('hello-blog');
        $original = $entry->t('title');
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin');

        $svc = app(EntryService::class);
        $svc->save($entry, $this->entryPayload($entry, '修订标题 ABC'));
        $this->assertSame(1, $entry->revisions()->count());
        $this->assertSame('修订标题 ABC', $entry->fresh()->t('title'));

        $rev = $entry->revisions()->first();
        $this->post(route('admin.entries.revisions.restore', [$entry, $rev]))
            ->assertRedirect(route('admin.entries.edit', $entry));

        $this->assertSame($original, $entry->fresh()->t('title'));
        $this->assertGreaterThanOrEqual(2, $entry->revisions()->count());
    }

    public function test_api_search_and_tags(): void
    {
        $this->getJson('/api/search')->assertStatus(400)->assertJsonPath('code', 400);
        $this->getJson('/api/search?q=Laravel')
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonStructure(['data' => ['list', 'total']]);

        $this->getJson('/api/tag/list')
            ->assertOk()
            ->assertJsonPath('code', 200);

        $tag = Tag::query()->first();
        $this->assertNotNull($tag);
        $this->getJson('/api/tag/'.$tag->t('slug'))
            ->assertOk()
            ->assertJsonPath('data.tag.slug', $tag->t('slug'))
            ->assertJsonStructure(['data' => ['tag', 'list']]);
    }

    /** @return array<string, mixed> */
    protected function entryPayload(Entry $entry, string $title): array
    {
        $locales = [];
        foreach (Locale::codes() as $code) {
            $locales[$code] = [
                'title' => $code === Locale::default() ? $title : (string) $entry->t('title', $code),
                'slug' => (string) $entry->t('slug', $code),
                'excerpt' => (string) $entry->t('excerpt', $code),
                'content' => (string) $entry->t('content', $code),
                'meta_title' => (string) $entry->t('meta_title', $code),
                'meta_description' => (string) $entry->t('meta_description', $code),
            ];
        }

        return [
            'model_id' => $entry->model_id,
            'category_id' => $entry->category_id,
            'category_ids' => $entry->categories->pluck('id')->all(),
            'locales' => $locales,
            'status' => $entry->status,
            'cover' => $entry->cover,
            'flags' => $entry->flags,
            'sort_order' => $entry->sort_order,
            'attrs' => $entry->attrs ?? [],
            'tag_ids' => $entry->tags->pluck('id')->all(),
        ];
    }
}
