<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Category;
use App\Models\Cms\Entry;
use App\Models\Cms\Setting;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultisiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
    }

    public function test_host_resolves_default_site_and_isolates_content(): void
    {
        $default = Site::default();
        $this->assertNotNull($default);

        $second = Site::query()->create([
            'name' => '第二站',
            'domain' => 'site-b.test',
            'is_default' => false,
            'is_active' => true,
        ]);
        $second->copyBasicsFrom($default);
        CurrentSite::run($second, function () {
            Setting::setValue('site_name', '第二站名称');
            $modelId = \App\Models\Cms\ContentModel::query()->value('id');
            if (! $modelId) {
                $modelId = \App\Models\Cms\ContentModel::query()->create([
                    'slug' => 'article',
                    'name' => '文章',
                    'kind' => 'list',
                    'is_active' => true,
                ])->id;
            }
            Category::query()->create([
                'title' => ['zh_cn' => 'B站栏目'],
                'slug' => ['zh_cn' => 'b-cat'],
                'type' => 'list',
                'model_id' => $modelId,
                'status' => 'published',
            ]);
        });

        $this->getJson('http://'.$default->domain.'/api/site/config')
            ->assertOk()
            ->assertJsonPath('data.site_id', $default->id)
            ->assertJsonPath('data.site_name', '综合 CMS 演示');

        $defaultCats = $this->getJson('http://'.$default->domain.'/api/category/list')->assertOk()->json('data.list');
        $this->assertNotEmpty($defaultCats);

        $this->getJson('http://site-b.test/api/site/config')
            ->assertOk()
            ->assertJsonPath('data.site_id', $second->id)
            ->assertJsonPath('data.site_name', '第二站名称');

        $bCats = $this->getJson('http://site-b.test/api/category/list')->assertOk()->json('data.list');
        $this->assertCount(1, $bCats);
        $this->assertSame('B站栏目', $bCats[0]['title'] ?? null);
    }

    public function test_editor_bound_to_default_site_can_switch(): void
    {
        $default = Site::default();
        $editor = AdminUser::query()->where('email', 'editor@example.com')->first();
        $this->assertNotNull($editor);
        $this->assertTrue($editor->sites->contains('id', $default->id));

        $this->actingAs($editor, 'admin')
            ->post('/admin/site/switch', ['site_id' => $default->id])
            ->assertRedirect();

        $second = Site::query()->create([
            'name' => '无权站',
            'domain' => 'no-access.test',
            'is_active' => true,
        ]);

        $this->actingAs($editor, 'admin')
            ->from('/admin')
            ->post('/admin/site/switch', ['site_id' => $second->id])
            ->assertRedirect('/admin')
            ->assertSessionHas('error');
    }

    public function test_entry_count_scoped_per_site(): void
    {
        $defaultCount = Entry::query()->count();
        $this->assertGreaterThan(0, $defaultCount);

        $second = Site::query()->create([
            'name' => '空站',
            'domain' => 'empty.test',
            'is_active' => true,
        ]);
        CurrentSite::set($second);
        $this->assertSame(0, Entry::query()->count());
        CurrentSite::clear();
    }
}
