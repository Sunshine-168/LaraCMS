<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\CmsFlag;
use App\Models\Cms\ContentModel;
use App\Models\Cms\Entry;
use App\Models\Cms\Member;
use App\Models\Cms\MemberGroup;
use App\Models\Cms\Setting;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FrontGateAndThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_download_and_rss_honor_member_gate(): void
    {
        $entry = Entry::query()->find(4);
        $this->assertNotNull($entry);
        $entry->attrs = array_merge($entry->attrs ?? [], [
            'require_login' => false,
            'islogin' => false,
            'member_only' => true,
            'file' => 'cms/missing.bin',
        ]);
        $entry->save();
        $this->assertTrue($entry->fresh()->isMemberGated());

        $this->get('/download/'.$entry->id)->assertRedirect();
        $this->get('/rss.xml')->assertOk()->assertDontSee($entry->t('title'), false);
        $this->get('/en/rss.xml')->assertOk();
        $this->get('/en/sitemap.xml')->assertOk()->assertSee('/en/', false);
    }

    public function test_default_theme_keeps_own_skin(): void
    {
        Setting::setValue('theme', 'default');
        $this->get('/news')
            ->assertOk()
            ->assertSee('Source Han Sans')
            ->assertDontSee('Literata')
            ->assertDontSee('#1b4f72');
        $this->get('/search')->assertOk()->assertSee('搜索');
        $this->get('/en/search')->assertOk()->assertSee('/en/search', false);
    }

    public function test_new_site_copies_member_groups_and_allows_same_flag_name(): void
    {
        $default = Site::default();
        $this->assertNotNull($default);
        $second = Site::query()->create([
            'name' => '二站',
            'domain' => 'site-c.test',
            'is_default' => false,
            'is_active' => true,
        ]);
        $second->copyBasicsFrom($default);

        CurrentSite::run($second, function () {
            $this->assertGreaterThan(0, MemberGroup::query()->count());
        });

        $ungrouped = Member::query()->create([
            'name' => '无组',
            'email' => 'nogroup@example.com',
            'password' => 'Secret#12345',
            'is_active' => true,
            'group_id' => null,
        ]);
        $this->assertFalse($ungrouped->canMember('download'));

        $empty = Site::query()->create([
            'name' => '空站旗标',
            'domain' => 'empty-flags.test',
            'is_active' => true,
        ]);
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->withSession(['admin_site_id' => $empty->id])
            ->post(route('admin.flags.store'), [
                'name' => 'recommend',
                'title' => '推荐',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.flags.index'));

        $this->assertNotNull(
            CmsFlag::query()->withoutGlobalScopes()
                ->where('site_id', $empty->id)
                ->where('name', 'recommend')
                ->first()
        );
    }

    public function test_api_search_skips_unsearchable_models_and_member_can_read_own_entry(): void
    {
        $article = ContentModel::query()->where('slug', 'article')->first();
        $this->assertNotNull($article);
        $article->is_searchable = false;
        $article->save();

        $list = $this->getJson('/api/search?q=CMS')->assertOk()->json('data.list');
        foreach ($list as $row) {
            $this->assertNotSame('article', $row['model'] ?? null);
        }

        $token = $this->postJson('/api/member/login', [
            'email' => 'member@example.com',
            'password' => 'Member@12345',
        ])->assertOk()->json('data.token');

        $created = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/member/entries', [
                'model_id' => $article->id,
                'category_id' => 1,
                'title' => '闸门测试稿',
                'content' => '<p>详情正文</p>',
            ])
            ->assertOk();
        $id = $created->json('data.id');
        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/member/entries/'.$id)
            ->assertOk()
            ->assertJsonPath('data.content', '<p>详情正文</p>');
    }

    public function test_guest_search_and_api_omit_gated_and_links_use_go(): void
    {
        $gated = Entry::query()->find(4);
        $this->assertNotNull($gated);

        $this->get('/search?q=Windows')
            ->assertOk()
            ->assertDontSee($gated->t('title'), false);

        $listIds = array_column($this->getJson('/api/content/list')->assertOk()->json('data.list'), 'id');
        $this->assertNotContains(4, $listIds);

        $searchIds = array_column($this->getJson('/api/search?q=Windows')->assertOk()->json('data.list'), 'id');
        $this->assertNotContains(4, $searchIds);

        $link = Entry::query()->with('modelDef')->find(5);
        $this->assertNotNull($link);
        $this->assertStringContainsString('/go/'.$link->id, $link->url);

        $this->get('/en/member/login')
            ->assertOk()
            ->assertSee('/en/member/login', false);

        $this->get('/en/search')
            ->assertOk()
            ->assertSee('Search', false);
    }

    public function test_admin_create_pages_do_not_compile_help_text_as_tags(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin);

        $this->actingAs($admin, 'admin')
            ->withSession(['admin_site_id' => Site::default()->id])
            ->get(route('admin.recommends.create'))
            ->assertOk()
            ->assertSee('@cmsAdv', false);

        $this->actingAs($admin, 'admin')
            ->withSession(['admin_site_id' => Site::default()->id])
            ->get(route('admin.menus.create'))
            ->assertOk()
            ->assertSee('@cmsMenu', false);

        $this->actingAs($admin, 'admin')
            ->withSession(['admin_site_id' => Site::default()->id])
            ->get(route('admin.forms.create'))
            ->assertOk()
            ->assertSee('@cmsForm', false);
    }

    public function test_theme_appearance_saves_and_renders_on_front(): void
    {
        Storage::fake('public');
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin);

        $this->actingAs($admin, 'admin')
            ->withSession(['admin_site_id' => Site::default()->id])
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('外观与品牌')
            ->assertSee('备案号');

        $this->actingAs($admin, 'admin')
            ->withSession(['admin_site_id' => Site::default()->id])
            ->put(route('admin.settings.update'), [
                'site_name' => '外观测试站',
                'theme' => 'download',
                'primary_color' => '#112233',
                'icp' => '京ICP备00000000号',
                'footer_text' => '页脚自定义行',
                'hero_title' => '自定义下载首页',
                'hero_subtitle' => '运营副标题',
                'logo_file' => UploadedFile::fake()->image('logo.png', 80, 40),
                'comments_enabled' => '1',
                'comments_guest' => '1',
                'tag_autolink' => '1',
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertSame('#112233', Setting::getValue('primary_color'));
        $this->assertSame('京ICP备00000000号', Setting::getValue('icp'));
        $this->assertNotEmpty(Setting::getValue('logo'));

        $this->get('/')
            ->assertOk()
            ->assertSee('#112233', false)
            ->assertSee('京ICP备00000000号')
            ->assertSee('页脚自定义行')
            ->assertSee('自定义下载首页')
            ->assertSee('运营副标题')
            ->assertDontSee('软件下载中心')
            ->assertSee('外观测试站')
            ->assertSee('/storage/cms/brand', false);

        $this->getJson('/api/site/config')
            ->assertOk()
            ->assertJsonPath('data.icp', '京ICP备00000000号')
            ->assertJsonPath('data.primary_color', '#112233')
            ->assertJsonPath('data.hero_title', '自定义下载首页');

        Setting::setValue('primary_color', 'red');
        $this->get('/')->assertOk()->assertDontSee('--accent: red', false);
    }
}
