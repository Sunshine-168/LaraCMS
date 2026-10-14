@php
    $front = app(\App\Services\Cms\SiteFrontService::class);
    $contactUrl = $front->categoryBySlug('gw-contact')?->frontUrl() ?: cms_front_url('gw-contact');
    $solutionsUrl = $front->categoryBySlug('gw-solutions')?->frontUrl() ?: cms_front_url('gw-solutions');
    $apiUrl = $front->categoryBySlug('gw-api')?->frontUrl() ?: cms_front_url('gw-api');
    $marketsUrl = $front->categoryBySlug('gw-markets')?->frontUrl() ?: cms_front_url('gw-markets');
    $insightsUrl = $front->categoryBySlug('gw-insights')?->frontUrl() ?: cms_front_url('gw-insights');
    $telegram = cms_theme('telegram');
    $email = cms_theme('email');
@endphp
<!DOCTYPE html>
<html lang="{{ \App\Support\Locale::htmlLang() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @cmsSeo
    @include('cms.partials.front-appearance')
    @include('themes.bc.partials.styles')
</head>
<body class="ig">
<header class="ig-top">
    <div class="ig-wrap ig-bar">
        <a class="ig-brand" href="{{ cms_front_url() }}">
            @if(!empty($site['logo_url']))
                <img src="{{ $site['logo_url'] }}" alt="">
            @else
                <span class="ig-mark">IG</span>
            @endif
            <span>{{ $site['title'] ?? 'IG包网' }}</span>
        </a>
        <button class="ig-nav-btn" type="button" aria-expanded="false">{{ cms_t('菜单', 'Menu') }}</button>
        <nav class="ig-nav">
            <a href="{{ $solutionsUrl }}" @if(cms_path_is_current($solutionsUrl)) aria-current="page" @endif>{{ cms_t('方案', 'Packages') }}</a>
            <a href="{{ $apiUrl }}" @if(cms_path_is_current($apiUrl)) aria-current="page" @endif>{{ cms_t('游戏', 'Games') }}</a>
            <a href="{{ $marketsUrl }}" @if(cms_path_is_current($marketsUrl)) aria-current="page" @endif>{{ cms_t('市场', 'Markets') }}</a>
            <a href="{{ $insightsUrl }}" @if(cms_path_is_current($insightsUrl)) aria-current="page" @endif>{{ cms_t('资讯', 'Notes') }}</a>
            @include('cms.partials.lang-switch')
            <a class="ig-btn" href="{{ $contactUrl }}">{{ cms_t('立即咨询', 'Contact') }}</a>
        </nav>
    </div>
</header>
<main id="content">
    @yield('content')
</main>
<footer class="ig-foot">
    <div class="ig-wrap ig-foot-grid">
        <div>
            <a class="ig-brand" href="{{ cms_front_url() }}"><span class="ig-mark">IG</span> {{ $site['title'] ?? 'IG包网' }}</a>
            <p>{{ cms_t('标准包网、定制包网、游戏 API。前台、后台和接入一起交付。', 'Standard, custom, and game API. Front, admin, and connections in one delivery.') }}</p>
            @if($telegram !== '')
                <p><a href="{{ $telegram }}" target="_blank" rel="noopener">Telegram</a></p>
            @endif
            @if($email !== '')
                <p><a href="mailto:{{ $email }}">{{ $email }}</a></p>
            @endif
        </div>
        <div>
            <h3>{{ cms_t('方案', 'Packages') }}</h3>
            @cmsContent(['model' => 'product', 'num' => 6])
                <a href="{{ $item->url }}">{{ $item->t('title') }}</a>
            @endcmsContent
        </div>
        <div>
            <h3>{{ cms_t('站点', 'Site') }}</h3>
            @cmsChannel(['type' => 'top', 'ismenu' => 1])
                <a href="{{ $item->frontUrl() }}">{{ $item->t('title') }}</a>
            @endcmsChannel
        </div>
        <div>
            <h3>{{ cms_t('咨询', 'Talk') }}</h3>
            <a href="{{ $contactUrl }}">{{ cms_t('留下联系方式', 'Send a note') }}</a>
            <a href="{{ cms_front_url('search') }}">{{ cms_ui('search') }}</a>
        </div>
    </div>
    <div class="ig-wrap ig-legal">
        © {{ date('Y') }} {{ $site['title'] ?? 'IG包网' }}
        @if(!empty($site['footer_text'])) · {{ $site['footer_text'] }} @endif
        @if(!empty($site['icp']))
            · <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener nofollow">{{ $site['icp'] }}</a>
        @endif
    </div>
</footer>
<a class="ig-float" href="{{ $contactUrl }}">{{ cms_t('咨询', 'Contact') }}</a>
{!! $site['analytics_code'] ?? '' !!}
<script>
    document.querySelector('.ig-nav-btn')?.addEventListener('click', function () {
        var nav = document.querySelector('.ig-nav');
        var open = nav.classList.toggle('is-open');
        this.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
</script>
</body>
</html>
