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

class ThemeWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    protected array $scratch = ['tdrop', 'tcopy', 'tcli'];

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

    public function test_list_is_a_workbench(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.themes.index'))
            ->assertOk()
            ->assertSee('外观主题')
            ->assertSee('复制一套自己改')
            ->assertSee('resources/views/themes/')
            ->assertSee('企业')
            ->assertSee('默认')
            ->assertSee('用于本站')
            ->assertSee('当前使用')
            ->assertSee('清空缓存')
            ->assertDontSee('应用市场')
            ->assertDontSee('config/cms.php');
    }

    public function test_drop_in_folder_appears_and_can_apply(): void
    {
        $this->writeDropIn();
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.themes.index'))
            ->assertOk()
            ->assertSee('独立主题')
            ->assertSee('tdrop')
            ->assertSee('丢文件夹即可');

        $this->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('独立主题')
            ->assertSee('复制或添加主题');

        $this->post(route('admin.themes.apply', 'tdrop'))
            ->assertRedirect(route('admin.themes.index'))
            ->assertSessionHas('status');

        $this->assertSame('tdrop', Setting::getValue('theme'));
        $this->get('/')
            ->assertOk()
            ->assertSee('独立主题首页标记');
    }

    public function test_copy_apply_and_remove(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->from(route('admin.themes.index'))
            ->post(route('admin.themes.store'), [
                'from' => 'default',
                'slug' => 'tcopy',
                'name' => '品牌站',
                'description' => '自己改的外观',
            ])
            ->assertRedirect(route('admin.themes.index'))
            ->assertSessionHas('status')
            ->assertSessionHas('theme_quick_added');

        $dir = resource_path('views/themes/tcopy');
        $this->assertDirectoryExists($dir);
        $this->assertFileExists($dir.DIRECTORY_SEPARATOR.'theme.json');
        $this->assertStringContainsString('themes.tcopy.', File::get($dir.DIRECTORY_SEPARATOR.'index'.DIRECTORY_SEPARATOR.'index.blade.php'));
        $this->assertStringNotContainsString('themes.default.', File::get($dir.DIRECTORY_SEPARATOR.'index'.DIRECTORY_SEPARATOR.'index.blade.php'));

        $this->get(route('admin.themes.index'))
            ->assertOk()
            ->assertSee('品牌站')
            ->assertSee('tcopy');

        $this->post(route('admin.themes.apply', 'tcopy'))
            ->assertRedirect(route('admin.themes.index'))
            ->assertSessionHas('status');
        $this->assertSame('tcopy', Setting::getValue('theme'));

        $this->delete(route('admin.themes.destroy', 'tcopy'))
            ->assertRedirect(route('admin.themes.index'))
            ->assertSessionHas('error');
        $this->assertDirectoryExists($dir);

        Setting::setValue('theme', 'enterprise');
        $this->delete(route('admin.themes.destroy', 'tcopy'))
            ->assertRedirect(route('admin.themes.index'))
            ->assertSessionHas('status');
        $this->assertDirectoryDoesNotExist($dir);
    }

    public function test_cannot_remove_bundled_theme(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->delete(route('admin.themes.destroy', 'enterprise'))
            ->assertRedirect(route('admin.themes.index'))
            ->assertSessionHas('error');
        $this->assertDirectoryExists(resource_path('views/themes/enterprise'));
    }

    public function test_artisan_copies_a_theme(): void
    {
        $this->artisan('cms:theme-make', [
            'slug' => 'tcli',
            '--from' => 'default',
            '--name' => '命令主题',
        ])->assertSuccessful();

        $this->assertDirectoryExists(resource_path('views/themes/tcli'));
        $meta = json_decode((string) File::get(resource_path('views/themes/tcli/theme.json')), true);
        $this->assertSame('命令主题', $meta['name'] ?? null);
    }

    public function test_editor_cannot_open_themes(): void
    {
        $editor = AdminUser::query()->where('email', 'editor@example.com')->first();
        $this->actingAs($editor, 'admin')
            ->get(route('admin.themes.index'))
            ->assertForbidden();
    }

    protected function writeDropIn(): void
    {
        $dir = resource_path('views/themes/tdrop');
        File::ensureDirectoryExists($dir.DIRECTORY_SEPARATOR.'index');
        File::put($dir.DIRECTORY_SEPARATOR.'theme.json', json_encode([
            'name' => '独立主题',
            'description' => '丢文件夹即可',
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n");
        File::put($dir.DIRECTORY_SEPARATOR.'index'.DIRECTORY_SEPARATOR.'index.blade.php', <<<'BLADE'
@extends('themes.enterprise.partials.layout')
@section('content')
<p>独立主题首页标记</p>
@endsection
BLADE
        );
    }

    protected function wipeScratch(): void
    {
        foreach ($this->scratch as $slug) {
            $dir = resource_path('views/themes/'.$slug);
            if (is_dir($dir)) {
                File::deleteDirectory($dir);
            }
        }
    }
}
