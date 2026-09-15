<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Setting;
use App\Models\Cms\Site;
use App\Services\Cms\DiskHtmlService;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HtmlCacheWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_page_is_a_workbench(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.html-cache.edit'))
            ->assertOk()
            ->assertSee('全页缓存')
            ->assertSee('启用，访客看到的是刚生成好的页面')
            ->assertSee('保存多久')
            ->assertSee('1 小时')
            ->assertSee('改内容后马上换新')
            ->assertSee('预热常用页')
            ->assertSee('清空已存页面')
            ->assertSee('磁盘静态页')
            ->assertSee('开始生成')
            ->assertSee('删掉静态文件')
            ->assertSee('已有文件仍可分批删除')
            ->assertSee('启用，允许在后台生成静态文件')
            ->assertSee('未开启')
            ->assertSee('清空缓存')
            ->assertSee('说明')
            ->assertDontSee('上线怎么配 PHP、MySQL、nginx')
            ->assertDontSee('TTL（秒')
            ->assertDontSee('失效策略')
            ->assertDontSee('epoch')
            ->assertDontSee('CMS_DISK_HTML')
            ->assertDontSee('需要时让运维打开后再来生成')
            ->assertDontSee('预热（首页/栏目/最近30篇）');
    }

    public function test_save_toggle_and_clear(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->put(route('admin.html-cache.update'), [
                'html_cache_enabled' => '1',
                'html_cache_ttl' => '900',
            ])
            ->assertRedirect(route('admin.html-cache.edit'))
            ->assertSessionHas('status');

        $this->assertSame('1', (string) Setting::getValue('html_cache_enabled'));
        $this->assertSame('900', (string) Setting::getValue('html_cache_ttl'));

        $this->get(route('admin.html-cache.edit'))
            ->assertOk()
            ->assertSee('已开启')
            ->assertSee('15 分钟');

        $this->post(route('admin.html-cache.clear'))
            ->assertRedirect(route('admin.html-cache.edit'))
            ->assertSessionHas('status');

        $this->get(route('admin.html-cache.edit'))
            ->assertOk()
            ->assertSee('你手动清空了页面缓存');
    }

    public function test_warm_requires_enabled(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        Setting::setValue('html_cache_enabled', '0');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.html-cache.warm'))
            ->assertRedirect(route('admin.html-cache.edit'))
            ->assertSessionHas('error');
    }

    public function test_disk_html_can_be_enabled_and_reports_progress(): void
    {
        $root = storage_path('framework/testing/disk-html');
        config(['cms.disk_html.root' => $root]);
        \Illuminate\Support\Facades\File::deleteDirectory($root);

        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin');

        $this->postJson(route('admin.html-cache.disk-build'))
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');

        $this->put(route('admin.html-cache.disk-update'), [
            'disk_html_enabled' => '1',
        ])
            ->assertRedirect(route('admin.html-cache.edit'))
            ->assertSessionHas('status');

        $this->assertSame('1', (string) Setting::getValue('disk_html_enabled'));

        $this->get(route('admin.html-cache.edit'))
            ->assertOk()
            ->assertSee('已打开')
            ->assertSee('id="diskHtmlProgress"', false)
            ->assertSee('开始生成');

        $start = $this->postJson(route('admin.html-cache.disk-build'))
            ->assertOk()
            ->assertJsonStructure(['status', 'kind', 'done', 'total', 'ok', 'fail', 'percent', 'message', 'file_count']);

        $this->assertGreaterThan(0, (int) $start->json('total'));
        $this->assertSame('running', $start->json('status'));

        $step = $this->postJson(route('admin.html-cache.disk-step'), ['chunk' => 1])
            ->assertOk();
        $this->assertGreaterThanOrEqual(1, (int) $step->json('done'));
        $this->assertContains($step->json('status'), ['running', 'done']);

        $this->getJson(route('admin.html-cache.disk-status'))
            ->assertOk()
            ->assertJsonPath('done', $step->json('done'));

        $this->postJson(route('admin.html-cache.disk-cancel'))
            ->assertOk();

        \Illuminate\Support\Facades\File::deleteDirectory($root);
    }

    public function test_disk_html_clear_reports_progress(): void
    {
        $root = storage_path('framework/testing/disk-html-clear');
        config(['cms.disk_html.root' => $root]);
        \Illuminate\Support\Facades\File::deleteDirectory($root);
        \Illuminate\Support\Facades\File::ensureDirectoryExists($root.DIRECTORY_SEPARATOR.'news'.DIRECTORY_SEPARATOR.'a');
        \Illuminate\Support\Facades\File::put($root.DIRECTORY_SEPARATOR.'index.html', 'home');
        \Illuminate\Support\Facades\File::put($root.DIRECTORY_SEPARATOR.'news'.DIRECTORY_SEPARATOR.'index.html', 'list');
        \Illuminate\Support\Facades\File::put($root.DIRECTORY_SEPARATOR.'news'.DIRECTORY_SEPARATOR.'a'.DIRECTORY_SEPARATOR.'index.html', 'item');

        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin');
        Setting::setValue('disk_html_enabled', '1');

        $start = $this->postJson(route('admin.html-cache.disk-clear'))
            ->assertOk()
            ->assertJsonPath('status', 'running')
            ->assertJsonPath('kind', 'clear')
            ->assertJsonPath('total', 3);

        $this->postJson(route('admin.html-cache.disk-build'))
            ->assertStatus(422)
            ->assertJsonPath('conflict', true);

        $step = $this->postJson(route('admin.html-cache.disk-step'), ['chunk' => 2])
            ->assertOk()
            ->assertJsonPath('kind', 'clear');
        $this->assertSame(2, (int) $step->json('done'));
        $this->assertContains($step->json('status'), ['running', 'done']);

        $job = $step->json();
        while (($job['status'] ?? '') === 'running') {
            $job = $this->postJson(route('admin.html-cache.disk-step'), ['chunk' => 2])->json();
        }

        $this->assertSame('done', $job['status']);
        $this->assertSame(3, (int) $job['ok']);
        $this->assertSame(0, (int) $job['file_count']);
        $this->assertFileDoesNotExist($root.DIRECTORY_SEPARATOR.'index.html');
        $this->assertDirectoryDoesNotExist($root.DIRECTORY_SEPARATOR.'news');

        \Illuminate\Support\Facades\File::deleteDirectory($root);
    }

    public function test_front_serves_generated_disk_html(): void
    {
        $root = storage_path('framework/testing/disk-html-serve');
        config(['cms.disk_html.root' => $root]);
        \Illuminate\Support\Facades\File::deleteDirectory($root);
        \Illuminate\Support\Facades\File::ensureDirectoryExists($root.DIRECTORY_SEPARATOR.'about');
        \Illuminate\Support\Facades\File::put(
            $root.DIRECTORY_SEPARATOR.'about'.DIRECTORY_SEPARATOR.'index.html',
            '<html><body>DISK_HIT_MARKER</body></html>'
        );
        Setting::setValue('disk_html_enabled', '1');

        $this->get('/about')
            ->assertOk()
            ->assertSee('DISK_HIT_MARKER')
            ->assertHeader('X-CMS-Disk-Html', 'HIT')
            ->assertDontSee('评论');

        $member = \App\Models\Cms\Member::query()->where('email', 'member@example.com')->first();
        $this->actingAs($member, 'member')
            ->get('/about')
            ->assertOk()
            ->assertDontSee('DISK_HIT_MARKER');

        \Illuminate\Support\Facades\File::deleteDirectory($root);
    }

    public function test_disk_html_skips_login_required_pages(): void
    {
        $urls = app(DiskHtmlService::class)->collectUrls();
        $joined = implode("\n", $urls);
        $this->assertStringNotContainsString('client-windows', $joined);
        $this->assertContains('/', $urls);
    }
}
