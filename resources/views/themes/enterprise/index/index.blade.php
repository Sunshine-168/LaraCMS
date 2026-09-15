@extends('themes.enterprise.partials.layout')

@section('content')
    @php
        $homeBanners = app(\App\Services\Cms\Tags\AdvTag::class)->get(['name' => 'home_banner']);
        $mapAddress = cms_theme('map_address');
        $mapPoint = cms_theme('map_point');
        if ($mapPoint === '') {
            $mapPoint = trim(\App\Models\Cms\Setting::getValue('map_default_lng', '116.397').','.\App\Models\Cms\Setting::getValue('map_default_lat', '39.908'), ',');
        }
    @endphp
    @forelse($homeBanners as $item)
        @if($item->html)
            {!! $item->html !!}
        @else
        <div class="banner-slide" @if($item->image) style="background-image:url('{{ $item->image }}')" @endif>
            <div>
                <h1>{{ $item->t('title') }}</h1>
                <p>{{ $item->t('notes') }}</p>
                @if($item->url)<p><a class="btn" href="{{ $item->url }}">{{ cms_theme('cta_text', cms_ui('more')) }}</a></p>@endif
            </div>
        </div>
        @endif
    @empty
        @if(!empty($site['hero_title']) || !empty($site['hero_subtitle']) || cms_theme('home_title') !== '' || cms_theme('cta_text') !== '')
            <div class="hero">
                <div>
                    @include('cms.partials.front-hero-copy')
                </div>
            </div>
        @endif
    @endforelse

    <section style="margin:32px 0">
        <h2>{{ cms_theme('about_us', cms_ui('about_us')) }}</h2>
        @cmsContent(['model' => 'page', 'num' => 1, 'flag' => 'about'])
            <div class="card-soft home-split" style="margin-top:12px">
                @if($item->cover)<img src="{{ cms_media($item->cover) }}" alt="" style="width:100%;border-radius:8px">@endif
                <div>
                    <h3>{{ $item->t('title') }}</h3>
                    <div class="content">{!! \App\Support\Cdn::rewriteHtml((string) $item->t('content')) !!}</div>
                    <p><a class="btn" href="{{ $item->url }}">{{ cms_ui('details') }}</a></p>
                </div>
            </div>
        @endcmsContent
    </section>

    <section style="margin:32px 0">
        <h2>{{ cms_theme('news', cms_ui('news')) }}</h2>
        <div class="grid" style="margin-top:12px">
            @cmsAdv(['name' => 'latest_news'])
                @if($item->html)
                    {!! $item->html !!}
                @else
                <article class="card-soft">
                    @if($item->image)<img src="{{ $item->image }}" alt="" style="width:100%;height:120px;object-fit:cover;border-radius:8px;margin-bottom:8px">@endif
                    <h3 style="margin:8px 0"><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h3>
                    <p class="muted">@cmsSubstr(['name' => $item->t('notes'), 'len' => 80])</p>
                </article>
                @endif
            @endcmsAdv
        </div>
    </section>

    <section style="margin:32px 0">
        <h2>{{ cms_theme('products', cms_ui('products')) }}</h2>
        <div class="grid" style="margin-top:12px">
            @cmsContent(['model' => 'product', 'num' => 4])
                <article class="card-soft">
                    @if($item->cover)<img src="{{ cms_media($item->cover) }}" alt="{{ $item->t('title') }}" style="width:100%;height:140px;object-fit:cover;border-radius:8px">@endif
                    <h3 style="margin:10px 0 6px"><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h3>
                    <p class="muted">{{ cms_ui('price') }}：{{ $item->attr('price') ?: cms_ui('price_ask') }}</p>
                </article>
            @endcmsContent
        </div>
    </section>

    <section style="margin:32px 0">
        <h2>{{ cms_theme('contact', cms_ui('contact')) }}</h2>
        <div class="card-soft home-contact" style="margin-top:12px">
            <div>
                @cmsForm(['slug' => 'contact', 'captcha' => true, 'submit' => cms_ui('send_message')])
            </div>
            <div>
                @cmsMap(['address' => $mapAddress, 'point' => $mapPoint, 'title' => cms_theme('map_title', cms_ui('company_address'))])
            </div>
        </div>
    </section>

    @include('cms.partials.friend-links')
@endsection
