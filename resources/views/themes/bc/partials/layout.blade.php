@php
    $front = app(\App\Services\Cms\SiteFrontService::class);
    $contactUrl = $front->categoryBySlug('gw-contact')?->frontUrl() ?: cms_front_url('gw-contact');
    $solutionsUrl = $front->categoryBySlug('gw-solutions')?->frontUrl() ?: cms_front_url('gw-solutions');
    $apiUrl = $front->categoryBySlug('gw-api')?->frontUrl() ?: cms_front_url('gw-api');
    $marketsUrl = $front->categoryBySlug('gw-markets')?->frontUrl() ?: cms_front_url('gw-markets');
    $insightsUrl = $front->categoryBySlug('gw-insights')?->frontUrl() ?: cms_front_url('gw-insights');
    $telegram = cms_theme('telegram');
    $email = cms_theme('email');
    $telegramHandle = '';
    if (preg_match('~(?:t\.me/|@)([A-Za-z0-9_]{3,})~', $telegram, $telegramMatch)) {
        $telegramHandle = '@'.$telegramMatch[1];
    }
@endphp
<!DOCTYPE html>
<html lang="{{ \App\Support\Locale::htmlLang() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('themes/bc/favicon.png') }}" type="image/png">
    @cmsSeo
    @include('cms.partials.front-appearance')
    @include('themes.bc.partials.styles')
</head>
<body class="ig">
<header class="ig-top">
    <div class="ig-wrap ig-bar">
        <a class="ig-brand" href="{{ cms_front_url() }}">
            <img class="ig-logo" src="{{ !empty($site['logo_url']) ? $site['logo_url'] : asset('themes/bc/logo.png') }}" alt="{{ cms_t($site['title'] ?? 'IG包网', 'IG') }}">
        </a>
        <button class="ig-nav-btn" type="button" aria-expanded="false">{{ cms_t('菜单', 'Menu') }}</button>
        @if($telegram !== '')
            <a class="ig-tg ig-tg-quick" href="{{ $telegram }}" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21.5 4.4 2.7 11.2c-1.3.5-1.2 1.2-.2 1.5l4.8 1.5 1.9 5.8c.2.7.1.9.8.9.5 0 .7-.2 1-.5l2.7-2.6 4.8 3.5c.9.5 1.5.2 1.7-.8l3.1-14.6c.3-1.2-.4-1.7-1.4-1.3z"/></svg>
                <span>{{ $telegramHandle }}</span>
            </a>
        @endif
        <nav class="ig-nav">
            <a href="{{ cms_front_url() }}#templates">{{ cms_t('模板展示', 'Templates') }}</a>
            <a href="{{ $apiUrl }}" @if(cms_path_is_current($apiUrl)) aria-current="page" @endif>{{ cms_t('游戏接口', 'Games') }}</a>
            <a href="{{ cms_front_url() }}#console">{{ cms_t('后台', 'Admin') }}</a>
            <a href="{{ $solutionsUrl }}" @if(cms_path_is_current($solutionsUrl)) aria-current="page" @endif>{{ cms_t('方案', 'Packages') }}</a>
            <a href="{{ $marketsUrl }}" @if(cms_path_is_current($marketsUrl)) aria-current="page" @endif>{{ cms_t('市场', 'Markets') }}</a>
            <a href="{{ $insightsUrl }}" @if(cms_path_is_current($insightsUrl)) aria-current="page" @endif>{{ cms_t('资讯', 'Insights') }}</a>
            @include('themes.bc.partials.lang')
            <span class="ig-nav-actions">
                @if($telegram !== '')
                    <a class="ig-tg" href="{{ $telegram }}" target="_blank" rel="noopener">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21.5 4.4 2.7 11.2c-1.3.5-1.2 1.2-.2 1.5l4.8 1.5 1.9 5.8c.2.7.1.9.8.9.5 0 .7-.2 1-.5l2.7-2.6 4.8 3.5c.9.5 1.5.2 1.7-.8l3.1-14.6c.3-1.2-.4-1.7-1.4-1.3zM8.8 14.2l9.4-5.9c.5-.3.9-.1.5.2l-7.8 7-.3 3.3-1.8-4.6z"/></svg>
                        <span>{{ $telegramHandle }}</span>
                    </a>
                @endif
                <a class="ig-btn" href="{{ $contactUrl }}">{{ cms_t('咨询开通', 'Contact') }}</a>
            </span>
        </nav>
    </div>
</header>
<main id="content">
    @yield('content')
