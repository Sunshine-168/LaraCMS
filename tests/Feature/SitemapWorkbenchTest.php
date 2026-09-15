<?php

namespace Tests\Feature;

use App\Models\Admin\AdminRole;
use App\Models\Admin\AdminUser;
use App\Models\Cms\Setting;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_page_is_a_workbench_in_more(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.sitemap.edit'))
            ->assertOk()
            ->assertSee('nav-fold is-open', false)
            ->assertSee('>地图</span>', false)
            ->assertSee('收录范围')
            ->assertSee('栏目（不含外链栏目）')
            ->assertSee('已发布的公开文章')
            ->assertSee('标签页')
            ->assertSee('提供网页版给访客')
            ->assertSee('/en/...', false)
            ->assertSee('补充地址')
            ->assertSee('打开 XML')
            ->assertSee('/sitemap.xml', false)
            ->assertSee('各语言')
            ->assertSee('robots.txt', false)
            ->assertSee('保存站点地图')
            ->assertSee('sitemap-title', false)
            ->assertSee('sitemap-loc', false)
            ->assertSee('sitemap-split', false)
            ->assertSee('sitemap-card-head', false)
            ->assertSee('现在 ', false)
            ->assertSee('table-scroll', false)
            ->assertDontSee('sitemap_include');
    }

    public function test_save_toggles_tags_and_extra_url(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('/tag/laravel', false)
            ->assertSee('<changefreq>', false)
            ->assertSee('<priority>', false)
            ->assertDontSee('/about-extra-map', false);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: ')
            ->assertSee('/en/sitemap.xml', false);

        $this->get('/en/robots.txt')
            ->assertOk()
            ->assertSee('/en/sitemap.xml', false);

        $this->get('/en/sitemap.xml')
            ->assertOk()
            ->assertSee('/en</loc>', false)
            ->assertSee('/en/', false);

        $this->get('/sitemap')
            ->assertOk()
            ->assertSee('站点地图')
            ->assertSee('栏目')
            ->assertSee('搜索引擎请抓')
            ->assertSee('>站点地图</span>', false)
            ->assertSee('rel="noopener"', false);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.sitemap.update'), [
                'sitemap_categories' => '1',
                'sitemap_entries' => '1',
                'sitemap_tags' => '0',
                'sitemap_html' => '1',
                'sitemap_entry_limit' => '200',
                'sitemap_extra' => "/about-extra-map\n<script>",
            ])
            ->assertRedirect(route('admin.sitemap.edit'))
            ->assertSessionHas('status', '站点地图已保存');

        $this->assertSame('0', Setting::getValue('sitemap_tags'));
        $this->assertSame('/about-extra-map', Setting::getValue('sitemap_extra'));

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertDontSee('/tag/laravel', false)
            ->assertSee('/about-extra-map', false)
            ->assertDontSee('<script>', false);
    }

    public function test_html_page_can_be_turned_off(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->put(route('admin.sitemap.update'), [
                'sitemap_categories' => '1',
                'sitemap_entries' => '1',
                'sitemap_tags' => '1',
                'sitemap_html' => '0',
                'sitemap_entry_limit' => '2000',
            ])
            ->assertRedirect();

        $this->get('/sitemap')->assertNotFound();
        $this->get('/en/sitemap')->assertNotFound();
        $this->get('/sitemap.xml')->assertOk();
        $this->get('/')->assertOk()->assertDontSee('>站点地图</a>', false);
    }

    public function test_closed_site_still_serves_xml(): void
    {
        Setting::setValue('site_closed', '1');
        $this->get('/sitemap.xml')->assertOk()->assertSee('<urlset', false);
        $this->get('/en/sitemap.xml')->assertOk();
        $this->get('/sitemap')->assertStatus(503);
        $this->get('/en/sitemap')->assertStatus(503);
    }

    public function test_extra_query_is_xml_escaped_and_scripts_dropped(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->put(route('admin.sitemap.update'), [
                'sitemap_categories' => '1',
                'sitemap_entries' => '0',
                'sitemap_tags' => '0',
                'sitemap_html' => '1',
                'sitemap_entry_limit' => '50',
                'sitemap_extra' => "javascript:alert(1)\ndata:text/html,hi\nftp://files.example.com\nmailto:hi@example.com\n/q?a=1&b=2\nhttps://example.com/offsite",
            ])
            ->assertRedirect();

        $this->assertSame("/q?a=1&b=2\nhttps://example.com/offsite", Setting::getValue('sitemap_extra'));
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('a=1&amp;b=2', false)
            ->assertSee('https://example.com/offsite', false)
            ->assertDontSee('javascript:', false)
            ->assertDontSee('data:text', false)
            ->assertDontSee('ftp://', false)
            ->assertDontSee('mailto:', false);
        $this->get('/sitemap')
            ->assertOk()
            ->assertSee('rel="noopener nofollow"', false);
    }

    public function test_html_cache_does_not_freeze_sitemap_xml(): void
    {
        Setting::setValue('html_cache_enabled', '1');
        Setting::setValue('html_cache_ttl', '3600');
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('/cache-map-extra', false);
        Setting::setValue('sitemap_extra', '/cache-map-extra');
        $this->get('/sitemap.xml')->assertOk()->assertSee('/cache-map-extra', false);
        $this->get('/en/sitemap.xml')->assertOk()->assertSee('/cache-map-extra', false);
    }

    public function test_editor_can_manage_and_operator_cannot(): void
    {
        $editor = AdminUser::query()->where('email', 'editor@example.com')->first();
        $this->assertNotNull($editor);
        $operatorRole = AdminRole::query()->where('slug', 'operator')->first();
        $operator = AdminUser::query()->create([
            'name' => '运营',
            'email' => 'operator-map@example.com',
            'password' => 'Operator@12345',
            'role_id' => $operatorRole->id,
            'is_active' => true,
        ]);
        $operator->sites()->sync([Site::default()->id]);

        $this->actingAs($editor, 'admin')
            ->get(route('admin.sitemap.edit'))
            ->assertOk()
            ->assertSee('收录范围');

        $this->actingAs($operator, 'admin')
            ->get(route('admin.sitemap.edit'))
            ->assertForbidden();
        $this->actingAs($operator, 'admin')
            ->put(route('admin.sitemap.update'), [
                'sitemap_html' => '0',
            ])
            ->assertForbidden();
    }

    public function test_empty_tag_and_duplicate_robots_sitemap_are_skipped(): void
    {
        \App\Models\Cms\Tag::query()->create([
            'title' => ['zh_cn' => '空标签', 'en' => 'Empty'],
            'slug' => ['zh_cn' => 'empty-map-tag', 'en' => 'empty-map-tag'],
        ]);
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('/tag/laravel', false)
            ->assertDontSee('/tag/empty-map-tag', false);

        Setting::setValue('robots_extra', 'Sitemap: '.cms_front_url('sitemap.xml')."\nDisallow: /private-area");
        $body = $this->get('/robots.txt')->assertOk()->getContent();
        $this->assertSame(1, substr_count($body, 'Sitemap: '.cms_front_url('sitemap.xml')));
        $this->assertStringContainsString('Disallow: /private-area', $body);
    }
}
