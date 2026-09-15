<?php

namespace App\Services\Admin;

use App\Models\Admin\AdminUser;
use App\Models\Cms\Setting;
use App\Support\MailConfig;
use App\Support\Result;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

    public function sendResetLink(string $email): array
    {
        $user = AdminUser::query()->where('email', $email)->where('is_active', true)->first();
        $generic = '若该邮箱可以登录后台，将收到重置邮件';
        if (! $user) {
            return Result::ok(null, $generic);
        }

        $token = Str::random(48);
        $user->forceFill([
            'reset_token' => hash('sha256', $token),
            'reset_token_expires_at' => now()->addHour(),
        ])->save();
        $url = route('admin.password.reset', ['token' => $token, 'email' => $user->email]);
        $sent = MailConfig::send($user->email, '重置后台密码', 'mail.reset', [
            'siteName' => (string) Setting::getValue('site_name', config('app.name')),
            'heading' => '重置后台密码',
            'audience' => '后台',
            'url' => $url,
        ]);
        if ($sent) {
            return Result::ok(null, '重置邮件已发送，请查收邮箱');
        }
        if (MailConfig::showDebugLink()) {
            return Result::ok(['reset_url' => $url], '未配置发信。本地调试可使用下面的链接（1 小时内有效）');
        }

        return Result::ok(null, $generic);
    }

    public function resetPassword(string $email, string $token, string $password): array
    {
        $user = AdminUser::query()->where('email', $email)->where('is_active', true)->first();
        if (
            ! $user
            || ! $user->reset_token
            || ! $user->reset_token_expires_at
            || $user->reset_token_expires_at->isPast()
            || ! hash_equals((string) $user->reset_token, hash('sha256', $token))
        ) {
            return Result::fail('重置链接无效或已过期');
        }
        $user->password = $password;
        $user->reset_token = null;
        $user->reset_token_expires_at = null;
        $user->save();

        return Result::ok($user, '密码已重置，请登录');
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
