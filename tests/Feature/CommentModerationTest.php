<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Comment;
use App\Models\Cms\Entry;
use App\Models\Cms\Setting;
use App\Support\Captcha;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(\App\Models\Cms\Site::default());
        Setting::setValue('comments_enabled', '1');
        Setting::setValue('comments_guest', '1');
        Setting::setValue('comments_auto_approve', '0');
        Setting::setValue('comments_rate_seconds', '10');
    }

    public function test_guest_comment_pending_then_admin_approves(): void
    {
        $entry = Entry::query()->published()->whereHas('modelDef', fn ($q) => $q->where('kind', 'list'))->first()
            ?: Entry::query()->published()->first();
        $this->assertNotNull($entry);
        $showUrl = '/e/'.$entry->id;

        Captcha::generate();
        $answer = session('cms_captcha');

        $this->post('/comment/'.$entry->id, [
            'author_name' => '访客甲',
            'author_email' => 'guest@example.com',
            'content' => '这是一条测试评论内容',
            'captcha' => (string) $answer,
        ])->assertRedirect();

        $comment = Comment::query()->where('entry_id', $entry->id)->first();
        $this->assertNotNull($comment);
        $this->assertSame(Comment::STATUS_PENDING, $comment->status);

        $this->get($showUrl)->assertOk()->assertDontSee('这是一条测试评论内容');

        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->post(route('admin.comments.moderate', $comment), ['status' => 'approved'])
            ->assertRedirect();

        $this->assertSame(Comment::STATUS_APPROVED, $comment->fresh()->status);
        $this->get($showUrl)->assertOk()->assertSee('这是一条测试评论内容');
    }

    public function test_admin_comments_index(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.comments.index'))
            ->assertOk()
            ->assertSee('>评论</h1>', false);
    }
}
