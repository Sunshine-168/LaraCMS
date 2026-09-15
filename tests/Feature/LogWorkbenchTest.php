<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\AdminLog;
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
}
