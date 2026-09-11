@extends('themes.sports.partials.layout')
@section('content')
    @include('cms.partials.front-hero-copy', [
        'fallbackTitle' => '体育信号 / 资讯聚合',
    ])
    @cmsAdv(['name'=>'live_board'])
        @if($item->html)
            {!! $item->html !!}
        @else
        <div class="card">
            <span class="live">FOCUS</span>
            <strong style="margin-left:8px">{{ $item->t('title') }}</strong>
            <p class="muted">{{ $item->t('notes') }}</p>
            <a class="btn" href="{{ $item->url }}">进入赛事</a>
        </div>
        @endif
    @endcmsAdv

    <div class="filters" style="margin:16px 0">
        @cmsFilter(['model'=>'signal'])
            @foreach(($item->choices ?? []) as $val => $label)
                @php $v = is_int($val) ? $label : $val; @endphp
                <a class="{{ request('filter.'.$item->name)==$v ? 'active' : '' }}"
                   href="?filter[{{ $item->name }}]={{ urlencode($v) }}">{{ $label }}</a>
            @endforeach
        @endcmsFilter
        · 排序
        @cmsOrder(['model'=>'signal'])
            <a class="{{ $item->current ? 'active' : '' }}" href="{{ $item->url }}">{{ $item->label }}</a>
        @endcmsOrder
    </div>

    @cmsContent(['model'=>'signal','num'=>20,'page'=>true])
        <div class="card">
            @if($item->hasFlag('live'))<span class="live">LIVE</span>@endif
            <h3 style="display:inline;margin-left:6px">{{ $item->t('title') }}</h3>
            <p class="muted">{{ $item->attr('sport') }} · {{ $item->attr('kickoff') }} · {{ $item->attr('quality') }}</p>
            <a class="btn" href="{{ cms_route('go', $item) }}" target="_blank" rel="noopener">打开源</a>
            <a href="{{ $item->url }}" style="margin-left:10px">详情</a>
        </div>
    @endcmsContent
    @cmsPaginate
@endsection
