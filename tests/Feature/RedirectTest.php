<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Redirect;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_front_redirect_and_hit_count(): void
    {
        Redirect::query()->create([
            'source_path' => '/old-about',
            'target_url' => '/about',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $this->get('/old-about')->assertRedirect('/about');

        $row = Redirect::query()->where('source_path', '/old-about')->first();
        $this->assertNotNull($row);
        $this->assertSame(1, (int) $row->hits);
    }

    public function test_inactive_redirect_ignored(): void
    {
        Redirect::query()->create([
            'source_path' => '/gone',
            'target_url' => '/about',
            'status_code' => 302,
            'is_active' => false,
        ]);

        $this->get('/gone')->assertNotFound();
    }

    public function test_admin_can_manage_redirects(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->post(route('admin.redirects.store'), [
                'source_path' => 'legacy/post',
                'target_url' => '/blog',
                'status_code' => 301,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.redirects.index'));

        $this->assertDatabaseHas('cms_redirects', [
            'source_path' => '/legacy/post',
            'target_url' => '/blog',
        ]);
    }
}
