@extends('themes.sports.partials.layout')
@section('content')
    @cmsBreadcrumb
    <h1>{{ $category->t('title') }}</h1>
    @cmsContent(['page'=>true])
        <div class="card">
            <h3>{{ $item->t('title') }}</h3>
            <p class="muted">{{ $item->attr('sport') }} · {{ $item->attr('kickoff') }}</p>
            <a class="btn" href="{{ route('go', $item) }}" target="_blank">打开源</a>
        </div>
    @endcmsContent
    @cmsPaginate
@endsection
