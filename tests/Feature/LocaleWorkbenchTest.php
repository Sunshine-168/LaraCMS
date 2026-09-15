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
            ->assertSee('新增语言')
            ->assertSee('名称，如 日本語')
            ->assertSee('简体中文')
            ->assertSee('English')
            ->assertSee('zh_cn')
            ->assertSee('地址不加前缀')
            ->assertSee('设为默认')
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
            ->assertRedirect(route('admin.locales.index'))
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
}
