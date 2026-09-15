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

class SideNavWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_sidebar_is_short_and_grouped(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('>内容</span>', false)
            ->assertSee('>栏目</span>', false)
            ->assertSee('>留言</span>', false)
            ->assertSee('>更多</summary>', false)
            ->assertSee('>友链</span>', false)
            ->assertSee('>推荐</span>', false)
            ->assertSee('>统计</span>', false)
            ->assertSee('side-foot-avatar', false)
            ->assertSee($admin->name)
            ->assertDontSee('角色：')
            ->assertDontSee('栏目管理')
            ->assertDontSee('流量统计')
            ->assertDontSee('留言收件箱')
            ->assertDontSee('内容管理');
    }

    public function test_counts_and_more_open_on_nested_page(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $entry = Entry::query()->first();
        $this->assertNotNull($entry);
        Comment::query()->create([
            'entry_id' => $entry->id,
            'author_name' => '侧栏访客',
            'content' => '待审评论',
            'status' => Comment::STATUS_PENDING,
            'ip' => '127.0.0.1',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.models.index'))
            ->assertOk()
            ->assertSee('nav-fold is-open', false)
            ->assertSee('nav-count', false)
            ->assertSee('>模型</span>', false);
    }
}
