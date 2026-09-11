@extends('themes.mobile.partials.layout')
@section('content')
    @cmsAdv(['name' => 'home_banner'])
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
    @endcmsAdv

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
