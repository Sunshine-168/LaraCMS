@extends('themes.source.partials.layout')
@section('content')
    <h1>搜索</h1>
    <form method="get" action="{{ route('search') }}">
        <input type="text" name="q" value="{{ $q }}">
        <button class="btn" type="submit">搜索</button>
    </form>
    @foreach($results ?: [] as $item)
        <div class="card"><a href="{{ $item->url }}">{{ $item->t('title') }}</a></div>
    @endforeach
@endsection
