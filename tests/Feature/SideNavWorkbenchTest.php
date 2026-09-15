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
            ->assertSee('>地图</span>', false)
            ->assertSee('>推荐</span>', false)
            ->assertSee('>统计</span>', false)
            ->assertSee('side-foot-avatar', false)
            ->assertSee($admin->name)
            ->assertSeeInOrder([
                '>评论</span>',
                '>更多</summary>',
                '>回收站</span>',
                '>运营</div>',
                '>会员</span>',
                '>采集</span>',
                '>缓存</span>',
                '>更多</summary>',
                '>友链</span>',
                '>地图</span>',
                '>跳转</span>',
                '>分组</span>',
                '>系统</div>',
                '>说明</span>',
            ], false)
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

    public function test_recycle_opens_content_more_and_editor_flattens_ops(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.recycle.index'))
            ->assertOk()
            ->assertSee('nav-fold is-open', false)
            ->assertSee('>回收站</span>', false);

        $editor = AdminUser::query()->where('email', 'editor@example.com')->first();
        $html = $this->actingAs($editor, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('>友链</span>', false)
            ->assertSee('>地图</span>', false)
            ->assertSee('>跳转</span>', false)
            ->assertSee('>回收站</span>', false)
            ->assertSee('>更多</summary>', false)
            ->getContent();
        $this->assertSame(1, substr_count($html, '>更多</summary>'));
        $this->assertStringNotContainsString('>会员</span>', $html);
        $this->assertStringNotContainsString('>缓存</span>', $html);
    }

    public function test_operator_sees_flat_ops_without_friend_links(): void
    {
        $role = \App\Models\Admin\AdminRole::query()->where('slug', 'operator')->first();
        $this->assertNotNull($role);
        $ops = AdminUser::query()->create([
            'name' => '运营',
            'email' => 'ops-nav@example.com',
            'password' => 'Ops@12345',
            'role_id' => $role->id,
            'is_active' => true,
        ]);
        $ops->sites()->sync([Site::default()->id]);

        $html = $this->actingAs($ops, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('>跳转</span>', false)
            ->assertSee('>说明</span>', false)
            ->assertDontSee('>友链</span>', false)
            ->assertDontSee('>地图</span>', false)
            ->assertDontSee('>更多</summary>', false)
            ->getContent();
        $this->assertSame(0, substr_count($html, '>更多</summary>'));
    }
}
