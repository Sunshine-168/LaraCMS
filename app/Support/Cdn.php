<?php

namespace App\Support;

use App\Models\Cms\Setting;

class Cdn
{
    public static function applyRuntime(): void
    {
        $base = self::base();
        $fallback = rtrim((string) config('app.url'), '/').'/storage';
        config([
            'filesystems.disks.public.url' => $base !== '' ? $base.'/storage' : $fallback,
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
        if ($path === '' || ! self::isMediaPath($path)) {
            return $path;
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

    public static function rewriteHtml(?string $html): string
    {
        $html = (string) $html;
        if ($html === '' || self::base() === '') {
            return $html;
        }

        return (string) preg_replace_callback(
            '#\b(src|href)=([\'"])([^\'"]+)\2#i',
            fn (array $m) => $m[1].'='.$m[2].self::url($m[3]).$m[2],
            $html
        );
    }

    public static function rewriteValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([self::class, 'rewriteValue'], $value);
        }
        if (! is_string($value) || $value === '') {
            return $value;
        }
        if (str_contains($value, '<')) {
            return self::rewriteHtml($value);
        }

        return self::url($value);
    }

    public static function isMediaPath(string $path): bool
    {
        if (str_starts_with($path, '/storage/') || str_starts_with($path, 'storage/')) {
            return true;
        }
        if (str_contains($path, '/storage/')) {
            return true;
        }

        return (bool) preg_match('#^https?://#i', $path);
    }
}
