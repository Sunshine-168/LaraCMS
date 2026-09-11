@extends('themes.source.partials.layout')
@section('content')
    @cmsBreadcrumb
    <h1>{{ $category->t('title') }}</h1>
    @cmsContent(['page'=>true])
        <div class="card">
            <h3><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h3>
            <p class="muted">{{ $item->attr('license') }} · {{ $item->attr('price') ?: '免费' }}</p>
            <a class="btn" href="{{ cms_route('download', $item) }}">下载</a>
        </div>
    @endcmsContent
    @cmsPaginate
@endsection
