<?php

return [
    'default_locale' => env('CMS_DEFAULT_LOCALE', 'zh-cn'),
    'config_cache_ttl' => (int) env('CMS_CONFIG_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Frontend theme
    |--------------------------------------------------------------------------
    |
    | Active theme name falls back to SiteService index_theme, then this value.
    | Views live under resources/views/themes/{theme}/ and are registered as
    | the cms_theme view namespace.
    |
    */
    'theme' => env('CMS_THEME', 'default'),
    'theme_path' => resource_path('views/themes'),
    'view_namespace' => 'cms_theme',
    'fallback_theme' => 'default',

    'default_templates' => [
        'index' => 'index/index',
        'category' => 'category/category',
        'list' => 'list/list',
        'show' => 'show/show',
        'page' => 'show/show',
    ],
];
