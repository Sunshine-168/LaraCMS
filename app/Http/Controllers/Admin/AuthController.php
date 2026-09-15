<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AuthService;
use App\Support\Captcha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    /** 登录页 */
    public function showLogin(): View|RedirectResponse
    {
        if (auth('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    /** 提交登录 */
    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'captcha' => config('cms.auth_captcha') ? 'required' : 'nullable',
        ]);
        Captcha::assertValid($request->input('captcha'));

        $result = $this->authService->login(
            $data['email'],
            $data['password'],
            $request->boolean('remember'),
            $request->ip()
        );

        if (! $result['ok']) {
            return back()->withErrors(['email' => $result['msg']])->withInput();
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    /** 退出登录 */
    public function logout(Request $request): RedirectResponse
    {
        $this->authService->logout(auth('admin')->user(), $request->ip());
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
