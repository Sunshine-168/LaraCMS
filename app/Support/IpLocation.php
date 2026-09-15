<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/** 把 IP 收成「本机 / Docker 内网 / 省市运营商」 */
class IpLocation
{
    /** @param  list<string>  $ips */
    public static function many(array $ips): array
    {
        $out = [];
        foreach (array_unique(array_filter(array_map('trim', $ips))) as $ip) {
            $out[$ip] = self::label($ip);
        }

        return $out;
    }

    public static function label(?string $ip): string
    {
        $ip = trim((string) $ip);
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return '';
        }
        if (self::isLoopback($ip)) {
            return '本机';
        }
        if (self::inCidr4($ip, '169.254.0.0/16') || self::isIpv6LinkLocal($ip)) {
            return '链路本地';
        }
        if (self::inCidr4($ip, '172.17.0.0/16')) {
            return '内网（Docker）';
        }
        if (self::isPrivate($ip)) {
            return '内网';
        }
        if (self::isDocumentation($ip)) {
            return '保留地址';
        }
        if (self::isReserved($ip)) {
            return '保留地址';
        }

        return self::lookupPublic($ip);
    }

    private static function lookupPublic(string $ip): string
    {
        if (app()->runningUnitTests() || ! config('cms.ip_lookup', true)) {
            return '公网';
        }

        try {
            return Cache::remember('ip-place:'.$ip, now()->addDays(30), function () use ($ip) {
                return self::fetchPublic($ip) ?: '公网';
            });
        } catch (\Throwable) {
            return '公网';
        }
    }

    private static function fetchPublic(string $ip): string
    {
        try {
            $res = Http::timeout(1.5)
                ->acceptJson()
                ->get('http://ip-api.com/json/'.$ip, [
                    'lang' => 'zh-CN',
                    'fields' => 'status,country,regionName,city,isp',
                ]);
            if (! $res->ok()) {
                return '';
            }
            $data = $res->json();
            if (! is_array($data) || ($data['status'] ?? '') !== 'success') {
                return '';
            }

            return self::formatPublic(
                (string) ($data['country'] ?? ''),
                (string) ($data['regionName'] ?? ''),
                (string) ($data['city'] ?? ''),
                (string) ($data['isp'] ?? ''),
            );
        } catch (\Throwable) {
            return '';
        }
    }

    private static function formatPublic(string $country, string $region, string $city, string $isp): string
    {
        $drop = ['', '0', '-', '内网', '局域网', '*'];
        $clean = function (string $value) use ($drop): string {
            $value = trim($value);

            return in_array($value, $drop, true) ? '' : $value;
        };
        $country = $clean($country);
        $region = $clean($region);
        $city = $clean($city);
        $isp = $clean($isp);
        $places = [];
        if ($country !== '' && $country !== '中国' && $country !== '中华人民共和国') {
            $places[] = $country;
        }
        foreach ([$region, $city] as $part) {
            if ($part !== '' && ! in_array($part, $places, true)) {
                $places[] = $part;
            }
        }
        $text = implode(' ', $places);
        if ($isp !== '' && $isp !== $text) {
            $text = $text === '' ? $isp : $text.' · '.$isp;
        }

        return $text !== '' ? $text : '公网';
    }

    private static function isLoopback(string $ip): bool
    {
        if ($ip === '::1') {
            return true;
        }

        return self::inCidr4($ip, '127.0.0.0/8');
    }

    private static function isPrivate(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false;
    }

    private static function isDocumentation(string $ip): bool
    {
        return self::inCidr4($ip, '192.0.2.0/24')
            || self::inCidr4($ip, '198.51.100.0/24')
            || self::inCidr4($ip, '203.0.113.0/24');
    }

    private static function isReserved(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) === false;
    }

    private static function isIpv6LinkLocal(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return false;
        }
        $packed = @inet_pton($ip);
        if ($packed === false || strlen($packed) < 2) {
            return false;
        }

        return (ord($packed[0]) === 0xfe) && ((ord($packed[1]) & 0xc0) === 0x80);
    }

    private static function inCidr4(string $ip, string $cidr): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }
        [$subnet, $bits] = array_pad(explode('/', $cidr, 2), 2, '32');
        $ipLong = ip2long($ip);
        $subLong = ip2long($subnet);
        if ($ipLong === false || $subLong === false) {
            return false;
        }
        $mask = -1 << (32 - (int) $bits);

        return ($ipLong & $mask) === ($subLong & $mask);
    }
}
