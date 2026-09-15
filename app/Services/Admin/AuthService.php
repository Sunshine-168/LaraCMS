<?php

namespace App\Services\Admin;

use App\Models\Admin\AdminUser;
use App\Support\Result;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /** 后台登录 */
    public function login(string $email, string $password, bool $remember = false, ?string $ip = null): array
    {
        $user = AdminUser::query()->where('email', $email)->first();

        if (! $user || ! $user->is_active || ! Hash::check($password, $user->password)) {
            return Result::fail('邮箱或密码错误');
        }

        Auth::guard('admin')->login($user, $remember);

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $ip ?: request()->ip(),
        ])->save();

        return Result::ok($user, '登录成功');
    }

    /** 退出登录 */
    public function logout(): array
    {
        Auth::guard('admin')->logout();

        return Result::ok(null, '已退出');
    }
}
