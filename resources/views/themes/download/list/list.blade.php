@extends('themes.download.partials.layout')
@section('content')
    @cmsBreadcrumb
    <h1>{{ $category->t('title') }}</h1>
    <div class="filters" style="margin:16px 0">
        @include('cms.partials.filter-all')
        @cmsFilter
            @foreach(($item->choices ?? []) as $val => $label)
                @php $v = is_int($val) ? $label : $val; @endphp
                <a class="{{ request('filter.'.$item->name) == $v ? 'active' : '' }}"
                   href="?filter[{{ $item->name }}]={{ urlencode($v) }}">{{ is_int($val) ? $label : $label }}</a>
            @endforeach
        @endcmsFilter
    </div>
    <div class="filters" style="margin:8px 0 16px">
        {{ cms_ui('sort') }}：
        @cmsOrder
            <a class="{{ $item->current ? 'active' : '' }}" href="{{ $item->url }}">{{ $item->label }}</a>
        @endcmsOrder
    </div>
    @cmsContent(['page'=>true,'num'=>$category->page_size ?: 12])
        <div class="card">
            <h3><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h3>
            <p class="muted">{{ $item->attr('platform') }} · {{ $item->attr('version') }} · {{ cms_ui('download') }} {{ $item->downloads_count }}</p>
            <a class="btn" href="{{ cms_route('download', $item) }}">{{ cms_ui('download') }}</a>
        </div>
    @endcmsContent
    @cmsPaginate
@endsection
