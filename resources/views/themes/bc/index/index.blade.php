@extends('themes.bc.partials.layout')

@php
    $front = app(\App\Services\Cms\SiteFrontService::class);
    $solutionsUrl = $front->categoryBySlug('gw-solutions')?->frontUrl() ?: cms_front_url('gw-solutions');
    $contactUrl = $front->categoryBySlug('gw-contact')?->frontUrl() ?: cms_front_url('gw-contact');
    $insightsUrl = $front->categoryBySlug('gw-insights')?->frontUrl() ?: cms_front_url('gw-insights');
    $marketsUrl = $front->categoryBySlug('gw-markets')?->frontUrl() ?: cms_front_url('gw-markets');
    $apiUrl = $front->categoryBySlug('gw-api')?->frontUrl() ?: cms_front_url('gw-api');
    $solutions = app(\App\Services\Cms\Tags\ContentTag::class)->get(['model' => 'product', 'num' => 3]);
    $modules = app(\App\Services\Cms\Tags\AdvTag::class)->get(['name' => 'home_modules']);
    $markets = app(\App\Services\Cms\Tags\AdvTag::class)->get(['name' => 'home_markets']);
    $news = app(\App\Services\Cms\Tags\ContentTag::class)->get(['model' => 'article', 'num' => 3, 'order' => 'published_at desc']);
    $icons = [
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/></svg>',
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="8" cy="9" r="2.2"/><circle cx="16" cy="9" r="2.2"/><path d="M4.5 18c.6-2 2.2-3 3.5-3s2.9 1 3.5 3M12.5 18c.6-2 2.2-3 3.5-3s2.9 1 3.5 3"/></svg>',
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 3v10M8 9l4 4 4-4"/><path d="M5 19h14"/></svg>',
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 12h16M12 4v16"/><circle cx="12" cy="12" r="8"/></svg>',
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3z"/></svg>',
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/></svg>',
    ];
@endphp

