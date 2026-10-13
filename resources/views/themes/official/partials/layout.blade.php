@php
    $navOn = function (string $name): string {
        return request()->routeIs($name, $name.'.*') ? 'is-on' : '';
    };
    $en = \App\Support\Locale::isEnglish();
@endphp
<!DOCTYPE html>
<html lang="{{ \App\Support\Locale::htmlLang() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', cms_t('LeafCMS 官网', 'LeafCMS'))</title>
    <meta name="description" content="@yield('description', cms_t('LeafCMS 是可商用的开源建站系统，用来做企业官网、博客和多站点。', 'LeafCMS is an open-source, commercial-use site builder for company sites, blogs, and multiple sites.'))">
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=2" sizes="32x32">
    <link rel="icon" href="{{ asset('brand/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('brand/site.css') }}?v=23">
</head>
<body>
<header class="top">
    <div class="wrap top-inner">
        <a class="brand" href="{{ cms_route('home') }}">
            <img class="mark" src="{{ asset('brand/logo.svg') }}" alt="" width="32" height="32">
            LeafCMS
        </a>
        <nav class="nav">
            <a class="{{ $navOn('home') }}" href="{{ cms_route('home') }}">{{ cms_t('首页', 'Home') }}</a>
            <a href="{{ cms_route('home') }}#features">{{ cms_t('特性', 'Features') }}</a>
            <a href="{{ cms_route('home') }}#cases">{{ cms_t('案例', 'Cases') }}</a>
            <a class="{{ $navOn('site.themes') }}" href="{{ cms_route('site.themes') }}">{{ cms_t('主题', 'Themes') }}</a>
            <a class="{{ $navOn('site.guide') }}" href="{{ cms_route('site.guide') }}">{{ cms_t('安装', 'Install') }}</a>
            <a class="{{ $navOn('site.docs') }}" href="{{ cms_route('site.docs') }}">{{ cms_t('说明', 'Docs') }}</a>
            <a class="{{ $navOn('site.open') }}" href="{{ cms_route('site.open') }}">{{ cms_t('开源', 'Open source') }}</a>
            <a class="{{ $navOn('site.news') }}" href="{{ cms_route('site.news') }}">{{ cms_t('动态', 'News') }}</a>
            <a class="nav-github" href="https://github.com/Sunshine-168/LeafCMS" target="_blank" rel="noopener">GitHub</a>
            <a class="nav-install" href="{{ cms_route('site.guide') }}">{{ cms_t('开始安装', 'Install') }}</a>
            <div class="nav-lang">
                <a class="{{ $en ? '' : 'is-on' }}" href="{{ cms_locale_url('zh_cn') }}">简体中文</a>
                <a class="{{ $en ? 'is-on' : '' }}" href="{{ cms_locale_url('en') }}">English</a>
            </div>
        </nav>
        <div class="lang">
            <a class="{{ $en ? '' : 'is-on' }}" href="{{ cms_locale_url('zh_cn') }}">简体中文</a>
            <span>·</span>
            <a class="{{ $en ? 'is-on' : '' }}" href="{{ cms_locale_url('en') }}">English</a>
        </div>
        <a class="btn btn-line hide-sm" href="https://github.com/Sunshine-168/LeafCMS" target="_blank" rel="noopener">GitHub</a>
        <a class="btn btn-solid hide-sm" href="{{ cms_route('site.guide') }}">{{ cms_t('开始安装', 'Install') }}</a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-label="{{ cms_t('打开菜单', 'Open menu') }}">{{ cms_t('菜单', 'Menu') }}</button>
    </div>
</header>
<main>
    @yield('content')
</main>
<footer class="foot">
    <div class="wrap">
        <div class="foot-grid">
            <div class="foot-brand">
                <a class="brand" href="{{ cms_route('home') }}"><img class="mark" src="{{ asset('brand/logo.svg') }}" alt="" width="32" height="32"> LeafCMS</a>
                <p>{{ cms_t('开源建站系统。企业官网、博客、下载站和多站点用同一套后台，前台用标签取内容。', 'Open-source site builder. Company sites, blogs, downloads, and multiple sites share one admin. Themes pull content with tags.') }}</p>
            </div>
            <div>
                <h3>{{ cms_t('产品', 'Product') }}</h3>
                <a href="{{ cms_route('home') }}#features">{{ cms_t('产品特性', 'Features') }}</a>
                <a href="{{ cms_route('home') }}#cases">{{ cms_t('上线案例', 'Live sites') }}</a>
                <a href="{{ cms_route('site.themes') }}">{{ cms_t('全部主题', 'Themes') }}</a>
            </div>
            <div>
                <h3>{{ cms_t('上手', 'Start') }}</h3>
                <a href="{{ cms_route('site.guide') }}">{{ cms_t('安装', 'Install') }}</a>
                <a href="{{ cms_route('site.docs') }}">{{ cms_t('系统说明', 'Docs') }}</a>
                <a href="{{ cms_route('site.news') }}">{{ cms_t('动态', 'News') }}</a>
                <a href="{{ url('/admin/login') }}">{{ cms_t('后台登录', 'Admin login') }}</a>
            </div>
            <div>
                <h3>{{ cms_t('开源', 'Open source') }}</h3>
                <a href="{{ cms_route('site.open') }}">{{ cms_t('开源说明', 'About the license') }}</a>
                <a href="https://github.com/Sunshine-168/LeafCMS" target="_blank" rel="noopener">GitHub</a>
                <a href="https://github.com/Sunshine-168/LeafCMS/archive/refs/heads/main.zip">{{ cms_t('下载 zip', 'Download zip') }}</a>
            </div>
        </div>
        <div class="foot-end">
            <span>© {{ date('Y') }} LeafCMS · MIT</span>
            <span>PHP 8.4+ · Laravel 13</span>
        </div>
    </div>
</footer>
<div class="dock" aria-label="{{ cms_t('飞机联系', 'Telegram') }}">
    <a class="dock-item is-group" href="https://t.me/applecms" target="_blank" rel="noopener" title="{{ cms_t('飞机群', 'Telegram group') }}" aria-label="{{ cms_t('飞机群', 'Telegram group') }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M16 11a3 3 0 1 0-3-3 3 3 0 0 0 3 3Zm-8 1a3.5 3.5 0 1 0-3.5-3.5A3.5 3.5 0 0 0 8 12Zm8 1.2c-2.1 0-6.3 1.05-6.3 3.15V18h12.6v-1.65c0-2.1-4.2-3.15-6.3-3.15ZM8 14.2c-.26 0-.54.01-.84.04C5.2 14.7 3 15.85 3 17.5V18h5.7v-1.65c0-.62.2-1.18.54-1.68A8.6 8.6 0 0 1 8 14.2Z"/></svg>
        <span>{{ cms_t('飞机群', 'Group') }}</span>
    </a>
    <a class="dock-item is-dm" href="https://t.me/sanpang" target="_blank" rel="noopener" title="{{ cms_t('飞机号', 'Telegram') }} @sanpang" aria-label="{{ cms_t('飞机号', 'Telegram') }} @sanpang">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M21.5 3.2 2.8 10.4c-1.3.5-1.2 1.2-.2 1.5l4.8 1.5 11.1-7c.5-.3 1-.1.6.2l-9 8.7-.3 4.7c.5 0 .7-.2 1-.5l2.3-2.2 4.8 3.5c.9.5 1.5.2 1.7-.8l3.1-14.6c.3-1.3-.5-1.9-1.6-1.5Z"/></svg>
        <span>@sanpang</span>
    </a>
</div>
<script src="{{ asset('brand/site.js') }}"></script>
</body>
</html>
