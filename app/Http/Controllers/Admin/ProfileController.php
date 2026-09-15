<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly AdminUserService $users)
    {
    }

    public function edit(): View
    {
        return view('admin.profile.edit', [
            'user' => auth('admin')->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'ui_locale' => 'nullable|in:zh_cn,en',
        ], [
            'name.required' => '请填写名称',
        ]);

        $user = auth('admin')->user();
        $result = $this->users->updateSelfName($user, (string) $request->input('name'));
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }
        if ($request->filled('ui_locale')) {
            $ui = $this->users->updateSelfUiLocale($user, (string) $request->input('ui_locale'));
            if ($ui['ok'] && ! empty($ui['data']['cookie'])) {
                return back()->with('status', $result['msg'])->withCookie($ui['data']['cookie']);
            }
        }

        return back()->with('status', $result['msg']);
    }

    public function password(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => '请填写当前密码',
            'password.required' => '请填写新密码',
            'password.min' => '新密码至少 8 位',
            'password.confirmed' => '两次新密码不一致',
        ]);

        $user = auth('admin')->user();
        $result = $this->users->changeOwnPassword(
            $user,
            (string) $request->input('current_password'),
            (string) $request->input('password')
        );
        if (! $result['ok']) {
            return back()->with('error', $result['msg']);
        }

        try {
            auth('admin')->logoutOtherDevices((string) $request->input('password'));
        } catch (\Throwable) {
        }
        $request->session()->regenerate();

        return back()->with('status', $result['msg']);
    }
}
