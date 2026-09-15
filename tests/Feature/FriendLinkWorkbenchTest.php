<?php

namespace Tests\Feature;

use App\Models\Admin\AdminRole;
use App\Models\Admin\AdminUser;
use App\Models\Cms\Recommend;
use App\Models\Cms\RecommendItem;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FriendLinkWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
    }

    public function test_list_is_a_workbench_on_partners_block(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $partners = Recommend::query()->where('name', 'partners')->first();
        $this->assertNotNull($partners);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('>友链</span>', false);

        $item = RecommendItem::query()->where('recommend_id', $partners->id)->first();
        $this->assertNotNull($item);

        $this->get(route('admin.friend-links.index'))
            ->assertOk()
            ->assertSee('nav-fold is-open', false)
            ->assertSee('添加友链')
            ->assertSee('网站名称，如 示例站')
            ->assertSee('打开完整表单')
            ->assertSee('阿里云')
            ->assertSee('https://www.aliyun.com')
            ->assertSee('网址目录')
            ->assertSee(route('admin.friend-links.create'), false)
            ->assertSee(route('admin.friend-links.edit', $item), false)
            ->assertDontSee('新建友链')
            ->assertDontSee('>标识</th>', false)
            ->assertDontSee('>推荐位</th>', false);
    }

    public function test_quick_add_toggle_and_front(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->from(route('admin.friend-links.index'))
            ->post(route('admin.friend-links.store'), [
                'title' => '示例站',
                'url' => 'example.com',
            ])
            ->assertRedirect(route('admin.friend-links.index'))
            ->assertSessionHas('status')
            ->assertSessionHas('friend_link_quick_added');

        $partners = Recommend::query()->where('name', 'partners')->first();
        $this->assertNotNull($partners);
        $row = RecommendItem::query()
            ->where('recommend_id', $partners->id)
            ->where('url', 'https://example.com')
            ->first();
        $this->assertNotNull($row);
        $this->assertSame('示例站', $row->t('title'));

        $this->get('/')
            ->assertOk()
            ->assertSee('示例站')
            ->assertSee('友情链接');

        $this->post(route('admin.friend-links.toggle', $row))
            ->assertRedirect();
        $this->assertFalse($row->fresh()->is_active);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('示例站');
    }

    public function test_editor_can_manage_and_operator_cannot(): void
    {
        $editor = AdminUser::query()->where('email', 'editor@example.com')->first();
        $this->assertNotNull($editor);
        $operatorRole = AdminRole::query()->where('slug', 'operator')->first();
        $this->assertNotNull($operatorRole);
        $operator = AdminUser::query()->create([
            'name' => '运营',
            'email' => 'operator-friend@example.com',
            'password' => 'Operator@12345',
            'role_id' => $operatorRole->id,
            'is_active' => true,
        ]);
        $operator->sites()->sync([Site::default()->id]);

        $this->actingAs($editor, 'admin')
            ->get(route('admin.friend-links.index'))
            ->assertOk()
            ->assertSee('添加友链');

        $this->actingAs($operator, 'admin')
            ->get(route('admin.friend-links.index'))
            ->assertForbidden();
    }

    public function test_empty_list_stays_a_workbench(): void
    {
        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $partners = Recommend::query()->where('name', 'partners')->first();
        $this->assertNotNull($partners);
        RecommendItem::query()->where('recommend_id', $partners->id)->delete();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.friend-links.index'))
            ->assertOk()
            ->assertSee('还没有友链')
            ->assertSee('添加友链')
            ->assertSee('打开完整表单')
            ->assertDontSee('暂无友情链接');
    }
}
