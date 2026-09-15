<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\AdminLog;
use App\Models\Cms\CmsLocale;
use App\Models\Cms\CollectorLog;
use App\Models\Cms\Comment;
use App\Models\Cms\Entry;
use App\Models\Cms\EntryRevision;
use App\Models\Cms\FormSubmission;
use App\Models\Cms\Media;
use App\Models\Cms\MemberFavorite;
use App\Models\Cms\Redirect;
use App\Models\Cms\StatHit;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_seed_fills_operational_tables(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(0, Comment::query()->count());
        $this->assertGreaterThan(0, FormSubmission::query()->count());
        $this->assertGreaterThan(0, Redirect::query()->count());
        $this->assertGreaterThan(0, AdminLog::query()->count());
        $this->assertGreaterThan(0, Media::query()->count());
        $this->assertGreaterThan(0, StatHit::query()->count());
        $this->assertGreaterThan(0, CollectorLog::query()->count());
        $this->assertGreaterThan(0, EntryRevision::query()->count());
        $this->assertGreaterThan(0, MemberFavorite::query()->count());
        $this->assertTrue(Comment::query()->pending()->exists());
        $this->assertTrue(FormSubmission::query()->where('is_read', false)->exists());
        $this->assertTrue(Entry::query()->where('status', 'pending')->exists());
        $this->assertTrue(Entry::query()->where('status', 'scheduled')->exists());
        $this->assertTrue(Entry::query()->where('status', 'draft')->exists());
        $this->assertNotNull(CmsLocale::query()->where('code', 'ja')->where('is_active', false)->first());

        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin?->last_login_at);
        $this->assertSame('127.0.0.1', $admin->last_login_ip);
        $this->assertNotNull(AdminUser::query()->where('email', 'operator@example.com')->first());
    }
}
