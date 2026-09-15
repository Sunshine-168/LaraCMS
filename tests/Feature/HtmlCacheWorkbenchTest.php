<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Setting;
use App\Models\Cms\Site;
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
            ->assertSee('启用，允许在后台生成静态文件')
            ->assertSee('未开启')
            ->assertDontSee('TTL（秒')
            ->assertDontSee('失效策略')
            ->assertDontSee('epoch')
            ->assertDontSee('CMS_DISK_HTML')
            ->assertDontSee('php artisan')
            ->assertDontSee('try_files')
            ->assertDontSee('需要时让运维打开后再来生成')
            ->assertDontSee('清空缓存</button>', false)
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
            ->assertJsonStructure(['status', 'done', 'total', 'ok', 'fail', 'percent', 'message', 'file_count']);

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
}
