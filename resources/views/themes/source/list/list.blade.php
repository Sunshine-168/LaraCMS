@extends('themes.source.partials.layout')
@section('content')
    @cmsBreadcrumb
    <h1>{{ $category->t('title') }}</h1>
    @cmsContent(['page'=>true])
        <div class="card">
            <h3><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h3>
            <p class="muted">{{ $item->attr('license') }} · {{ $item->attr('price') ?: cms_ui('free') }}</p>
            <a class="btn" href="{{ cms_route('download', $item) }}">{{ cms_ui('download') }}</a>
        </div>
    @endcmsContent
    @cmsPaginate
@endsection
