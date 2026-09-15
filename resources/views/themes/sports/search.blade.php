@extends('themes.sports.partials.layout')
@section('content')
    <h1>{{ cms_ui('search') }}</h1>
    @include('cms.partials.search-form')
    @foreach($results ?: [] as $item)
        <div class="card"><a href="{{ $item->url }}">{{ $item->t('title') }}</a></div>
    @endforeach
@endsection