@section('content')
    <section class="ig-hero">
        <div class="ig-hero-copy">
            <p class="ig-kicker">IG {{ cms_t('包网', 'WHITE LABEL') }}</p>
            <h1>{!! cms_t('用你的品牌<br>上线一整套平台', 'Launch under<br>your own brand') !!}</h1>
            <p class="ig-lead">{{ cms_t('标准包网、定制包网、游戏 API。前台、后台和游戏一起交付。', 'Standard, custom, or game API. Front, admin, and games together.') }}</p>
            <div class="ig-actions">
                <a class="ig-btn" href="{{ $solutionsUrl }}">{{ cms_t('看方案', 'Packages') }}</a>
                <a class="ig-btn ig-btn-line" href="{{ $contactUrl }}">{{ cms_t('咨询', 'Contact') }}</a>
            </div>
        </div>
        <div class="ig-hero-photo" role="img" aria-label="{{ cms_t('娱乐场', 'Floor') }}"></div>
    </section>
    <div class="ig-statbar">
        <div class="ig-wrap ig-stats">
            <div><b>3</b><span>{{ cms_t('档方案', 'Packages') }}</span></div>
            <div><b>5</b><span>{{ cms_t('类游戏', 'Game types') }}</span></div>
            <div><b>4</b><span>{{ cms_t('个区域', 'Regions') }}</span></div>
            <div><b>1</b><span>{{ cms_t('次对接', 'Connection') }}</span></div>
        </div>
    </div>

    @include('themes.bc.partials.cats', ['apiUrl' => $apiUrl])

    <section class="ig-section">
        <div class="ig-wrap ig-split">
            <div>
                <p class="ig-kicker">{{ cms_t('包网', 'Platform') }}</p>
                <h2>{{ cms_t('系统是现成的，品牌是你的。', 'The system is ready. The brand is yours.') }}</h2>
            </div>
            <p>{{ cms_t('不用从零写前台和后台。选定档位之后，换上名字、Logo 和要接的游戏，就可以按市场开站。', 'You do not build the front and admin from zero. Pick a package, then add the name, logo, and games for that market.') }}</p>
        </div>
    </section>

    <section id="solutions">
        <div class="ig-rows">
            @foreach($solutions as $item)
                <a class="ig-row" href="{{ $item->url }}">
                    @include('themes.bc.partials.shot', ['slug' => $item->t('slug'), 'alt' => $item->t('title')])
                    <div>
                        <p class="ig-kicker">0{{ $loop->iteration }}</p>
                        <h3>{{ $item->t('title') }}</h3>
                        <p>{{ $item->t('excerpt') }}</p>
                        <em>{{ cms_t('查看这一档', 'Open this package') }}</em>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    <section class="ig-section">
        <div class="ig-wrap ig-work">
            <div class="ig-frame" role="img" aria-label="{{ cms_t('数据看板', 'Dashboard') }}"></div>
            <div>
                <p class="ig-kicker">{{ cms_t('后台', 'Admin') }}</p>
                <h2>{{ cms_t('开站之后每天用这些。', 'What you use after launch.') }}</h2>
                @foreach($modules as $item)
                    <article class="ig-mod">
                        {!! $icons[$loop->index] ?? $icons[0] !!}
                        <div>
                            <h3>{{ $item->t('title') }}</h3>
                            <p>{{ $item->t('notes') }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="ig-city">
        <div class="ig-wrap">
            <p class="ig-kicker">{{ cms_t('市场', 'Markets') }}</p>
            <h2>{{ cms_t('先看这四个区域。', 'Four regions to start.') }}</h2>
            <div class="ig-markets">
                @foreach($markets as $item)
                    <a class="ig-market" href="{{ $marketsUrl }}">
                        <b>{{ $item->t('title') }}</b>
                        <span>{{ $item->t('notes') }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="ig-section">
        <div class="ig-wrap">
            <p class="ig-kicker">{{ cms_t('流程', 'Process') }}</p>
            <h2>{{ cms_t('四步。', 'Four steps.') }}</h2>
            <div class="ig-steps">
                <div class="ig-step"><b>01</b><strong>{{ cms_t('定档位', 'Choose') }}</strong><span>{{ cms_t('市场，以及标准、定制或 API。', 'Market, then standard, custom, or API.') }}</span></div>
                <div class="ig-step"><b>02</b><strong>{{ cms_t('交素材', 'Files') }}</strong><span>{{ cms_t('品牌名、Logo、对接人。', 'Name, logo, contact.') }}</span></div>
                <div class="ig-step"><b>03</b><strong>{{ cms_t('验收', 'Check') }}</strong><span>{{ cms_t('前台、后台和接入做好再看。', 'Front, admin, and connections.') }}</span></div>
                <div class="ig-step"><b>04</b><strong>{{ cms_t('上线', 'Launch') }}</strong><span>{{ cms_t('之后按约定维护。', 'Then the agreed upkeep.') }}</span></div>
            </div>
        </div>
    </section>

    <section class="ig-section" style="padding-top:0">
        <div class="ig-wrap">
            <p class="ig-kicker">{{ cms_t('资讯', 'Notes') }}</p>
            <div class="ig-news">
                @foreach($news as $item)
                    <a href="{{ $item->url }}">
                        <time>@cmsDate(['name' => $item->published_at])</time>
                        <div>
                            <h3>{{ $item->t('title') }}</h3>
                            <p>{{ $item->t('excerpt') }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="ig-contact" id="contact">
        <div class="ig-contact-photo" role="img" aria-label=""></div>
        <div class="ig-form">
            <p class="ig-kicker">{{ cms_t('咨询', 'Contact') }}</p>
            <h2>{{ cms_t('留下 Telegram。', 'Leave Telegram.') }}</h2>
            @cmsForm(['slug' => 'gw-inquiry', 'captcha' => true, 'submit' => cms_t('提交', 'Send')])
        </div>
    </section>
@endsection
