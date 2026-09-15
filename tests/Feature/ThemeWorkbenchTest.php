<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Category;
use App\Models\Cms\ContentModel;
use App\Models\Cms\Setting;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class ThemeWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    protected array $scratch = ['tdrop', 'tcopy', 'tcli', 'tzip', 'tpack'];

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
            ->assertSee('>主题</h1>', false)
            ->assertSee('复制一套')
            ->assertSee('上传 zip')
            ->assertSee('resources/views/themes/')
            ->assertSee('企业')
            ->assertSee('默认')
            ->assertSee('用于本站')
            ->assertSee('配置')
            ->assertSee('当前使用')
            ->assertSee('体育信号')
            ->assertSee('多语言')
            ->assertSee('自适应')
            ->assertSee(route('admin.themes.preview', 'enterprise'), false)
            ->assertSee(route('admin.themes.preview', 'sports'), false)
            ->assertDontSee('缺模型')
            ->assertDontSee('应用市场')
            ->assertDontSee('config/cms.php');

        $this->get(route('admin.themes.index', ['add' => 'copy']))
            ->assertOk()
            ->assertSee('标识 brand');
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
            ->assertSee('丢文件夹即可')
            ->assertSee(route('admin.themes.preview', 'tdrop'), false);

        $this->get(route('admin.themes.preview', 'tdrop'))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

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
            ->assertSessionHas('status');

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

    public function test_upload_zip_then_remove(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive 不可用');
        }

        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $pack = $this->makeThemeZip('tzip-pack.zip', [
            'tzip/theme.json' => json_encode(['name' => '压缩主题', 'description' => 'zip 包'], JSON_UNESCAPED_UNICODE),
            'tzip/shell.php' => '<?php echo 1;',
            'tzip/index/index.blade.php' => <<<'BLADE'
@extends('themes.enterprise.partials.layout')
@section('content')
<p>压缩主题首页标记</p>
@endsection
BLADE
        ]);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.themes.index'))
            ->post(route('admin.themes.upload'), [
                'pack' => $pack,
                'name' => '压缩主题',
            ])
            ->assertRedirect(route('admin.themes.index'))
            ->assertSessionHas('status');

        $dir = resource_path('views/themes/tzip');
        $this->assertDirectoryExists($dir);
        $this->assertFileExists($dir.DIRECTORY_SEPARATOR.'index'.DIRECTORY_SEPARATOR.'index.blade.php');
        $this->assertFileDoesNotExist($dir.DIRECTORY_SEPARATOR.'shell.php');

        $this->get(route('admin.themes.index'))
            ->assertOk()
            ->assertSee('压缩主题')
            ->assertSee('tzip');

        $this->delete(route('admin.themes.destroy', 'tzip'))
            ->assertRedirect(route('admin.themes.index'))
            ->assertSessionHas('status');
        $this->assertDirectoryDoesNotExist($dir);
    }

    public function test_upload_zip_rejects_traversal_and_bundled(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive 不可用');
        }

        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin');

        $slip = $this->makeThemeZip('tzip-slip.zip', [
            '../evil/index/index.blade.php' => '<p>no</p>',
        ]);
        $this->from(route('admin.themes.index'))
            ->post(route('admin.themes.upload'), ['pack' => $slip])
            ->assertRedirect(route('admin.themes.index'))
            ->assertSessionHas('error');
        $this->assertDirectoryDoesNotExist(resource_path('views/themes/evil'));

        $overwrite = $this->makeThemeZip('enterprise.zip', [
            'index/index.blade.php' => '<p>no</p>',
        ]);
        $this->from(route('admin.themes.index'))
            ->post(route('admin.themes.upload'), [
                'pack' => $overwrite,
                'slug' => 'enterprise',
            ])
            ->assertRedirect(route('admin.themes.index'))
            ->assertSessionHas('error');
        $this->assertStringNotContainsString('压缩主题首页标记', File::get(resource_path('views/themes/enterprise/index/index.blade.php')));
    }

    public function test_copy_keeps_declared_models(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->from(route('admin.themes.index'))
            ->post(route('admin.themes.store'), [
                'from' => 'sports',
                'slug' => 'tpack',
                'name' => '赛场',
            ])
            ->assertRedirect(route('admin.themes.index'))
            ->assertSessionHas('status');

        $meta = json_decode((string) File::get(resource_path('views/themes/tpack/theme.json')), true);
        $this->assertContains('signal', $meta['models'] ?? []);
        $this->assertNotEmpty($meta['categories'] ?? []);
        $this->assertTrue($meta['i18n'] ?? false);
        $this->assertTrue($meta['responsive'] ?? false);
    }

    public function test_apply_installs_pack_models_once(): void
    {
        $this->writePackTheme();
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.themes.index'))
            ->assertOk()
            ->assertSee('包装条目')
            ->assertSee('缺模型');

        $this->post(route('admin.themes.apply', 'tpack'))
            ->assertRedirect(route('admin.themes.index'))
            ->assertSessionHas('status');

        $model = ContentModel::query()->where('slug', 'tpackitem')->first();
        $this->assertNotNull($model);
        $this->assertSame('包装条目', $model->name);
        $this->assertTrue($model->fields()->where('name', 'line')->exists());

        $cat = Category::query()->get()->first(fn ($c) => ($c->slug['zh_cn'] ?? '') === 'tpack-cat');
        $this->assertNotNull($cat);
        $this->assertSame($model->id, (int) $cat->model_id);

        $this->post(route('admin.themes.apply', 'tpack'))
            ->assertRedirect(route('admin.themes.index'))
            ->assertSessionHas('status');
        $this->assertSame(1, ContentModel::query()->where('slug', 'tpackitem')->count());
        $this->assertSame(1, Category::query()->get()->filter(fn ($c) => ($c->slug['zh_cn'] ?? '') === 'tpack-cat')->count());
    }

    public function test_apply_writes_category_locale_titles(): void
    {
        $this->writePackTheme();
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->post(route('admin.themes.apply', 'tpack'))
            ->assertRedirect(route('admin.themes.index'));

        $cat = Category::query()->get()->first(fn ($c) => ($c->slug['zh_cn'] ?? '') === 'tpack-cat');
        $this->assertNotNull($cat);
        $this->assertSame('模型栏目', $cat->translation('title', 'zh_cn'));
        $this->assertSame('Pack channel', $cat->translation('title', 'en'));
        $this->assertSame('tpack-cat', $cat->translation('slug', 'en'));
    }

    public function test_apply_does_not_overwrite_existing_model(): void
    {
        $this->writePackTheme();
        ContentModel::query()->create([
            'slug' => 'tpackitem',
            'name' => '旧名字',
            'kind' => 'list',
            'list_tpl' => 'list.list',
            'show_tpl' => 'show.show',
            'sort_order' => 0,
            'is_active' => true,
        ]);
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->post(route('admin.themes.apply', 'tpack'))
            ->assertRedirect(route('admin.themes.index'));

        $model = ContentModel::query()->where('slug', 'tpackitem')->first();
        $this->assertNotNull($model);
        $this->assertSame('旧名字', $model->name);
        $this->assertSame(0, $model->fields()->count());
    }

    public function test_editor_cannot_open_themes(): void
    {
        $editor = AdminUser::query()->where('email', 'editor@example.com')->first();
        $this->actingAs($editor, 'admin')
            ->get(route('admin.themes.index'))
            ->assertForbidden();
        $this->get(route('admin.themes.preview', 'enterprise'))
            ->assertForbidden();
        $this->get(route('admin.themes.show', 'enterprise'))
            ->assertForbidden();
    }

    public function test_missing_preview_file_falls_back_to_svg(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.themes.preview', 'enterprise'))
            ->assertOk()
            ->assertHeader('content-type', 'image/svg+xml; charset=utf-8')
            ->assertSee('企业', false);
    }

    public function test_theme_config_saves_vars_and_phrases(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.themes.show', 'enterprise'))
            ->assertOk()
            ->assertSee('模板多语言')
            ->assertSee('这套要用的模型')
            ->assertSee('自定义参数')
            ->assertSee('首页公告')
            ->assertSee('文章')
            ->assertSee(admin_back('themes'));

        $this->put(route('admin.themes.update', 'enterprise'), [
            'i18n' => '1',
            'responsive' => '1',
            'models' => ['article', 'page', 'product', 'job'],
            'vars' => [
                'home_notice' => '主题公告标记',
                'cta_text' => '主题按钮',
                'cta_url' => '/about',
            ],
            'phrases' => [
                'search' => ['zh_cn' => '搜本套', 'en' => 'Search here'],
            ],
        ])
            ->assertRedirect(route('admin.themes.show', 'enterprise'))
            ->assertSessionHas('status');

        $this->get('/')
            ->assertOk()
            ->assertSee('主题公告标记')
            ->assertSee('搜本套');
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
        File::put(
            $dir.DIRECTORY_SEPARATOR.'preview.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')
        );
    }

    protected function writePackTheme(): void
    {
        $dir = resource_path('views/themes/tpack');
        File::ensureDirectoryExists($dir.DIRECTORY_SEPARATOR.'index');
        File::ensureDirectoryExists($dir.DIRECTORY_SEPARATOR.'models');
        File::put($dir.DIRECTORY_SEPARATOR.'theme.json', json_encode([
            'name' => '模型主题',
            'description' => '自带模型包',
            'models' => ['tpackitem'],
            'categories' => [
                ['slug' => 'tpack-cat', 'title' => ['zh_cn' => '模型栏目', 'en' => 'Pack channel'], 'model' => 'tpackitem', 'type' => 'list', 'menu' => true],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n");
        File::put($dir.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'tpackitem.json', json_encode([
            'version' => 1,
            'model' => [
                'slug' => 'tpackitem',
                'name' => '包装条目',
                'kind' => 'list',
                'list_tpl' => 'list.list',
                'show_tpl' => 'show.show',
                'sort_order' => 0,
                'is_active' => true,
                'is_searchable' => true,
                'allow_contribute' => false,
            ],
            'fields' => [
                ['name' => 'line', 'label' => '线路', 'type' => 'text', 'is_list' => true, 'sort_order' => 0],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n");
        File::put($dir.DIRECTORY_SEPARATOR.'index'.DIRECTORY_SEPARATOR.'index.blade.php', <<<'BLADE'
@extends('themes.enterprise.partials.layout')
@section('content')
<p>包装主题首页</p>
@endsection
BLADE
        );
    }

    /** @param array<string, string> $files */
    protected function makeThemeZip(string $filename, array $files): UploadedFile
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.$filename;
        if (is_file($path)) {
            unlink($path);
        }
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true);
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        return new UploadedFile($path, $filename, 'application/zip', null, true);
    }

    protected function wipeScratch(): void
    {
        foreach ($this->scratch as $slug) {
            $dir = resource_path('views/themes/'.$slug);
            if (is_dir($dir)) {
                File::deleteDirectory($dir);
            }
        }
        $model = ContentModel::query()->where('slug', 'tpackitem')->first();
        if ($model) {
            Category::query()->where('model_id', $model->id)->delete();
            $model->fields()->delete();
            $model->delete();
        }
        foreach (Category::query()->get() as $cat) {
            if (in_array('tpack-cat', (array) $cat->slug, true)) {
                $cat->delete();
            }
        }
    }
}
