<?php

namespace App\Services\Admin;

use App\Models\Admin\AdminUser;
use App\Support\Result;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AuthService
{
    public function __construct(private readonly AdminLogService $logs)
    {
    }

    /** 后台登录 */
    public function login(string $email, string $password, bool $remember = false, ?string $ip = null): array
    {
        $ip = $ip ?: (string) request()->ip();
        $user = AdminUser::query()->where('email', $email)->first();

        if (! $user || ! $user->is_active || ! Hash::check($password, $user->password)) {
            $this->recordAuth('login', $user, $email, $ip, 401);

            return Result::fail('邮箱或密码错误');
        }

        Auth::guard('admin')->login($user, $remember);

        if (Schema::hasColumn('admin_users', 'last_login_at')) {
            $user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $ip,
            ])->save();
        }

        $this->recordAuth('login', $user, $email, $ip, 302);

        return Result::ok($user, '登录成功');
    }

    /** 退出登录 */
    public function logout(?AdminUser $user = null, ?string $ip = null): array
    {
        if ($user) {
            $this->recordAuth('logout', $user, $user->email, $ip ?: (string) request()->ip(), 302);
        }
        Auth::guard('admin')->logout();

        return Result::ok(null, '已退出');
    }

    private function recordAuth(string $action, ?AdminUser $user, string $email, string $ip, int $status): void
    {
        $this->logs->record([
            'admin_id' => $user?->id,
            'admin_name' => $user?->name ?: $email,
            'method' => 'POST',
            'path' => '/admin/'.$action,
            'detail' => $email,
            'ip' => mb_substr($ip, 0, 45),
            'status' => $status,
        ]);
    }
}
