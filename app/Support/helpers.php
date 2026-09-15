<?php

use App\Support\Cdn;
use App\Support\Locale;
use App\Support\UrlMode;
use Illuminate\Support\Facades\Route;

if (! function_exists('cms_front_url')) {
    function cms_front_url(string $path = '', ?string $locale = null): string
    {
        $path = trim($path, '/');
        $prefix = UrlMode::localePrefix($locale);

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
        return \App\Support\Locale::isEnglish() ? $en : $zh;
    }
}

if (! function_exists('cms_ui')) {
    function cms_ui(string $key, array $replace = []): string
    {
        $text = \App\Support\ThemeUi::get($key);
        foreach ($replace as $search => $value) {
            $text = str_replace(':'.$search, (string) $value, $text);
        }

        return $text;
    }
}

if (! function_exists('cms_locale_url')) {
    function cms_locale_url(string $targetLocale): string
    {
        $path = trim(request()->path(), '/');
        foreach (\App\Support\Locale::codes() as $code) {
            $public = \App\Support\Locale::toPublic($code);
            if ($path === $public) {
                $path = '';
                break;
            }
            if (str_starts_with($path, $public.'/')) {
                $path = substr($path, strlen($public) + 1);
                break;
            }
        }
        $prefix = trim(\App\Support\UrlMode::localePrefix($targetLocale), '/');
        $joined = trim(($prefix === '' ? '' : $prefix).($path === '' ? '' : '/'.$path), '/');
        $url = $joined === '' ? '/' : '/'.$joined;
        $query = request()->query();
        unset($query['locale']);
        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        return $url;
    }
}

if (! function_exists('admin_t')) {
    function admin_t(string $key, array $replace = []): string
    {
        return trans('admin.'.$key, $replace);
    }
}

if (! function_exists('admin_help_view')) {
    function admin_help_view(string $topic): string
    {
        $zh = match ($topic) {
            'use' => 'admin.help._use',
            'admin' => 'admin.help._admin',
            'themes' => 'admin.help._themes',
            'theme' => 'admin.help._theme',
            'tags' => 'admin.help._tags',
            'schedule' => 'admin.help._schedule',
            'env' => 'install._deploy',
            'laravel' => 'install._laravel',
            'docker' => 'install._docker',
            default => 'admin.help._use',
        };
        if (\App\Support\AdminUi::current() === \App\Support\AdminUi::EN) {
            $en = 'admin.help.en._'.$topic;
            if (view()->exists($en)) {
                return $en;
            }
        }

        return $zh;
    }
}

if (! function_exists('cms_media')) {
    function cms_media(?string $path): string
    {
        return Cdn::url($path);
    }
}
