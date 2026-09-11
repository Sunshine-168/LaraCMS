<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\CmsForm;
use App\Models\Cms\Entry;
use App\Models\Cms\FormSubmission;
use App\Models\Cms\Setting;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InboxAndMangaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_form_inbox_detail_marks_read_and_can_reply(): void
    {
        $form = CmsForm::query()->first();
        $this->assertNotNull($form);
        $submission = FormSubmission::query()->create([
            'form_id' => $form->id,
            'data' => ['name' => 'Tester', 'email' => 'guest@example.com', 'message' => 'Need help'],
            'ip' => '127.0.0.1',
            'is_read' => false,
        ]);

        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.form-submissions.show', $submission))
            ->assertOk()
            ->assertSee('Need help')
            ->assertSee('guest@example.com');

        $this->assertTrue($submission->fresh()->is_read);

        Mail::fake();
        $this->actingAs($admin, 'admin')
            ->put(route('admin.form-submissions.update', $submission), [
                'admin_note' => 'We will follow up.',
                'send_reply' => '1',
            ])
            ->assertRedirect(route('admin.form-submissions.show', $submission));

        $fresh = $submission->fresh();
        $this->assertNotNull($fresh->replied_at);
        $this->assertSame('We will follow up.', $fresh->admin_note);
    }

    public function test_manga_lists_related_chapters_and_chapter_links_back(): void
    {
        Setting::setValue('theme', 'manga');

        $manga = Entry::query()->with(['category', 'modelDef'])->find(10);
        $chapter = Entry::query()->with(['category', 'modelDef'])->find(11);
        $this->assertNotNull($manga);
        $this->assertNotNull($chapter);

        $this->get($manga->url)
            ->assertOk()
            ->assertSee($chapter->t('title'))
            ->assertDontSee('manga_id');

        $this->get($chapter->url)
            ->assertOk()
            ->assertSee($manga->t('title'))
            ->assertSee('cms-comments', false);
    }
}
