@extends('themes.sports.partials.layout')
@section('content')
    @include('cms.partials.front-hero-copy', [
        'fallbackTitle' => cms_ui('sports_hero'),
    ])
    @if(cms_theme('live_hint') !== '')
        <p class="muted">{{ cms_theme('live_hint') }}</p>
    @endif
    @cmsAdv(['name'=>'live_board'])
        @if($item->html)
            {!! $item->html !!}
        @else
        <div class="card">
            <span class="live">FOCUS</span>
            <strong style="margin-left:8px">{{ $item->t('title') }}</strong>
            <p class="muted">{{ $item->t('notes') }}</p>
            <a class="btn" href="{{ $item->url }}">{{ cms_ui('enter_match') }}</a>
        </div>
        @endif
    @endcmsAdv

    <div class="filters" style="margin:16px 0">
        @include('cms.partials.filter-all')
        @cmsFilter(['model'=>'signal'])
            @foreach(($item->choices ?? []) as $val => $label)
                @php $v = is_int($val) ? $label : $val; @endphp
                <a class="{{ request('filter.'.$item->name)==$v ? 'active' : '' }}"
                   href="?filter[{{ $item->name }}]={{ urlencode($v) }}">{{ $label }}</a>
            @endforeach
        @endcmsFilter
        · {{ cms_ui('sort') }}
        @cmsOrder(['model'=>'signal'])
            <a class="{{ $item->current ? 'active' : '' }}" href="{{ $item->url }}">{{ $item->label }}</a>
        @endcmsOrder
    </div>

    @cmsContent(['model'=>'signal','num'=>20,'page'=>true])
        <div class="card">
            @if($item->hasFlag('live'))<span class="live">LIVE</span>@endif
            <h3 style="display:inline;margin-left:6px"><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h3>
            <p class="muted">{{ $item->attr('sport') }} · {{ $item->attr('kickoff') }} · {{ $item->attr('quality') }}</p>
            <a class="btn" href="{{ cms_route('go', $item) }}" target="_blank" rel="noopener">{{ cms_ui('open_signal') }}</a>
            <a href="{{ $item->url }}" style="margin-left:10px">{{ cms_ui('details') }}</a>
        </div>
    @endcmsContent
    @cmsPaginate
    @include('cms.partials.friend-links')
@endsection
