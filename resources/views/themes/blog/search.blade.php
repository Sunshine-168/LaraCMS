@extends('themes.blog.partials.layout')
@section('content')
    <h1>搜索</h1>
    @include('cms.partials.search-form')
    @foreach($results ?: [] as $item)
        <article><h2><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h2></article>
    @endforeach
@endsection
