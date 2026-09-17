<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Setting;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ThemeTemplateEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
        $this->wipeScratch();
    }

    protected function tearDown(): void
    {
        $this->wipeScratch();
        parent::tearDown();
    }

    public function test_template_index_is_a_page_picker(): void
    {
        $this->copyTheme();
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $html = $this->actingAs($admin, 'admin')
            ->get(route('admin.themes.templates', 'tcopy'))
            ->assertOk()
            ->assertSee('还没有打开文件')
            ->assertSee('首页')
            ->assertSee('整站头尾')
            ->assertSee('tpl-group', false)
            ->assertSee('搜页面')
            ->assertSee('Ctrl+S')
            ->assertSee('绿色是 Blade')
            ->assertSee('@cms')
            ->assertSee('TplCodeEditor', false)
            ->assertSee('codemirror.min.js', false)
            ->assertSee('blade.js', false)
            ->assertSee('插入附件')
            ->assertSee('id="mediaPickerUpload"', false)
            ->assertSee('mediaUrl', false)
            ->assertSee('uploadUrl', false)
            ->assertSee(route('admin.themes.show', 'tcopy'), false)
            ->getContent();

        $this->assertStringNotContainsString('hooks.php', $html);
        $this->assertStringNotContainsString('theme.json', $html);
    }

    public function test_bundled_theme_is_read_only(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.themes.templates', 'enterprise'))
            ->assertOk()
            ->assertSee('自带主题只读')
            ->assertSee('复制一套');

        $original = File::get(resource_path('views/themes/enterprise/index/index.blade.php'));
        $this->postJson(route('admin.themes.templates.save', 'enterprise'), [
            'path' => 'index/index.blade.php',
            'content' => '<p>不该写入自带主题</p>',
        ])->assertOk()->assertJsonPath('ok', false)->assertJsonPath('msg', '自带主题请先复制一套再改');
        $this->assertSame($original, File::get(resource_path('views/themes/enterprise/index/index.blade.php')));
    }

    public function test_can_read_save_backup_and_rollback(): void
    {
        $this->copyTheme();
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin');

        $this->get(route('admin.themes.templates.read', ['theme' => 'tcopy', 'path' => 'index/index.blade.php']))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.path', 'index/index.blade.php')
            ->assertJsonPath('data.label', '首页')
            ->assertJsonPath('data.writable', true);

        $this->postJson(route('admin.themes.templates.save', 'tcopy'), [
            'path' => '../enterprise/index/index.blade.php',
            'content' => '<p>no</p>',
        ])->assertOk()->assertJsonPath('ok', false);

        $this->postJson(route('admin.themes.templates.save', 'tcopy'), [
            'path' => 'missing.blade.php',
            'content' => '<p>no</p>',
        ])->assertOk()->assertJsonPath('ok', false)->assertJsonPath('msg', '文件不存在');

        $this->postJson(route('admin.themes.templates.save', 'tcopy'), [
            'path' => 'index/index.blade.php',
            'content' => "<p>模板编辑器标记A</p>\n",
        ])->assertOk()->assertJsonPath('ok', true);
        $this->assertStringContainsString('模板编辑器标记A', File::get(resource_path('views/themes/tcopy/index/index.blade.php')));

        $this->postJson(route('admin.themes.templates.save', 'tcopy'), [
            'path' => 'index/index.blade.php',
            'content' => "<p>模板编辑器标记B</p>\n",
        ])->assertOk()->assertJsonPath('ok', true);

        $this->postJson(route('admin.themes.templates.rollback', 'tcopy'), [
            'path' => 'index/index.blade.php',
        ])->assertOk()->assertJsonPath('ok', true);
        $this->assertStringContainsString('模板编辑器标记A', File::get(resource_path('views/themes/tcopy/index/index.blade.php')));
        $this->assertStringNotContainsString('模板编辑器标记B', File::get(resource_path('views/themes/tcopy/index/index.blade.php')));

        $this->post(route('admin.themes.apply', 'tcopy'))
            ->assertRedirect(route('admin.themes.index'));
        $this->assertSame('tcopy', Setting::getValue('theme'));
        $this->get('/')
            ->assertOk()
            ->assertSee('模板编辑器标记A');
    }

    public function test_editor_cannot_open_templates(): void
    {
        $editor = AdminUser::query()->where('email', 'editor@example.com')->first();
        $this->actingAs($editor, 'admin')
            ->get(route('admin.themes.templates', 'enterprise'))
            ->assertForbidden();
        $this->postJson(route('admin.themes.templates.save', 'enterprise'), [
            'path' => 'index/index.blade.php',
            'content' => 'no',
        ])->assertForbidden();
    }

    protected function copyTheme(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->post(route('admin.themes.store'), [
                'from' => 'default',
                'slug' => 'tcopy',
                'name' => '可改主题',
            ])
            ->assertRedirect(route('admin.themes.index'))
            ->assertSessionHas('status');
    }

    protected function wipeScratch(): void
    {
        foreach (['tcopy'] as $slug) {
            $dir = resource_path('views/themes/'.$slug);
            if (is_dir($dir)) {
                File::deleteDirectory($dir);
            }
            $bak = storage_path('app/theme-backups'.DIRECTORY_SEPARATOR.$slug);
            if (is_dir($bak)) {
                File::deleteDirectory($bak);
            }
        }
    }
}
