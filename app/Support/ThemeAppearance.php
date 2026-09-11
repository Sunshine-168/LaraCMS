<?php

namespace App\Support;

use App\Models\Cms\Setting;
use Illuminate\Support\Facades\Storage;

class ThemeAppearance
{
    /** @return array<string, string> */
    public static function forFront(): array
    {
        $logo = trim((string) Setting::getValue('logo', ''));
        $favicon = trim((string) Setting::getValue('favicon', ''));

        return [
            'logo' => $logo,
            'logo_url' => self::mediaUrl($logo),
            'favicon' => $favicon,
            'favicon_url' => self::mediaUrl($favicon),
            'primary_color' => self::sanitizeColor((string) Setting::getValue('primary_color', '')),
            'footer_text' => trim((string) Setting::getValue('footer_text', '')),
            'icp' => trim((string) Setting::getValue('icp', '')),
            'hero_title' => trim((string) Setting::getValue('hero_title', '')),
            'hero_subtitle' => trim((string) Setting::getValue('hero_subtitle', '')),
        ];
    }

    public static function sanitizeColor(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
            return strtolower($value);
        }

        return '';
    }

    public static function mediaUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $path) || str_starts_with($path, '//')) {
            return cms_media($path);
        }
        if (str_starts_with($path, '/storage/') || str_starts_with($path, 'storage/')) {
            return cms_media('/'.ltrim($path, '/'));
        }

        return cms_media(Storage::disk('public')->url($path));
    }
}
