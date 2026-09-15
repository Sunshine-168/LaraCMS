@extends('themes.sports.partials.layout')
@section('content')
    <h1>{{ cms_ui('search') }}</h1>
    @include('cms.partials.search-form')
    @include('cms.partials.search-results', ['searchItemClass' => 'card'])
@endsection
