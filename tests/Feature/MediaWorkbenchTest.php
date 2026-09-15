<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Media;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
        Storage::fake('public');
    }

    public function test_library_is_a_workbench(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        Storage::disk('public')->put('cms/2026/09/shot.png', 'x');
        Media::query()->create([
            'disk' => 'public',
            'path' => 'cms/2026/09/shot.png',
            'filename' => 'shot.png',
            'mime' => 'image/png',
            'size' => 1200,
            'alt' => '封面图',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.media.index'))
            ->assertOk()
            ->assertSee('>媒体</h1>', false)
            ->assertSee('点击或拖入文件')
            ->assertSee('搜索文件名或说明')
            ->assertSee('shot.png')
            ->assertSee('封面图')
            ->assertSee('复制链接')
            ->assertSee('打开')
            ->assertSee('>删除</button>', false)
            ->assertDontSee('>Alt</label>', false)
            ->assertDontSee('>预览</th>', false)
            ->assertDontSee('>文件名</th>', false)
            ->assertDontSee('class="btn btn-danger"', false);
    }

    public function test_search_and_type_filter(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        Storage::disk('public')->put('cms/2026/09/cover.jpg', 'x');
        Media::query()->create([
            'disk' => 'public',
            'path' => 'cms/2026/09/cover.jpg',
            'filename' => 'cover.jpg',
            'mime' => 'image/jpeg',
            'size' => 800,
            'alt' => '封面',
        ]);
        Media::query()->create([
            'disk' => 'public',
            'path' => 'cms/2026/09/note.txt',
            'filename' => 'note.txt',
            'mime' => 'text/plain',
            'size' => 40,
            'alt' => '备忘',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.media.index', ['q' => 'cover']))
            ->assertOk()
            ->assertSee('cover.jpg')
            ->assertDontSee('note.txt');

        $this->get(route('admin.media.index', ['type' => 'file']))
            ->assertOk()
            ->assertSee('note.txt')
            ->assertDontSee('cover.jpg');
    }

    public function test_can_upload_multiple_from_library(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->post(route('admin.media.store'), [
                'files' => [$this->fakeJpeg('a.jpg')],
            ])
            ->assertRedirect(route('admin.media.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('cms_media', ['filename' => 'a.jpg']);
    }

    protected function fakeJpeg(string $name): UploadedFile
    {
        $bytes = base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAn/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAGfAP/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAQUCf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQMBAT8Bf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQIBAT8Bf//Z');
        $this->assertNotFalse($bytes);

        return UploadedFile::fake()->createWithContent($name, $bytes);
    }
}
