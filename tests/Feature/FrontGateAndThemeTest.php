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
}
