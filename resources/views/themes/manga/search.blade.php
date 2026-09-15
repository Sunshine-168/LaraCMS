@extends('themes.manga.partials.layout')
@section('content')
    <h1>{{ cms_ui('search') }}</h1>
    @include('cms.partials.search-form')
    @include('cms.partials.search-results', ['searchListClass' => 'grid', 'searchItemClass' => 'card'])
@endsection