</main>
<footer class="ig-foot">
    <div class="ig-wrap ig-foot-grid">
        <div class="ig-foot-brand">
            <a class="ig-brand" href="{{ cms_front_url() }}"><img class="ig-logo" src="{{ asset('themes/bc/logo.png') }}" alt="{{ cms_t($site['title'] ?? 'IG包网', 'IG') }}"></a>
            <p>{{ cms_t('九套前台模板，同一套后台、代理台和游戏接口。', 'Nine templates. One admin, one agent desk, and one game API.') }}</p>
            @if($telegram !== '')
                <a class="ig-tg" href="{{ $telegram }}" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21.5 4.4 2.7 11.2c-1.3.5-1.2 1.2-.2 1.5l4.8 1.5 1.9 5.8c.2.7.1.9.8.9.5 0 .7-.2 1-.5l2.7-2.6 4.8 3.5c.9.5 1.5.2 1.7-.8l3.1-14.6c.3-1.2-.4-1.7-1.4-1.3z"/></svg><span>{{ $telegramHandle }}</span></a>
            @endif
            @if($email !== '')
                <a href="mailto:{{ $email }}">{{ $email }}</a>
            @endif
        </div>
        <div class="ig-foot-col ig-foot-packs">
            <h3>{{ cms_t('方案', 'Packages') }}</h3>
            <a href="{{ $solutionsUrl }}/standard">{{ cms_t('标准包网', 'Standard') }}</a>
            <a href="{{ $solutionsUrl }}/custom">{{ cms_t('定制包网', 'Custom') }}</a>
            <a href="{{ $apiUrl }}">{{ cms_t('游戏 API', 'Game API') }}</a>
        </div>
        <div class="ig-foot-site">
            <div class="ig-foot-col">
                <h3>{{ cms_t('平台', 'Platform') }}</h3>
                @cmsChannel(['type' => 'top', 'ismenu' => 1])
                    @php $footPath = preg_replace('#^/[a-z]{2}(?:-[A-Za-z]{2})?(?=/)#', '', parse_url($item->frontUrl(), PHP_URL_PATH) ?: '') ?: '/'; @endphp
                    @if(in_array($footPath, ['/gw-solutions', '/gw-api', '/gw-platform', '/gw-markets'], true))
                        <a href="{{ $item->frontUrl() }}">{{ $item->t('title') }}</a>
                    @endif
                @endcmsChannel
            </div>
            <div class="ig-foot-col">
                <h3>{{ cms_t('了解', 'Learn') }}</h3>
                @cmsChannel(['type' => 'top', 'ismenu' => 1])
                    @php $footPath = preg_replace('#^/[a-z]{2}(?:-[A-Za-z]{2})?(?=/)#', '', parse_url($item->frontUrl(), PHP_URL_PATH) ?: '') ?: '/'; @endphp
                    @if(in_array($footPath, ['/gw-insights', '/gw-faq', '/gw-about', '/gw-contact'], true))
                        <a href="{{ $item->frontUrl() }}">{{ $item->t('title') }}</a>
                    @endif
                @endcmsChannel
            </div>
        </div>
        <div class="ig-foot-col">
            <h3>{{ cms_t('咨询', 'Contact') }}</h3>
            <a href="{{ $contactUrl }}">{{ cms_t('留下联系方式', 'Send a message') }}</a>
            <a href="{{ cms_front_url('search') }}">{{ cms_ui('search') }}</a>
        </div>
    </div>
    <div class="ig-wrap ig-legal">
        © {{ date('Y') }} {{ cms_t($site['title'] ?? 'IG包网', 'IG') }}
        @if(!empty($site['footer_text'])) · {{ $site['footer_text'] }} @endif
        @if(!empty($site['icp']))
            · <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener nofollow">{{ $site['icp'] }}</a>
        @endif
    </div>
</footer>
@if($telegram !== '')
    <a class="ig-float" href="{{ $telegram }}" target="_blank" rel="noopener" aria-label="{{ $telegramHandle !== '' ? $telegramHandle : 'Telegram' }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21.5 4.4 2.7 11.2c-1.3.5-1.2 1.2-.2 1.5l4.8 1.5 1.9 5.8c.2.7.1.9.8.9.5 0 .7-.2 1-.5l2.7-2.6 4.8 3.5c.9.5 1.5.2 1.7-.8l3.1-14.6c.3-1.2-.4-1.7-1.4-1.3z"/></svg>
        <span>{{ $telegramHandle !== '' ? $telegramHandle : 'Telegram' }}</span>
    </a>
@endif
{!! $site['analytics_code'] ?? '' !!}
@stack('scripts')
<script>
    document.querySelector('.ig-nav-btn')?.addEventListener('click', function () {
        var nav = document.querySelector('.ig-nav');
        var open = nav.classList.toggle('is-open');
        this.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    var floatBtn = document.querySelector('.ig-float');
    if (floatBtn && window.matchMedia('(max-width: 980px)').matches) {
        var showFloat = function () {
            floatBtn.classList.toggle('is-on', window.scrollY > 520);
        };
        showFloat();
        window.addEventListener('scroll', showFloat, { passive: true });
    }
</script>
</body>
</html>
