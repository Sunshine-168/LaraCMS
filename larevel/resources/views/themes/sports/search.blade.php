@extends('themes.sports.partials.layout')
@section('content')
    <h1>搜索</h1>
    <form method="get" action="{{ route('search') }}"><input name="q" value="{{ $q }}"><button class="btn" type="submit">搜</button></form>
    @foreach($results ?: [] as $item)
        <div class="card"><a href="{{ $item->url }}">{{ $item->t('title') }}</a></div>
    @endforeach
@endsection
