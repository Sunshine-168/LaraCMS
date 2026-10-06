<?php

namespace Tests\Feature;

use App\Services\Cms\InstallService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicStorageFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_file_is_served_without_symlink(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('cms/2026/10/shot.txt', 'cms-storage');

        $response = $this->get('/storage/cms/2026/10/shot.txt');
        $response->assertOk();
        $this->assertStringContainsString('cms-storage', $response->streamedContent());
    }

    public function test_storage_rejects_path_traversal(): void
    {
        Storage::fake('public');

        $this->get('/storage/../.env')->assertNotFound();
    }

    public function test_relative_storage_target_points_at_app_public(): void
    {
        $relative = (new class extends InstallService
        {
            public function expose(string $link, string $target): string
            {
                return $this->relativeStorageTarget($link, $target);
            }
        })->expose(public_path('storage'), storage_path('app/public'));

        $this->assertSame('../storage/app/public', str_replace('\\', '/', $relative));
    }
}
