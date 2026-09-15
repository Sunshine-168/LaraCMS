@extends('themes.download.partials.layout')
@section('content')
    @include('cms.partials.front-hero-copy', [
        'fallbackTitle' => cms_ui('download_hero'),
        'fallbackSub' => cms_ui('download_hero_sub'),
    ])
    <div class="filters" style="margin:16px 0">
        @include('cms.partials.filter-all')
        @cmsFilter(['model' => 'download'])
            @foreach(($item->choices ?? []) as $val => $label)
                @php $v = is_int($val) ? $label : $val; $text = is_int($val) ? $label : $label; @endphp
                <a class="{{ request('filter.'.$item->name) == $v ? 'active' : '' }}"
                   href="?filter[{{ $item->name }}]={{ urlencode($v) }}">{{ $text }}</a>
            @endforeach
        @endcmsFilter
    </div>
    @cmsContent(['model'=>'download','num'=>12,'page'=>true])
        <div class="card">
            <h3><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h3>
            <p class="muted">{{ $item->attr('platform') }} · v{{ $item->attr('version') }} · {{ $item->attr('size') }} · {{ cms_ui('download') }} {{ $item->downloads_count }}</p>
            <a class="btn" href="{{ cms_route('download', $item) }}">{{ cms_ui('download') }}</a>
        </div>
    @endcmsContent
    @cmsPaginate
    @include('cms.partials.friend-links')
@endsection
