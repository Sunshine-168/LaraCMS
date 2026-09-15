@extends('themes.enterprise.partials.layout')

@section('content')
    @php
        $homeBanners = app(\App\Services\Cms\Tags\AdvTag::class)->get(['name' => 'home_banner']);
    @endphp
    @forelse($homeBanners as $item)
        @if($item->html)
            {!! $item->html !!}
        @else
        <div class="banner-slide" @if($item->image) style="background-image:url('{{ $item->image }}')" @endif>
            <div>
                <h1>{{ $item->t('title') }}</h1>
                <p>{{ $item->t('notes') }}</p>
                @if($item->url)<p><a class="btn" href="{{ $item->url }}">了解更多</a></p>@endif
            </div>
        </div>
        @endif
    @empty
        @if(!empty($site['hero_title']) || !empty($site['hero_subtitle']))
            <div class="hero">
                <div>
                    @include('cms.partials.front-hero-copy')
                </div>
            </div>
        @endif
    @endforelse

    <section style="margin:32px 0">
        <h2>公司简介</h2>
        @cmsContent(['model' => 'page', 'num' => 1, 'flag' => 'about'])
            <div class="card-soft" style="display:grid;grid-template-columns:1fr 1.2fr;gap:20px;margin-top:12px">
                @if($item->cover)<img src="{{ cms_media($item->cover) }}" alt="" style="width:100%;border-radius:8px">@endif
                <div>
                    <h3>{{ $item->t('title') }}</h3>
                    <div class="content">{!! \App\Support\Cdn::rewriteHtml((string) $item->t('content')) !!}</div>
                    <p><a class="btn" href="{{ $item->url }}">查看详情</a></p>
                </div>
            </div>
        @endcmsContent
    </section>

    <section style="margin:32px 0">
        <h2>新闻动态</h2>
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
        <h2>产品中心</h2>
        <div class="grid" style="margin-top:12px">
            @cmsContent(['model' => 'product', 'num' => 4])
                <article class="card-soft">
                    @if($item->cover)<img src="{{ cms_media($item->cover) }}" alt="" style="width:100%;height:140px;object-fit:cover;border-radius:8px">@endif
                    <h3 style="margin:10px 0 6px"><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h3>
                    <p class="muted">{{ $item->attr('price') }}</p>
                </article>
            @endcmsContent
        </div>
    </section>

    <section style="margin:32px 0">
        <h2>联系我们</h2>
        <div class="card-soft" style="margin-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:20px">
            <div>
                @cmsForm(['slug' => 'contact', 'captcha' => true, 'submit' => '发送留言'])
            </div>
            <div>
                @cmsMap(['address' => '北京市东城区', 'point' => '116.397,39.908', 'title' => '公司地址'])
            </div>
        </div>
    </section>

    @include('cms.partials.friend-links')
@endsection
