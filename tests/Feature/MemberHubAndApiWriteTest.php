<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Comment;
use App\Models\Cms\Entry;
use App\Models\Cms\FormSubmission;
use App\Models\Cms\Member;
use App\Models\Cms\Setting;
use App\Models\Cms\Site;
use App\Support\Captcha;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MemberHubAndApiWriteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
        Setting::setValue('comments_enabled', '1');
        Setting::setValue('comments_guest', '1');
        Setting::setValue('comments_auto_approve', '0');
        Setting::setValue('comments_rate_seconds', '10');
        Cache::flush();
    }

    public function test_scheduled_publish_is_hidden_on_id_url_and_flagged_in_admin(): void
    {
        $entry = $this->seededEntry('hello-blog');
        $entry->status = 'published';
        $entry->published_at = now()->addDay();
        $entry->save();

        $this->get('/e/'.$entry->id)->assertNotFound();
        $this->get('/download/'.$entry->id)->assertNotFound();

        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.entries.index', ['model_id' => $entry->model_id]))
            ->assertOk()
            ->assertSee('定时');
    }

    public function test_member_profile_lists_comments_submissions_and_gated_content(): void
    {
        $member = Member::query()->where('email', 'member@example.com')->first();
        $this->assertNotNull($member);
        $entry = $this->seededEntry('hello-blog');

        Captcha::generate();
        $this->actingAs($member, 'member')
            ->post('/comment/'.$entry->id, [
                'content' => '会员中心可见的评论正文',
                'captcha' => (string) session('cms_captcha'),
            ])
            ->assertRedirect();

        Captcha::generate();
        $this->actingAs($member, 'member')
            ->post('/form/contact', [
                'name' => '演示会员',
                'email' => 'member@example.com',
                'message' => '来自会员中心的留言',
                'captcha' => (string) session('cms_captcha'),
            ])
            ->assertRedirect();

        $this->assertSame($member->id, FormSubmission::query()->latest('id')->first()?->member_id);

        $gated = $this->seededEntry('client-windows');
        $this->assertTrue($gated?->isMemberGated());

        $this->actingAs($member, 'member')
            ->get(route('member.profile'))
            ->assertOk()
            ->assertSee('我的评论')
            ->assertSee('会员中心可见的评论正文')
            ->assertSee('我的留言')
            ->assertSee('来自会员中心的留言')
            ->assertSee('会员内容')
            ->assertSee($gated->t('title'));
    }

    public function test_sitemap_includes_tags_and_skips_member_gated_entries(): void
    {
        $gated = $this->seededEntry('client-windows');

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('/tag/laravel', false)
            ->assertSee('<lastmod>', false)
            ->assertDontSee('/downloads/client-windows', false);
    }

    public function test_api_form_and_comment_with_token_skip_captcha(): void
    {
        Setting::setValue('api_enable', '1');
        Setting::setValue('api_token', 'write-token-test');
        $entry = $this->seededEntry('hello-blog');

        $this->withHeaders(['X-CMS-Token' => 'write-token-test'])
            ->postJson('/api/form/contact', [
                'name' => 'API User',
                'email' => 'api@example.com',
                'message' => 'hello from api',
            ])
            ->assertOk()
            ->assertJsonPath('code', 200);

        $this->assertNotNull(FormSubmission::query()->where('data->message', 'hello from api')->first());

        $this->withHeaders(['X-CMS-Token' => 'write-token-test'])
            ->postJson('/api/comment/'.$entry->id, [
                'author_name' => 'API Guest',
                'content' => 'api comment body text',
            ])
            ->assertOk()
            ->assertJsonPath('code', 200);

        $this->assertNotNull(Comment::query()->where('content', 'api comment body text')->first());
    }
}
