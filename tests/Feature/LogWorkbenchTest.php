<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\AdminLog;
use App\Models\Cms\Entry;
use App\Models\Cms\Site;
use App\Support\AdminLogAction;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_actions_read_in_chinese(): void
    {
        $this->assertSame('新增了标签', AdminLogAction::label('POST', '/admin/tags'));
        $this->assertSame('保存了标签', AdminLogAction::label('PUT', '/admin/tags/3'));
        $this->assertSame('批量处理了内容', AdminLogAction::label('POST', '/admin/entries/batch'));
        $this->assertSame('上传了文件', AdminLogAction::label('POST', '/admin/media/upload'));
        $this->assertSame('预览了内容', AdminLogAction::label('POST', '/admin/entries/12/preview'));
        $this->assertSame('清空了缓存', AdminLogAction::label('POST', '/admin/cache/clear'));
        $this->assertSame('保存了站点设置', AdminLogAction::label('PUT', '/admin/settings'));
        $this->assertSame('保存了站点地图', AdminLogAction::label('PUT', '/admin/sitemap'));
        $this->assertSame('修改了自己的密码', AdminLogAction::label('PUT', '/admin/profile/password'));
        $this->assertSame('登录了后台', AdminLogAction::label('POST', '/admin/login'));
        $this->assertSame('登录失败', AdminLogAction::label('POST', '/admin/login', 401));
        $this->assertSame('退出了登录', AdminLogAction::label('POST', '/admin/logout'));
        $this->assertSame('生成静态页（进度）', AdminLogAction::label('POST', '/admin/html-cache/disk-step'));
        $this->assertSame('保存了磁盘静态页设置', AdminLogAction::label('PUT', '/admin/html-cache/disk'));
        $this->assertSame('把主题用于本站', AdminLogAction::label('POST', '/admin/themes/blog/use'));
        $this->assertSame('从回收站恢复了内容', AdminLogAction::label('POST', '/admin/recycle/1/restore'));
        $this->assertSame('提交了操作', AdminLogAction::label('POST', '/admin/foo-bar'));
        $this->assertSame('关于我们 · 已发布', AdminLogAction::summarize([
            'locales' => ['zh_cn' => ['title' => '关于我们', 'content' => '<p>长文</p>']],
            'status' => 'published',
        ]));
        $this->assertSame('选了 3 条 · 发布到前台', AdminLogAction::summarize([
            'ids' => [1, 2, 3],
            'action' => 'publish',
        ]));
        $this->assertSame('打开磁盘静态页', AdminLogAction::summarize(['disk_html_enabled' => '1']));
        $this->assertSame(['resource' => 'entries', 'object' => '内容', 'id' => 1], AdminLogAction::target('/admin/recycle/1/restore'));
        $this->assertSame('设为默认语言', AdminLogAction::label('POST', '/admin/locales/2/default'));
        $this->assertSame('新闻', AdminLogAction::summarize(['title' => '新闻']));
        $this->assertSame('选了 3 条', AdminLogAction::summarize(['ids' => [1, 2, 3]]));
        $this->assertSame('logo.png', AdminLogAction::summarize([], ['logo.png']));
        $this->assertSame(['resource' => 'tags', 'object' => '标签', 'id' => 3], AdminLogAction::target('/admin/tags/3'));
    }

    public function test_empty_explains(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.logs.index'))
            ->assertOk()
            ->assertSee('还没有操作记录')
            ->assertSee('打开页面不会记')
            ->assertSee('写清改了哪一条')
            ->assertSee('搜索管理员、IP 或改动内容')
            ->assertDontSee('暂无日志')
            ->assertDontSee('POST/PUT/DELETE')
            ->assertDontSee('>ID</th>', false)
            ->assertDontSee('>方法</th>', false);
    }

    public function test_list_is_a_workbench(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin);

        AdminLog::query()->create([
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'method' => 'PUT',
            'path' => '/admin/tags/1',
            'detail' => null,
            'ip' => '127.0.0.1',
            'status' => 302,
        ]);
        AdminLog::query()->create([
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'method' => 'POST',
            'path' => '/admin/entries/batch',
            'detail' => '选了 3 条',
            'ip' => '127.0.0.1',
            'status' => 500,
        ]);
        AdminLog::query()->create([
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'method' => 'POST',
            'path' => '/admin/html-cache/disk-step',
            'detail' => null,
            'ip' => '172.17.0.1',
            'status' => 200,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.logs.index'))
            ->assertOk()
            ->assertSee('保存了标签「Laravel」')
            ->assertSee('批量处理了内容')
            ->assertSee('选了 3 条')
            ->assertSee($admin->name)
            ->assertSee('没成功')
            ->assertSee('返回 500')
            ->assertSee('我的')
            ->assertSee('今天')
            ->assertSee('近7天')
            ->assertSee('name="from"', false)
            ->assertSee('127.0.0.1')
            ->assertSee('本机')
            ->assertSee('内网（Docker）')
            ->assertSee('生成静态页（进度）')
            ->assertSee(route('admin.logs.index', ['ip' => '127.0.0.1']), false)
            ->assertDontSee('>ID</th>', false)
            ->assertDontSee('>方法</th>', false)
            ->assertDontSee('>路径</th>', false)
            ->assertDontSee('>状态</th>', false)
            ->assertDontSee('记录后台 POST');
    }

    public function test_writing_is_logged_and_mine_filters(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->post(route('admin.tags.store'), [
                'title' => '日志标签',
            ])
            ->assertRedirect();

        $this->assertTrue(AdminLog::query()->where('path', 'like', '%/admin/tags')->exists());

        $this->get(route('admin.logs.index'))
            ->assertOk()
            ->assertSee('新增了标签')
            ->assertSee('日志标签');

        $this->get(route('admin.logs.index', ['mine' => '1']))
            ->assertOk()
            ->assertSee('新增了标签');
    }

    public function test_can_filter_by_day(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();

        $old = AdminLog::query()->create([
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'method' => 'POST',
            'path' => '/admin/tags',
            'detail' => '旧标签',
            'ip' => '127.0.0.1',
            'status' => 302,
        ]);
        $old->forceFill([
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ])->save();

        AdminLog::query()->create([
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'method' => 'POST',
            'path' => '/admin/menus',
            'detail' => '今日导航',
            'ip' => '127.0.0.1',
            'status' => 302,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.logs.index', [
                'from' => now()->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('今日导航')
            ->assertDontSee('旧标签');

        $this->get(route('admin.logs.index', [
            'from' => now()->subDays(10)->toDateString(),
            'to' => now()->subDays(10)->toDateString(),
        ]))
            ->assertOk()
            ->assertSee('旧标签')
            ->assertDontSee('今日导航');
    }

    public function test_login_logout_and_ip_are_logged_and_filterable(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10']);
        $this->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'wrong-pass',
        ]);
        $this->assertTrue(AdminLog::query()->where('path', '/admin/login')->where('status', 401)->exists());

        $this->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'Admin@12345',
        ])->assertRedirect();
        $this->assertSame('203.0.113.10', $admin->fresh()->last_login_ip);

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));
        $this->assertTrue(AdminLog::query()->where('path', '/admin/logout')->exists());

        AdminLog::query()->create([
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'method' => 'POST',
            'path' => '/admin/tags',
            'detail' => '别的地址',
            'ip' => '198.51.100.2',
            'status' => 302,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.logs.index', ['ip' => '203.0.113.10']))
            ->assertOk()
            ->assertSee('登录了后台')
            ->assertSee('登录失败')
            ->assertSee('退出了登录')
            ->assertSee('203.0.113.10')
            ->assertSee('保留地址')
            ->assertDontSee('别的地址');

        $this->get(route('admin.logs.index', ['q' => '203.0.113.10']))
            ->assertOk()
            ->assertSee('登录了后台')
            ->assertDontSee('别的地址');
    }

    public function test_restore_and_entry_save_show_titles(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $entry = Entry::query()->first();
        $this->assertNotNull($admin);
        $this->assertNotNull($entry);
        $title = (string) $entry->t('title');
        $this->assertNotSame('', $title);

        $entry->delete();
        $this->actingAs($admin, 'admin')
            ->post(route('admin.recycle.restore', $entry))
            ->assertRedirect();

        $this->get(route('admin.logs.index'))
            ->assertOk()
            ->assertSee('从回收站恢复了内容「'.$title.'」')
            ->assertDontSee('编号 '.$entry->id)
            ->assertDontSee('新增了一项');
    }

    public function test_list_paginates_twenty(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin);

        for ($i = 1; $i <= 21; $i++) {
            AdminLog::query()->create([
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'method' => 'POST',
                'path' => '/admin/tags',
                'detail' => sprintf('条目#%02d', $i),
                'ip' => '127.0.0.1',
                'status' => 302,
            ]);
        }

        $this->actingAs($admin, 'admin')
            ->get(route('admin.logs.index'))
            ->assertOk()
            ->assertSee('共 21 条')
            ->assertSee('条目#21')
            ->assertDontSee('条目#01')
            ->assertSee('page=2', false);

        $this->get(route('admin.logs.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('条目#01')
            ->assertDontSee('条目#21');
    }

    public function test_static_page_progress_is_not_logged(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $before = AdminLog::query()->count();
        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.html-cache.disk-step'), ['chunk' => 1]);
        $this->assertSame($before, AdminLog::query()->count());
    }
}
