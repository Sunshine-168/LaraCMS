<?php

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Category;
use App\Models\Cms\ContentModel;
use App\Models\Cms\Entry;
use App\Models\Cms\Member;
use App\Models\Cms\MemberGroup;
use App\Models\Cms\Setting;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberContributeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
        Setting::setValue('api_enable', '1');
        Setting::setValue('api_token', 'contrib-token');
    }

    public function test_member_can_submit_draft_for_review_and_admin_can_publish(): void
    {
        $member = Member::query()->where('email', 'member@example.com')->first();
        $this->assertNotNull($member);
        $this->assertTrue($member->canMember('contribute'));

        $article = ContentModel::query()->where('slug', 'article')->first();
        $this->assertTrue((bool) $article?->allow_contribute);
        $news = Category::query()->get()->first(fn (Category $c) => $c->t('slug') === 'news');
        $this->assertNotNull($news);

        $this->actingAs($member, 'member')
            ->get(route('member.profile'))
            ->assertOk()
            ->assertSee('我的投稿')
            ->assertSee('写新稿');

        $this->actingAs($member, 'member')
            ->post(route('member.entries.store'), [
                'model_id' => $article->id,
                'category_id' => $news->id,
                'title' => '会员投来的新闻',
                'excerpt' => '摘要',
                'content' => '这是待审正文内容',
                'action' => 'submit',
            ])
            ->assertRedirect();

        $entry = Entry::query()->where('member_id', $member->id)->orderByDesc('id')->first();
        $this->assertNotNull($entry);
        $this->assertSame('pending', $entry->status);
        $this->assertFalse($entry->isLive());
        $this->get('/e/'.$entry->id)->assertNotFound();

        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.entries.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('会员投来的新闻')
            ->assertSee('待审');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.entries.batch'), [
                'action' => 'publish',
                'ids' => [$entry->id],
            ])
            ->assertRedirect();

        $entry->refresh();
        $this->assertTrue($entry->isLive());
        $this->get($entry->url)->assertOk()->assertSee('会员投来的新闻');
    }

    public function test_group_without_contribute_cannot_open_editor(): void
    {
        $group = MemberGroup::query()->create([
            'name' => '只读',
            'slug' => 'readonly',
            'is_active' => true,
            'permissions' => ['download', 'form_submit'],
        ]);
        $member = Member::query()->where('email', 'member@example.com')->first();
        $member->group_id = $group->id;
        $member->save();

        $this->actingAs($member, 'member')
            ->get(route('member.entries.create'))
            ->assertRedirect(route('member.profile'));
    }

    public function test_member_api_can_create_and_submit_entry(): void
    {
        $article = ContentModel::query()->where('slug', 'article')->first();
        $news = Category::query()->get()->first(fn (Category $c) => $c->t('slug') === 'news');
        $headers = ['X-CMS-Token' => 'contrib-token'];

        $token = $this->withHeaders($headers)
            ->postJson('/api/member/login', [
                'email' => 'member@example.com',
                'password' => 'Member@12345',
            ])
            ->assertOk()
            ->json('data.token');

        $auth = array_merge($headers, ['Authorization' => 'Bearer '.$token]);
        $created = $this->withHeaders($auth)
            ->postJson('/api/member/entries', [
                'model_id' => $article->id,
                'category_id' => $news->id,
                'title' => 'API 投稿标题',
                'content' => 'API 正文',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'draft');

        $id = $created->json('data.id');
        $this->withHeaders($auth)
            ->postJson('/api/member/entries/'.$id.'/submit')
            ->assertOk()
            ->assertJsonPath('data.status', 'pending');
    }
}
