<?php

namespace App\Support;

use App\Models\Cms\Setting;

class Cdn
{
    public static function applyRuntime(): void
    {
        $base = self::base();
        if ($base === '') {
            return;
        }

        config([
            'app.asset_url' => $base,
            'filesystems.disks.public.url' => $base.'/storage',
        ]);
    }

    public static function base(): string
    {
        try {
            $v = trim((string) Setting::getValue('cdn_url', ''));
        } catch (\Throwable) {
            return '';
        }

        return rtrim($v, '/');
    }

    public static function url(?string $path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        $cdn = self::base();
        if ($cdn === '') {
            return $path;
        }

        if (str_starts_with($path, '//')) {
            return $path;
        }

        if (preg_match('#^https?://#i', $path)) {
            $app = rtrim((string) config('app.url'), '/');
            if ($app !== '' && str_starts_with($path, $app)) {
                $rest = substr($path, strlen($app)) ?: '';
                if ($rest === '' || str_starts_with($rest, '/')) {
                    return $cdn.$rest;
                }
            }

            return $path;
        }

        return $cdn.'/'.ltrim($path, '/');
    }
}
