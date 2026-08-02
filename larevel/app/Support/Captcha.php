<?php

namespace App\Support;

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
}
