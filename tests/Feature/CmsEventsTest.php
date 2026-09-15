<?php

namespace Tests\Feature;

use App\Events\CommentSubmitted;
use App\Events\EntrySaved;
use App\Events\FormSubmitted;
use App\Events\SettingSaved;
use App\Events\ThemeActivated;
use App\Models\Admin\AdminUser;
use App\Models\Cms\CmsForm;
use App\Models\Cms\Entry;
use App\Models\Cms\Setting;
use App\Models\Cms\Site;
use App\Services\Admin\EntryService;
use App\Services\Admin\SettingService;
use App\Services\Cms\CommentService;
use App\Services\Cms\FormSubmitService;
use App\Services\Cms\ThemeCatalog;
use App\Support\CurrentSite;
use App\Support\Locale;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CmsEventsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_saving_entry_and_settings_dispatch_events(): void
    {
        Event::fake([EntrySaved::class, SettingSaved::class, ThemeActivated::class]);
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin');
        $entry = Entry::query()->published()->first();
        $this->assertNotNull($entry);

        $locales = [];
        foreach (Locale::codes() as $code) {
            $locales[$code] = [
                'title' => $entry->t('title', $code) ?: '事件稿',
                'slug' => ($entry->t('slug', $code) ?: 'event-entry').'-evt',
                'excerpt' => (string) $entry->t('excerpt', $code),
                'content' => (string) $entry->t('content', $code),
                'meta_title' => '',
                'meta_description' => '',
            ];
        }
        $saved = app(EntryService::class)->save($entry, [
            'model_id' => $entry->model_id,
            'category_id' => $entry->category_id,
            'status' => 'published',
            'locales' => $locales,
            'cover' => $entry->cover,
        ]);
        $this->assertTrue($saved['ok']);
        Event::assertDispatched(EntrySaved::class, function (EntrySaved $e) use ($entry) {
            return (int) $e->entry->id === (int) $entry->id && $e->created === false;
        });

        app(SettingService::class)->save(['site_name' => '事件站']);
        Event::assertDispatched(SettingSaved::class, fn (SettingSaved $e) => in_array('site_name', $e->keys, true));

        app(ThemeCatalog::class)->apply('blog');
        Event::assertDispatched(ThemeActivated::class, fn (ThemeActivated $e) => $e->slug === 'blog');
    }

    public function test_comment_and_form_dispatch_events(): void
    {
        Event::fake([CommentSubmitted::class, FormSubmitted::class]);
        $entry = Entry::query()->published()->first();
        $this->assertNotNull($entry);
        app(CommentService::class)->submit($entry, [
            'author_name' => '事件访客',
            'author_email' => 'evt@example.com',
            'content' => '请审核这条用来测事件的评论',
        ], null, '127.0.0.1', 'phpunit', true);
        Event::assertDispatched(CommentSubmitted::class);

        $form = CmsForm::query()->where('slug', 'contact')->first();
        $this->assertNotNull($form);
        $request = Request::create('/form/contact', 'POST', [
            'name' => '事件',
            'email' => 'evt@example.com',
            'message' => 'hello event',
        ]);
        $request->setLaravelSession($this->app['session.store']);
        $result = app(FormSubmitService::class)->submit('contact', $request, null, true);
        $this->assertTrue($result['ok']);
        Event::assertDispatched(FormSubmitted::class);
    }
}
