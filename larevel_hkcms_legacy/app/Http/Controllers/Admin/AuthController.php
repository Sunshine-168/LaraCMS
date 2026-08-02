<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends BaseAdminController
{
    public function login(Request $request): View|RedirectResponse
    {
        if ($this->auth->check()) {
            return redirect()->route('admin.dashboard');
        }

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'username' => ['required', 'alpha_dash', 'min:3', 'max:16'],
                'password' => ['required', 'min:6'],
            ]);

            if ($this->auth->tooManyFailures($data['username'])) {
                return back()->withErrors(['username' => '登录失败次数过多，请稍后再试'])->withInput();
            }

            $loggedIn = $this->auth->attempt($data['username'], $data['password'], (string) $request->ip());

            if ($loggedIn === true) {
                return redirect()->to('/'.ltrim((string) $request->input('url', route('admin.dashboard', absolute: false)), '/'));
            }

            if ($loggedIn === 0) {
                return back()->withErrors(['username' => '账号已被禁用'])->withInput();
            }

            return back()->withErrors(['username' => '用户名或密码错误'])->withInput();
        }

        return view('admin.auth.login', [
            'redirectUrl' => $request->query('url', ''),
        ]);
    }

    public function logout(): RedirectResponse
    {
        $this->auth->logout();

        return redirect()->route('admin.login');
    }
}
