@extends('themes.bc.partials.layout')

@section('content')
    <header class="ig-pagehead">
        <div class="ig-wrap">
            <h1>{{ cms_ui('search') }}</h1>
        </div>
    </header>
    <div class="ig-wrap bc-page bc-prose">
        @include('cms.partials.search-form')
        @include('cms.partials.search-results', ['searchListClass' => 'bc-news'])
    </div>
@endsection
