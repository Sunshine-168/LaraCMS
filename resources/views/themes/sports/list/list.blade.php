@extends('themes.sports.partials.layout')
@section('content')
    @cmsBreadcrumb
    <h1>{{ $category->t('title') }}</h1>
    @cmsContent(['page'=>true])
        <div class="card">
            <h3><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h3>
            <p class="muted">{{ $item->attr('sport') }} · {{ $item->attr('kickoff') }}</p>
            <a class="btn" href="{{ cms_route('go', $item) }}" target="_blank">{{ cms_ui('open_signal') }}</a>
        </div>
    @endcmsContent
    @cmsPaginate
@endsection
