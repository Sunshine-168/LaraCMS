<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Entry;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecycleWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_empty_recycle_explains_and_links_back(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.recycle.index'))
            ->assertOk()
            ->assertSee('回收站是空的')
            ->assertSee('不会自动清空')
            ->assertSee('去内容列表')
            ->assertDontSee('>ID</th>', false)
            ->assertDontSee('清空回收站');
    }

    public function test_deleted_entry_can_be_found_and_restored(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $about = Entry::query()->find(1);
        $gateway = Entry::query()->find(3);
        $this->assertNotNull($about);
        $this->assertNotNull($gateway);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.entries.destroy', $about))
            ->assertRedirect();
        $this->delete(route('admin.entries.destroy', $gateway));

        $this->get(route('admin.recycle.index'))
            ->assertOk()
            ->assertSee('关于我们')
            ->assertSee('智能网关')
            ->assertSee('还原')
            ->assertSee('form="batchForm"', false)
            ->assertSee('id="batchBar" hidden', false);

        $this->get(route('admin.recycle.index', ['q' => '关于我们']))
            ->assertOk()
            ->assertSee('关于我们')
            ->assertDontSee('智能网关');

        $this->post(route('admin.recycle.restore', $about->id))
            ->assertRedirect(route('admin.recycle.index'))
            ->assertSessionHas('status');

        $this->assertNull(Entry::onlyTrashed()->find($about->id));
        $this->assertNotNull(Entry::query()->find($about->id));
        $this->get(route('admin.recycle.index'))->assertDontSee('关于我们');
    }

    public function test_batch_restore_keeps_search(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $entry = Entry::query()->find(2);
        $this->assertNotNull($entry);
        $entry->delete();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.recycle.batch'), [
                'action' => 'restore',
                'ids' => [$entry->id],
                'keep_q' => 'CMS',
            ])
            ->assertRedirect(route('admin.recycle.index', ['q' => 'CMS']))
            ->assertSessionHas('status');

        $this->assertNotNull(Entry::query()->find($entry->id));
    }
}
