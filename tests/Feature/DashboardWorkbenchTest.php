<?php

namespace Tests\Feature;

use App\Models\Admin\AdminRole;
use App\Models\Admin\AdminUser;
use App\Models\Cms\CmsForm;
use App\Models\Cms\Collector;
use App\Models\Cms\Comment;
use App\Models\Cms\Entry;
use App\Models\Cms\FormSubmission;
use App\Models\Cms\Site;
use App\Models\Cms\StatHit;
use App\Services\Admin\QuickPanelService;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_super_dashboard_is_a_workbench_not_a_catalog(): void
    {
        StatHit::query()->create([
            'path' => '/',
            'ip' => '1.1.1.1',
            'visitor_hash' => hash('sha256', 'dash-today'),
            'user_agent' => 'Mozilla/5.0 Chrome/120',
            'is_spider' => false,
            'status_code' => 200,
            'created_at' => now(),
        ]);
        StatHit::query()->create([
            'path' => '/',
            'ip' => '1.1.1.2',
            'visitor_hash' => hash('sha256', 'dash-yday'),
            'user_agent' => 'Mozilla/5.0 Chrome/120',
            'is_spider' => false,
            'status_code' => 200,
            'created_at' => now()->subDay(),
        ]);

        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('今日 PV')
            ->assertSee('较昨日')
            ->assertSee('待处理')
            ->assertSee('内容健康')
            ->assertSee('已发布')
            ->assertSee('近 7 日 PV / UV')
            ->assertSee('待我处理')
            ->assertSee('当前没有待审稿、未读留言、待审评论')
            ->assertSee('写内容')
            ->assertSee('审稿')
            ->assertSee('收件箱')
            ->assertSee('站点健康')
            ->assertDontSee('内容 Entry')
            ->assertDontSee(app()->version())
            ->assertDontSee('快捷导航');
    }

    public function test_editor_does_not_see_ops_noise(): void
    {
        $editor = AdminUser::query()->where('email', 'editor@example.com')->first();
        $defaults = app(QuickPanelService::class)->defaultKeys($editor);
        $this->assertContains('entries_write', $defaults);
        $this->assertNotContains('models', $defaults);
        $this->assertNotContains('html_cache', $defaults);

        $this->actingAs($editor, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('待我处理')
            ->assertSee('写内容')
            ->assertSee('审稿')
            ->assertDontSee('今日蜘蛛')
            ->assertDontSee('站点健康')
            ->assertDontSee('采集模式')
            ->assertDontSee(app()->version());
    }

    public function test_operator_sees_traffic_and_inbox_not_models(): void
    {
        $role = AdminRole::query()->where('slug', 'operator')->first();
        $ops = AdminUser::query()->create([
            'name' => '运营',
            'email' => 'ops@example.com',
            'password' => 'Ops@12345',
            'role_id' => $role->id,
            'is_active' => true,
        ]);
        $ops->sites()->sync([Site::default()->id]);

        $this->actingAs($ops, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('今日 PV')
            ->assertSee('待处理')
            ->assertSee('看流量')
            ->assertSee('收件箱')
            ->assertDontSee('内容健康')
            ->assertDontSee('今日蜘蛛')
            ->assertDontSee('站点健康')
            ->assertDontSee('写内容');
    }

    public function test_dashboard_lists_pending_work_and_collector_failure(): void
    {
        $entry = Entry::query()->first();
        $this->assertNotNull($entry);
        $entry->status = 'pending';
        $entry->published_at = null;
        $entry->save();

        $form = CmsForm::query()->first();
        FormSubmission::query()->create([
            'form_id' => $form->id,
            'data' => ['name' => '张三', 'message' => '想咨询演示'],
            'ip' => '8.8.8.8',
            'is_read' => false,
        ]);

        Comment::query()->create([
            'entry_id' => $entry->id,
            'author_name' => '访客',
            'content' => '这篇能不能加个下载',
            'status' => Comment::STATUS_PENDING,
            'ip' => '8.8.8.8',
        ]);

        $collector = Collector::query()->first();
        if ($collector) {
            $collector->forceFill([
                'is_active' => true,
                'last_status' => 'fail',
                'last_run_at' => now()->subHour(),
                'last_message' => '源站超时',
            ])->save();
        }

        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('去处理')
            ->assertSee('待审稿')
            ->assertSee('未读留言')
            ->assertSee('张三')
            ->assertSee('待审评论')
            ->assertSee('采集任务最近失败')
            ->assertDontSee('当前没有待审稿');
    }
}
