<?php

namespace App\Support;

use App\Models\Cms\Setting;

/** 后台界面语言（和前台内容语言分开） */
class AdminUi
{
    public const ZH = 'zh_cn';

    public const EN = 'en';

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::ZH => '中文',
            self::EN => 'English',
        ];
    }

    public static function current(): string
    {
        $user = auth('admin')->user();
        if ($user && is_string($user->ui_locale) && self::isValid($user->ui_locale)) {
            return $user->ui_locale;
        }
        $session = session('admin_ui_locale');
        if (is_string($session) && self::isValid($session)) {
            return $session;
        }
        $cookie = request()->cookie('admin_ui_locale');
        if (is_string($cookie) && self::isValid($cookie)) {
            return $cookie;
        }
        try {
            $site = (string) Setting::getValue('admin_ui_locale', self::ZH);
            if (self::isValid($site)) {
                return $site;
            }
        } catch (\Throwable) {
        }

        return self::ZH;
    }

    public static function isValid(string $code): bool
    {
        return array_key_exists($code, self::options());
    }

    public static function apply(): void
    {
        $code = self::current();
        app()->setLocale($code === self::ZH ? 'zh_CN' : 'en');
    }

    public static function htmlLang(): string
    {
        return self::current() === self::EN ? 'en' : 'zh-CN';
    }

    public static function laravelLocale(string $code): string
    {
        return $code === self::ZH ? 'zh_CN' : 'en';
    }

    /** @return array{code: string, cookie: \Symfony\Component\HttpFoundation\Cookie} */
    public static function remember(string $code, bool $saveUser = true): array
    {
        $code = self::isValid($code) ? $code : self::ZH;
        session(['admin_ui_locale' => $code]);
        $user = auth('admin')->user();
        if ($saveUser && $user && \Illuminate\Support\Facades\Schema::hasColumn('admin_users', 'ui_locale')) {
            $user->forceFill(['ui_locale' => $code])->save();
        }
        $cookie = cookie('admin_ui_locale', $code, 60 * 24 * 365);

        return ['code' => $code, 'cookie' => $cookie];
    }
}
