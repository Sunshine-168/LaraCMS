@extends('themes.nav.partials.layout')
@section('content')
    @include('cms.partials.front-hero-copy', [
        'fallbackTitle' => cms_ui('nav_hero'),
        'fallbackSub' => cms_ui('nav_hero_sub'),
    ])
    <section class="section">
        <h2>{{ cms_theme('hot', cms_ui('hot')) }}</h2>
        <div class="grid">
            @cmsAdv(['name'=>'hot_links'])
                @if($item->html)
                    {!! $item->html !!}
                @else
                <a class="link" href="{{ $item->url }}" target="_blank" rel="noopener">
                    @if($item->image)<img src="{{ $item->image }}" alt="">@endif
                    <div>{{ $item->t('title') }}</div>
                </a>
                @endif
            @endcmsAdv
        </div>
    </section>
    <section class="section">
        <h2>{{ cms_theme('all_cats', cms_ui('all_cats')) }}</h2>
        @cmsChannel(['type'=>'top','ismenu'=>1, 'as' => 'cat'])
            <h3 style="margin-top:20px">{{ $cat->t('title') }}</h3>
            <div class="grid">
                @cmsContent(['catid'=>$cat->id,'model'=>'link','num'=>24,'insub'=>false, 'as' => 'link'])
                    <a class="link" href="{{ cms_route('go', $link) }}" target="_blank" rel="noopener">
                        @if($link->cover || $link->attr('icon'))
                            <img src="{{ cms_media($link->cover ?: $link->attr('icon')) }}" alt="">
                        @endif
                        <div>{{ $link->t('title') }}</div>
                        <div class="muted">{{ cms_ui('clicks') }} {{ $link->clicks }}</div>
                    </a>
                @endcmsContent
            </div>
        @endcmsChannel
    </section>
    @include('cms.partials.friend-links')
@endsection
