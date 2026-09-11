<?php

namespace App\Http\Controllers\Api;

use App\Http\Middleware\ApiToken;
use App\Models\Cms\Entry;
use App\Models\Cms\Member;
use App\Services\Cms\MemberContributeService;
use App\Services\Cms\MemberFavoriteService;
use App\Support\Captcha;
use App\Support\Cdn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MemberApiController extends ApiController
{
    public function __construct(
        private readonly MemberFavoriteService $favorites,
        private readonly MemberContributeService $contribute,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $skipCaptcha = ApiToken::providedTokenIsValid($request);
        $needCaptcha = Captcha::enabled() && ! $skipCaptcha;
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'captcha' => $needCaptcha ? 'required' : 'nullable',
        ]);
        if ($needCaptcha) {
            Captcha::assertValid($request->input('captcha'));
        }

        $member = Member::query()->where('email', $data['email'])->where('is_active', true)->first();
        if (! $member || ! Hash::check($data['password'], $member->password)) {
            return $this->fail('邮箱或密码错误', 401);
        }

        $token = $member->issueApiToken();
        $member->loadMissing('group');

        return $this->ok([
            'token' => $token,
            'member' => $this->payload($member),
        ], '登录成功');
    }

    public function logout(): JsonResponse
    {
        $member = Auth::guard('member')->user();
        if ($member instanceof Member) {
            $member->clearApiToken();
        }

        return $this->ok(null, '已退出');
    }

    public function me(): JsonResponse
    {
        $member = Auth::guard('member')->user();
        if (! $member instanceof Member) {
            return $this->fail('请先登录会员', 401);
        }
        $member->loadMissing('group');

        return $this->ok($this->payload($member));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $member = Auth::guard('member')->user();
        if (! $member instanceof Member) {
            return $this->fail('请先登录会员', 401);
        }

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:50',
            'avatar' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:32',
            'bio' => 'nullable|string|max:500',
        ]);

        foreach (['name', 'avatar', 'phone', 'bio'] as $field) {
            if (array_key_exists($field, $data)) {
                $member->{$field} = $data[$field] !== '' ? $data[$field] : null;
            }
        }
        if ($member->name === null || $member->name === '') {
            return $this->fail('昵称不能为空');
        }
        $member->save();
        $member->loadMissing('group');

        return $this->ok($this->payload($member), '资料已更新');
    }

    public function favorites(Request $request): JsonResponse
    {
        $member = Auth::guard('member')->user();
        if (! $member instanceof Member) {
            return $this->fail('请先登录会员', 401);
        }

        $limit = max(1, min(50, (int) $request->query('limit', 20)));
        $page = $member->favoriteEntries()
            ->with(['category', 'modelDef'])
            ->orderByDesc('cms_member_favorites.id')
            ->paginate($limit);

        return $this->ok([
            'list' => $page->getCollection()->map(fn (Entry $e) => [
                'id' => $e->id,
                'title' => $e->t('title'),
                'url' => $e->url,
                'cover' => cms_media($e->cover),
            ])->values(),
            'total' => $page->total(),
            'page' => $page->currentPage(),
            'limit' => $page->perPage(),
        ]);
    }

    public function toggleFavorite(int $entry): JsonResponse
    {
        $member = Auth::guard('member')->user();
        if (! $member instanceof Member) {
            return $this->fail('请先登录会员', 401);
        }

        $model = Entry::query()->find($entry);
        if (! $model || ! $model->isLive()) {
            return $this->fail('内容不存在', 404);
        }
        if ($model->isMemberGated() && ! $member->canMember('view_member_content')) {
            return $this->fail('无权收藏该内容', 403);
        }

        $result = $this->favorites->toggle($member, $model);
        if (! $result['ok']) {
            return $this->fail($result['msg']);
        }

        return $this->ok($result['data'], $result['msg']);
    }

    public function entries(Request $request): JsonResponse
    {
        $member = Auth::guard('member')->user();
        if (! $member instanceof Member) {
            return $this->fail('请先登录会员', 401);
        }
        if (! $member->canMember('contribute')) {
            return $this->fail('当前会员组无权投稿', 403);
        }

        $limit = max(1, min(50, (int) $request->query('limit', 20)));
        $page = $member->contributedEntries()->with(['category', 'modelDef'])->orderByDesc('id')->paginate($limit);

        return $this->ok([
            'list' => $page->getCollection()->map(fn (Entry $e) => $this->entryPayload($e, false))->values(),
            'total' => $page->total(),
            'page' => $page->currentPage(),
            'limit' => $page->perPage(),
        ]);
    }

    public function storeEntry(Request $request): JsonResponse
    {
        $member = Auth::guard('member')->user();
        if (! $member instanceof Member) {
            return $this->fail('请先登录会员', 401);
        }
        $submit = $request->boolean('submit');
        $result = $this->contribute->save($member, null, $request->all(), $submit);
        if (! $result['ok']) {
            return $this->fail($result['msg']);
        }

        return $this->ok($this->entryPayload($result['data'], true), $result['msg']);
    }

    public function updateEntry(Request $request, int $entry): JsonResponse
    {
        $member = Auth::guard('member')->user();
        if (! $member instanceof Member) {
            return $this->fail('请先登录会员', 401);
        }
        $model = Entry::query()->find($entry);
        if (! $model) {
            return $this->fail('内容不存在', 404);
        }
        $submit = $request->boolean('submit') || $request->input('action') === 'submit';
        $result = $this->contribute->save($member, $model, $request->all(), $submit);
        if (! $result['ok']) {
            return $this->fail($result['msg']);
        }

        return $this->ok($this->entryPayload($result['data'], true), $result['msg']);
    }

    public function submitEntry(int $entry): JsonResponse
    {
        $member = Auth::guard('member')->user();
        if (! $member instanceof Member) {
            return $this->fail('请先登录会员', 401);
        }
        $model = Entry::query()->find($entry);
        if (! $model) {
            return $this->fail('内容不存在', 404);
        }
        $result = $this->contribute->submit($member, $model);
        if (! $result['ok']) {
            return $this->fail($result['msg']);
        }

        return $this->ok($this->entryPayload($result['data'], true), $result['msg']);
    }

    public function showEntry(int $entry): JsonResponse
    {
        $member = Auth::guard('member')->user();
        if (! $member instanceof Member) {
            return $this->fail('请先登录会员', 401);
        }
        if (! $member->canMember('contribute')) {
            return $this->fail('当前会员组无权投稿', 403);
        }
        $model = $member->contributedEntries()->with(['category', 'modelDef'])->find($entry);
        if (! $model) {
            return $this->fail('内容不存在', 404);
        }

        return $this->ok($this->entryPayload($model, true));
    }

    /** @return array<string, mixed> */
    protected function entryPayload(Entry $e, bool $detail = false): array
    {
        $data = [
            'id' => $e->id,
            'title' => $e->t('title'),
            'slug' => $e->t('slug'),
            'excerpt' => $e->t('excerpt'),
            'cover' => cms_media($e->cover),
            'status' => $e->status,
            'category_id' => $e->category_id,
            'model_id' => $e->model_id,
            'url' => $e->status === 'published' ? $e->url : null,
            'attrs' => $e->attrs ?? [],
        ];
        if ($detail) {
            $data['content'] = Cdn::rewriteHtml((string) $e->t('content'));
            $data['attrs'] = Cdn::rewriteValue($e->attrs ?? []);
        }

        return $data;
    }

    /** @return array<string, mixed> */
    protected function payload(Member $member): array
    {
        return [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'avatar' => cms_media($member->avatar),
            'phone' => $member->phone,
            'bio' => $member->bio,
            'group' => $member->group?->name,
        ];
    }
}
