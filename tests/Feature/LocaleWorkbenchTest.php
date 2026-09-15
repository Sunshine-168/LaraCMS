<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\CmsLocale;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use App\Support\Locale;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_list_is_a_workbench(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $zh = CmsLocale::query()->where('code', 'zh_cn')->first();
        $en = CmsLocale::query()->where('code', 'en')->first();
        $this->assertNotNull($zh);
        $this->assertNotNull($en);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.locales.index'))
            ->assertOk()
            ->assertSee('语言管理')
            ->assertSee('内容语言')
            ->assertSee('后台界面')
            ->assertSee('主题文案')
            ->assertSee('内容多语言怎么写')
            ->assertSee('新增语言')
            ->assertSee('名称，如 日本語')
            ->assertSee('简体中文')
            ->assertSee('English')
            ->assertSee('zh_cn')
            ->assertSee('地址不加前缀')
            ->assertSee('当前默认')
            ->assertSee('设为默认')
            ->assertSee('译文')
            ->assertSee(route('admin.locales.edit', $zh), false)
            ->assertDontSee('隐藏默认前缀')
            ->assertDontSee('本地名')
            ->assertDontSee('>代码</th>', false)
            ->assertDontSee('>状态</th>', false)
            ->assertDontSee('>编辑</a>', false)
            ->assertDontSee('>默认</th>', false);
    }

    public function test_quick_add_toggle_and_make_default(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->from(route('admin.locales.index'))
            ->post(route('admin.locales.store'), [
                'label' => '日本語',
                'code' => 'ja',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.locales.index', ['tab' => 'content']))
            ->assertSessionHas('status')
            ->assertSessionHas('locale_quick_added');

        $row = CmsLocale::query()->where('code', 'ja')->first();
        $this->assertNotNull($row);
        $this->assertSame('日本語', $row->label);
        $this->assertSame('日本語', $row->native);
        $this->assertTrue($row->is_active);
        $this->assertFalse($row->is_default);

        $this->get(route('admin.locales.index'))
            ->assertOk()
            ->assertSee('日本語')
            ->assertSee('设为默认');

        $this->post(route('admin.locales.toggle', $row))
            ->assertRedirect();
        $this->assertFalse($row->fresh()->is_active);

        $this->post(route('admin.locales.default', $row->fresh()))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->post(route('admin.locales.toggle', $row->fresh()))->assertRedirect();
        $this->post(route('admin.locales.default', $row->fresh()))
            ->assertRedirect()
            ->assertSessionHas('status');
        $this->assertTrue($row->fresh()->is_default);
        $this->assertFalse(CmsLocale::query()->where('id', '!=', $row->id)->where('is_default', true)->exists());
        $this->assertTrue(Locale::hideDefault());
    }

    public function test_create_form_is_name_first(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.locales.create'))
            ->assertOk()
            ->assertSee('名称')
            ->assertSee('前台怎么写')
            ->assertSee('代码')
            ->assertSee('添加语言')
            ->assertSee('添加后不能改')
            ->assertDontSee('本地显示名')
            ->assertDontSee('隐藏默认语言 URL 前缀')
            ->assertDontSee('代码（如 zh_cn');
    }

    public function test_cannot_toggle_or_delete_default_locale(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $zh = CmsLocale::query()->where('code', 'zh_cn')->first();
        $this->assertNotNull($zh);
        $this->assertTrue($zh->is_default);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.locales.toggle', $zh))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertTrue($zh->fresh()->is_active);

        $this->delete(route('admin.locales.destroy', $zh))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertNotNull($zh->fresh());
    }

    public function test_existing_code_cannot_change(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $en = CmsLocale::query()->where('code', 'en')->first();
        $this->assertNotNull($en);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.locales.update', $en), [
                'from_form' => '1',
                'label' => 'English',
                'native' => 'EN',
                'code' => 'ja',
                'sort' => 20,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.locales.index', ['tab' => 'content']));

        $fresh = $en->fresh();
        $this->assertSame('en', $fresh->code);
        $this->assertSame('EN', $fresh->native);
    }

    public function test_admin_ui_switches_sidebar_to_english(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->from(route('admin.locales.index', ['tab' => 'admin']))
            ->post(route('admin.ui-locale'), ['ui_locale' => 'en'])
            ->assertRedirect();

        $this->assertSame('en', $admin->fresh()->ui_locale);

        $this->get(route('admin.locales.index'))
            ->assertOk()
            ->assertSee('Languages')
            ->assertSee('Admin UI')
            ->assertSee('Theme phrases')
            ->assertSee('>Dashboard</span>', false)
            ->assertSee('>Entries</span>', false)
            ->assertSee('View site');
    }

    public function test_theme_phrases_save_and_show_on_front(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->put(route('admin.locales.theme'), [
                'theme_ui' => [
                    'search' => ['zh_cn' => '站内找', 'en' => 'Find'],
                    'login' => ['zh_cn' => '登录', 'en' => 'Log in'],
                    'register' => ['zh_cn' => '注册', 'en' => 'Register'],
                    'member' => ['zh_cn' => '会员', 'en' => 'Member'],
                    'logout' => ['zh_cn' => '退出', 'en' => 'Log out'],
                    'home' => ['zh_cn' => '首页', 'en' => 'Home'],
                ],
            ])
            ->assertRedirect(route('admin.locales.index', ['tab' => 'theme']));

        $this->get('/')
            ->assertOk()
            ->assertSee('站内找')
            ->assertSee('简体中文')
            ->assertSee('English');

        $this->get('/en')
            ->assertOk()
            ->assertSee('Find');
    }

    public function test_quick_add_keeps_input_and_shows_code_error(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->from(route('admin.locales.index'))
            ->followingRedirects()
            ->post(route('admin.locales.store'), [
                'label' => '日本語',
                'code' => 'en',
                'is_active' => '1',
            ])
            ->assertOk()
            ->assertSee('这个代码已经有了')
            ->assertSee('value="日本語"', false)
            ->assertSee('value="en"', false);
    }

    public function test_front_lang_switch_keeps_search_query(): void
    {
        $this->get('/search?q=hello')
            ->assertOk()
            ->assertSee('aria-current="page"', false)
            ->assertSee('href="/en/search?q=hello"', false)
            ->assertSee('>搜索</h1>', false)
            ->assertSee('placeholder="关键词"', false);

        $this->get('/en/search?q=hello')
            ->assertOk()
            ->assertSee('>Search</h1>', false)
            ->assertSee('placeholder="Keywords"', false)
            ->assertSee('href="/search?q=hello"', false);
    }

    public function test_saving_theme_clears_stale_disk_html(): void
    {
        $root = storage_path('framework/testing/disk-html-locale');
        config(['cms.disk_html.root' => $root]);
        \Illuminate\Support\Facades\File::deleteDirectory($root);
        \Illuminate\Support\Facades\File::ensureDirectoryExists($root);
        \Illuminate\Support\Facades\File::put($root.DIRECTORY_SEPARATOR.'index.html', '<html>STALE_LANG</html>');
        \App\Models\Cms\Setting::setValue('disk_html_enabled', '1');

        $this->get('/')
            ->assertOk()
            ->assertSee('STALE_LANG')
            ->assertHeader('X-CMS-Disk-Html', 'HIT');

        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->put(route('admin.locales.theme'), [
                'theme_ui' => [
                    'search' => ['zh_cn' => '站内找', 'en' => 'Find'],
                    'login' => ['zh_cn' => '登录', 'en' => 'Log in'],
                    'register' => ['zh_cn' => '注册', 'en' => 'Register'],
                    'member' => ['zh_cn' => '会员', 'en' => 'Member'],
                    'logout' => ['zh_cn' => '退出', 'en' => 'Log out'],
                    'home' => ['zh_cn' => '首页', 'en' => 'Home'],
                ],
            ])
            ->assertRedirect(route('admin.locales.index', ['tab' => 'theme']));

        $this->assertFileDoesNotExist($root.DIRECTORY_SEPARATOR.'index.html');
        $this->get('/')
            ->assertOk()
            ->assertDontSee('STALE_LANG')
            ->assertSee('aria-current="page"', false);

        \Illuminate\Support\Facades\File::deleteDirectory($root);
    }

    public function test_create_form_follows_admin_ui_locale(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->post(route('admin.ui-locale'), ['ui_locale' => 'en'])
            ->assertRedirect();

        $this->get(route('admin.locales.create'))
            ->assertOk()
            ->assertSee('Name')
            ->assertSee('Front label')
            ->assertSee('Add language')
            ->assertSee('Hide the default language in the URL');
    }

    public function test_coverage_counts_exact_titles_not_fallback(): void
    {
        $entry = \App\Models\Cms\Entry::query()->first();
        $this->assertNotNull($entry);
        $title = is_array($entry->title) ? $entry->title : [];
        $title['en'] = '';
        $entry->title = $title;
        $entry->save();

        $expectEn = 0;
        $expectZh = 0;
        \App\Models\Cms\Entry::query()->select(['id', 'title'])->each(function ($row) use (&$expectEn, &$expectZh) {
            if ($row->translation('title', 'en') !== '') {
                $expectEn++;
            }
            if ($row->translation('title', 'zh_cn') !== '') {
                $expectZh++;
            }
        });

        $cov = app(\App\Services\Admin\LocaleAdminService::class)->coverage();
        $this->assertSame($expectEn, $cov['en']['filled']);
        $this->assertSame($expectZh, $cov['zh_cn']['filled']);
        $this->assertLessThan($cov['en']['total'], $cov['en']['filled']);
    }

    public function test_pack_keeps_inactive_locale_values(): void
    {
        $out = \App\Support\Translatable::packTranslations(
            ['zh_cn' => ['title' => '中文'], 'en' => ['title' => 'English']],
            ['title'],
            ['title' => ['zh_cn' => '旧', 'ja' => '日本語', 'en' => 'Old']]
        );
        $this->assertSame('中文', $out['title']['zh_cn']);
        $this->assertSame('English', $out['title']['en']);
        $this->assertSame('日本語', $out['title']['ja']);
    }

    public function test_theme_and_menu_forms_include_inactive_locales(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->post(route('admin.locales.store'), [
                'label' => '日本語',
                'code' => 'ja',
                'is_active' => '1',
            ]);
        $row = CmsLocale::query()->where('code', 'ja')->first();
        $this->assertNotNull($row);
        $this->post(route('admin.locales.toggle', $row));
        $this->assertFalse($row->fresh()->is_active);

        $this->get(route('admin.locales.index', ['tab' => 'theme']))
            ->assertOk()
            ->assertSee('日本語')
            ->assertSee('theme_ui[search][ja]', false)
            ->assertSee('theme_ui[prev][ja]', false)
            ->assertSee('上一篇');

        $this->get(route('admin.menus.create'))
            ->assertOk()
            ->assertSee('items[0][title_ja]', false);

        $this->get(route('admin.recommends.create'))
            ->assertOk()
            ->assertSee('items[0][title_ja]', false);
    }

    public function test_entry_form_starts_with_default_locale_tab(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $modelId = \App\Models\Cms\ContentModel::query()->value('id');
        $this->assertNotNull($modelId);

        $html = $this->actingAs($admin, 'admin')
            ->get(route('admin.entries.create', ['model_id' => $modelId]))
            ->assertOk()
            ->getContent();

        $zh = strpos($html, 'data-locale-tab="zh_cn"');
        $en = strpos($html, 'data-locale-tab="en"');
        $this->assertNotFalse($zh);
        $this->assertNotFalse($en);
        $this->assertLessThan($en, $zh);
    }

    public function test_member_login_and_comments_follow_content_locale(): void
    {
        $this->get('/member/login')
            ->assertOk()
            ->assertSee('会员登录')
            ->assertSee('忘记密码');

        $this->get('/en/member/login')
            ->assertOk()
            ->assertSee('Member login')
            ->assertSee('Forgot password')
            ->assertSee('Log in');

        $this->get('/news/cms-launch')
            ->assertOk()
            ->assertSee('评论')
            ->assertSee('提交评论');

        $this->get('/en/news/cms-launch')
            ->assertOk()
            ->assertSee('Comments')
            ->assertSee('Post comment');
    }
}
