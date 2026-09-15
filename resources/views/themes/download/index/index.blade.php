@extends('themes.download.partials.layout')
@section('content')
    @include('cms.partials.front-hero-copy', [
        'fallbackTitle' => '软件下载中心',
        'fallbackSub' => '按平台筛选，一键下载。',
    ])
    <div class="filters" style="margin:16px 0">
        <a href="{{ request()->url() }}" class="{{ !request('filter.platform') ? 'active' : '' }}">全部</a>
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
            <p class="muted">{{ $item->attr('platform') }} · v{{ $item->attr('version') }} · {{ $item->attr('size') }} · 下载 {{ $item->downloads_count }}</p>
            <a class="btn" href="{{ cms_route('download', $item) }}">下载</a>
        </div>
    @endcmsContent
    @cmsPaginate
    @include('cms.partials.friend-links')
@endsection
