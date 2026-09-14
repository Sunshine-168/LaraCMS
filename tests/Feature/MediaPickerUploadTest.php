<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Entry;
use App\Models\Cms\Media;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaPickerUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
        Storage::fake('public');
    }

    public function test_entry_form_picker_has_inline_upload(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $entry = Entry::query()->first();
        $this->assertNotNull($entry);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.entries.edit', $entry))
            ->assertOk()
            ->assertSee('选择或上传图片')
            ->assertSee('点击或拖入图片')
            ->assertSee('id="mediaPickerUpload"', false)
            ->assertDontSee('请先到「媒体附件」上传');
    }

    public function test_cover_field_shows_image_preview(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $entry = Entry::query()->whereNotNull('cover')->where('cover', '!=', '')->first();
        $this->assertNotNull($entry);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.entries.edit', $entry))
            ->assertOk()
            ->assertSee('data-media-preview="#entryCover"', false)
            ->assertSee('封面预览')
            ->assertSee($entry->cover, false);
    }

    public function test_admin_can_upload_image_from_picker_endpoint(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $file = $this->fakeJpeg('cover.jpg');

        $json = $this->actingAs($admin, 'admin')
            ->post(route('admin.media.upload'), [
                'file' => $file,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->json();

        $this->assertTrue($json['ok']);
        $this->assertNotEmpty($json['data']['url']);
        $this->assertDatabaseHas('cms_media', ['filename' => 'cover.jpg']);
        $media = Media::query()->where('filename', 'cover.jpg')->first();
        $this->assertNotNull($media);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_editor_can_upload_image_without_leaving_editor(): void
    {
        $editor = AdminUser::query()->where('email', 'editor@example.com')->first();
        $file = $this->fakeJpeg('from-editor.jpg');

        $this->actingAs($editor, 'admin')
            ->post(route('admin.media.upload'), ['file' => $file], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_picker_upload_rejects_non_image(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $file = UploadedFile::fake()->create('notes.txt', 12, 'text/plain');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.media.upload'), ['file' => $file], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }

    protected function fakeJpeg(string $name): UploadedFile
    {
        $bytes = base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAn/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAGfAP/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAQUCf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQMBAT8Bf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQIBAT8Bf//Z');
        $this->assertNotFalse($bytes);

        return UploadedFile::fake()->createWithContent($name, $bytes);
    }
}
