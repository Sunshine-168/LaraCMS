@extends('themes.source.partials.layout')
@section('content')
    <h1>搜索</h1>
    @include('cms.partials.search-form')
    @foreach($results ?: [] as $item)
        <div class="card"><a href="{{ $item->url }}">{{ $item->t('title') }}</a></div>
    @endforeach
@endsection
