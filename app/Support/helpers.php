<?php

use App\Support\Cdn;
use App\Support\UrlMode;

if (! function_exists('cms_front_url')) {
    function cms_front_url(string $path = ''): string
    {
        $path = trim($path, '/');
        $prefix = UrlMode::localePrefix();

        return url($prefix.($path === '' ? '' : '/'.$path));
    }
}

if (! function_exists('cms_media')) {
    function cms_media(?string $path): string
    {
        return Cdn::url($path);
    }
}
