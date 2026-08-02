<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cms\Member;
use App\Models\Cms\MemberGroup;
use App\Services\Cms\SiteFrontService;
use App\Support\MailConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function __construct(private readonly SiteFrontService $site)
    {
    }

    /** 会员登录页 */
    public function showLogin(): View
    {
        $site = $this->site->bootSite();

        return view($this->site->themeView('member.login'), compact('site'));
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
        $site = $this->site->bootSite();

        return view($this->site->themeView('member.register', 'member.login'), compact('site'));
    }

    /** 提交注册 */
    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'email' => 'required|email|max:120|unique:cms_members,email',
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
        $site = $this->site->bootSite();

        return view($this->site->themeView('member.forgot', 'member.login'), compact('site'));
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

        return view($this->site->themeView('member.reset', 'member.login'), [
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
        $member?->loadMissing('group');

        return view($this->site->themeView('member.profile', 'member.login'), compact('site', 'member'));
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
        ]);
        $member->name = $data['name'];
        $member->save();

        return back()->with('status', '资料已更新');
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
}
