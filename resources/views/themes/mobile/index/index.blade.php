@extends('themes.mobile.partials.layout')
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
                @if($item->url)<p><a class="btn" href="{{ $item->url }}">查看</a></p>@endif
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

    <section style="margin:20px 0">
        <h2>最新新闻</h2>
        <div class="grid" style="margin-top:10px">
            @cmsAdv(['name' => 'latest_news'])
                @if($item->html)
                    {!! $item->html !!}
                @else
                <a class="card-soft" href="{{ $item->url }}">
                    <strong>{{ $item->t('title') }}</strong>
                    <div class="muted">{{ $item->t('notes') }}</div>
                </a>
                @endif
            @endcmsAdv
        </div>
    </section>
@endsection
