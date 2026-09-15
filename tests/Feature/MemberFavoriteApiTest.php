<?php

namespace Tests\Feature;

use App\Models\Cms\Entry;
use App\Models\Cms\Member;
use App\Models\Cms\MemberFavorite;
use App\Models\Cms\Setting;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberFavoriteApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
        Setting::setValue('api_enable', '1');
        Setting::setValue('api_token', 'member-api-test');
    }

    public function test_member_can_toggle_favorite_and_see_it_in_profile(): void
    {
        $member = Member::query()->where('email', 'member@example.com')->first();
        $this->assertNotNull($member);
        $entry = $this->seededEntry('hello-blog');

        $this->actingAs($member, 'member')
            ->post(route('member.favorite', $entry))
            ->assertRedirect();

        $this->assertTrue($member->hasFavorited($entry->id));

        $this->actingAs($member, 'member')
            ->get(route('member.profile'))
            ->assertOk()
            ->assertSee('我的收藏')
            ->assertSee($entry->t('title'))
            ->assertSee('手机')
            ->assertSee('简介');

        $this->actingAs($member, 'member')
            ->post(route('member.profile.update'), [
                'name' => $member->name,
                'phone' => '13800138000',
                'bio' => '喜欢 Laravel',
            ])
            ->assertRedirect();

        $member->refresh();
        $this->assertSame('13800138000', $member->phone);
        $this->assertSame('喜欢 Laravel', $member->bio);

        $this->actingAs($member, 'member')
            ->post(route('member.favorite', $entry))
            ->assertRedirect();

        $this->assertFalse($member->hasFavorited($entry->id));
        $this->assertSame(0, MemberFavorite::query()->count());
    }

    public function test_guest_favorite_redirects_to_login(): void
    {
        $entry = $this->seededEntry('hello-blog');

        $this->post(route('member.favorite', $entry))
            ->assertRedirect(route('member.login'));
    }

    public function test_member_api_login_profile_favorites_and_gated_content(): void
    {
        $entry = $this->seededEntry('hello-blog');
        $gated = $this->seededEntry('client-windows');
        $this->assertTrue($gated?->isMemberGated());

        $headers = ['X-CMS-Token' => 'member-api-test'];

        $this->withHeaders($headers)
            ->getJson('/api/content/'.$gated->id)
            ->assertStatus(403);

        $login = $this->withHeaders($headers)
            ->postJson('/api/member/login', [
                'email' => 'member@example.com',
                'password' => 'Member@12345',
            ])
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.member.email', 'member@example.com');

        $token = $login->json('data.token');
        $this->assertIsString($token);
        $this->assertStringStartsWith('mbr_', $token);

        $auth = array_merge($headers, ['Authorization' => 'Bearer '.$token]);

        $this->withHeaders($auth)
            ->getJson('/api/member/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'member@example.com');

        $this->withHeaders($auth)
            ->putJson('/api/member/profile', [
                'phone' => '13900001111',
                'bio' => 'API bio',
            ])
            ->assertOk()
            ->assertJsonPath('data.phone', '13900001111')
            ->assertJsonPath('data.bio', 'API bio');

        $this->withHeaders($auth)
            ->postJson('/api/member/favorite/'.$entry->id)
            ->assertOk()
            ->assertJsonPath('data.favorited', true);

        $this->withHeaders($auth)
            ->getJson('/api/member/favorites')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.list.0.id', $entry->id);

        $this->withHeaders($auth)
            ->getJson('/api/content/'.$gated->id)
            ->assertOk()
            ->assertJsonPath('data.id', $gated->id);

        $this->withHeaders($auth)
            ->postJson('/api/member/logout')
            ->assertOk();

        $this->withHeaders($auth)
            ->getJson('/api/member/me')
            ->assertStatus(401);
    }
}
