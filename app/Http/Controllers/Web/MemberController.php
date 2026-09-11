<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cms\Entry;
use App\Models\Cms\Member;
use App\Models\Cms\MemberGroup;
use App\Services\Cms\MemberChunkUploadService;
use App\Services\Cms\MemberContributeService;
use App\Services\Cms\MemberFavoriteService;
use App\Services\Cms\SiteFrontService;
use App\Support\CurrentSite;
use App\Support\MailConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function __construct(
        private readonly SiteFrontService $site,
        private readonly MemberChunkUploadService $chunkUpload,
        private readonly MemberFavoriteService $favorites,
        private readonly MemberContributeService $contribute,
    ) {}

    /** 会员登录页 */
    public function showLogin(): View
    {
        return $this->memberPage('member.login');
    }

    /** 提交登录 */
    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $ok = Auth::guard('member')->attempt(
            ['email' => $data['email'], 'password' => $data['password'], 'is_active' => true],
            $request->boolean('remember')
        );
        if (! $ok) {
            return back()->withErrors(['email' => '邮箱或密码错误'])->withInput();
        }

        $request->session()->regenerate();

        return redirect()->intended(route('member.profile'))->with('status', '登录成功');
    }

    /** 注册页 */
    public function showRegister(): View
    {
        return $this->memberPage('member.register');
    }

    /** 提交注册 */
    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'email' => [
                'required', 'email', 'max:120',
                Rule::unique('cms_members', 'email')->where(fn ($q) => $q->where('site_id', CurrentSite::idOrDefault())),
            ],
            'password' => 'required|string|min:6|confirmed',
        ]);

        $defaultGroupId = MemberGroup::query()
            ->where('slug', 'default')
            ->where('is_active', true)
            ->value('id');

        $member = Member::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_active' => true,
            'group_id' => $defaultGroupId,
        ]);

        Auth::guard('member')->login($member);
        $request->session()->regenerate();

        return redirect()->route('member.profile')->with('status', '注册成功');
    }

    /** 找回密码页 */
    public function showForgot(): View
    {
        return $this->memberPage('member.forgot');
    }

    /** 申请重置：生成一次性链接（未配邮件时直接展示链接，便于本地调试） */
    public function sendReset(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => 'required|email']);
        $member = Member::query()->where('email', $data['email'])->where('is_active', true)->first();

        if ($member) {
            $token = Str::random(48);
            $member->reset_token = hash('sha256', $token);
            $member->reset_token_expires_at = now()->addHour();
            $member->save();

            $url = route('member.reset', ['token' => $token, 'email' => $member->email]);

            $mailed = false;
            try {
                MailConfig::applyFromSettings();
                if (config('mail.mailers.smtp.host')) {
                    Mail::raw("请在 1 小时内打开以下链接重置密码：\n{$url}", function ($message) use ($member) {
                        $message->to($member->email)->subject('重置密码');
                    });
                    $mailed = true;
                }
            } catch (\Throwable) {
                $mailed = false;
            }

            if ($mailed) {
                return back()->with('status', '重置邮件已发送，请查收邮箱');
            }

            return back()->with('status', '重置链接已生成（1 小时内有效，未配置邮件时直接展示）')->with('reset_url', $url);
        }

        return back()->with('status', '若该邮箱已注册，将发送重置指引');
    }

    /** 重置密码页 */
    public function showReset(Request $request): View|RedirectResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
        ]);
        $site = $this->site->bootSite();

        return $this->memberPage('member.reset', [
            'site' => $site,
            'email' => $request->query('email'),
            'token' => $request->query('token'),
        ]);
    }

    /** 提交新密码 */
    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $member = Member::query()->where('email', $data['email'])->where('is_active', true)->first();
        if (
            ! $member
            || ! $member->reset_token
            || ! $member->reset_token_expires_at
            || $member->reset_token_expires_at->isPast()
            || ! hash_equals($member->reset_token, hash('sha256', $data['token']))
        ) {
            return back()->withErrors(['email' => '重置链接无效或已过期']);
        }

        $member->password = $data['password'];
        $member->reset_token = null;
        $member->reset_token_expires_at = null;
        $member->save();

        return redirect()->route('member.login')->with('status', '密码已重置，请登录');
    }

    /** 会员中心 */
    public function profile(): View|RedirectResponse
    {
        if (! Auth::guard('member')->check()) {
            return redirect()->route('member.login');
        }
        $site = $this->site->bootSite();
        $member = Auth::guard('member')->user();
        if (! $member) {
            return redirect()->route('member.login');
        }
        $member->loadMissing('group');

        $comments = $member->comments()->with('entry')->orderByDesc('id')->paginate(8, ['*'], 'comments_page');
        $submissions = $member->formSubmissions()->with('form')->orderByDesc('id')->paginate(8, ['*'], 'forms_page');
        $favorites = $member->favoriteEntries()->with(['category', 'modelDef'])
            ->orderByDesc('cms_member_favorites.id')
            ->paginate(12, ['*'], 'fav_page');
        $myEntries = $member->contributedEntries()->with(['category', 'modelDef'])->orderByDesc('id')->paginate(8, ['*'], 'posts_page');
        $canContribute = $member->canMember('contribute');
        $contributeModels = $canContribute ? $this->contribute->modelsFor($member) : collect();
        $memberEntries = collect();
        if ($member->canMember('view_member_content')) {
            $memberEntries = Entry::query()->with(['category', 'modelDef'])->published()
                ->orderByDesc('published_at')->orderByDesc('id')->limit(80)->get()
                ->filter(fn (Entry $e) => $e->isMemberGated() && $e->modelDef?->slug !== 'link')
                ->values();
        }

        return $this->memberPage('member.profile', compact(
            'site', 'member', 'comments', 'submissions', 'favorites', 'memberEntries',
            'myEntries', 'canContribute', 'contributeModels'
        ));
    }

    /** 更新资料 */
    public function updateProfile(Request $request): RedirectResponse
    {
        $member = Auth::guard('member')->user();
        if (! $member) {
            return redirect()->route('member.login');
        }
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'avatar' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:32',
            'bio' => 'nullable|string|max:500',
        ]);
        $member->name = $data['name'];
        if (array_key_exists('avatar', $data) && $data['avatar'] !== null) {
            $member->avatar = $data['avatar'] !== '' ? $data['avatar'] : null;
        }
        $member->phone = ($data['phone'] ?? '') !== '' ? $data['phone'] : null;
        $member->bio = ($data['bio'] ?? '') !== '' ? $data['bio'] : null;
        $member->save();

        return back()->with('status', '资料已更新');
    }

    /** 分片上传：初始化 */
    public function uploadInit(Request $request): JsonResponse
    {
        $member = Auth::guard('member')->user();
        if (! $member) {
            return response()->json(['ok' => false, 'msg' => '未登录'], 401);
        }
        $data = $request->validate([
            'filename' => 'required|string|max:200',
            'total_chunks' => 'required|integer|min:1|max:2000',
            'total_size' => 'required|integer|min:1|max:52428800',
        ]);
        $result = $this->chunkUpload->init($member, $data['filename'], (int) $data['total_chunks'], (int) $data['total_size']);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /** 分片上传：单片 */
    public function uploadChunk(Request $request): JsonResponse
    {
        $member = Auth::guard('member')->user();
        if (! $member) {
            return response()->json(['ok' => false, 'msg' => '未登录'], 401);
        }
        $data = $request->validate([
            'upload_id' => 'required|string|max:64',
            'index' => 'required|integer|min:0',
            'chunk' => 'required|file|max:2048',
        ]);
        $result = $this->chunkUpload->storeChunk($member, $data['upload_id'], (int) $data['index'], $request->file('chunk'));

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /** 分片上传：合并 */
    public function uploadComplete(Request $request): JsonResponse
    {
        $member = Auth::guard('member')->user();
        if (! $member) {
            return response()->json(['ok' => false, 'msg' => '未登录'], 401);
        }
        $data = $request->validate([
            'upload_id' => 'required|string|max:64',
            'purpose' => 'nullable|in:file,avatar',
        ]);
        $result = $this->chunkUpload->complete($member, $data['upload_id'], (string) ($data['purpose'] ?? 'file'));

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /** 收藏 / 取消收藏 */
    public function toggleFavorite(Request $request, Entry $entry): RedirectResponse|JsonResponse
    {
        $member = Auth::guard('member')->user();
        if (! $member) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'msg' => '未登录'], 401);
            }

            return redirect()->route('member.login')->with('url.intended', url()->previous());
        }

        if ($entry->isMemberGated() && ! $member->canMember('view_member_content')) {
            return back()->withErrors(['favorite' => '无权收藏该内容']);
        }

        $result = $this->favorites->toggle($member, $entry);
        if ($request->expectsJson()) {
            return response()->json($result, $result['ok'] ? 200 : 422);
        }

        return back()->with('status', $result['msg']);
    }

    /** 修改密码 */
    public function updatePassword(Request $request): RedirectResponse
    {
        $member = Auth::guard('member')->user();
        if (! $member) {
            return redirect()->route('member.login');
        }

        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if (! Hash::check($data['current_password'], $member->password)) {
            return back()->withErrors(['current_password' => '当前密码不正确']);
        }

        $member->password = $data['password'];
        $member->save();

        return back()->with('status', '密码已更新');
    }

    /** 退出 */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('member')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /** @param  array<string, mixed>  $data */
    private function memberPage(string $name, array $data = []): View
    {
        $data['site'] = $data['site'] ?? $this->site->bootSite();

        return $this->site->renderThemePage($name, $data);
    }
}
