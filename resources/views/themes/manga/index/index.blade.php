@extends('themes.manga.partials.layout')
@section('content')
    @include('cms.partials.front-hero-copy', [
        'fallbackTitle' => '漫画库',
        'fallbackSub' => '筛选地区 / 状态',
    ])
    <div style="margin:12px 0">
        @cmsFilter(['model'=>'manga'])
            @foreach(($item->choices ?? []) as $val => $label)
                @php $v = is_int($val) ? $label : $val; @endphp
                <a class="btn" style="margin:0 6px 6px 0;{{ request('filter.'.$item->name)==$v ? '' : 'opacity:.7' }}"
                   href="?filter[{{ $item->name }}]={{ urlencode($v) }}">{{ $label }}</a>
            @endforeach
        @endcmsFilter
    </div>
    <div class="grid">
        @cmsContent(['model'=>'manga','num'=>12,'page'=>true])
            <a class="card" href="{{ $item->url }}">
                <img src="{{ cms_media($item->cover) ?: 'https://picsum.photos/seed/'.$item->id.'/400/560' }}" alt="">
                <div class="body">
                    <strong>{{ $item->t('title') }}</strong>
                    <div class="muted">{{ $item->attr('author') }} · {{ $item->attr('status') }}</div>
                </div>
            </a>
        @endcmsContent
    </div>
    @cmsPaginate
    @include('cms.partials.friend-links')
@endsection
