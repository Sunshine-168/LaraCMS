<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Entry;
use App\Models\Cms\Setting;
use App\Models\Cms\Site;
use App\Services\Admin\EntryService;
use App\Services\Cms\CommentService;
use App\Support\CurrentSite;
use App\Support\Locale;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailAndSeoWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_robots_and_share_tags(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('User-agent: *')
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap:')
            ->assertDontSee('Disallow: /private-area');

        Setting::setValue('robots_extra', "Disallow: /private-area\n# note");
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /private-area')
            ->assertSee('# note');

        $this->get('/')
            ->assertOk()
            ->assertSee('property="og:title"', false)
            ->assertSee('rel="canonical"', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('"@type":"WebSite"', false);

        $entry = Entry::query()->published()->first();
        $this->assertNotNull($entry);
        $this->get($entry->url)
            ->assertOk()
            ->assertSee('property="og:type" content="article"', false)
            ->assertSee('"@type":"Article"', false);
    }

    public function test_themed_404(): void
    {
        $this->get('/this-page-does-not-exist-xyz999')
            ->assertNotFound()
            ->assertSee('页面不存在')
            ->assertSee('回首页');
    }

    public function test_admin_forgot_password_sends_mail_and_resets(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('忘记密码')
            ->assertSee(route('admin.password.forgot'), false);

        $this->get(route('admin.password.forgot'))
            ->assertOk()
            ->assertSee('找回密码');

        Mail::fake();
        $this->post(route('admin.password.forgot.submit'), [
            'email' => 'admin@example.com',
        ])->assertRedirect()->assertSessionHas('status', '重置邮件已发送，请查收邮箱');

        Mail::assertOutgoingCount(1);
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->assertNotEmpty($admin->reset_token);

        $token = 'reset-token-workbench';
        $admin->forceFill([
            'reset_token' => hash('sha256', $token),
            'reset_token_expires_at' => now()->addHour(),
        ])->save();

        $this->post(route('admin.password.reset.submit'), [
            'email' => 'admin@example.com',
            'token' => $token,
            'password' => 'Admin@67890',
            'password_confirmation' => 'Admin@67890',
        ])->assertRedirect(route('admin.login'));

        $this->assertTrue(Hash::check('Admin@67890', $admin->fresh()->password));
        $this->assertNull($admin->fresh()->reset_token);
    }

    public function test_pending_entry_and_comment_notify_ops_mailbox(): void
    {
        Setting::setValue('comments_notify_email', 'ops@example.com');
        Mail::fake();

        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin');
        $entry = Entry::query()->published()->first();
        $this->assertNotNull($entry);

        $locales = [];
        foreach (Locale::codes() as $code) {
            $locales[$code] = [
                'title' => $entry->t('title', $code) ?: '待审通知稿',
                'slug' => ($entry->t('slug', $code) ?: 'pending-mail').'-ops',
                'excerpt' => (string) $entry->t('excerpt', $code),
                'content' => (string) $entry->t('content', $code),
                'meta_title' => '',
                'meta_description' => '',
            ];
        }
        $saved = app(EntryService::class)->save($entry, [
            'model_id' => $entry->model_id,
            'category_id' => $entry->category_id,
            'status' => 'pending',
            'locales' => $locales,
            'cover' => $entry->cover,
        ]);
        $this->assertTrue($saved['ok']);
        Mail::assertOutgoingCount(1);

        $live = Entry::query()->published()->first();
        $this->assertNotNull($live);
        app(CommentService::class)->submit($live, [
            'author_name' => '访客乙',
            'author_email' => 'guest-b@example.com',
            'content' => '请审核这条评论内容',
        ], null, '127.0.0.1', 'phpunit', true);
        Mail::assertOutgoingCount(2);
    }
}
