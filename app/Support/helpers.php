<?php

use App\Support\Cdn;
use App\Support\Locale;
use App\Support\UrlMode;
use Illuminate\Support\Facades\Route;

if (! function_exists('cms_front_url')) {
    function cms_front_url(string $path = ''): string
    {
        $path = trim($path, '/');
        $prefix = UrlMode::localePrefix();

        return url($prefix.($path === '' ? '' : '/'.$path));
    }
}

if (! function_exists('cms_route')) {
    function cms_route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        $locale = Locale::current();
        $suffix = '';
        if (! Locale::hideDefault() || $locale !== Locale::default()) {
            $suffix = '.'.$locale;
        }
        $named = $name.$suffix;
        if ($suffix !== '' && Route::has($named)) {
            return route($named, $parameters, $absolute);
        }

        return route($name, $parameters, $absolute);
    }
}

if (! function_exists('cms_t')) {
    function cms_t(string $zh, string $en): string
    {
        return Locale::isEnglish() ? $en : $zh;
    }
}

if (! function_exists('cms_media')) {
    function cms_media(?string $path): string
    {
        return Cdn::url($path);
    }
}
