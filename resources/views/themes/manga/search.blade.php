@extends('themes.manga.partials.layout')
@section('content')
    <h1>搜索</h1>
    @include('cms.partials.search-form')
    <div class="grid">
        @foreach($results ?: [] as $item)
            <a class="card" href="{{ $item->url }}"><div class="body">{{ $item->t('title') }}</div></a>
        @endforeach
    </div>
@endsection
