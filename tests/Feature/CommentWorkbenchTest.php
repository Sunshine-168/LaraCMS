<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Comment;
use App\Models\Cms\Entry;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_empty_queue_explains(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.comments.index'))
            ->assertOk()
            ->assertSee('当前没有待审评论')
            ->assertSee('查看全部评论')
            ->assertDontSee('暂无评论')
            ->assertDontSee('>ID</th>', false)
            ->assertDontSee('内容 ID')
            ->assertDontSee('批量通过')
            ->assertDontSee('>执行</button>', false)
            ->assertDontSee('待审 0');
    }

    public function test_list_is_a_workbench(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $entry = Entry::query()->first();
        $this->assertNotNull($entry);
        Comment::query()->create([
            'entry_id' => $entry->id,
            'author_name' => '访客甲',
            'author_email' => 'guest@example.com',
            'content' => '这篇文章写得清楚',
            'status' => Comment::STATUS_PENDING,
            'ip' => '127.0.0.1',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.comments.index'))
            ->assertOk()
            ->assertSee('这篇文章写得清楚')
            ->assertSee('访客甲')
            ->assertSee($entry->t('title'))
            ->assertSee('>通过</button>', false)
            ->assertSee('搜索评论或作者')
            ->assertSee('id="batchBar" hidden', false)
            ->assertDontSee('>ID</th>', false)
            ->assertDontSee('>状态</th>', false)
            ->assertDontSee('class="btn btn-sm btn-danger"', false)
            ->assertDontSee('>待审</span>', false);
    }

    public function test_search_and_status_chips(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $entry = Entry::query()->first();
        $this->assertNotNull($entry);
        Comment::query()->create([
            'entry_id' => $entry->id,
            'author_name' => '待审客',
            'content' => '请尽快审核',
            'status' => Comment::STATUS_PENDING,
        ]);
        Comment::query()->create([
            'entry_id' => $entry->id,
            'author_name' => '通过客',
            'content' => '写得真好',
            'status' => Comment::STATUS_APPROVED,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.comments.index'))
            ->assertOk()
            ->assertSee('请尽快审核')
            ->assertDontSee('写得真好');

        $this->get(route('admin.comments.index', ['status' => 'all', 'q' => '写得真好']))
            ->assertOk()
            ->assertSee('通过客')
            ->assertDontSee('请尽快审核');
    }
}
