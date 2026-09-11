<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Category;
use App\Models\Cms\ContentModel;
use App\Models\Cms\Entry;
use App\Models\Cms\Setting;
use App\Models\Cms\Site;
use App\Services\Admin\EntryService;
use App\Services\Admin\SiteBackupService;
use App\Support\Cdn;
use App\Support\CurrentSite;
use App\Support\Locale;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class PublishBackupCdnTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_editor_cannot_publish_and_super_can(): void
    {
        $editor = AdminUser::query()->where('email', 'editor@example.com')->first();
        $this->assertNotNull($editor);
        $this->assertFalse($editor->canPerm('publish'));
        $this->assertTrue($editor->canPerm('entries'));

        $article = ContentModel::query()->where('slug', 'article')->first();
        $news = Category::query()->get()->first(fn (Category $c) => $c->t('slug') === 'news');
        $this->assertNotNull($article);
        $this->assertNotNull($news);

        $this->actingAs($editor, 'admin')
            ->get(route('admin.entries.create', ['model_id' => $article->id]))
            ->assertOk()
            ->assertSee('无发布权限')
            ->assertDontSee('>已发布</option>', false);

        $svc = app(EntryService::class);
        $result = $svc->save(null, $this->newEntryPayload($article->id, $news->id, '编辑想直接发布'));
        $this->assertTrue($result['ok']);
        $entry = $result['data'];
        $this->assertSame('pending', $entry->status);
        $this->assertFalse($entry->isLive());

        $this->actingAs($editor, 'admin')
            ->post(route('admin.entries.batch'), [
                'action' => 'publish',
                'ids' => [$entry->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame('pending', $entry->fresh()->status);

        $super = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($super, 'admin')
            ->post(route('admin.entries.batch'), [
                'action' => 'publish',
                'ids' => [$entry->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('status');
        $this->assertTrue($entry->fresh()->isLive());
    }

    public function test_cdn_rewrites_storage_and_app_url_covers(): void
    {
        Setting::setValue('cdn_url', 'https://cdn.example.test');
        Cdn::applyRuntime();

        $this->assertSame(
            'https://cdn.example.test/storage/cms/a.jpg',
            Cdn::url('/storage/cms/a.jpg')
        );

        $app = rtrim((string) config('app.url'), '/');
        $this->assertSame(
            'https://cdn.example.test/storage/cms/a.jpg',
            Cdn::url($app.'/storage/cms/a.jpg')
        );
        $this->assertSame(
            'https://picsum.photos/seed/x/400',
            Cdn::url('https://picsum.photos/seed/x/400')
        );

        $entry = Entry::query()->published()->first();
        $this->assertNotNull($entry);
        $entry->cover = $app.'/storage/cms/demo.jpg';
        $entry->save();

        $this->getJson('/api/content/'.$entry->id)
            ->assertOk()
            ->assertJsonPath('data.cover', 'https://cdn.example.test/storage/cms/demo.jpg');
    }

    public function test_backup_zip_contains_sql_and_can_download(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive 不可用');
        }

        $this->artisan('cms:backup')->assertSuccessful();
        $listed = app(SiteBackupService::class)->list();
        $this->assertNotEmpty($listed);
        $path = app(SiteBackupService::class)->pathFor($listed[0]['file']);
        $this->assertNotNull($path);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $this->assertNotFalse($zip->locateName('database.sql'));
        $this->assertNotFalse($zip->locateName('manifest.json'));
        $sql = $zip->getFromName('database.sql');
        $this->assertIsString($sql);
        $this->assertStringContainsString('INSERT INTO', $sql);
        $zip->close();

        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.cache.backup.download', $listed[0]['file']))
            ->assertOk();
    }

    public function test_download_and_nav_themes_have_tag_pages(): void
    {
        Setting::setValue('theme', 'download');
        $this->get('/tag/laravel')->assertOk()->assertSee('标签');

        Setting::setValue('theme', 'nav');
        $this->get('/tag/laravel')->assertOk()->assertSee('标签');
    }

    /** @return array<string, mixed> */
    protected function newEntryPayload(int $modelId, int $categoryId, string $title): array
    {
        $locales = [];
        foreach (Locale::codes() as $code) {
            $locales[$code] = [
                'title' => $title,
                'slug' => 'editor-want-publish',
                'excerpt' => '',
                'content' => '正文',
                'meta_title' => '',
                'meta_description' => '',
            ];
        }

        return [
            'model_id' => $modelId,
            'category_id' => $categoryId,
            'locales' => $locales,
            'status' => 'published',
            'sort_order' => 0,
            'attrs' => [],
        ];
    }
}
