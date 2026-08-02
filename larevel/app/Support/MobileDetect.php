<?php

namespace App\Support;

use App\Models\Cms\Setting;
use Illuminate\Http\Request;

class MobileDetect
{
    public static function shouldUseMobile(Request $request): bool
    {
        $theme = trim((string) Setting::getValue('mobile_theme', ''));
        if ($theme === '') {
            return false;
        }

        $mode = (string) Setting::getValue('mobile_detect', 'both'); // domain|ua|both
        $domains = array_filter(array_map('trim', explode(',', (string) Setting::getValue('mobile_domain', ''))));
        $host = strtolower($request->getHost());
        $onMobileDomain = $domains !== [] && in_array($host, array_map('strtolower', $domains), true);
        $uaMobile = (bool) preg_match(
            '/Mobile|Android|iPhone|iPad|iPod|webOS|BlackBerry|IEMobile|Opera Mini/i',
            (string) $request->userAgent()
        );

        return match ($mode) {
            'domain' => $onMobileDomain,
            'ua' => $uaMobile,
            default => $onMobileDomain || $uaMobile,
        };
    }

    public static function mobileTheme(): ?string
    {
        $theme = trim((string) Setting::getValue('mobile_theme', ''));

        return $theme !== '' ? $theme : null;
    }
}
