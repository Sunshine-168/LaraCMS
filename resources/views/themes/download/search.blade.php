@extends('themes.download.partials.layout')
@section('content')
    <h1>{{ cms_ui('search') }}</h1>
    @include('cms.partials.search-form')
    @foreach($results ?: [] as $item)
        <div class="card">
            <h3><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h3>
        </div>
    @endforeach
    @cmsPaginate
@endsection
