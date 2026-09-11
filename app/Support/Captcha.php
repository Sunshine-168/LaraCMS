<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class Captcha
{
    public static function generate(): array
    {
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        session(['cms_captcha' => $a + $b]);

        return [
            'question' => "{$a} + {$b} = ?",
            'a' => $a,
            'b' => $b,
        ];
    }

    public static function check(?string $answer): bool
    {
        $expect = session('cms_captcha');
        session()->forget('cms_captcha');

        return $expect !== null && (int) $answer === (int) $expect;
    }

    public static function enabled(): bool
    {
        return (bool) config('cms.auth_captcha', true);
    }

    public static function assertValid(?string $answer): void
    {
        if (! self::enabled()) {
            return;
        }
        if (! self::check($answer)) {
            throw ValidationException::withMessages([
                'captcha' => '验证码错误',
            ]);
        }
    }
}
