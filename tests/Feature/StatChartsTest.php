<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Site;
use App\Models\Cms\StatHit;
use App\Services\Cms\Stats\StatService;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatChartsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_stats_page_renders_charts(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.stats.index'))
            ->assertOk()
            ->assertSee('近 14 日趋势')
            ->assertSee('近 7 日访问时段')
            ->assertSee('周期对比')
            ->assertSee('设备')
            ->assertSee('导出 CSV')
            ->assertSee('trendChart')
            ->assertSee('Chart.js')
            ->assertSee('近期 404');
    }

    public function test_page_titles_week_compare_and_export(): void
    {
        StatHit::query()->create([
            'path' => '/',
            'ip' => '1.1.1.1',
            'visitor_hash' => hash('sha256', 'home'),
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            'is_spider' => false,
            'created_at' => now(),
        ]);
        StatHit::query()->create([
            'path' => '/news',
            'ip' => '1.1.1.2',
            'visitor_hash' => hash('sha256', 'news'),
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile Safari/604.1',
            'is_spider' => false,
            'created_at' => now(),
        ]);

        $stats = app(StatService::class);
        $pages = $stats->topPages(now()->toDateString(), now()->toDateString());
        $this->assertContains('首页', $pages->pluck('title')->all());
        $this->assertContains('新闻中心', $pages->pluck('title')->all());

        $week = $stats->last7VersusPrev7();
        $this->assertArrayHasKey('current', $week);
        $this->assertSame(2, $week['current']['pv']);

        $clients = $stats->clientBreakdown(now()->toDateString(), now()->toDateString());
        $this->assertSame(1, collect($clients['devices'])->firstWhere('name', '桌面')['hits']);
        $this->assertSame(1, collect($clients['devices'])->firstWhere('name', '手机')['hits']);
        $this->assertSame(1, collect($clients['browsers'])->firstWhere('name', 'Chrome')['hits']);

        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $csv = $this->actingAs($admin, 'admin')
            ->get(route('admin.stats.export'))
            ->assertOk()
            ->streamedContent();
        $this->assertStringContainsString('新闻中心', $csv);
        $this->assertStringContainsString('首页', $csv);
    }

    public function test_hourly_and_referer_host_aggregations(): void
    {
        StatHit::query()->create([
            'path' => '/',
            'ip' => '1.1.1.1',
            'visitor_hash' => hash('sha256', 'a'),
            'referer' => 'https://google.com/search',
            'is_spider' => false,
            'created_at' => now(),
        ]);
        StatHit::query()->create([
            'path' => '/news',
            'ip' => '1.1.1.2',
            'visitor_hash' => hash('sha256', 'b'),
            'referer' => null,
            'is_spider' => false,
            'created_at' => now(),
        ]);

        $stats = app(StatService::class);
        $hourly = $stats->hourlyDistribution(7);
        $this->assertCount(24, $hourly);
        $this->assertSame(2, $hourly->sum('pv'));

        $hosts = $stats->topRefererHosts(now()->toDateString(), now()->toDateString());
        $this->assertEqualsCanonicalizing(['google.com', '直接访问'], $hosts->pluck('host')->all());
    }

    public function test_spider_health_page_and_export(): void
    {
        StatHit::query()->create([
            'path' => '/',
            'ip' => '8.8.8.8',
            'visitor_hash' => hash('sha256', 'g'),
            'is_spider' => true,
            'spider_name' => 'Googlebot',
            'status_code' => 200,
            'created_at' => now(),
        ]);
        StatHit::query()->create([
            'path' => '/missing',
            'ip' => '1.2.3.4',
            'visitor_hash' => hash('sha256', 'b'),
            'is_spider' => true,
            'spider_name' => 'Baiduspider',
            'status_code' => 404,
            'created_at' => now(),
        ]);

        $stats = app(StatService::class);
        $kpis = $stats->spiderKpis(now()->toDateString(), now()->toDateString());
        $this->assertSame(2, $kpis['pv']);
        $this->assertSame(1, $kpis['errors']);
        $this->assertTrue(collect($kpis['presence'])->firstWhere('name', 'Googlebot')['today']);
        $this->assertContains('搜索引擎', collect($stats->spiderComposition(now()->toDateString(), now()->toDateString()))->pluck('name'));
        $this->assertSame('首页', $stats->topSpiderPages(now()->toDateString(), now()->toDateString())->firstWhere('path', '/')['title']);

        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.stats.spiders'))
            ->assertOk()
            ->assertSee('搜索引擎今日到访')
            ->assertSee('被爬最多的页')
            ->assertSee('非 200 抓取')
            ->assertSee('最近抓取')
            ->assertSee('spiderTrendChart');

        $csv = $this->actingAs($admin, 'admin')
            ->get(route('admin.stats.spiders.export'))
            ->assertOk()
            ->streamedContent();
        $this->assertStringContainsString('Googlebot', $csv);
        $this->assertStringContainsString('搜索引擎', $csv);
    }

    public function test_access_logs_are_readable_and_filterable(): void
    {
        StatHit::query()->create([
            'path' => '/',
            'ip' => '127.0.0.1',
            'visitor_hash' => hash('sha256', 'home-log'),
            'user_agent' => 'curl/8.21.0',
            'referer' => null,
            'is_spider' => false,
            'status_code' => 200,
            'created_at' => now(),
        ]);
        StatHit::query()->create([
            'path' => '/news',
            'ip' => '8.8.8.8',
            'visitor_hash' => hash('sha256', 'news-log'),
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            'referer' => 'https://google.com/search',
            'is_spider' => false,
            'status_code' => 200,
            'created_at' => now(),
        ]);
        StatHit::query()->create([
            'path' => '/missing',
            'ip' => '8.8.8.8',
            'visitor_hash' => hash('sha256', 'miss-log'),
            'user_agent' => 'Mozilla/5.0',
            'is_spider' => false,
            'status_code' => 404,
            'created_at' => now(),
        ]);

        $stats = app(StatService::class);
        $this->assertSame('直接访问', $stats->refererLabel(null));
        $this->assertSame('google.com', $stats->refererLabel('https://google.com/search'));
        $this->assertSame('curl', $stats->clientLabel('curl/8.21.0'));
        $this->assertTrue($stats->isLocalProbe('127.0.0.1', 'curl/8.21.0'));

        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.stats.logs', ['path' => '/news']))
            ->assertOk()
            ->assertSee('新闻中心')
            ->assertSee('google.com')
            ->assertDontSee('/missing')
            ->assertSee('共 1 条');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.stats.logs', ['status' => '404']))
            ->assertOk()
            ->assertSee('/missing')
            ->assertSee('404')
            ->assertSee('仅 404')
            ->assertSee('今天')
            ->assertSee('近 7 天');

        $hash = hash('sha256', 'trail-log');
        StatHit::query()->create([
            'path' => '/',
            'ip' => '9.9.9.9',
            'visitor_hash' => $hash,
            'user_agent' => 'Mozilla/5.0 Chrome/120.0.0.0',
            'is_spider' => false,
            'status_code' => 200,
            'created_at' => now()->subMinutes(2),
        ]);
        StatHit::query()->create([
            'path' => '/missing',
            'ip' => '9.9.9.9',
            'visitor_hash' => $hash,
            'user_agent' => 'Mozilla/5.0 Chrome/120.0.0.0',
            'is_spider' => false,
            'status_code' => 404,
            'created_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.stats.logs', ['hash' => $hash]))
            ->assertOk()
            ->assertSee('该访客轨迹')
            ->assertSee('首页')
            ->assertSee('/missing')
            ->assertSee('同 IP')
            ->assertSee('轨迹');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.stats.logs', ['probe' => 'hide']))
            ->assertOk()
            ->assertDontSee('curl/8.21.0')
            ->assertSee('8.8.8.8');

        $csv = $this->actingAs($admin, 'admin')
            ->get(route('admin.stats.logs.export'))
            ->assertOk()
            ->streamedContent();
        $this->assertStringContainsString('首页', $csv);
        $this->assertStringContainsString('新闻中心', $csv);
    }

    public function test_visit_logs_paginate_twenty(): void
    {
        for ($i = 1; $i <= 21; $i++) {
            StatHit::query()->create([
                'path' => '/paginate-hit',
                'ip' => '10.20.0.'.$i,
                'visitor_hash' => hash('sha256', 'paginate-'.$i),
                'user_agent' => 'Mozilla/5.0 Chrome/120.0.0.0',
                'is_spider' => false,
                'status_code' => 200,
                'created_at' => now(),
            ]);
        }

        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.stats.logs', ['path' => '/paginate-hit']))
            ->assertOk()
            ->assertSee('共 21 条')
            ->assertSee('page=2', false);
    }

    public function test_pagination_uses_compact_bootstrap_not_tailwind_svg(): void
    {
        $html = (new \Illuminate\Pagination\LengthAwarePaginator(
            collect(range(1, 40)),
            80,
            40,
            1,
            ['path' => '/admin/stats/logs']
        ))->links()->toHtml();

        $this->assertStringContainsString('pagination', $html);
        $this->assertStringNotContainsString('w-5 h-5', $html);
    }
}
