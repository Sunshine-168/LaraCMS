<?php

return [
    'theme' => env('CMS_THEME', 'enterprise'),
    'default_locale' => env('CMS_DEFAULT_LOCALE', 'zh_cn'),
    'hide_default_locale' => (bool) env('CMS_HIDE_DEFAULT_LOCALE', true),
    'locales' => [
        'zh_cn' => ['label' => '简体中文', 'native' => '简体中文'],
        'en' => ['label' => 'English', 'native' => 'English'],
    ],
    'themes' => [
        'enterprise', 'mobile', 'download', 'nav', 'source',
        'blog', 'manga', 'sports', 'default',
    ],
    'form_captcha' => (bool) env('CMS_FORM_CAPTCHA', true),
    /** 侧栏是否显示旧 Posts/Pages（路由仍可用） */
    'show_legacy_admin' => (bool) env('CMS_SHOW_LEGACY_ADMIN', false),

    /*
    | 后台任务运行模式（多站点可按站切换）
    | schedule = schedule:run 进程内同步执行（不需 queue:work）
    | queue    = 调度只投递队列，由 queue:work 消费（多站可共用 worker）
    | off      = 不自动跑，仅手动 artisan / 后台按钮
    */
    'runtime' => [
        'schedule_enabled' => (bool) env('CMS_SCHEDULE_ENABLED', true),
        'collect' => env('CMS_COLLECT_MODE', 'schedule'), // schedule|queue|off
        'collect_queue' => env('CMS_COLLECT_QUEUE', 'cms'),
        'stat_prune' => env('CMS_STAT_PRUNE_MODE', 'schedule'),
        'stat_prune_queue' => env('CMS_STAT_PRUNE_QUEUE', 'cms'),
    ],

    /*
    | 全页 HTML 缓存（伪静态 URL 不变，命中后直接返回 HTML）
    | file/database 驱动用 epoch 失效；redis/memcached 可用标签精细失效
    */
    'html_cache' => [
        'enabled' => (bool) env('CMS_HTML_CACHE', false),
        'ttl' => (int) env('CMS_HTML_CACHE_TTL', 3600),
        'store' => env('CMS_HTML_CACHE_STORE'), // null = 默认 CACHE_STORE
        'exclude_prefixes' => [
            'admin', 'member', 'form', 'download', 'go', 'livewire', 'storage', 'api', 'up',
        ],
        'exclude_path_prefixes' => [
            'search', // 搜索结果不缓存
        ],
    ],
];

