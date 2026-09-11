<?php

namespace Tests\Feature;

use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
    }

    public function test_site_config(): void
    {
        $this->getJson('/api/site/config')
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonStructure(['code', 'msg', 'data' => ['site_name', 'locales', 'url_mode']]);
    }

    public function test_category_and_content_list(): void
    {
        $this->getJson('/api/category/list')->assertOk()->assertJsonPath('code', 200);
        $this->getJson('/api/category/tree')->assertOk()->assertJsonPath('code', 200);

        $list = $this->getJson('/api/content/list?limit=1')->assertOk()->json('data.list');
        $this->assertIsArray($list);
        $this->assertNotEmpty($list);

        $id = $list[0]['id'];
        $this->getJson('/api/content/'.$id)
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonStructure(['data' => ['id', 'title', 'content', 'attrs']]);
    }

    public function test_recommend_query_block(): void
    {
        $this->getJson('/api/recommend?name=latest_news')
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonStructure(['data']);
    }

    public function test_front_and_admin_login_pages(): void
    {
        $this->get('/')->assertOk();
        $this->get('/admin/login')->assertOk();
        $this->get('/member/login')->assertOk();
    }
}
